<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\AjukanPencairanRequest;
use App\Models\Disbursement;
use App\Models\Pesanan;
use App\Models\RekeningBank;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaldoController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $umkm = $user->umkm;

        $rekeningBankList = RekeningBank::where('umkm_id', $umkm->id)
            ->orderBy('aktif', 'desc')
            ->orderBy('urutan', 'asc')
            ->orderBy('nama_bank', 'asc')
            ->get();

        $pengajuanAktif = Disbursement::with(['rekeningBank'])
            ->where('umkm_id', $umkm->id)
            ->whereIn('status', ['diajukan', 'diproses'])
            ->latest()
            ->first();

        $pesananSiapCair = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
            ->where('status', 'Selesai')
            ->whereDoesntHave('disbursements', fn($q) => $q->whereIn('status', ['diajukan', 'diproses', 'dibayar']))
            ->get();

        $saldoTersedia = (float) $pesananSiapCair->sum('pendapatan_penjual');

        $saldoDiajukan = (float) Disbursement::where('umkm_id', $umkm->id)
            ->whereIn('status', ['diajukan', 'diproses'])
            ->sum('jumlah');

        $saldoDicairkan = (float) Disbursement::where('umkm_id', $umkm->id)
            ->where('status', 'dibayar')
            ->sum('jumlah');

        $riwayat = Disbursement::with(['admin', 'rekeningBank', 'pesanan'])
            ->where('umkm_id', $umkm->id)
            ->latest()
            ->paginate(10);

        return view('seller.saldo.index', compact(
            'umkm',
            'rekeningBankList',
            'pengajuanAktif',
            'pesananSiapCair',
            'saldoTersedia',
            'saldoDiajukan',
            'saldoDicairkan',
            'riwayat'
        ));
    }

    public function store(AjukanPencairanRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $umkm = $user->umkm;

        if (!$umkm) {
            return back()->with('error', 'Profil UMKM tidak ditemukan.');
        }

        $result = DB::transaction(function () use ($request, $user, $umkm, $logger) {
            // 1. Validasi kepemilikan rekening bank dan status aktif
            $rekening = RekeningBank::where('id', $request->rekening_bank_id)
                ->where('umkm_id', $umkm->id)
                ->where('aktif', true)
                ->first();

            if (!$rekening) {
                return ['success' => false, 'message' => 'Rekening bank tujuan tidak valid atau belum diaktifkan.'];
            }

            // 2. Cek tidak ada pengajuan aktif untuk UMKM ini (dengan row lock)
            $activeExists = Disbursement::where('umkm_id', $umkm->id)
                ->whereIn('status', ['diajukan', 'diproses'])
                ->lockForUpdate()
                ->exists();

            if ($activeExists) {
                return ['success' => false, 'message' => 'UMKM Anda masih memiliki pengajuan pencairan yang sedang diproses oleh admin. Mohon tunggu hingga selesai.'];
            }

            // 3. Query pesanan eligible dengan row lock
            $pesanan = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
                ->where('status', 'Selesai')
                ->whereDoesntHave('disbursements', fn($q) => $q->whereIn('status', ['diajukan', 'diproses', 'dibayar']))
                ->lockForUpdate()
                ->get();

            if ($pesanan->isEmpty()) {
                return ['success' => false, 'message' => 'Tidak ada saldo pesanan selesai yang dapat dicairkan saat ini.'];
            }

            // 4. Hitung total jumlah dari server
            $jumlah = (float) $pesanan->sum('pendapatan_penjual');

            if ($jumlah <= 0) {
                return ['success' => false, 'message' => 'Nominal saldo yang dapat dicairkan harus lebih besar dari Rp0.'];
            }

            // 5. Simpan snapshot rekening bank
            $snapshot = [
                'nama_bank'      => $rekening->nama_bank,
                'nomor_rekening' => $rekening->nomor_rekening,
                'atas_nama'      => $rekening->atas_nama,
            ];

            // 6. Buat disbursement status 'diajukan'
            $disbursement = Disbursement::create([
                'umkm_id'                => $umkm->id,
                'rekening_bank_id'       => $rekening->id,
                'rekening_bank_snapshot' => $snapshot,
                'jumlah'                 => $jumlah,
                'status'                 => 'diajukan',
                'catatan'                => $request->catatan ?: "Pengajuan pencairan saldo oleh mitra {$umkm->nama_umkm}",
                'requested_by'           => $user->id,
                'diajukan_at'            => now(),
            ]);

            // 7. Attach pesanan ke pivot disbursement
            $disbursement->pesanan()->attach($pesanan->pluck('id'));

            // 8. Log aktivitas
            $logger->log(
                "Mengajukan pencairan saldo sebesar Rp" . number_format($jumlah, 0, ',', '.') . " ke rekening {$rekening->nama_bank} ({$rekening->nomor_rekening})",
                $user,
                $request->ip()
            );

            return [
                'success' => true,
                'message' => "Permintaan pencairan dana sebesar Rp" . number_format($jumlah, 0, ',', '.') . " berhasil diajukan ke Admin. Mohon tunggu proses verifikasi dan transfer.",
            ];
        });

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('seller.saldo.index')->with('success', $result['message']);
    }

    public function storeRekening(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $umkm = $user->umkm;

        if (!$umkm) {
            return back()->with('error', 'Profil UMKM tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_bank'      => ['required', 'string', 'max:100'],
            'nomor_rekening' => ['required', 'string', 'max:50'],
            'atas_nama'      => ['required', 'string', 'max:100'],
        ], [
            'nama_bank.required'      => 'Nama bank wajib diisi.',
            'nomor_rekening.required' => 'Nomor rekening wajib diisi.',
            'atas_nama.required'      => 'Nama pemilik rekening wajib diisi.',
        ]);

        $account = RekeningBank::create([
            'umkm_id'        => $umkm->id,
            'nama_bank'      => $validated['nama_bank'],
            'nomor_rekening' => $validated['nomor_rekening'],
            'atas_nama'      => $validated['atas_nama'],
            'aktif'          => true,
            'urutan'         => RekeningBank::where('umkm_id', $umkm->id)->count() + 1,
        ]);

        $logger->log("Menambahkan rekening bank UMKM: {$account->nama_bank} ({$account->nomor_rekening})", $user, $request->ip());

        return back()->with('success', 'Rekening bank berhasil disimpan dan siap digunakan.');
    }
}

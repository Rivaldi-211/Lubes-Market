<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\Pesanan;
use App\Models\Umkm;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DisbursementController extends Controller
{
    public function index(): View
    {
        $permintaanMasuk = Disbursement::with(['umkm', 'requester', 'rekeningBank', 'pesanan'])
            ->where('status', 'diajukan')
            ->latest('diajukan_at')
            ->get();

        $umkmList = Umkm::with(['user'])->get()->map(function ($umkm) {
            $pesananPending = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
                ->where('status', 'Selesai')
                ->whereDoesntHave('disbursements', fn($q) => $q->whereIn('status', ['diajukan', 'diproses', 'dibayar']))
                ->get();

            $umkm->total_pesanan_pending = $pesananPending->count();
            $umkm->saldo_pending = (float) $pesananPending->sum('pendapatan_penjual');
            $umkm->komisi_admin_pending = (float) $pesananPending->sum('komisi_admin');
            return $umkm;
        });

        $riwayat = Disbursement::with(['umkm', 'admin', 'requester', 'rekeningBank', 'pesanan'])
            ->whereIn('status', ['dibayar', 'ditolak'])
            ->latest()
            ->paginate(15);

        return view('admin.disbursement.index', compact('permintaanMasuk', 'umkmList', 'riwayat'));
    }

    public function approve(Request $request, Disbursement $disbursement, ActivityLogger $logger): RedirectResponse
    {
        return DB::transaction(function () use ($request, $disbursement, $logger) {
            $locked = Disbursement::where('id', $disbursement->id)->lockForUpdate()->firstOrFail();

            if (!in_array($locked->status, ['diajukan', 'diproses'])) {
                return back()->with('error', 'Permintaan pencairan ini sudah diproses sebelumnya.');
            }

            $catatan = $request->catatan ?: ($locked->catatan ?: "Pencairan dana telah ditransfer oleh Admin");

            $locked->update([
                'status'     => 'dibayar',
                'dibayar_at' => now(),
                'admin_id'   => auth()->id(),
                'catatan'    => $catatan,
            ]);

            $logger->log(
                "Menyetujui pencairan dana #DISB-{$locked->id} sebesar Rp" . number_format($locked->jumlah, 0, ',', '.') . " ke UMKM {$locked->umkm->nama_umkm}",
                auth()->user(),
                $request->ip()
            );

            return back()->with('success', "Pencairan dana sebesar Rp" . number_format($locked->jumlah, 0, ',', '.') . " ke {$locked->umkm->nama_umkm} berhasil disetujui.");
        });
    }

    public function reject(Request $request, Disbursement $disbursement, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'alasan_penolakan' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($request, $disbursement, $logger) {
            $locked = Disbursement::where('id', $disbursement->id)->lockForUpdate()->firstOrFail();

            if (!in_array($locked->status, ['diajukan', 'diproses'])) {
                return back()->with('error', 'Permintaan pencairan ini sudah diproses sebelumnya.');
            }

            $locked->pesanan()->detach();

            $alasan = $request->alasan_penolakan
                ? ("Ditolak: " . $request->alasan_penolakan)
                : 'Pengajuan pencairan ditolak oleh admin.';

            $locked->update([
                'status'     => 'ditolak',
                'ditolak_at' => now(),
                'admin_id'   => auth()->id(),
                'catatan'    => $alasan,
            ]);

            $logger->log(
                "Menolak pengajuan pencairan dana #DISB-{$locked->id} UMKM {$locked->umkm->nama_umkm}. Alasan: {$alasan}",
                auth()->user(),
                $request->ip()
            );

            return back()->with('success', "Pengajuan pencairan dana #DISB-{$locked->id} berhasil ditolak dan saldo dikembalikan ke penjual.");
        });
    }

    public function store(Request $request, Umkm $umkm, ActivityLogger $logger): RedirectResponse
    {
        return DB::transaction(function () use ($request, $umkm, $logger) {
            $pesanan = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
                ->where('status', 'Selesai')
                ->whereDoesntHave('disbursements', fn($q) => $q->whereIn('status', ['diajukan', 'diproses', 'dibayar']))
                ->lockForUpdate()
                ->get();

            if ($pesanan->isEmpty()) {
                return back()->with('error', "Tidak ada saldo pesanan selesai yang dapat dicairkan untuk {$umkm->nama_umkm}.");
            }

            $jumlah = (float) $pesanan->sum('pendapatan_penjual');

            $disbursement = Disbursement::create([
                'umkm_id'    => $umkm->id,
                'jumlah'     => $jumlah,
                'status'     => 'dibayar',
                'dibayar_at' => now(),
                'admin_id'   => auth()->id(),
                'catatan'    => $request->catatan ?: "Pencairan dana langsung oleh Admin untuk {$umkm->nama_umkm}",
            ]);

            $disbursement->pesanan()->attach($pesanan->pluck('id'));

            $logger->log("Mencatat pencairan dana langsung Rp" . number_format($jumlah, 0, ',', '.') . " ke UMKM {$umkm->nama_umkm}", auth()->user(), $request->ip());

            return back()->with('success', "Pencairan dana sebesar Rp" . number_format($jumlah, 0, ',', '.') . " ke {$umkm->nama_umkm} berhasil dicatat.");
        });
    }
}

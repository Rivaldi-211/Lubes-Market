<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\Pesanan;
use App\Models\Umkm;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisbursementController extends Controller
{
    public function index(): View
    {
        $umkmList = Umkm::with(['user'])->get()->map(function ($umkm) {
            // Find all completed and paid orders for products belonging to this UMKM that haven't been disbursed yet
            $pesananPending = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
                ->where('status', 'Selesai')
                ->whereDoesntHave('disbursements')
                ->get();

            $umkm->total_pesanan_pending = $pesananPending->count();
            $umkm->saldo_pending = (float) $pesananPending->sum('pendapatan_penjual');
            $umkm->komisi_admin_pending = (float) $pesananPending->sum('komisi_admin');
            return $umkm;
        });

        $riwayat = Disbursement::with(['umkm', 'admin', 'pesanan'])->latest()->paginate(15);

        return view('admin.disbursement.index', compact('umkmList', 'riwayat'));
    }

    public function store(Request $request, Umkm $umkm, ActivityLogger $logger): RedirectResponse
    {
        $pesanan = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
            ->where('status', 'Selesai')
            ->whereDoesntHave('disbursements')
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
            'catatan'    => $request->catatan ?: "Pencairan dana pesanan selesai untuk {$umkm->nama_umkm}",
        ]);

        $disbursement->pesanan()->attach($pesanan->pluck('id'));

        $logger->log("Mencatat pencairan dana Rp" . number_format($jumlah, 0, ',', '.') . " ke UMKM {$umkm->nama_umkm}", auth()->user(), $request->ip());

        return back()->with('success', "Pencairan dana sebesar Rp" . number_format($jumlah, 0, ',', '.') . " ke {$umkm->nama_umkm} berhasil dicatat.");
    }
}

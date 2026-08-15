<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifikasiPenjualController extends Controller
{
    public function index(): View
    {
        $menunggu = Umkm::with(['user', 'sellerOnboarding'])
            ->where('status_verifikasi', 'menunggu')
            ->whereHas('sellerOnboarding')
            ->latest()
            ->get();

        $riwayat = Umkm::with(['user', 'verifier'])
            ->whereIn('status_verifikasi', ['disetujui', 'ditolak'])
            ->latest('verified_at')
            ->paginate(15);

        return view('admin.verifikasi-penjual.index', compact('menunggu', 'riwayat'));
    }

    public function approve(Umkm $umkm, ActivityLogger $logger): RedirectResponse
    {
        $umkm->update([
            'status'              => 'aktif',
            'status_verifikasi'   => 'disetujui',
            'verified_at'         => now(),
            'verified_by'         => auth()->id(),
        ]);

        if ($umkm->user) {
            $umkm->user->update(['status' => 'aktif']);
        }

        $logger->log("Menyetujui pendaftaran mitra penjual {$umkm->nama_umkm}", auth()->user(), request()->ip());

        return back()->with('success', "Penjual {$umkm->nama_umkm} berhasil diverifikasi dan diaktifkan.");
    }

    public function reject(Request $request, Umkm $umkm, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'max:500'],
        ]);

        $umkm->update([
            'status_verifikasi'  => 'ditolak',
            'catatan_verifikasi' => $request->catatan,
            'verified_at'        => now(),
            'verified_by'        => auth()->id(),
        ]);

        $logger->log("Menolak pendaftaran mitra penjual {$umkm->nama_umkm} dengan alasan: {$request->catatan}", auth()->user(), request()->ip());

        return back()->with('success', "Permohonan penjual {$umkm->nama_umkm} telah ditolak.");
    }
}

<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerOnboarding;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $umkm = auth()->user()->umkm;
        if (!$umkm) {
            return redirect()->route('home');
        }

        if ($umkm->status_verifikasi === 'disetujui') {
            return redirect()->route('seller.dashboard');
        }

        if ($umkm->sellerOnboarding()->exists()) {
            if ($umkm->status_verifikasi === 'ditolak') {
                return redirect()->route('seller.onboarding.rejected');
            }
            return redirect()->route('seller.onboarding.waiting');
        }

        return view('seller.onboarding');
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'deskripsi_produk'   => ['required', 'string', 'min:10'],
            'kapasitas_mingguan' => ['required', 'integer', 'min:1'],
            'punya_izin'         => ['required', 'in:ya,tidak'],
            'nomor_izin'         => ['nullable', 'string'],
            'cara_kemas'         => ['required', 'string'],
            'sanggup_24jam'      => ['required', 'in:ya,tidak'],
            'foto_ktp'           => ['required', 'image', 'max:5120'],
            'foto_produk'        => ['required', 'image', 'max:5120'],
        ]);

        $umkm = auth()->user()->umkm;

        $jawaban = collect($validated)->except(['foto_ktp', 'foto_produk'])->toArray();
        $jawaban['foto_ktp'] = $request->file('foto_ktp')->store('onboarding', 'public');
        $jawaban['foto_produk'] = $request->file('foto_produk')->store('onboarding', 'public');

        SellerOnboarding::create([
            'umkm_id' => $umkm->id,
            'jawaban' => $jawaban,
        ]);

        $logger->log("Mengirim berkas verifikasi onboarding penjual {$umkm->nama_umkm}", auth()->user(), $request->ip());

        return redirect()->route('seller.onboarding.waiting')
            ->with('success', 'Informasi usaha berhasil dikirim. Tunggu proses verifikasi dari admin.');
    }
}

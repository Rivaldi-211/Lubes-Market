<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use App\Http\Requests\Buyer\ProfileUpdateRequest;
use App\Models\Pesanan;
use App\Models\ZonaPengiriman;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $statsRaw = Pesanan::query()
            ->where('pembeli_id', $user->id)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'Menunggu' THEN 1 END) as menunggu,
                COUNT(CASE WHEN status = 'Diproses' THEN 1 END) as diproses,
                COUNT(CASE WHEN status = 'Selesai' THEN 1 END) as selesai,
                COALESCE(SUM(CASE WHEN status = 'Selesai' THEN total_harga ELSE 0 END), 0) as total_belanja
            ")
            ->first();

        $totalUlasan = \App\Models\Ulasan::where('pembeli_id', $user->id)->count();

        $stats = [
            'total'         => (int) ($statsRaw->total ?? 0),
            'menunggu'      => (int) ($statsRaw->menunggu ?? 0),
            'diproses'      => (int) ($statsRaw->diproses ?? 0),
            'selesai'       => (int) ($statsRaw->selesai ?? 0),
            'total_belanja' => (float) ($statsRaw->total_belanja ?? 0),
            'total_ulasan'  => $totalUlasan,
        ];

        $zonaPengiriman = ZonaPengiriman::aktif()->orderBy('urutan')->get();

        return view('buyer.profile', compact('user', 'stats', 'zonaPengiriman'));
    }

    public function update(ProfileUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $data['foto_profil'] = $request->file('foto_profil')->store('avatars', 'public');
        }

        $user->update($data);

        $logger->log('Memperbarui informasi profil akun pembeli', $user, $request->ip());

        $tab = $request->input('redirect_tab', 'akun');

        return redirect()->route('buyer.profile.edit', ['tab' => $tab])
            ->with('success', 'Informasi profil dan data akun Anda berhasil disimpan.');
    }

    public function updatePassword(PasswordUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('password'),
        ]);

        $logger->log('Memperbarui kata sandi akun pembeli', $user, $request->ip());

        return redirect()->route('buyer.profile.edit', ['tab' => 'keamanan'])
            ->with('success', 'Kata sandi berhasil diperbarui.');
    }
}

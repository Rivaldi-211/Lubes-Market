<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use App\Http\Requests\Seller\AccountUpdateRequest;
use App\Http\Requests\Seller\ProfileRequest;
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
        $umkm = $user->umkm;

        return view('seller.profile', compact('umkm', 'user'));
    }

    public function update(ProfileRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $umkm = $user->umkm;
        $data = $request->safe()->except('foto');

        if ($request->hasFile('foto')) {
            if ($umkm->foto) {
                Storage::disk('public')->delete($umkm->foto);
            }
            $data['foto'] = $request->file('foto')->store('umkm', 'public');
        }

        $umkm->update($data);
        $logger->log('Memperbarui profil UMKM', $user, $request->ip());

        return redirect()->route('seller.profile.edit', ['tab' => 'umkm'])
            ->with('success', 'Profil UMKM berhasil diperbarui.');
    }

    public function updateAccount(AccountUpdateRequest $request, ActivityLogger $logger): RedirectResponse
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

        $logger->log('Memperbarui informasi profil dan akun penjual', $user, $request->ip());

        return redirect()->route('seller.profile.edit', ['tab' => 'akun'])
            ->with('success', 'Informasi akun dan foto profil penjual berhasil diperbarui.');
    }

    public function updatePassword(PasswordUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('password'),
        ]);

        $logger->log('Memperbarui kata sandi akun penjual', $user, $request->ip());

        return redirect()->route('seller.profile.edit', ['tab' => 'keamanan'])
            ->with('success', 'Kata sandi berhasil diperbarui.');
    }
}

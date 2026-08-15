<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use App\Models\LogAktivitas;
use App\Models\Pesanan;
use App\Models\Umkm;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'total_umkm'     => Umkm::count(),
            'total_pesanan'  => Pesanan::count(),
            'total_pengguna' => User::count(),
            'log_admin'      => LogAktivitas::where('user_id', $user->id)->count(),
        ];

        return view('admin.profile', compact('user', 'stats'));
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nama_lengkap'  => ['required', 'string', 'max:100'],
            'username'      => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email'         => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'no_hp'         => ['nullable', 'string', 'max:20'],
            'alamat_utama'  => ['nullable', 'string', 'max:500'],
            'jenis_kelamin' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'foto_profil'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'username.required'     => 'Username wajib diisi.',
            'username.unique'       => 'Username sudah digunakan oleh akun lain.',
            'email.unique'          => 'Email sudah terdaftar pada akun lain.',
            'foto_profil.image'     => 'Berkas foto profil harus berupa gambar.',
            'foto_profil.max'       => 'Ukuran foto profil maksimal 2 MB.',
        ]);

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $validated['foto_profil'] = $request->file('foto_profil')->store('avatars', 'public');
        }

        $user->update($validated);

        $logger->log('Memperbarui profil administrator', $user, $request->ip());

        return redirect()->route('admin.profile.edit', ['tab' => 'akun'])
            ->with('success', 'Informasi profil dan foto avatar administrator berhasil diperbarui.');
    }

    public function updatePassword(PasswordUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('password'),
        ]);

        $logger->log('Memperbarui kata sandi akun administrator', $user, $request->ip());

        return redirect()->route('admin.profile.edit', ['tab' => 'keamanan'])
            ->with('success', 'Kata sandi administrator berhasil diperbarui.');
    }
}

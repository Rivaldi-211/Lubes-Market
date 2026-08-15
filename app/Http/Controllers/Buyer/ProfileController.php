<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use App\Http\Requests\Buyer\ProfileUpdateRequest;
use App\Models\Pesanan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                COUNT(CASE WHEN status = 'Selesai' THEN 1 END) as selesai
            ")
            ->first();

        $stats = [
            'total'    => (int) ($statsRaw->total ?? 0),
            'menunggu' => (int) ($statsRaw->menunggu ?? 0),
            'diproses' => (int) ($statsRaw->diproses ?? 0),
            'selesai'  => (int) ($statsRaw->selesai ?? 0),
        ];

        return view('buyer.profile', compact('user', 'stats'));
    }

    public function update(ProfileUpdateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        $logger->log('Memperbarui informasi akun pembeli', $user, $request->ip());

        return redirect()->route('buyer.profile.edit', ['tab' => 'akun'])
            ->with('success', 'Informasi profil akun Anda berhasil diperbarui.');
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

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required', 'string'],
        ], [
            'identifier.required' => 'Masukkan username atau alamat email akun Anda.',
        ]);

        $identifier = trim((string) $request->input('identifier'));

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['identifier' => 'Akun dengan username atau email tersebut tidak ditemukan.'])
                ->withInput();
        }

        if (!$user->isActive()) {
            return back()
                ->withErrors(['identifier' => 'Akun ini sedang dinonaktifkan. Silakan hubungi admin.'])
                ->withInput();
        }

        if (empty($user->email)) {
            return back()
                ->withErrors(['identifier' => 'Akun ini belum memiliki alamat email terdaftar. Silakan hubungi admin platform.'])
                ->withInput();
        }

        $token = Str::random(64);
        $targetEmail = $user->email;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $targetEmail],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $targetEmail,
            'username' => $user->username,
        ]);

        try {
            Mail::to($targetEmail)->send(new ResetPasswordMail($token, $resetUrl, $user));
            $logger->log("Mengirim email reset password ke {$targetEmail} untuk akun {$user->username}", $user, $request->ip());

            $maskedEmail = $this->maskEmail($targetEmail);

            return back()->with('success', "Tautan reset password telah berhasil dikirim ke email Anda ({$maskedEmail}). Silakan periksa Kotak Masuk (Inbox) atau folder Spam.");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim email reset password: " . $e->getMessage());

            if (config('app.env') === 'local') {
                return back()->with('error', "Gagal mengirim email: " . $e->getMessage() . ". Pastikan konfigurasi SMTP di file .env sudah benar.");
            }

            return back()->with('error', 'Terjadi kendala saat mengirim email. Pastikan koneksi dan pengaturan email server aktif.');
        }
    }

    public function showResetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = (string) $request->query('email');
        $username = (string) $request->query('username');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Tautan atau sesi reset password sudah kedaluwarsa. Silakan ajukan permohonan baru.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
            'username' => $username,
        ]);
    }

    public function resetPassword(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $email = (string) $request->input('email');
        $token = (string) $request->input('token');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check($token, $record->token)) {
            return back()->withErrors(['password' => 'Token reset password tidak valid atau sudah kedaluwarsa.']);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Sesi reset password sudah kedaluwarsa. Silakan ajukan ulang.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('password.request')
                ->withErrors(['identifier' => 'Pengguna dengan email tersebut tidak ditemukan.']);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $logger->log("Berhasil mereset password akun {$user->username}", $user, $request->ip());

        return redirect()->route('login')
            ->with('success', 'Password Anda berhasil diperbarui! Silakan masuk menggunakan password baru.');
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) < 2) return $email;

        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', max(3, $len - 4)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}

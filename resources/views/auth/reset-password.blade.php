@extends('layouts.auth')

@section('title', 'Buat Password Baru')

@section('content')
<div class="auth-heading">
    <small>RESET PASSWORD</small>
    <h2>Tentukan Password Baru.</h2>
    <p>Buat password baru yang kuat dan mudah Anda ingat.</p>
</div>

@if(session('status'))
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-check-circle-fill" style="color: #16a34a; font-size: 16px; flex-shrink: 0;"></i>
        <span>{{ session('status') }}</span>
    </div>
@endif

<form class="auth-form" method="post" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">

    @if(!empty($username))
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; font-size: 13px;">
            <span style="color: #64748b; font-size: 11px; display: block; text-transform: uppercase; font-weight: 700;">Akun yang direset:</span>
            <strong style="color: #0f172a;">{{ '@' . $username }}</strong>
        </div>
    @endif

    <label>
        Password Baru
        <div class="password-field">
            <input type="password" name="password" id="newPassword" autocomplete="new-password" minlength="6" placeholder="Minimal 6 karakter" required autofocus>
            <i class="bi bi-eye-slash" data-toggle-password title="Lihat password" aria-label="Tampilkan password"></i>
        </div>
    </label>

    <label>
        Konfirmasi Password Baru
        <div class="password-field">
            <input type="password" name="password_confirmation" id="newPasswordConfirm" autocomplete="new-password" minlength="6" placeholder="Ketik ulang password baru" required>
            <i class="bi bi-eye-slash" data-toggle-password title="Lihat password" aria-label="Tampilkan password"></i>
        </div>
    </label>

    <button class="button wide" type="submit">
        Simpan Password Baru <i class="bi bi-check2-circle"></i>
    </button>
</form>

<p class="auth-switch"><a href="{{ route('login') }}">Batal dan kembali ke halaman masuk</a></p>
@endsection

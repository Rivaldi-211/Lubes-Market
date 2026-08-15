@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div class="auth-heading">
    <small>AKSES SISTEM</small>
    <h2>Masuk ke akun Anda.</h2>
    <p>Gunakan username dan password yang telah terdaftar.</p>
</div>

<form class="auth-form" method="post" action="{{ route('login.store') }}">
    @csrf
    <label>
        Username
        <input name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
    </label>

    <label>
        Password
        <div class="password-field">
            <input type="password" name="password" id="loginPassword" autocomplete="current-password" required>
            <i class="bi bi-eye-slash" data-toggle-password title="Lihat password" aria-label="Tampilkan password"></i>
        </div>
    </label>

    <div style="display: flex; justify-content: space-between; align-items: center; margin: 4px 0 12px;">
        <label class="check-row" style="margin: 0;">
            <input type="checkbox" name="remember" value="1"> Ingat sesi saya
        </label>
        <a href="{{ route('password.request') }}" style="font-size: 12.5px; font-weight: 700; color: #b45309; text-decoration: none;">
            Lupa password?
        </a>
    </div>

    <button class="button wide" type="submit">Masuk <i class="bi bi-arrow-right"></i></button>
</form>

<p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai pembeli atau penjual</a></p>
@endsection

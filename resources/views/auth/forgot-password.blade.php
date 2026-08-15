@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
<div class="auth-heading">
    <small>PEMULIHAN AKUN</small>
    <h2>Lupa Password Anda?</h2>
    <p>Masukkan username atau email akun Anda untuk melanjutkan proses pembuatan password baru.</p>
</div>

<form class="auth-form" method="post" action="{{ route('password.email') }}">
    @csrf

    <label>
        Username atau Alamat Email
        <input name="identifier" value="{{ old('identifier') }}" placeholder="Contoh: budi_pembeli atau budi@gmail.com" required autofocus>
    </label>

    <button class="button wide" type="submit">
        Verifikasi &amp; Lanjutkan <i class="bi bi-arrow-right"></i>
    </button>
</form>

<p class="auth-switch">Sudah ingat password? <a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
@endsection

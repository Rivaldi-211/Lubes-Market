@extends('layouts.auth')

@section('title', 'Daftar')

@section('content')
<div class="auth-heading">
    <small>AKUN BARU</small>
    <h2>Mulai sebagai pembeli atau mitra UMKM.</h2>
    <p>Jika memilih penjual, sistem otomatis membuat profil UMKM yang dapat dilengkapi setelah masuk.</p>
</div>

<form class="auth-form" method="post" action="{{ route('register.store') }}">
    @csrf
    <div class="auth-grid">
        <label>
            Nama lengkap
            <input name="nama_lengkap" value="{{ old('nama_lengkap') }}" required>
        </label>
        <label>
            Username
            <input name="username" value="{{ old('username') }}" required>
        </label>
        <label>
            Email <span>(opsional)</span>
            <input type="email" name="email" value="{{ old('email') }}">
        </label>
        <label>
            No. HP <span>(opsional)</span>
            <input name="no_hp" value="{{ old('no_hp') }}">
        </label>
        <label>
            Peran
            <select name="role" id="roleSelect" required>
                <option value="pembeli" @selected(old('role') === 'pembeli')>Pembeli</option>
                <option value="penjual" @selected(old('role') === 'penjual')>Penjual / Mitra UMKM</option>
            </select>
        </label>
        <label data-seller-field>
            Nama UMKM
            <input name="nama_umkm" value="{{ old('nama_umkm') }}">
        </label>
        <label class="auth-span-2" data-seller-field>
            Alamat usaha
            <input name="alamat" value="{{ old('alamat') }}">
        </label>
        <label>
            Password
            <div class="password-field">
                <input type="password" name="password" required>
                <i class="bi bi-eye-slash" data-toggle-password title="Lihat password" aria-label="Tampilkan password"></i>
            </div>
        </label>
        <label>
            Konfirmasi password
            <div class="password-field">
                <input type="password" name="password_confirmation" required>
                <i class="bi bi-eye-slash" data-toggle-password title="Lihat password" aria-label="Tampilkan password"></i>
            </div>
        </label>
    </div>

    <button class="button wide" type="submit">Buat akun <i class="bi bi-arrow-right"></i></button>
</form>

<p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>

<script>
    const role = document.getElementById('roleSelect');
    const sync = () => document.querySelectorAll('[data-seller-field]').forEach(el => {
        el.style.display = role.value === 'penjual' ? 'block' : 'none';
        const input = el.querySelector('input');
        if (input) input.required = role.value === 'penjual' && input.name === 'nama_umkm';
    });
    role.addEventListener('change', sync);
    sync();
</script>
@endsection

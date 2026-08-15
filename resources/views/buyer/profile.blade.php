@extends('layouts.dashboard')
@section('title', 'Pengaturan Akun')
@section('eyebrow', 'Akun Pembeli')
@section('page_title', 'Pengaturan Akun')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Profil & Keamanan Akun</p>
        <h1>Kelola Informasi Akun Anda</h1>
        <p>Perbarui biodata profil dan amankan kata sandi akun untuk kenyamanan bertransaksi di LUDES-MARKET.</p>
    </div>
    <a class="button button-outline" href="{{ route('buyer.dashboard') }}">
        <i class="bi bi-bag-check"></i> Riwayat Pesanan
    </a>
</section>

{{-- Tab Switcher --}}
<div class="profile-tabs-header">
    <button type="button" class="profile-tab-btn {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" data-tab-target="tab-akun">
        <i class="bi bi-person-badge"></i> Informasi Akun
    </button>
    <button type="button" class="profile-tab-btn {{ request('tab') === 'keamanan' ? 'active' : '' }}" data-tab-target="tab-keamanan">
        <i class="bi bi-shield-lock"></i> Keamanan & Kata Sandi
    </button>
</div>

{{-- TAB 1: INFORMASI AKUN PEMBELI --}}
<div class="profile-tab-content {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" id="tab-akun">
    <form class="form-page" method="post" action="{{ route('buyer.profile.update') }}">
        @csrf
        @method('PATCH')

        <div class="form-card">
            <h2>Biodata Akun Pembeli</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Informasi ini digunakan pada formulir checkout pesanan dan pengiriman nota transaksi.
            </p>

            <div class="field-grid">
                <label class="full">Nama Lengkap <span style="color:#b91c1c">*</span>
                    <input name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required placeholder="Nama lengkap Anda">
                </label>
                <label>Username Login <span style="color:#b91c1c">*</span>
                    <input name="username" value="{{ old('username', $user->username) }}" required placeholder="Username unik (tanpa spasi)">
                </label>
                <label>Alamat Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="nama@email.com">
                </label>
                <label class="full">Nomor WhatsApp / HP
                    <input name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 081234567890">
                </label>
            </div>

            <button class="button" style="margin-top:20px">
                <i class="bi bi-check2-circle"></i> Simpan Perubahan Akun
            </button>
        </div>

        <aside class="profile-badge-card">
            <div class="profile-avatar-circle">
                {{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}
            </div>
            <div>
                <h3>{{ $user->nama_lengkap }}</h3>
                <p>{{ '@' . $user->username }}</p>
            </div>
            <x-status-badge status="Aktif" />

            <div class="profile-meta-list">
                <div class="profile-meta-item">
                    <span>Peran Akun</span>
                    <strong>Pembeli Terdaftar</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Terdaftar Sejak</span>
                    <strong>{{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-' }}</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Total Pesanan</span>
                    <strong>{{ $stats['total'] }} Transaksi</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Pesanan Selesai</span>
                    <strong style="color:var(--green-800)">{{ $stats['selesai'] }} Selesai</strong>
                </div>
            </div>

            <a href="{{ route('buyer.dashboard') }}" class="btn-secondary" style="margin-top:8px;width:100%;justify-content:center;padding:9px 12px;font-size:11px;">
                <i class="bi bi-bag-check"></i> Lihat Riwayat Belanja
            </a>
        </aside>
    </form>
</div>

{{-- TAB 2: KEAMANAN & KATA SANDI --}}
<div class="profile-tab-content {{ request('tab') === 'keamanan' ? 'active' : '' }}" id="tab-keamanan">
    <div class="form-page">
        <form class="form-card" method="post" action="{{ route('buyer.profile.password') }}">
            @csrf
            @method('PATCH')

            <h2>Ganti Kata Sandi</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Gunakan kombinasi kata sandi yang aman untuk melindungi akun dan riwayat transaksi Anda.
            </p>

            <div class="field-grid">
                <label class="full">Kata Sandi Saat Ini <span style="color:#b91c1c">*</span>
                    <div class="input-password-wrap" style="margin-top:5px;">
                        <input type="password" name="current_password" required placeholder="Masukkan kata sandi saat ini">
                        <button type="button" class="btn-toggle-pw" aria-label="Lihat kata sandi"><i class="bi bi-eye"></i></button>
                    </div>
                </label>
                <label>Kata Sandi Baru <span style="color:#b91c1c">*</span>
                    <div class="input-password-wrap" style="margin-top:5px;">
                        <input type="password" name="password" required placeholder="Minimal 8 karakter">
                        <button type="button" class="btn-toggle-pw" aria-label="Lihat kata sandi"><i class="bi bi-eye"></i></button>
                    </div>
                </label>
                <label>Konfirmasi Kata Sandi Baru <span style="color:#b91c1c">*</span>
                    <div class="input-password-wrap" style="margin-top:5px;">
                        <input type="password" name="password_confirmation" required placeholder="Ulangi kata sandi baru">
                        <button type="button" class="btn-toggle-pw" aria-label="Lihat kata sandi"><i class="bi bi-eye"></i></button>
                    </div>
                </label>
            </div>

            <div class="security-tips-box">
                <h4><i class="bi bi-info-circle"></i> Petunjuk Keamanan</h4>
                <ul>
                    <li>Kata sandi baru wajib berisi minimal <strong>8 karakter</strong>.</li>
                    <li>Gunakan kombinasi huruf, angka, dan karakter khusus.</li>
                    <li>Jangan gunakan kata sandi yang mudah ditebak seperti tanggal lahir atau nomor telepon.</li>
                </ul>
            </div>

            <button class="button" style="margin-top:20px">
                <i class="bi bi-shield-check"></i> Perbarui Kata Sandi
            </button>
        </form>

        <aside class="form-card">
            <h2>Tips Akun Aman</h2>
            <div style="font-size:12px;color:#555e56;line-height:1.6;display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-lock-fill" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Jaga kerahasiaan kata sandi Anda dan jangan berikan kepada orang lain.</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-check-circle-fill" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Pastikan nomor WhatsApp aktif untuk konfirmasi pesanan dari pihak UMKM.</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-shield-fill-check" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Hubungi admin BUMDes jika Anda menemukan aktivitas mencurigakan pada akun Anda.</span>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab Navigation
    const tabBtns = document.querySelectorAll('.profile-tab-btn');
    const tabPanes = document.querySelectorAll('.profile-tab-content');

    function activateTab(tabId) {
        tabBtns.forEach(b => {
            if (b.dataset.tabTarget === tabId) {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });
        tabPanes.forEach(p => {
            if (p.id === tabId) {
                p.classList.add('active');
            } else {
                p.classList.remove('active');
            }
        });
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            activateTab(this.dataset.tabTarget);
        });
    });

    // Toggle Password Visibility
    document.querySelectorAll('.btn-toggle-pw').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });
});
</script>
@endpush

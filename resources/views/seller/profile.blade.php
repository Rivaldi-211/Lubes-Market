@extends('layouts.dashboard')
@section('title', 'Profil & Akun Penjual')
@section('eyebrow', 'Mitra UMKM')
@section('page_title', 'Profil & Akun')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Pengaturan Mitra UMKM</p>
        <h1>Kelola Profil Usaha & Kredensial Akun</h1>
        <p>Perbarui identitas toko yang tampil pada katalog atau amankan informasi akun login Anda.</p>
    </div>
</section>

{{-- Tab Switcher --}}
<div class="profile-tabs-header">
    <button type="button" class="profile-tab-btn {{ request('tab', 'umkm') === 'umkm' ? 'active' : '' }}" data-tab-target="tab-umkm">
        <i class="bi bi-shop"></i> Profil Usaha (UMKM)
    </button>
    <button type="button" class="profile-tab-btn {{ request('tab') === 'akun' ? 'active' : '' }}" data-tab-target="tab-akun">
        <i class="bi bi-person-badge"></i> Data Akun Penjual
    </button>
    <button type="button" class="profile-tab-btn {{ request('tab') === 'keamanan' ? 'active' : '' }}" data-tab-target="tab-keamanan">
        <i class="bi bi-shield-lock"></i> Keamanan & Kata Sandi
    </button>
</div>

{{-- TAB 1: PROFIL UMKM --}}
<div class="profile-tab-content {{ request('tab', 'umkm') === 'umkm' ? 'active' : '' }}" id="tab-umkm">
    <form class="form-page" method="post" enctype="multipart/form-data" action="{{ route('seller.profile.update') }}">
        @csrf
        @method('PATCH')

        <div class="form-card">
            <h2>Identitas Usaha UMKM</h2>
            <div class="field-grid">
                <label>Nama UMKM <span style="color:#b91c1c">*</span>
                    <input name="nama_umkm" value="{{ old('nama_umkm', $umkm->nama_umkm) }}" required placeholder="Contoh: Aneka Keripik Berkah">
                </label>
                <label>Nama Pemilik <span style="color:#b91c1c">*</span>
                    <input name="pemilik" value="{{ old('pemilik', $umkm->pemilik) }}" required placeholder="Nama pemilik usaha">
                </label>
                <label>No. HP / WhatsApp Usaha
                    <input name="no_hp" value="{{ old('no_hp', $umkm->no_hp) }}" placeholder="Contoh: 08123456789">
                </label>
                <label class="full">Alamat Lengkap Usaha
                    <input name="alamat" value="{{ old('alamat', $umkm->alamat) }}" placeholder="Contoh: Dusun Moncongloe, RT 01 / RW 02">
                </label>
                <label class="full">Deskripsi Usaha
                    <textarea name="deskripsi" rows="6" placeholder="Ceritakan sejarah singkat, keunggulan, atau ciri khas produk toko Anda...">{{ old('deskripsi', $umkm->deskripsi) }}</textarea>
                </label>
            </div>
            <button class="button" style="margin-top:20px">
                <i class="bi bi-check2-circle"></i> Simpan Profil Usaha
            </button>
        </div>

        <aside class="form-card">
            <h2>Foto Tempat Usaha / Logo</h2>
            <div class="image-preview" id="umkmPhotoBox">
                @if($umkm->foto)
                    <img id="umkmPhotoImg" src="{{ asset('storage/'.$umkm->foto) }}" alt="{{ $umkm->nama_umkm }}">
                @else
                    <i id="umkmPhotoPlaceholder" class="bi bi-shop" style="font-size:44px;color:#708071"></i>
                @endif
            </div>
            <label style="display:block;margin-top:14px;font-size:11px;font-weight:700">
                Ganti Foto Toko
                <input class="form-control" type="file" name="foto" id="umkmPhotoInput" accept="image/jpeg,image/png,image/webp">
            </label>
            <p class="help">Format JPG, PNG, atau WebP maksimal 2 MB. Gunakan foto tempat usaha atau logo yang jelas dan menarik.</p>
        </aside>
    </form>
</div>

{{-- TAB 2: DATA AKUN PENJUAL --}}
<div class="profile-tab-content {{ request('tab') === 'akun' ? 'active' : '' }}" id="tab-akun">
    <form class="form-page" method="post" action="{{ route('seller.profile.account') }}">
        @csrf
        @method('PATCH')

        <div class="form-card">
            <h2>Informasi Akun Penjual</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Data ini digunakan untuk keperluan login akun dan komunikasi resmi platform.
            </p>
            <div class="field-grid">
                <label class="full">Nama Lengkap Pengguna <span style="color:#b91c1c">*</span>
                    <input name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required placeholder="Nama lengkap Anda">
                </label>
                <label>Username Login <span style="color:#b91c1c">*</span>
                    <input name="username" value="{{ old('username', $user->username) }}" required placeholder="Username unik (tanpa spasi)">
                </label>
                <label>Alamat Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="contoh@email.com">
                </label>
                <label class="full">Nomor HP Pribadi
                    <input name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xxxxxxxxxx">
                </label>
            </div>
            <button class="button" style="margin-top:20px">
                <i class="bi bi-check2-circle"></i> Simpan Informasi Akun
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
                    <strong>Mitra UMKM</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Toko Terdaftar</span>
                    <strong>{{ $umkm->nama_umkm ?: '-' }}</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Terdaftar Sejak</span>
                    <strong>{{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-' }}</strong>
                </div>
            </div>
        </aside>
    </form>
</div>

{{-- TAB 3: KEAMANAN & KATA SANDI --}}
<div class="profile-tab-content {{ request('tab') === 'keamanan' ? 'active' : '' }}" id="tab-keamanan">
    <div class="form-page">
        <form class="form-card" method="post" action="{{ route('seller.profile.password') }}">
            @csrf
            @method('PATCH')

            <h2>Ganti Kata Sandi</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Pastikan Anda menggunakan kata sandi yang kuat dan tidak membagikannya kepada pihak lain.
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
                    <li>Gunakan kombinasi huruf besar, huruf kecil, angka, atau simbol agar lebih aman.</li>
                    <li>Setelah kata sandi diperbarui, sesi login Anda akan tetap aktif.</li>
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
                    <span>Jangan pernah memberitahukan kata sandi Anda kepada siapa pun termasuk pengelola platform.</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-phone-fill" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Pastikan nomor WhatsApp dan email Anda aktif untuk menerima notifikasi pesanan secara berkala.</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-clock-history" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Disarankan mengganti kata sandi secara berkala setiap 3 hingga 6 bulan.</span>
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

    // UMKM Live Photo Preview
    const photoInput = document.getElementById('umkmPhotoInput');
    const photoBox = document.getElementById('umkmPhotoBox');
    if (photoInput && photoBox) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let img = photoBox.querySelector('img');
                    if (!img) {
                        photoBox.innerHTML = '';
                        img = document.createElement('img');
                        img.id = 'umkmPhotoImg';
                        photoBox.appendChild(img);
                    }
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endpush

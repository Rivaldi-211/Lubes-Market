@extends('layouts.dashboard')
@section('title', 'Profil & Pengaturan Admin')
@section('eyebrow', 'Administrator')
@section('page_title', 'Profil Administrator')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Profil &amp; Keamanan Sistem</p>
        <h1>Kelola Profil Administrator</h1>
        <p>Perbarui identitas pengelola platform, foto profil avatar, dan amankan kredensial akses sistem.</p>
    </div>
    <a class="button button-outline" href="{{ route('admin.dashboard') }}">
        <i class="bi bi-grid"></i> Dashboard Admin
    </a>
</section>

{{-- Tab Switcher --}}
<div class="profile-tabs-header">
    <button type="button" class="profile-tab-btn {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" data-tab-target="tab-akun">
        <i class="bi bi-person-badge"></i> Biodata &amp; Foto Profil
    </button>
    <button type="button" class="profile-tab-btn {{ request('tab') === 'keamanan' ? 'active' : '' }}" data-tab-target="tab-keamanan">
        <i class="bi bi-shield-lock"></i> Keamanan &amp; Kata Sandi
    </button>
</div>

{{-- TAB 1: BIODATA & FOTO PROFIL ADMIN --}}
<div class="profile-tab-content {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" id="tab-akun">
    <form class="form-page" method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="form-card">
            <h2>Biodata Administrator</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Identitas ini digunakan sebagai nama penanggung jawab dan dicatat pada audit log aktivitas platform.
            </p>

            {{-- Foto Profil Avatar Section --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="position: relative; width: 76px; height: 76px; border-radius: 50%; overflow: hidden; background: #123825; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; border: 3px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.08); flex-shrink: 0;">
                    @if($user->foto_profil)
                        <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="{{ $user->nama_lengkap }}" id="adminAvatarPreview" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span id="adminAvatarInitials">{{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}</span>
                        <img src="" alt="" id="adminAvatarPreview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    @endif
                </div>
                <div style="flex: 1; min-width: 220px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #1e293b;">Foto Profil Avatar Admin</label>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <label for="adminAvatarInput" class="button" style="cursor: pointer; padding: 8px 16px; font-size: 12.5px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; margin: 0; background: #123825; border: 1px solid #123825; color: #ffffff; box-shadow: 0 2px 4px rgba(18, 56, 37, 0.2); transition: all 0.15s ease;">
                            <i class="bi bi-camera-fill" style="color: #eab308; font-size: 14px;"></i> Pilih Foto
                            <input type="file" name="foto_profil" id="adminAvatarInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                        </label>
                        <span id="adminAvatarFileName" style="font-size: 12px; color: #64748b; font-style: italic;">Belum ada foto baru dipilih</span>
                    </div>
                    <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 6px;">Format: JPG, PNG, atau WebP (Maksimal 2 MB).</small>
                </div>
            </div>

            <div class="field-grid">
                <label class="full">Nama Lengkap Administrator <span style="color:#b91c1c">*</span>
                    <input name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required placeholder="Nama lengkap pengelola">
                </label>

                <label>Username Akses <span style="color:#b91c1c">*</span>
                    <input name="username" value="{{ old('username', $user->username) }}" required placeholder="Username unik">
                </label>

                <label>Email Resmi / Pengelola
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="admin@ludesmarket.id">
                </label>

                <label>Nomor HP / WhatsApp
                    <input name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xxxxxxxxxx">
                </label>

                <label>Jenis Kelamin
                    <select name="jenis_kelamin">
                        <option value="">— Pilih Jenis Kelamin —</option>
                        <option value="Laki-laki" @selected(old('jenis_kelamin', $user->jenis_kelamin) === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('jenis_kelamin', $user->jenis_kelamin) === 'Perempuan')>Perempuan</option>
                    </select>
                </label>

                <label class="full">Alamat Kantor / Domisili
                    <textarea name="alamat_utama" rows="3" placeholder="Alamat kantor BUMDes atau domisili administrator">{{ old('alamat_utama', $user->alamat_utama) }}</textarea>
                </label>
            </div>

            <button class="button" style="margin-top:20px">
                <i class="bi bi-check2-circle"></i> Simpan Profil Administrator
            </button>
        </div>

        {{-- Aside Identity Card --}}
        <aside class="profile-badge-card">
            <div class="profile-avatar-circle" style="overflow: hidden; padding: 0;">
                @if($user->foto_profil)
                    <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="{{ $user->nama_lengkap }}" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    {{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}
                @endif
            </div>
            <div>
                <h3>{{ $user->nama_lengkap }}</h3>
                <p>{{ '@' . $user->username }}</p>
            </div>
            <x-status-badge status="Aktif" />

            <div class="profile-meta-list">
                <div class="profile-meta-item">
                    <span>Tingkat Akses</span>
                    <strong style="color:var(--gold-dark, #b48325)"><i class="bi bi-shield-shaded"></i> Super Administrator</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Mitra UMKM Terdaftar</span>
                    <strong>{{ $stats['total_umkm'] }} UMKM</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Total Transaksi</span>
                    <strong>{{ $stats['total_pesanan'] }} Pesanan</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Total Pengguna</span>
                    <strong>{{ $stats['total_pengguna'] }} Akun</strong>
                </div>
                <div class="profile-meta-item">
                    <span>Aktivitas Tercatat</span>
                    <strong>{{ $stats['log_admin'] }} Log Aksi</strong>
                </div>
            </div>

            <a href="{{ route('admin.logs.index') }}" class="btn-secondary" style="margin-top:8px;width:100%;justify-content:center;padding:9px 12px;font-size:11px;">
                <i class="bi bi-clock-history"></i> Lihat Riwayat Log Aktivitas
            </a>
        </aside>
    </form>
</div>

{{-- TAB 2: KEAMANAN & KATA SANDI ADMIN --}}
<div class="profile-tab-content {{ request('tab') === 'keamanan' ? 'active' : '' }}" id="tab-keamanan">
    <div class="form-page">
        <form class="form-card" method="post" action="{{ route('admin.profile.password') }}">
            @csrf
            @method('PATCH')

            <h2>Ganti Kata Sandi Administrator</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Sebagai pengelola sistem dengan hak akses penuh, pastikan akun dilindungi dengan kata sandi yang sangat kuat.
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
                <h4><i class="bi bi-shield-lock-fill"></i> Standar Keamanan Administrator</h4>
                <ul>
                    <li>Gunakan minimal <strong>8 karakter</strong> kombinasi huruf kapital, angka, dan simbol unik.</li>
                    <li>Jangan pernah membagikan kredensial login admin kepada pihak yang tidak berwenang.</li>
                    <li>Pastikan logout setelah selesai mengelola sistem di komputer umum.</li>
                </ul>
            </div>

            <button class="button" style="margin-top:20px">
                <i class="bi bi-shield-check"></i> Perbarui Kata Sandi Admin
            </button>
        </form>

        <aside class="form-card">
            <h2>Pusat Keamanan Platform</h2>
            <div style="font-size:12px;color:#555e56;line-height:1.6;display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-shield-fill-check" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Setiap aksi pengelolaan data (verifikasi, perubahan status, pencairan) tercatat secara permanen di Log Aktivitas.</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-key-fill" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Ganti kata sandi secara berkala untuk menjaga integritas data UMKM dan transaksi desa.</span>
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

    // Admin Avatar Live Preview & Filename updater
    const adminAvatarInput = document.getElementById('adminAvatarInput');
    const adminAvatarPreview = document.getElementById('adminAvatarPreview');
    const adminAvatarInitials = document.getElementById('adminAvatarInitials');
    const adminAvatarFileName = document.getElementById('adminAvatarFileName');

    if (adminAvatarInput && adminAvatarPreview) {
        adminAvatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                if (adminAvatarFileName) {
                    adminAvatarFileName.textContent = file.name;
                    adminAvatarFileName.style.fontStyle = 'normal';
                    adminAvatarFileName.style.fontWeight = '600';
                    adminAvatarFileName.style.color = '#123825';
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    adminAvatarPreview.src = e.target.result;
                    adminAvatarPreview.style.display = 'block';
                    if (adminAvatarInitials) adminAvatarInitials.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                if (adminAvatarFileName) {
                    adminAvatarFileName.textContent = 'Belum ada foto baru dipilih';
                    adminAvatarFileName.style.fontStyle = 'italic';
                    adminAvatarFileName.style.fontWeight = 'normal';
                    adminAvatarFileName.style.color = '#64748b';
                }
            }
        });
    }
});
</script>
@endpush

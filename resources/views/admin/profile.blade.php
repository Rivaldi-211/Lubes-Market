@extends('layouts.dashboard')
@section('title', 'Profil Administrator')
@section('eyebrow', 'Administrator')
@section('page_title', 'Profil & Pengaturan Akun')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Akun &amp; Kredensial Administrator</p>
        <h1>Profil &amp; Pengaturan Akun Admin</h1>
        <p>Kelola identitas resmi, nomor kontak pengaduan platform, avatar, dan kata sandi administrator LUDES-MARKET.</p>
    </div>
    <a class="button button-outline" href="{{ route('admin.dashboard') }}">
        <i class="bi bi-speedometer2"></i> Dashboard Admin
    </a>
</section>

{{-- Metric Summary --}}
<div class="metric-grid">
    <article>
        <small>TOTAL MITRA UMKM</small>
        <strong>{{ $stats['total_umkm'] }}</strong>
        <span>Toko UMKM Binaan</span>
    </article>
    <article>
        <small>TOTAL TRANSAKSI</small>
        <strong>{{ $stats['total_pesanan'] }}</strong>
        <span>Pesanan Masuk</span>
    </article>
    <article>
        <small>TOTAL PENGGUNA</small>
        <strong>{{ $stats['total_pengguna'] }}</strong>
        <span>Akun Terdaftar</span>
    </article>
    <article>
        <small>AKTIVITAS SISTEM</small>
        <strong>{{ $stats['log_admin'] }}</strong>
        <span>Tindakan Dicatat</span>
    </article>
</div>

{{-- Tab Switcher --}}
<div class="profile-tabs-header">
    <button type="button" class="profile-tab-btn {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" data-tab-target="tab-akun">
        <i class="bi bi-person-badge"></i> Data Profil &amp; Kontak Admin
    </button>
    <button type="button" class="profile-tab-btn {{ request('tab') === 'keamanan' ? 'active' : '' }}" data-tab-target="tab-keamanan">
        <i class="bi bi-shield-lock"></i> Keamanan &amp; Kata Sandi
    </button>
</div>

{{-- TAB 1: DATA PROFIL & KONTAK --}}
<div class="profile-tab-content {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" id="tab-akun">
    <form class="form-page" method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="form-card">
            <h2>Informasi Identitas &amp; Kontak Resmi</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Nomor HP dan email di bawah digunakan sebagai kontak resmi pusat bantuan platform dan saluran komunikasi kepada pembeli &amp; penjual.
            </p>

            {{-- Avatar Section --}}
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
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #1e293b;">Foto Profil Avatar Administrator</label>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <label for="adminAvatarInput" class="button" style="cursor: pointer; padding: 8px 16px; font-size: 12.5px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; margin: 0; background: #123825; border: 1px solid #123825; color: #ffffff; box-shadow: 0 2px 4px rgba(18, 56, 37, 0.2); transition: all 0.15s ease;">
                            <i class="bi bi-camera-fill" style="color: #eab308; font-size: 14px;"></i> Pilih Foto Baru
                            <input type="file" name="foto_profil" id="adminAvatarInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                        </label>
                        <span id="adminAvatarFileName" style="font-size: 12px; color: #64748b; font-style: italic;">Belum ada foto baru dipilih</span>
                    </div>
                    <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 6px;">Format yang didukung: JPG, PNG, atau WebP (Maksimal 2 MB).</small>
                </div>
            </div>

            <div class="field-grid">
                <label class="full">Nama Lengkap Administrator <span style="color:#b91c1c">*</span>
                    <input name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required placeholder="Nama lengkap pengelola admin">
                </label>

                <label>Username Akun <span style="color:#b91c1c">*</span>
                    <input name="username" value="{{ old('username', $user->username) }}" required placeholder="Username unik">
                </label>

                <label>Email Resmi Platform
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="admin@ludesmarket.id">
                </label>

                <label>Nomor WhatsApp / HP Layanan Bantuan
                    <input name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 081234500001">
                </label>

                <label>Jenis Kelamin
                    <select name="jenis_kelamin">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="Laki-laki" {{ old('jenis_kelamin', $user->jenis_kelamin) === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('jenis_kelamin', $user->jenis_kelamin) === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </label>

                <label>Tanggal Lahir
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($user->tanggal_lahir)->format('Y-m-d')) }}">
                </label>

                <label class="full">Alamat Kantor / Domisili
                    <textarea name="alamat_utama" rows="3" placeholder="Contoh: Kantor Pengelola BUMDes / Platform LUDES-MARKET, Desa Moncongloe">{{ old('alamat_utama', $user->alamat_utama) }}</textarea>
                </label>
            </div>

            <button class="button" style="margin-top:24px; padding: 12px 24px; font-weight: 700;">
                <i class="bi bi-check2-circle"></i> Simpan Profil Administrator
            </button>
        </div>
    </form>
</div>

{{-- TAB 2: KEAMANAN & KATA SANDI --}}
<div class="profile-tab-content {{ request('tab') === 'keamanan' ? 'active' : '' }}" id="tab-keamanan">
    <form class="form-page" method="post" action="{{ route('admin.profile.password') }}">
        @csrf
        @method('PATCH')

        <div class="form-card">
            <h2>Perbarui Kata Sandi Administrator</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Gunakan kombinasi minimal 8 karakter huruf dan angka untuk memastikan keamanan akun administrator Anda.
            </p>

            <div class="field-grid">
                <label class="full">Kata Sandi Saat Ini <span style="color:#b91c1c">*</span>
                    <div style="position: relative;">
                        <input type="password" name="current_password" id="admin_current_password" required placeholder="Masukkan kata sandi lama Anda" style="width: 100%; padding-right: 42px;">
                        <button type="button" class="btn-toggle-pwd" data-target="admin_current_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </label>

                <label>Kata Sandi Baru <span style="color:#b91c1c">*</span>
                    <div style="position: relative;">
                        <input type="password" name="password" id="admin_password" required placeholder="Minimal 8 karakter" style="width: 100%; padding-right: 42px;">
                        <button type="button" class="btn-toggle-pwd" data-target="admin_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </label>

                <label>Konfirmasi Kata Sandi Baru <span style="color:#b91c1c">*</span>
                    <div style="position: relative;">
                        <input type="password" name="password_confirmation" id="admin_password_confirmation" required placeholder="Ulangi kata sandi baru" style="width: 100%; padding-right: 42px;">
                        <button type="button" class="btn-toggle-pwd" data-target="admin_password_confirmation" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </label>
            </div>

            <button class="button" style="margin-top:24px; padding: 12px 24px; font-weight: 700; background: #0f766e;">
                <i class="bi bi-shield-check"></i> Perbarui Kata Sandi
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Tab Switcher
    const tabBtns = document.querySelectorAll('.profile-tab-btn');
    const tabContents = document.querySelectorAll('.profile-tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-tab-target');
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.add('active');
            }

            // Update URL query parameter without page reload
            const tabName = targetId.replace('tab-', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        });
    });

    // 2. Avatar Preview
    const avatarInput = document.getElementById('adminAvatarInput');
    const avatarPreview = document.getElementById('adminAvatarPreview');
    const avatarInitials = document.getElementById('adminAvatarInitials');
    const avatarFileName = document.getElementById('adminAvatarFileName');

    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal ukuran foto adalah 2 MB.');
                    this.value = '';
                    return;
                }

                if (avatarFileName) {
                    avatarFileName.textContent = file.name;
                    avatarFileName.style.color = '#15803d';
                    avatarFileName.style.fontStyle = 'normal';
                }

                const reader = new FileReader();
                reader.onload = function(evt) {
                    if (avatarPreview) {
                        avatarPreview.src = evt.target.result;
                        avatarPreview.style.display = 'block';
                    }
                    if (avatarInitials) {
                        avatarInitials.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 3. Toggle Password Visibility
    document.querySelectorAll('.btn-toggle-pwd').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetInputId = btn.getAttribute('data-target');
            const input = document.getElementById(targetInputId);
            const icon = btn.querySelector('i');
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });
});
</script>
@endpush

@extends('layouts.dashboard')
@section('title', 'Profil & Pengaturan Akun')
@section('eyebrow', 'Akun Pembeli')
@section('page_title', 'Profil Pengguna')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Profil &amp; Keamanan Akun</p>
        <h1>Kelola Profil Akun Pembeli</h1>
        <p>Lengkapi foto profil, biodata, dan alamat pengiriman utama untuk kemudahan belanja di LUDES-MARKET.</p>
    </div>
    <a class="button button-outline" href="{{ route('buyer.dashboard') }}">
        <i class="bi bi-bag-check"></i> Pesanan Saya
    </a>
</section>

{{-- Tab Switcher --}}
<div class="profile-tabs-header">
    <button type="button" class="profile-tab-btn {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" data-tab-target="tab-akun">
        <i class="bi bi-person-badge"></i> Biodata &amp; Foto Profil
    </button>
    <button type="button" class="profile-tab-btn {{ request('tab') === 'alamat' ? 'active' : '' }}" data-tab-target="tab-alamat">
        <i class="bi bi-geo-alt"></i> Alamat Pengiriman Utama
    </button>
    <button type="button" class="profile-tab-btn {{ request('tab') === 'keamanan' ? 'active' : '' }}" data-tab-target="tab-keamanan">
        <i class="bi bi-shield-lock"></i> Keamanan &amp; Kata Sandi
    </button>
</div>

{{-- TAB 1: INFORMASI & BIODATA PEMBELI --}}
<div class="profile-tab-content {{ request('tab', 'akun') === 'akun' ? 'active' : '' }}" id="tab-akun">
    <form class="form-page" method="post" action="{{ route('buyer.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <input type="hidden" name="redirect_tab" value="akun">

        <div class="form-card">
            <h2>Biodata Profil Pembeli</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Perbarui identitas pribadi dan foto profil Anda. Informasi ini akan memudahkan kurir dan penjual mengenali Anda.
            </p>

            {{-- Foto Profil Upload Section --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="position: relative; width: 76px; height: 76px; border-radius: 50%; overflow: hidden; background: #123825; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; border: 3px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.08); flex-shrink: 0;">
                    @if($user->foto_profil)
                        <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="{{ $user->nama_lengkap }}" id="avatarPreview" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span id="avatarInitials">{{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}</span>
                        <img src="" alt="" id="avatarPreview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    @endif
                </div>
                <div style="flex: 1; min-width: 220px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #1e293b;">Foto Profil Avatar</label>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <label for="avatarInput" class="button" style="cursor: pointer; padding: 8px 16px; font-size: 12.5px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; margin: 0; background: #123825; border: 1px solid #123825; color: #ffffff; box-shadow: 0 2px 4px rgba(18, 56, 37, 0.2); transition: all 0.15s ease;">
                            <i class="bi bi-camera-fill" style="color: #eab308; font-size: 14px;"></i> Pilih Foto
                            <input type="file" name="foto_profil" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                        </label>
                        <span id="avatarFileName" style="font-size: 12px; color: #64748b; font-style: italic;">Belum ada foto baru dipilih</span>
                    </div>
                    <small style="color: #64748b; font-size: 11.5px; display: block; margin-top: 6px;">Format yang didukung: JPG, PNG, atau WebP (Maksimal 2 MB).</small>
                </div>
            </div>

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

                <label>Nomor WhatsApp / HP
                    <input name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 081234567890">
                </label>

                <label>Jenis Kelamin
                    <select name="jenis_kelamin">
                        <option value="">— Pilih Jenis Kelamin —</option>
                        <option value="Laki-laki" @selected(old('jenis_kelamin', $user->jenis_kelamin) === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('jenis_kelamin', $user->jenis_kelamin) === 'Perempuan')>Perempuan</option>
                    </select>
                </label>

                <label class="full">Tanggal Lahir
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', optional($user->tanggal_lahir)->format('Y-m-d')) }}">
                </label>
            </div>

            <button class="button" style="margin-top:20px">
                <i class="bi bi-check2-circle"></i> Simpan Biodata Profil
            </button>
        </div>

        {{-- Aside Info Card --}}
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
                <div class="profile-meta-item">
                    <span>Total Belanja</span>
                    <strong style="color:#059669">Rp{{ number_format($stats['total_belanja'], 0, ',', '.') }}</strong>
                </div>
            </div>

            <a href="{{ route('buyer.dashboard') }}" class="btn-secondary" style="margin-top:8px;width:100%;justify-content:center;padding:9px 12px;font-size:11px;">
                <i class="bi bi-bag-check"></i> Riwayat Belanja Saya
            </a>
        </aside>
    </form>
</div>

{{-- TAB 2: ALAMAT PENGIRIMAN UTAMA --}}
<div class="profile-tab-content {{ request('tab') === 'alamat' ? 'active' : '' }}" id="tab-alamat">
    <form class="form-page" method="post" action="{{ route('buyer.profile.update') }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="redirect_tab" value="alamat">
        <input type="hidden" name="nama_lengkap" value="{{ $user->nama_lengkap }}">
        <input type="hidden" name="username" value="{{ $user->username }}">
        <input type="hidden" name="email" value="{{ $user->email }}">
        <input type="hidden" name="no_hp" value="{{ $user->no_hp }}">

        <div class="form-card">
            <h2>Alamat Pengiriman Utama</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Simpan alamat utama dan zona pengiriman langganan Anda agar otomatis terisi saat berbelanja (*checkout*).
            </p>

            <div class="field-grid">
                <label class="full">Zona Pengiriman Default
                    <select name="zona_pengiriman" style="width: 100%; font-weight: 600;">
                        <option value="">— Pilih Zona Pengiriman Default —</option>
                        @foreach($zonaPengiriman as $z)
                            <option value="{{ $z->nama_zona }}" @selected(old('zona_pengiriman', $user->zona_pengiriman) === $z->nama_zona)>
                                {{ $z->nama_zona }} — Ongkir Rp{{ number_format($z->biaya, 0, ',', '.') }} ({{ $z->keterangan }})
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="full">Alamat Lengkap / Patokan Pengantaran
                    <textarea name="alamat_utama" rows="4" placeholder="Tuliskan Dusun, RT/RW, nomor rumah, nama jalan, atau patokan tempat pengantaran Anda">{{ old('alamat_utama', $user->alamat_utama) }}</textarea>
                </label>
            </div>

            <button class="button" style="margin-top:20px">
                <i class="bi bi-geo-alt-fill"></i> Simpan Alamat Utama
            </button>
        </div>

        <aside class="form-card">
            <h2><i class="bi bi-info-circle"></i> Kemudahan Checkout</h2>
            <div style="font-size:12px;color:#555e56;line-height:1.6;display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-lightning-charge-fill" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Setelah disimpan, alamat dan zona pengiriman ini akan otomatis terisi setiap kali Anda checkout produk.</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <i class="bi bi-truck" style="color:var(--gold);font-size:18px;margin-top:2px;"></i>
                    <span>Anda tetap dapat mengubah alamat pengiriman sewaktu-waktu jika ingin mengirim ke lokasi lain.</span>
                </div>
            </div>
        </aside>
    </form>
</div>

{{-- TAB 3: KEAMANAN & KATA SANDI --}}
<div class="profile-tab-content {{ request('tab') === 'keamanan' ? 'active' : '' }}" id="tab-keamanan">
    <div class="form-page">
        <form class="form-card" method="post" action="{{ route('buyer.profile.password') }}">
            @csrf
            @method('PATCH')

            <h2>Ganti Kata Sandi</h2>
            <p style="font-size:12px;color:#64748b;margin-top:0;margin-bottom:18px;">
                Gunakan kombinasi kata sandi yang aman untuk melindungi akun dan riwayat belanja Anda.
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
                    <span>Hubungi admin platform jika Anda menemukan aktivitas mencurigakan pada akun Anda.</span>
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

    // Avatar Live Preview & Filename updater
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarInitials = document.getElementById('avatarInitials');
    const avatarFileName = document.getElementById('avatarFileName');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                if (avatarFileName) {
                    avatarFileName.textContent = file.name;
                    avatarFileName.style.fontStyle = 'normal';
                    avatarFileName.style.fontWeight = '600';
                    avatarFileName.style.color = '#123825';
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.style.display = 'block';
                    if (avatarInitials) avatarInitials.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                if (avatarFileName) {
                    avatarFileName.textContent = 'Belum ada foto baru dipilih';
                    avatarFileName.style.fontStyle = 'italic';
                    avatarFileName.style.fontWeight = 'normal';
                    avatarFileName.style.color = '#64748b';
                }
            }
        });
    }
});
</script>
@endpush

@extends('layouts.dashboard') @section('title','Pengguna') @section('eyebrow','Administrator') @section('page_title','Pengguna')
@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Akses sistem</p>
        <h1>Kelola akun pengguna terdaftar.</h1>
        <p>Lihat detail profil, aktivitas, dan kelola status akses pengguna platform.</p>
    </div>
</section>

<div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
    <a href="{{ route('admin.users.index', array_filter(['q' => request('q')])) }}" class="button button-small {{ !request('role') ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Semua ({{ $totalUsers }})
    </a>
    <a href="{{ route('admin.users.index', array_filter(['role' => 'pembeli', 'q' => request('q')])) }}" class="button button-small {{ request('role') === 'pembeli' ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Pembeli ({{ $totalPembeli }})
    </a>
    <a href="{{ route('admin.users.index', array_filter(['role' => 'penjual', 'q' => request('q')])) }}" class="button button-small {{ request('role') === 'penjual' ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Penjual ({{ $totalPenjual }})
    </a>
    <a href="{{ route('admin.users.index', array_filter(['role' => 'admin', 'q' => request('q')])) }}" class="button button-small {{ request('role') === 'admin' ? 'button-dark' : 'button-outline' }}" style="padding: 6px 14px; text-decoration: none; border-radius: 8px;">
        Admin ({{ $totalAdmin }})
    </a>
</div>

<section class="data-panel">
    <div class="panel-heading">
        <form class="filter-bar">
            @if(request('role'))
                <input type="hidden" name="role" value="{{ request('role') }}">
            @endif
            <label>Cari
                <input name="q" value="{{ request('q') }}" placeholder="Nama / Username / Email / HP...">
            </label>
            <button class="btn-primary">Terapkan</button>
        </form>
    </div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>UMKM</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $userPhoneDigits = preg_replace('/[^0-9]/', '', $user->no_hp ?? '');
                        if (str_starts_with($userPhoneDigits, '0')) {
                            $userWaPhone = '62' . substr($userPhoneDigits, 1);
                        } else {
                            $userWaPhone = $userPhoneDigits;
                        }
                    @endphp
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                @if($user->foto_profil)
                                    <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="{{ $user->nama_lengkap }}" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0;">
                                @else
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #164e31; color: #fde047; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0;">
                                        {{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <b style="color: #0f172a; font-size: 13px;">{{ $user->nama_lengkap }}</b>
                                    <br><small style="color: #64748b; font-size: 11.5px;">{{ $user->email ?? 'Tanpa Email' }}</small>
                                </div>
                            </div>
                        </td>
                        <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11.5px; color: #334155;">{{ $user->username }}</code></td>
                        <td>
                            <span style="font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; {{ $user->role==='admin' ? 'background:#fef3c7;color:#92400e;' : ($user->role==='penjual' ? 'background:#dbeafe;color:#1e40af;' : 'background:#f3f4f6;color:#374151;') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>{{ $user->umkm?->nama_umkm ?? '-' }}</td>
                        <td><x-status-badge :status="$user->status"/></td>
                        <td>
                            <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <button type="button" class="btn-secondary" data-detail-open="user-detail-{{ $user->id }}" style="padding: 4px 10px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;" title="Lihat Detail Lengkap">
                                    <i class="bi bi-eye-fill"></i> Detail
                                </button>

                                @if($user->id!==auth()->id())
                                    <form method="post" action="{{ route('admin.users.status',$user) }}" style="margin: 0;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $user->status==='aktif'?'nonaktif':'aktif' }}">
                                        <button class="{{ $user->status==='aktif'?'btn-danger':'btn-primary' }}" style="padding: 4px 10px; font-size: 11.5px;">
                                            {{ $user->status==='aktif'?'Nonaktifkan':'Aktifkan' }}
                                        </button>
                                    </form>
                                @else
                                    <small style="color:#6b7280; font-weight:700; font-size: 11px;">Akun Anda</small>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- User Detail Dialog Modal --}}
                    <dialog id="user-detail-{{ $user->id }}" class="user-detail-dialog" style="border-radius: 18px; border: 1px solid #e2e8f0; max-width: 580px; width: 92%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 0; overflow: hidden;">
                        <div style="background: linear-gradient(135deg, #164e31, #236c46); color: white; padding: 22px 24px; position: relative;">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    @if($user->foto_profil)
                                        <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="{{ $user->nama_lengkap }}" style="width: 58px; height: 58px; border-radius: 50%; object-fit: cover; border: 2.5px solid rgba(255,255,255,0.8); flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                                    @else
                                        <div style="width: 58px; height: 58px; border-radius: 50%; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); color: #fde047; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 22px; flex-shrink: 0;">
                                            {{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px; flex-wrap: wrap;">
                                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #ffffff;">{{ $user->nama_lengkap }}</h3>
                                            <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 4px; letter-spacing: 0.05em; text-transform: uppercase; {{ $user->role==='admin' ? 'background:#fef3c7;color:#92400e;' : ($user->role==='penjual' ? 'background:#dbeafe;color:#1e40af;' : 'background:#f1f5f9;color:#334155;') }}">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </div>
                                        <p style="margin: 0; font-size: 12px; color: #d1fae5;">
                                            <code>@ {{ $user->username }}</code> · ID: #USR-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                                        </p>
                                    </div>
                                </div>
                                <button type="button" data-detail-close="user-detail-{{ $user->id }}" style="font-size: 26px; color: #a7f3d0; line-height: 1; border: none; background: none; cursor: pointer; padding: 0 4px;">&times;</button>
                            </div>
                        </div>

                        <div style="padding: 22px 24px; background: #fafafa; max-height: 72vh; overflow-y: auto;">
                            {{-- Status & Registration Banner --}}
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <div>
                                    <small style="color: #64748b; font-size: 11px; display: block;">Status Akun:</small>
                                    <x-status-badge :status="$user->status"/>
                                </div>
                                <div style="text-align: right;">
                                    <small style="color: #64748b; font-size: 11px; display: block;">Terdaftar Sejak:</small>
                                    <strong style="color: #0f172a; font-size: 12.5px;">{{ optional($user->created_at)->translatedFormat('d M Y, H:i') ?: '-' }}</strong>
                                </div>
                            </div>

                            {{-- Personal & Contact Information --}}
                            <div style="margin-bottom: 18px;">
                                <h4 style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin: 0 0 10px; display: flex; align-items: center; gap: 6px;">
                                    <i class="bi bi-person-lines-fill" style="color: #164e31;"></i> Informasi Kontak & Pribadi
                                </h4>
                                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: 12.5px; display: grid; gap: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px;">
                                        <span style="color: #64748b;">Email:</span>
                                        <span style="color: #0f172a; font-weight: 600;">
                                            @if($user->email)
                                                <a href="mailto:{{ $user->email }}" style="color: #0f766e; text-decoration: none;">{{ $user->email }}</a>
                                            @else
                                                <span style="color: #94a3b8;">-</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px;">
                                        <span style="color: #64748b;">No. Handphone / WA:</span>
                                        <span style="color: #0f172a; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                            {{ $user->no_hp ?: '-' }}
                                            @if($userWaPhone)
                                                <a href="https://wa.me/{{ $userWaPhone }}" target="_blank" rel="noopener noreferrer" style="color: #16a34a; font-size: 14px; text-decoration: none;" title="Chat WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                            @endif
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px;">
                                        <span style="color: #64748b;">Jenis Kelamin:</span>
                                        <strong style="color: #0f172a;">{{ $user->jenis_kelamin ? ucfirst($user->jenis_kelamin) : '-' }}</strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px;">
                                        <span style="color: #64748b;">Tanggal Lahir / Usia:</span>
                                        <span style="color: #0f172a; font-weight: 600;">
                                            @if($user->tanggal_lahir)
                                                {{ $user->tanggal_lahir->format('d M Y') }} ({{ $user->tanggal_lahir->age }} tahun)
                                            @else
                                                <span style="color: #94a3b8;">-</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px;">
                                        <span style="color: #64748b;">Zona Pengiriman:</span>
                                        <strong style="color: #0f172a;">{{ $user->zona_pengiriman ?: '-' }}</strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <span style="color: #64748b; flex-shrink: 0;">Alamat Utama:</span>
                                        <span style="color: #0f172a; font-weight: 500; text-align: right; max-width: 65%;">{{ $user->alamat_utama ?: '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Role Specific Details --}}
                            @if($user->role === 'penjual' && $user->umkm)
                                <div style="margin-bottom: 10px;">
                                    <h4 style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #1e40af; margin: 0 0 10px; display: flex; align-items: center; gap: 6px;">
                                        <i class="bi bi-shop"></i> Data Usaha UMKM Terdaftar
                                    </h4>
                                    <div style="background: #ffffff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 14px 16px; font-size: 12.5px; display: grid; gap: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eff6ff; padding-bottom: 8px;">
                                            <span style="color: #64748b;">Nama UMKM:</span>
                                            <strong style="color: #1e40af; font-size: 13.5px;">{{ $user->umkm->nama_umkm }}</strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eff6ff; padding-bottom: 8px;">
                                            <span style="color: #64748b;">Pemilik:</span>
                                            <strong style="color: #0f172a;">{{ $user->umkm->pemilik ?: '-' }}</strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eff6ff; padding-bottom: 8px;">
                                            <span style="color: #64748b;">Kategori Usaha:</span>
                                            <span style="background: #eff6ff; color: #1e40af; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 11.5px;">{{ $user->umkm->kategori_usaha ?: '-' }}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eff6ff; padding-bottom: 8px;">
                                            <span style="color: #64748b;">Status Verifikasi:</span>
                                            <span style="font-weight: 700; color: {{ $user->umkm->status_verifikasi==='verified'?'#15803d':($user->umkm->status_verifikasi==='rejected'?'#dc2626':'#d97706') }};">
                                                {{ ucfirst($user->umkm->status_verifikasi ?? 'Belum terverifikasi') }}
                                            </span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eff6ff; padding-bottom: 8px;">
                                            <span style="color: #64748b;">Tahun Berdiri / Karyawan:</span>
                                            <strong style="color: #0f172a;">{{ $user->umkm->tahun_berdiri ?: '-' }} · {{ $user->umkm->jumlah_karyawan ? $user->umkm->jumlah_karyawan . ' Orang' : '-' }}</strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eff6ff; padding-bottom: 8px;">
                                            <span style="color: #64748b;">Instagram:</span>
                                            <strong style="color: #0f172a;">{{ $user->umkm->instagram ?: '-' }}</strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <span style="color: #64748b; flex-shrink: 0;">Alamat UMKM:</span>
                                            <span style="color: #0f172a; text-align: right; max-width: 65%;">{{ $user->umkm->alamat ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @elseif($user->role === 'pembeli')
                                <div style="margin-bottom: 10px;">
                                    <h4 style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #166534; margin: 0 0 10px; display: flex; align-items: center; gap: 6px;">
                                        <i class="bi bi-bag-check"></i> Aktivitas Belanja Pembeli
                                    </h4>
                                    <div style="background: #ffffff; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 14px 16px; font-size: 12.5px; display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #64748b;">Total Pesanan Dibuat:</span>
                                        <strong style="color: #166534; font-size: 14px;">{{ $user->pesanan_count ?? 0 }} Transaksi</strong>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div style="background: #f1f5f9; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; gap: 10px; border-top: 1px solid #e2e8f0; flex-wrap: wrap;">
                            <div>
                                @if($userWaPhone)
                                    <a href="https://wa.me/{{ $userWaPhone }}" target="_blank" rel="noopener noreferrer" class="button button-small" style="background: #25D366; color: #ffffff; text-decoration: none; border: none; font-size: 12px; font-weight: 700; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="bi bi-whatsapp"></i> Chat WhatsApp
                                    </a>
                                @endif
                            </div>
                            <div style="display: flex; gap: 8px;">
                                @if($user->id!==auth()->id())
                                    <form method="post" action="{{ route('admin.users.status',$user) }}" style="margin: 0;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $user->status==='aktif'?'nonaktif':'aktif' }}">
                                        <button class="{{ $user->status==='aktif'?'btn-danger':'btn-primary' }}" style="padding: 6px 14px; font-size: 12px;">
                                            {{ $user->status==='aktif'?'Nonaktifkan Akun':'Aktifkan Akun' }}
                                        </button>
                                    </form>
                                @endif
                                <button type="button" class="btn-secondary" data-detail-close="user-detail-{{ $user->id }}" style="font-size: 12px; padding: 6px 16px;">Tutup</button>
                            </div>
                        </div>
                    </dialog>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #64748b;">
                            Tidak ada data pengguna yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $users->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-detail-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-detail-open');
            const dialog = document.getElementById(targetId);
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        });
    });

    document.querySelectorAll('[data-detail-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-detail-close');
            const dialog = document.getElementById(targetId);
            if (dialog && typeof dialog.close === 'function') {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('.user-detail-dialog').forEach(dialog => {
        dialog.addEventListener('click', (e) => {
            const rect = dialog.getBoundingClientRect();
            if (
                e.clientX < rect.left ||
                e.clientX > rect.right ||
                e.clientY < rect.top ||
                e.clientY > rect.bottom
            ) {
                dialog.close();
            }
        });
    });
});
</script>
@endpush

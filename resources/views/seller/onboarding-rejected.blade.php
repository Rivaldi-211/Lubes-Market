@extends('layouts.auth')

@section('title', 'Pendaftaran Ditolak')

@section('content')
<div class="auth-box" style="max-width: 560px; margin: 0 auto; text-align: center; padding: 40px 24px;">
    <div style="width: 72px; height: 72px; background: #fef2f2; border: 2px solid #fca5a5; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
        <i class="bi bi-x-circle"></i>
    </div>

    <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Pendaftaran Mitra Belum Disetujui</h2>
    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">
        Mohon maaf, permohonan verifikasi usaha <strong>{{ auth()->user()->umkm->nama_umkm ?? 'Anda' }}</strong> belum dapat disetujui oleh admin platform saat ini.
    </p>

    @if(auth()->user()->umkm?->catatan_verifikasi)
    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 18px; text-align: left; margin-bottom: 24px; font-size: 13px; color: #991b1b;">
        <strong style="display: block; margin-bottom: 4px;"><i class="bi bi-exclamation-triangle-fill"></i> Alasan Penolakan Admin:</strong>
        <p style="margin: 0; line-height: 1.5;">{{ auth()->user()->umkm->catatan_verifikasi }}</p>
    </div>
    @endif

    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="button button-outline" style="width: 100%; justify-content: center;">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </button>
    </form>
</div>
@endsection

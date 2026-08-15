@extends('layouts.auth')

@section('title', 'Menunggu Verifikasi Admin')

@section('content')
<div class="auth-box" style="max-width: 560px; margin: 0 auto; text-align: center; padding: 40px 24px;">
    <div style="width: 72px; height: 72px; background: #fffbeb; border: 2px solid #fde68a; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
        <i class="bi bi-clock-history"></i>
    </div>

    <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Permohonan Verifikasi Sedang Diproses</h2>
    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 24px;">
        Terima kasih! Formulir onboarding toko <strong>{{ auth()->user()->umkm->nama_umkm ?? 'Usaha Anda' }}</strong> telah diterima. Admin LUDES-MARKET sedang meninjau dokumen KTP & produk Anda.
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; text-align: left; margin-bottom: 24px; font-size: 13px; color: #334155;">
        <strong style="display: block; color: #0f172a; margin-bottom: 6px;"><i class="bi bi-info-circle-fill" style="color: #2563eb;"></i> Informasi Penting:</strong>
        <ul style="margin: 0; padding-left: 20px; line-height: 1.6;">
            <li>Proses verifikasi membutuhkan waktu maksimal 1x24 jam kerja.</li>
            <li>Anda dapat kembali ke halaman ini secara berkala untuk mengecek status persetujuan.</li>
            <li>Setelah disetujui, Anda langsung dapat login dan menambahkan produk ke katalog.</li>
        </ul>
    </div>

    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="button button-outline" style="width: 100%; justify-content: center;">
            <i class="bi bi-box-arrow-right"></i> Keluar / Login Nanti
        </button>
    </form>
</div>
@endsection

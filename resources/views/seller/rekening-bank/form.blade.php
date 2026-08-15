@extends('layouts.dashboard')

@section('title', ($isEdit ? 'Edit Rekening Bank' : 'Tambah Rekening Bank') . ' — UMKM')
@section('eyebrow', 'Mitra UMKM')
@section('page_title', $isEdit ? 'Edit Rekening Bank' : 'Tambah Rekening Bank')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Form Rekening Bank</p>
        <h1>{{ $isEdit ? 'Perbarui Detail Rekening' : 'Tambah Rekening Pembayaran Baru' }}</h1>
        <p>Pastikan nama bank, nomor rekening, dan nama pemilik sesuai dengan buku tabungan Anda.</p>
    </div>
    <a class="btn-secondary" href="{{ route('seller.rekening-bank.index') }}">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
    </a>
</section>

<section class="data-panel" style="max-width: 680px;">
    <form method="post" action="{{ $isEdit ? route('seller.rekening-bank.update', $account) : route('seller.rekening-bank.store') }}" style="padding: 24px;">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <label>
                Nama Bank <span style="color:red">*</span>
                <input type="text" name="nama_bank" class="form-control" value="{{ old('nama_bank', $account->nama_bank) }}" required placeholder="Contoh: Bank BRI, Bank BCA, Bank Mandiri">
            </label>

            <label>
                Nomor Rekening <span style="color:red">*</span>
                <input type="text" name="nomor_rekening" class="form-control" value="{{ old('nomor_rekening', $account->nomor_rekening) }}" required placeholder="Contoh: 0234-01-001892-53-4">
            </label>

            <label>
                Atas Nama / Pemilik Rekening <span style="color:red">*</span>
                <input type="text" name="atas_nama" class="form-control" value="{{ old('atas_nama', $account->atas_nama) }}" required placeholder="Nama lengkap sesuai rekening bank">
            </label>

            <div style="display: flex; gap: 16px;">
                <label style="flex: 1;">
                    Urutan Tampil <span>(opsional)</span>
                    <input type="number" name="urutan" class="form-control" value="{{ old('urutan', $account->urutan ?? 1) }}" min="0">
                </label>

                <label style="flex: 1; display: flex; align-items: center; gap: 8px; margin-top: 24px; cursor: pointer;">
                    <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $account->exists ? $account->aktif : true)) style="width: 18px; height: 18px;">
                    <strong style="font-size: 0.9rem;">Aktifkan Rekening Ini</strong>
                </label>
            </div>

            <div style="margin-top: 12px; display: flex; gap: 10px;">
                <button type="submit" class="button">
                    <i class="bi bi-check-lg"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Rekening' }}
                </button>
                <a href="{{ route('seller.rekening-bank.index') }}" class="btn-secondary">Batal</a>
            </div>
        </div>
    </form>
</section>
@endsection

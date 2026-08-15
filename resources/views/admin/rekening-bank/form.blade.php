@extends('layouts.dashboard')
@section('title', $account->exists ? 'Edit Rekening Bank' : 'Tambah Rekening Bank')
@section('eyebrow', 'Administrator')
@section('page_title', $account->exists ? 'Edit Rekening Bank' : 'Tambah Rekening Bank')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Master Pembayaran</p>
        <h1>{{ $account->exists ? 'Perbarui data rekening bank.' : 'Tambahkan rekening bank platform baru.' }}</h1>
        <p>Rekening ini akan ditampilkan sebagai opsi tujuan transfer bagi pembeli saat checkout.</p>
    </div>
</section>

<form class="form-page" method="post" action="{{ $account->exists ? route('admin.rekening-bank.update', $account) : route('admin.rekening-bank.store') }}">
    @csrf
    @if($account->exists)
        @method('PATCH')
    @endif

    <div class="form-card" style="max-width: 650px;">
        <h2>Formulir Rekening Bank</h2>
        <div class="field-grid">
            <label class="full">
                Nama Bank
                <input name="nama_bank" placeholder="Contoh: Bank BRI, Bank BNI, Bank Mandiri, Bank BCA" value="{{ old('nama_bank', $account->nama_bank) }}" required>
            </label>

            <label class="full">
                Nomor Rekening
                <input name="nomor_rekening" placeholder="Contoh: 1234-01-000123-53-0" value="{{ old('nomor_rekening', $account->nomor_rekening) }}" required>
            </label>

            <label class="full">
                Atas Nama Pemilik Rekening
                <input name="atas_nama" placeholder="Contoh: LUDES-MARKET Admin" value="{{ old('atas_nama', $account->atas_nama) }}" required>
            </label>

            <label>
                Urutan Tampil
                <input type="number" min="0" name="urutan" value="{{ old('urutan', $account->urutan ?? 0) }}">
            </label>

            <label style="display: flex; align-items: center; gap: 10px; margin-top: 14px;">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $account->exists ? $account->aktif : true)) style="width: 18px; height: 18px;">
                <span style="font-weight: 700; color: #1e293b;">Aktifkan rekening ini (Tampilkan di checkout)</span>
            </label>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button class="button">{{ $account->exists ? 'Simpan Perubahan' : 'Tambahkan Rekening' }}</button>
            <a href="{{ route('admin.rekening-bank.index') }}" class="btn-secondary" style="padding: 10px 18px; text-decoration: none;">Batal</a>
        </div>
    </div>
</form>
@endsection

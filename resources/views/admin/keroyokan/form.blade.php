@extends('layouts.dashboard')
@section('title', $group->exists ? 'Edit Kelompok Keroyokan' : 'Tambah Kelompok Keroyokan')
@section('eyebrow', 'Administrator')
@section('page_title', $group->exists ? 'Edit Kelompok Keroyokan' : 'Tambah Kelompok Keroyokan')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Program Desa</p>
        <h1>{{ $group->exists ? 'Perbarui Kelompok Keroyokan' : 'Buat Kelompok Keroyokan Baru' }}</h1>
        <p>Pastikan kategori sesuai dengan jenis produk anggota yang akan digabungkan.</p>
    </div>
</section>

<form class="form-page" method="post" action="{{ $group->exists ? route('admin.keroyokan.update', $group) : route('admin.keroyokan.store') }}">
    @csrf
    @if($group->exists)
        @method('PUT')
    @endif

    <div class="form-card">
        <h2>Data Kelompok</h2>
        <div class="field-grid">
            <label class="full">Nama Kelompok
                <input name="nama_kelompok" value="{{ old('nama_kelompok', $group->nama_kelompok) }}" placeholder="Contoh: Snack Box Standar" required>
            </label>

            <label>Kategori
                <select name="kategori_id" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('kategori_id', $group->kategori_id) == $cat->id)>{{ $cat->nama_kategori }}</option>
                    @endforeach
                </select>
            </label>

            <label>Status Aktif
                <select name="aktif" required>
                    <option value="1" @selected(old('aktif', $group->aktif ? '1' : '0') === '1')>Aktif</option>
                    <option value="0" @selected(old('aktif', $group->aktif ? '1' : '0') === '0')>Nonaktif</option>
                </select>
            </label>

            <label class="full">Deskripsi (Opsional)
                <textarea name="deskripsi" rows="5" placeholder="Jelaskan spesifikasi standar kelompok produk ini...">{{ old('deskripsi', $group->deskripsi) }}</textarea>
            </label>
        </div>

        <button class="button" style="margin-top:20px">{{ $group->exists ? 'Simpan Perubahan' : 'Buat Kelompok' }}</button>
    </div>
</form>
@endsection

@extends('layouts.dashboard')

@section('title', 'Kelola Zona Pengiriman & Ongkir')
@section('eyebrow', 'Administrator')
@section('page_title', 'Zona Pengiriman')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Tarif Pengiriman</p>
        <h1>Kelola Zona Pengiriman & Ongkos Kirim.</h1>
        <p>Atur besaran biaya ongkos kirim berdasarkan zona wilayah yang dipilih oleh pembeli saat checkout.</p>
    </div>
</section>

<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small>MASTER DATA</small>
            <h2>Daftar Tarif Zona Pengiriman</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Urutan</th>
                    <th>Nama Zona</th>
                    <th>Cakupan Wilayah / Keterangan</th>
                    <th>Tarif Ongkir (Rp)</th>
                    <th>Status Aktif</th>
                    <th>Aksi Simpan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($zonas as $z)
                    <tr>
                        <td><strong>#{{ $z->urutan }}</strong></td>
                        <td>
                            <strong style="font-size: 1.05rem; color: #0f172a;">{{ $z->nama_zona }}</strong>
                        </td>
                        <form method="post" action="{{ route('admin.zona-pengiriman.update', $z) }}">
                            @csrf
                            @method('PATCH')
                            <td>
                                <input type="text" name="keterangan" value="{{ old('keterangan', $z->keterangan) }}" style="width: 100%; min-width: 200px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </td>
                            <td>
                                <input type="number" min="0" step="500" name="biaya" value="{{ old('biaya', (int)$z->biaya) }}" style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; color: #059669;">
                            </td>
                            <td>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="checkbox" name="aktif" value="1" @checked($z->aktif) style="width: 16px; height: 16px;">
                                    <span style="font-size: 12px; font-weight: 600; color: {{ $z->aktif ? '#15803d' : '#64748b' }};">
                                        {{ $z->aktif ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </label>
                            </td>
                            <td>
                                <button type="submit" class="button button-small" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;">
                                    <i class="bi bi-check2"></i> Simpan Tarif
                                </button>
                            </td>
                        </form>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

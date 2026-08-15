@extends('layouts.dashboard')
@section('title','Kelompok Keroyokan')
@section('eyebrow','Administrator')
@section('page_title','Kelompok Keroyokan')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Program Desa</p>
        <h1>Kelola Kelompok Keroyokan</h1>
        <p>Tentukan kelompok produk setara agar UMKM lokal dapat memenuhi pesanan besar bersama-sama.</p>
    </div>
    <a class="button" href="{{ route('admin.keroyokan.create') }}"><i class="bi bi-plus-lg"></i> Tambah Kelompok</a>
</section>

<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small>KELOMPOK AKTIF & NONAKTIF</small>
            <h2>Daftar Kelompok</h2>
        </div>
    </div>

    @if($groups->count())
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Kelompok</th>
                        <th>Kategori</th>
                        <th>Jumlah Anggota</th>
                        <th>Total Stok Anggota</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                        @php
                            $totalStok = $group->produk->where('stok_status', '!=', 'Habis')->sum('stok_jumlah');
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $group->nama_kelompok }}</strong>
                                @if($group->deskripsi)
                                    <small style="display:block;color:var(--muted)">{{ Str::limit($group->deskripsi, 60) }}</small>
                                @endif
                            </td>
                            <td>{{ $group->kategori->nama_kategori }}</td>
                            <td>{{ $group->produk_count }} produk</td>
                            <td><strong>{{ number_format($totalStok) }}</strong> unit</td>
                            <td>
                                @if($group->aktif)
                                    <span class="status-badge status-selesai">Aktif</span>
                                @else
                                    <span class="status-badge status-dibatalkan">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn-secondary" href="{{ route('admin.keroyokan.edit', $group) }}"><i class="bi bi-pencil"></i> Edit</a>
                                    <form method="post" action="{{ route('admin.keroyokan.destroy', $group) }}" onsubmit="return confirm('Hapus kelompok keroyokan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            {{ $groups->links() }}
        </div>
    @else
        <x-empty-state title="Belum ada kelompok Keroyokan" text="Buat kelompok produk setara untuk mengaktifkan pemesanan Keroyokan."/>
    @endif
</section>
@endsection

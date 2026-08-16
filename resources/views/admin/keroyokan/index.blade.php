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

{{-- MONITORING BATCH KEROYOKAN & FULFILLMENT --}}
<section class="data-panel" style="margin-top: 28px;">
    <div class="panel-heading">
        <div>
            <small>PENGEMASAN &amp; FULFILLMENT TERPADU</small>
            <h2>Monitoring Batch Pesanan Keroyokan</h2>
        </div>
    </div>

    @if(isset($recentBatches) && $recentBatches->count())
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Kelompok / Paket</th>
                        <th>Pembeli &amp; Kontak</th>
                        <th>Target &amp; Total</th>
                        <th>UMKM Berkontribusi</th>
                        <th>Status Keseluruhan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBatches as $batch)
                        @php
                            $overallStatus = $batch->calculateOverallStatus();
                            $umkms = $batch->pesanan->pluck('produk.umkm.nama_umkm')->filter()->unique();
                        @endphp
                        <tr>
                            <td>
                                <strong>#KR-{{ str_pad($batch->id, 5, '0', STR_PAD_LEFT) }}</strong>
                                <small style="display: block; color: var(--muted);">{{ $batch->created_at->format('d M Y, H:i') }}</small>
                            </td>
                            <td>
                                <strong>{{ $batch->kelompokKeroyokan?->nama_kelompok ?? 'Paket Keroyokan' }}</strong>
                                <span style="display: block; font-size: 11px; color: #166534;"><i class="bi bi-box-seam"></i> 1 Box Kemasan LUDES-MARKET</span>
                            </td>
                            <td>
                                <strong>{{ $batch->pembeli?->nama_lengkap ?? 'Pembeli' }}</strong>
                                <small style="display: block; color: var(--muted);">{{ $batch->pesanan->first()?->no_hp_pembeli ?? '-' }}</small>
                            </td>
                            <td>
                                <strong>{{ $batch->target_jumlah }} unit</strong>
                                <small style="display: block; color: #059669; font-weight: 700;">Rp{{ number_format($batch->total_harga, 0, ',', '.') }}</small>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: #334155;">
                                    @foreach($batch->pesanan as $p)
                                        <div>• {{ $p->produk?->umkm?->nama_umkm }}: <b>{{ $p->jumlah }}×</b> {{ $p->produk?->nama_produk }}</div>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <x-status-badge :status="$overallStatus"/>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="padding: 24px; text-align: center; color: #64748b; font-size: 13px;">
            <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 6px; color: #94a3b8;"></i>
            Belum ada transaksi batch pesanan Keroyokan yang masuk.
        </div>
    @endif
</section>
@endsection

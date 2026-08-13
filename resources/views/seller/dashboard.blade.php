@extends('layouts.dashboard')
@section('title','Dashboard Penjual')
@section('eyebrow','Mitra UMKM')
@section('page_title','Ringkasan Usaha')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>{{ $umkm->nama_umkm }}</p>
        <h1>Data usaha yang penting, tanpa memenuhi layar.</h1>
        <p>Pantau stok, pesanan masuk, dan omzet transaksi selesai.</p>
    </div>
    <a class="button" href="{{ route('seller.products.create') }}"><i class="bi bi-plus-lg"></i> Tambah produk</a>
</section>

<div class="metric-grid">
    <article><small>Produk aktif</small><strong>{{ $stats['products'] }}</strong><span>Katalog usaha</span></article>
    <article><small>Total pesanan</small><strong>{{ $stats['orders'] }}</strong><span>Semua status</span></article>
    <article><small>Menunggu</small><strong>{{ $stats['waiting'] }}</strong><span>Perlu diproses</span></article>
    <article><small>Omzet selesai</small><strong>Rp{{ number_format($stats['revenue'],0,',','.') }}</strong><span>Transaksi selesai</span></article>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <section class="data-panel">
        <div class="panel-heading">
            <div>
                <small>STATISTIK</small>
                <h2>🏆 Top 5 Menu Terlaris Toko</h2>
            </div>
            <a class="outline-link" href="{{ route('seller.reports.index') }}">Laporan Sales</a>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Peringkat & Produk</th>
                        <th style="text-align: center;">Terjual</th>
                        <th style="text-align: right;">Total Omzet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $rank => $prod)
                        <tr>
                            <td>
                                <strong>#{{ $rank + 1 }} {{ $prod->nama_produk }}</strong>
                                <small style="display: block; color: #64748b;">Rp{{ number_format($prod->harga, 0, ',', '.') }} / unit</small>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge" style="background: #f5f1e7; color: #173d2b; font-weight: 700; border: 1px solid #d9c9ac;">
                                    {{ number_format($prod->total_terjual ?? 0, 0, ',', '.') }} porsi
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <strong style="color: #173d2b;">Rp{{ number_format($prod->total_omzet ?? 0, 0, ',', '.') }}</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-empty-state title="Belum ada data statistik" text="Statistik menu terlaris akan tampil setelah ada transaksi diproses."/>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="data-panel">
        <div class="panel-heading">
            <div>
                <small>PESANAN TERBARU</small>
                <h2>Aktivitas penjualan</h2>
            </div>
            <a class="outline-link" href="{{ route('seller.orders.index') }}">Lihat semua</a>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pesanan</th>
                        <th>Produk</th>
                        <th>Pembeli</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $order)
                        <tr>
                            <td>#{{ $order->id }}<br><small>{{ optional($order->tanggal_pesan)->format('d/m/Y') }}</small></td>
                            <td>{{ $order->produk->nama_produk }}</td>
                            <td>{{ $order->pembeli->nama_lengkap }}</td>
                            <td>Rp{{ number_format((float)$order->total_harga,0,',','.') }}</td>
                            <td><x-status-badge :status="$order->status"/></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><x-empty-state title="Belum ada pesanan" text="Pesanan baru akan tampil di sini."/></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

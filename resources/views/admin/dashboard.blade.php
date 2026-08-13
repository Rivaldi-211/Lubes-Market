@extends('layouts.dashboard')
@section('title','Dashboard Admin')
@section('eyebrow','Administrator')
@section('page_title','Ringkasan BUMDes')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Operasional BUMDes</p>
        <h1>Satu tampilan untuk melihat gerak usaha desa.</h1>
        <p>Fokus pada UMKM aktif, produk, pengguna, pesanan, dan nilai transaksi selesai.</p>
    </div>
    <a class="button" href="{{ route('admin.umkm.create') }}"><i class="bi bi-plus-lg"></i> Tambah UMKM</a>
</section>

<div class="metric-grid">
    <article><small>Mitra UMKM</small><strong>{{ $stats['umkm'] }}</strong><span>Terdaftar</span></article>
    <article><small>Produk</small><strong>{{ $stats['products'] }}</strong><span>Katalog</span></article>
    <article><small>Pengguna</small><strong>{{ $stats['users'] }}</strong><span>Semua role</span></article>
    <article><small>Pesanan</small><strong>{{ $stats['orders'] }}</strong><span>Rp{{ number_format($stats['revenue'],0,',','.') }} selesai</span></article>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <section class="data-panel">
        <div class="panel-heading">
            <div>
                <small>STATISTIK BUMDES</small>
                <h2>🏆 Top 10 Menu Terlaris Desa</h2>
            </div>
            <a class="outline-link" href="{{ route('admin.reports.index') }}">Laporan BUMDes</a>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Peringkat & Produk</th>
                        <th>Mitra UMKM</th>
                        <th style="text-align: center;">Terjual</th>
                        <th style="text-align: right;">Omzet Produksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $rank => $prod)
                        <tr>
                            <td>
                                <strong>
                                    @if($rank === 0) 🥇 @elseif($rank === 1) 🥈 @elseif($rank === 2) 🥉 @else #{{ $rank + 1 }} @endif
                                    {{ $prod->nama_produk }}
                                </strong>
                                <small style="display: block; color: #64748b;">Rp{{ number_format($prod->harga, 0, ',', '.') }}</small>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #173d2b;">{{ $prod->umkm->nama_umkm ?? '-' }}</span>
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
                            <td colspan="4">
                                <x-empty-state title="Belum ada data statistik" text="Statistik menu terlaris desa akan tampil setelah ada transaksi diproses."/>
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
                <small>TRANSAKSI TERBARU</small>
                <h2>Pesanan lintas UMKM</h2>
            </div>
            <a class="outline-link" href="{{ route('admin.orders.index') }}">Kelola pesanan</a>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>UMKM</th>
                        <th>Produk</th>
                        <th>Pembeli</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->produk->umkm->nama_umkm }}</td>
                            <td>{{ $order->produk->nama_produk }}</td>
                            <td>{{ $order->pembeli->nama_lengkap }}</td>
                            <td>Rp{{ number_format((float)$order->total_harga,0,',','.') }}</td>
                            <td><x-status-badge :status="$order->status"/></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

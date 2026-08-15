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

@if(isset($rekomendasiBelumDibaca) && $rekomendasiBelumDibaca > 0)
<div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; box-shadow: 0 2px 6px rgba(5,150,105,0.08);">
    <div style="display:flex; align-items:center; gap:12px; min-width:0; flex:1;">
        <div style="width:38px; height:38px; background:#10b981; color:#fff; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;">
            <i class="bi bi-lightbulb-fill"></i>
        </div>
        <div style="min-width:0;">
            <strong style="color:#065f46; font-size:0.98rem; display:block;">
                Ada {{ $rekomendasiBelumDibaca }} rekomendasi strategi baru dari platform!
            </strong>
            <span style="color:#047857; font-size:0.85rem;">Dapatkan saran konkret pengembangan produk dan penawaran toko Anda.</span>
        </div>
    </div>
    <a href="{{ route('seller.analytics') }}" class="button" style="padding:8px 16px; font-size:12px; border-radius:8px; white-space:nowrap; background:#059669; border-color:#059669; flex-shrink:0;">
        Lihat Sekarang →
    </a>
</div>
@endif

<div class="metric-grid">
    <article><small>Produk aktif</small><strong>{{ $stats['products'] }}</strong><span>Katalog usaha</span></article>
    <article><small>Total pesanan</small><strong>{{ $stats['orders'] }}</strong><span>Semua status</span></article>
    <article style="border-left: 3px solid #10b981;">
        <small style="color:#059669; font-weight:700;">Sudah Dibayar</small>
        <strong style="color:#065f46;">{{ $stats['paid_count'] }} <span style="font-size:13px; font-weight:600;">pesanan</span></strong>
        <span>Rp{{ number_format($stats['paid_revenue'],0,',','.') }} terverifikasi</span>
    </article>
    <article style="border-left: 3px solid #f59e0b;">
        <small style="color:#d97706; font-weight:700;">Belum Dibayar / COD</small>
        <strong style="color:#b45309;">{{ $stats['unpaid_count'] }} <span style="font-size:13px; font-weight:600;">pesanan</span></strong>
        <span>Menunggu pembayaran / COD</span>
    </article>
</div>

<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
    <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-wallet2" style="color: #059669;"></i> Ringkasan Keuangan Toko (Setelah Komisi Platform 10%)
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px;">
            <small style="color: #166534; font-weight: 700; display: block; margin-bottom: 4px;">💰 Pendapatan Bersih</small>
            <strong style="font-size: 1.25rem; color: #14532d; display: block;">Rp{{ number_format($stats['pendapatan_bersih'], 0, ',', '.') }}</strong>
            <span style="font-size: 11px; color: #166534;">Dari pesanan selesai (setelah potong 10%)</span>
        </div>
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px;">
            <small style="color: #b45309; font-weight: 700; display: block; margin-bottom: 4px;">📋 Menunggu Pencairan Admin</small>
            <strong style="font-size: 1.25rem; color: #78350f; display: block;">Rp{{ number_format($stats['saldo_pending'], 0, ',', '.') }}</strong>
            <span style="font-size: 11px; color: #92400e;">Akan ditransfer oleh Admin Platform</span>
        </div>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px;">
            <small style="color: #1e40af; font-weight: 700; display: block; margin-bottom: 4px;">✅ Sudah Dicairkan</small>
            <strong style="font-size: 1.25rem; color: #1e3a8a; display: block;">Rp{{ number_format($stats['saldo_dicairkan'], 0, ',', '.') }}</strong>
            <span style="font-size: 11px; color: #1d4ed8;">Telah ditransfer ke rekening usaha</span>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; align-items: start; margin-bottom: 24px;">
    @php
        $totalTerjualSum = $topProducts->sum(fn($p) => (int)($p->total_terjual ?? 0));
        $totalOmzetSum = $topProducts->sum(fn($p) => (float)($p->total_omzet ?? 0));
        $themeColors = ['#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16'];
    @endphp

    <section class="data-panel">
        <div class="panel-heading">
            <div>
                <small>STATISTIK</small>
                <h2>Top 5 Menu Terlaris Toko</h2>
            </div>
            <div class="stats-chart-header-actions">
                @if($topProducts->isNotEmpty())
                <div class="stats-toggle-group" id="sellerMetricToggle">
                    <button type="button" class="stats-toggle-btn active" data-metric="terjual">Porsi Terjual</button>
                    <button type="button" class="stats-toggle-btn" data-metric="omzet">Omzet (Rp)</button>
                </div>
                @endif
                <a class="outline-link" href="{{ route('seller.reports.index') }}">Laporan Sales</a>
            </div>
        </div>

        @if($topProducts->isEmpty())
            <x-empty-state title="Belum ada data statistik" text="Statistik menu terlaris akan tampil setelah ada transaksi diproses."/>
        @else
            <div class="stats-chart-wrapper">
                <div class="stats-chart-grid">
                    <div class="stats-chart-left-col">
                        <div class="stats-chart-canvas-box">
                            <canvas id="sellerTopProductsChart"></canvas>
                            <div class="stats-chart-center-text" id="sellerChartCenterText">
                                <small id="sellerCenterLabel">Total Terjual</small>
                                <strong id="sellerCenterValue">{{ number_format($totalTerjualSum, 0, ',', '.') }} <span style="font-size:11px; font-weight:600;">porsi</span></strong>
                            </div>
                        </div>
                        <div class="stats-chart-summary-box">
                            <small>Total Omzet Sales</small>
                            <strong>Rp{{ number_format($totalOmzetSum, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="stats-legend-list" id="sellerLegendList">
                        @foreach($topProducts as $rank => $prod)
                            @php
                                $color = $themeColors[$rank % count($themeColors)];
                                $terjual = (int)($prod->total_terjual ?? 0);
                                $omzet = (float)($prod->total_omzet ?? 0);
                                $pctTerjual = $totalTerjualSum > 0 ? round(($terjual / $totalTerjualSum) * 100, 1) : 0;
                                $pctOmzet = $totalOmzetSum > 0 ? round(($omzet / $totalOmzetSum) * 100, 1) : 0;
                            @endphp
                            <div class="stats-legend-item" data-index="{{ $rank }}" data-name="{{ $prod->nama_produk }}">
                                <div class="stats-legend-left">
                                    <span class="stats-legend-dot" style="background-color: {{ $color }};"></span>
                                    <div class="stats-legend-info">
                                        <span class="stats-legend-name" title="{{ $prod->nama_produk }}">#{{ $rank + 1 }} {{ $prod->nama_produk }}</span>
                                        <span class="stats-legend-sub">Rp{{ number_format($prod->harga, 0, ',', '.') }} / unit</span>
                                    </div>
                                </div>
                                <div class="stats-legend-right">
                                    <span class="stats-legend-qty" data-terjual="{{ number_format($terjual, 0, ',', '.') }} porsi ({{ $pctTerjual }}%)" data-omzet="{{ $pctOmzet }}%">
                                        {{ number_format($terjual, 0, ',', '.') }} porsi ({{ $pctTerjual }}%)
                                    </span>
                                    <span class="stats-legend-omzet">
                                        Rp{{ number_format($omzet, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if($totalTerjualSum == 0)
                    <div style="font-size: 11.5px; color: #7c847e; background: #fbfaf7; border: 1px dashed #d9d5cb; padding: 8px 14px; border-radius: 8px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-info-circle" style="color: #173d2b; font-size: 14px;"></i>
                        <span>Diagram pie siap aktif otomatis saat pesanan dengan status <strong>Diproses</strong> atau <strong>Selesai</strong> bertambah.</span>
                    </div>
                @endif
            </div>
        @endif
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
                        <th>Pembayaran</th>
                        <th>Status Pesanan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $order)
                        @php $payInfo = $order->payment_status_info; @endphp
                        <tr>
                            <td>#{{ $order->id }}<br><small>{{ optional($order->tanggal_pesan)->format('d/m/Y') }}</small></td>
                            <td>{{ $order->produk->nama_produk }}</td>
                            <td>{{ $order->pembeli->nama_lengkap }}</td>
                            <td><strong>Rp{{ number_format((float)$order->total_harga,0,',','.') }}</strong></td>
                            <td>
                                <span class="payment-badge {{ $payInfo['class'] }}">
                                    <i class="bi {{ $payInfo['icon'] }}"></i> {{ $payInfo['label'] }}
                                </span>
                            </td>
                            <td><x-status-badge :status="$order->status"/></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><x-empty-state title="Belum ada pesanan" text="Pesanan baru akan tampil di sini."/></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('sellerTopProductsChart');
    if (!canvas) return;

    const rawLabels = @json($topProducts->pluck('nama_produk'));
    const rawTerjual = @json($topProducts->map(fn($p) => (int)($p->total_terjual ?? 0)));
    const rawOmzet = @json($topProducts->map(fn($p) => (float)($p->total_omzet ?? 0)));
    const themeColors = ['#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16'];

    const totalTerjual = {{ $totalTerjualSum }};
    const totalOmzet = {{ $totalOmzetSum }};

    let currentMetric = 'terjual'; // 'terjual' | 'omzet'

    const formatRp = (num) => 'Rp' + new Intl.NumberFormat('id-ID').format(num);
    const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num);

    const isAllZero = (arr) => arr.length === 0 || arr.every(v => v === 0);

    function getChartConfig(metric) {
        const dataValues = metric === 'terjual' ? rawTerjual : rawOmzet;
        const total = metric === 'terjual' ? totalTerjual : totalOmzet;
        const empty = total === 0 || isAllZero(dataValues);

        if (empty) {
            return {
                labels: ['Belum ada data'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#e7e3d8'],
                    borderColor: ['#dfdbd0'],
                    borderWidth: 2,
                    hoverOffset: 0
                }]
            };
        }

        return {
            labels: rawLabels,
            datasets: [{
                data: dataValues,
                backgroundColor: themeColors.slice(0, dataValues.length),
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 6
            }]
        };
    }

    const chartInstance = new Chart(canvas, {
        type: 'doughnut',
        data: getChartConfig('terjual'),
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#173d2b',
                    titleColor: '#f5f1e7',
                    bodyColor: '#ffffff',
                    borderColor: '#d9c9ac',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function (context) {
                            if (currentMetric === 'terjual') {
                                if (totalTerjual === 0) return ' Belum ada transaksi penjualan';
                                const val = rawTerjual[context.dataIndex] || 0;
                                const pct = totalTerjual > 0 ? ((val / totalTerjual) * 100).toFixed(1) : 0;
                                return ` Terjual: ${formatNumber(val)} porsi (${pct}%)`;
                            } else {
                                if (totalOmzet === 0) return ' Belum ada transaksi omzet';
                                const val = rawOmzet[context.dataIndex] || 0;
                                const pct = totalOmzet > 0 ? ((val / totalOmzet) * 100).toFixed(1) : 0;
                                return ` Omzet: ${formatRp(val)} (${pct}%)`;
                            }
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 800
            }
        }
    });

    // Toggle button handler
    const toggleBtns = document.querySelectorAll('#sellerMetricToggle .stats-toggle-btn');
    const centerLabel = document.getElementById('sellerCenterLabel');
    const centerValue = document.getElementById('sellerCenterValue');
    const legendItems = document.querySelectorAll('#sellerLegendList .stats-legend-item');

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.classList.contains('active')) return;
            toggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            currentMetric = btn.dataset.metric;
            chartInstance.data = getChartConfig(currentMetric);
            chartInstance.update();

            if (currentMetric === 'terjual') {
                centerLabel.textContent = 'Total Terjual';
                centerValue.innerHTML = `${formatNumber(totalTerjual)} <span style="font-size:11px; font-weight:600;">porsi</span>`;
                legendItems.forEach(item => {
                    const badge = item.querySelector('.stats-legend-qty');
                    if (badge) badge.textContent = badge.dataset.terjual;
                });
            } else {
                centerLabel.textContent = 'Total Omzet';
                centerValue.innerHTML = `${formatRp(totalOmzet)}`;
                legendItems.forEach(item => {
                    const badge = item.querySelector('.stats-legend-qty');
                    if (badge) badge.textContent = badge.dataset.omzet;
                });
            }
        });
    });

    // Hover on legend items highlights chart slices
    legendItems.forEach(item => {
        const idx = parseInt(item.dataset.index, 10);
        item.addEventListener('mouseenter', () => {
            const dataValues = currentMetric === 'terjual' ? rawTerjual : rawOmzet;
            if (chartInstance && !isAllZero(dataValues)) {
                chartInstance.setActiveElements([{ datasetIndex: 0, index: idx }]);
                chartInstance.update();
            }
        });
        item.addEventListener('mouseleave', () => {
            if (chartInstance) {
                chartInstance.setActiveElements([]);
                chartInstance.update();
            }
        });
    });
});
</script>
@endpush

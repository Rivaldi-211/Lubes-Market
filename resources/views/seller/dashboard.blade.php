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
    @php
        $totalTerjualSum = $topProducts->sum(fn($p) => (int)($p->total_terjual ?? 0));
        $totalOmzetSum = $topProducts->sum(fn($p) => (float)($p->total_omzet ?? 0));
        $themeColors = ['#173d2b', '#2d6a4f', '#d97706', '#52b788', '#c28b38', '#3d5a80', '#e76f51', '#74c69d'];
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
                    <div class="stats-chart-canvas-box">
                        <canvas id="sellerTopProductsChart"></canvas>
                        <div class="stats-chart-center-text" id="sellerChartCenterText">
                            <small id="sellerCenterLabel">Total Terjual</small>
                            <strong id="sellerCenterValue">{{ number_format($totalTerjualSum, 0, ',', '.') }} <span style="font-size:11px; font-weight:600;">porsi</span></strong>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('sellerTopProductsChart');
    if (!canvas) return;

    const rawLabels = @json($topProducts->pluck('nama_produk'));
    const rawTerjual = @json($topProducts->map(fn($p) => (int)($p->total_terjual ?? 0)));
    const rawOmzet = @json($topProducts->map(fn($p) => (float)($p->total_omzet ?? 0)));
    const themeColors = ['#173d2b', '#2d6a4f', '#d97706', '#52b788', '#c28b38', '#3d5a80', '#e76f51', '#74c69d'];

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

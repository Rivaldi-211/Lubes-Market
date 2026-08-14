@extends('layouts.dashboard')
@section('title', 'Analitik Usaha — ' . $umkm->nama_umkm)
@section('eyebrow', 'Mitra UMKM')
@section('page_title', 'Analitik & Akselerasi Usaha')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>{{ $umkm->nama_umkm }}</p>
        <h1>Evaluasi kinerja & arah pengembangan toko.</h1>
        <p>Pantau grafik omzet 6 bulan, evaluasi ulasan pembeli, dan terapkan rekomendasi BUMDes.</p>
    </div>
    <a class="button" href="{{ route('umkm.show', $umkm) }}" target="_blank"><i class="bi bi-shop"></i> Lihat Toko Publik</a>
</section>

<!-- 4 Metric Cards -->
<div class="metric-grid" style="margin-bottom: 24px;">
    <article style="border-left: 4px solid #10b981;">
        <small style="color:#059669; font-weight:700;">Omzet Bulan Ini</small>
        <strong>Rp{{ number_format($omzetBulanIni, 0, ',', '.') }}</strong>
        @if($pertumbuhanPct !== null)
            <span style="font-weight:700; color: {{ $pertumbuhanPct >= 0 ? '#059669' : '#dc2626' }};">
                <i class="bi bi-arrow-{{ $pertumbuhanPct >= 0 ? 'up' : 'down' }}-short"></i> {{ abs($pertumbuhanPct) }}% vs bulan lalu
            </span>
        @else
            <span>Data perdana</span>
        @endif
    </article>

    <article style="border-left: 4px solid #3b82f6;">
        <small style="color:#2563eb; font-weight:700;">Transaksi Selesai</small>
        <strong>{{ $trendOmzet->last()?->jumlah_transaksi ?? 0 }} <span style="font-size:13px; font-weight:600;">pesanan</span></strong>
        <span>Bulan ini ({{ number_format($trendOmzet->last()?->total_item ?? 0) }} item)</span>
    </article>

    <article style="border-left: 4px solid #f59e0b;">
        <small style="color:#d97706; font-weight:700;">Rating Pelanggan</small>
        <strong>{{ number_format($trendUlasan->last()?->avg_rating ?? 0, 1) }} <span style="font-size:14px; color:#f59e0b;"><i class="bi bi-star-fill"></i></span></strong>
        <span>Rata-rata rating ulasan</span>
    </article>

    <article style="border-left: 4px solid #8b5cf6;">
        <small style="color:#7c3aed; font-weight:700;">Feedback Masuk</small>
        <strong>{{ $trendUlasan->last()?->jumlah_ulasan ?? 0 }} <span style="font-size:13px; font-weight:600;">ulasan</span></strong>
        <span>Ulasan bulan ini</span>
    </article>
</div>

<!-- Charts Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Grafik 1: Bar Chart Omzet 6 Bulan -->
    <section class="data-panel" style="padding: 24px;">
        <div class="panel-heading" style="margin-bottom: 20px;">
            <div>
                <small>TRENS SALES</small>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 2px 0 0 0;">Omzet 6 Bulan Terakhir</h2>
            </div>
            @if($pertumbuhanPct !== null)
                <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; background: {{ $pertumbuhanPct >= 0 ? '#d1fae5' : '#fee2e2' }}; color: {{ $pertumbuhanPct >= 0 ? '#047857' : '#b91c1c' }};">
                    {{ $pertumbuhanPct >= 0 ? '+' : '' }}{{ $pertumbuhanPct }}% Growth
                </span>
            @endif
        </div>
        <div style="height: 240px; position: relative;">
            <canvas id="sellerOmzetBarChart"></canvas>
        </div>
    </section>

    <!-- Grafik 2: Line Chart Rating 3 Bulan -->
    <section class="data-panel" style="padding: 24px;">
        <div class="panel-heading" style="margin-bottom: 20px;">
            <div>
                <small>SINYAL PASAR</small>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 2px 0 0 0;">Tren Kepuasan Pelanggan (3 Bulan)</h2>
            </div>
            <span style="font-size: 12px; color: #d97706; font-weight: 700;">
                <i class="bi bi-star-fill"></i> Rating 0 – 5
            </span>
        </div>
        <div style="height: 240px; position: relative;">
            <canvas id="sellerRatingLineChart"></canvas>
        </div>
    </section>
</div>

<!-- Product Evaluation Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Produk Terbaik -->
    <section class="data-panel" style="padding: 24px;">
        <div class="panel-heading" style="margin-bottom: 16px;">
            <div>
                <small style="color: #059669; font-weight: 700;">PERFORMANSA TINGGI</small>
                <h3 style="font-size: 1.05rem; font-weight: 700; margin: 2px 0 0 0;">🌟 Produk Terlaris & Favorit</h3>
            </div>
        </div>
        <div class="data-table-wrap">
            <table class="data-table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Rating</th>
                        <th>Ulasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($produkTerbaik as $p)
                        <tr>
                            <td><strong>{{ $p->nama_produk }}</strong><br><small style="color:#6b7280;">Rp{{ number_format((float)$p->harga, 0, ',', '.') }}</small></td>
                            <td><strong style="color: #d97706;"><i class="bi bi-star-fill"></i> {{ number_format((float)$p->avg_rating, 1) }}</strong></td>
                            <td>{{ $p->ulasan_count }} ulasan</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center; color:#9ca3af; padding: 16px;">Belum ada ulasan produk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Produk Perlu Perhatian -->
    <section class="data-panel" style="padding: 24px;">
        <div class="panel-heading" style="margin-bottom: 16px;">
            <div>
                <small style="color: #dc2626; font-weight: 700;">EVALUASI KUALITAS</small>
                <h3 style="font-size: 1.05rem; font-weight: 700; margin: 2px 0 0 0;">⚠️ Perlu Perhatian (Rating < 3.5)</h3>
            </div>
        </div>
        <div class="data-table-wrap">
            <table class="data-table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Rating</th>
                        <th>Saran Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($produkPerhatian as $p)
                        <tr>
                            <td><strong>{{ $p->nama_produk }}</strong></td>
                            <td><strong style="color: #dc2626;"><i class="bi bi-star-fill"></i> {{ number_format((float)$p->avg_rating, 1) }}</strong></td>
                            <td><small style="color:#991b1b; font-weight:600;">Perbaiki kemasan / rasa</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center; color:#059669; padding: 16px; font-weight:600;">
                                <i class="bi bi-check-circle-fill"></i> Semua produk memiliki rating baik!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Recommendations Section -->
<section class="data-panel" style="padding: 28px; margin-bottom: 32px;">
    <div class="panel-heading" style="margin-bottom: 20px;">
        <div>
            <small style="color: #059669; font-weight: 700;">AKSELERASI BUMDES</small>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 2px 0 0 0;">💡 Rekomendasi Strategi dari Admin BUMDes</h2>
        </div>
    </div>

    @if($rekomendasi->isEmpty())
        <div style="background: #f9fafb; border: 1px dashed #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; color: #6b7280;">
            <i class="bi bi-lightbulb" style="font-size: 24px; color: #9ca3af; display: block; margin-bottom: 8px;"></i>
            Belum ada rekomendasi strategi tertulis dari pengelola BUMDes Berkah untuk toko Anda.
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            @foreach($rekomendasi as $r)
                @php
                    $badgeColors = [
                        'promosi' => ['bg' => '#d1fae5', 'color' => '#047857', 'icon' => 'bi-megaphone-fill'],
                        'produk' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'bi-box-seam-fill'],
                        'harga' => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-tag-fill'],
                        'distribusi' => ['bg' => '#f3e8ff', 'color' => '#6b21a8', 'icon' => 'bi-truck'],
                    ];
                    $b = $badgeColors[$r->tipe] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-info-circle-fill'];
                @endphp
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <span style="background: {{ $b['bg'] }}; color: {{ $b['color'] }}; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; text-transform: uppercase; display: flex; align-items: center; gap: 4px;">
                            <i class="bi {{ $b['icon'] }}"></i> {{ strtoupper($r->tipe) }}
                        </span>
                        <small style="color: #6b7280; font-weight: 600;">Periode {{ $r->periode }}</small>
                    </div>
                    <h4 style="font-size: 1rem; font-weight: 700; margin: 0 0 8px 0; color: #111827;">{{ $r->judul }}</h4>
                    <p style="color: #4b5563; font-size: 0.88rem; line-height: 1.6; margin: 0;">{{ $r->isi }}</p>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Bar Chart Omzet 6 Bulan
    const ctxBar = document.getElementById('sellerOmzetBarChart');
    if (ctxBar) {
        const omzetData = @json($trendOmzet);
        const labels = omzetData.map(d => d.bulan);
        const dataValues = omzetData.map(d => d.omzet);
        const lastIdx = dataValues.length - 1;

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Omzet (Rp)',
                    data: dataValues,
                    backgroundColor: dataValues.map((_, i) => i === lastIdx ? '#10b981' : '#a7f3d0'),
                    borderColor: '#059669',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' Omzet: Rp' + new Intl.NumberFormat('id-ID').format(ctx.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (v) => 'Rp' + (v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : new Intl.NumberFormat('id-ID').format(v))
                        }
                    }
                }
            }
        });
    }

    // 2. Line Chart Rating 3 Bulan
    const ctxLine = document.getElementById('sellerRatingLineChart');
    if (ctxLine) {
        const ratingData = @json($trendUlasan);
        const labelsLine = ratingData.map(d => d.bulan);
        const dataLine = ratingData.map(d => d.avg_rating);

        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: labelsLine,
                datasets: [{
                    label: 'Rata-rata Rating',
                    data: dataLine,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#f59e0b',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` Rating: ${ctx.raw} / 5`
                        }
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 5,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>
@endpush

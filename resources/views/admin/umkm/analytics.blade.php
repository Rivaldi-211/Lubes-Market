@extends('layouts.dashboard')
@section('title', 'Analitik Akselerasi UMKM — Platform')
@section('eyebrow', 'Pengelolaan Desa')
@section('page_title', 'Analitik Akselerasi UMKM')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Monitoring Kinerja Desa</p>
        <h1>Evaluasi & akselerasi pertumbuhan 15 UMKM Desa.</h1>
        <p>Pantau perbandingan omzet bulanan, tingkat pertumbuhan (growth %), dan kirim rekomendasi strategi langsung ke penjual.</p>
    </div>
</section>

<!-- Overview Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Horizontal Bar Chart Ranking Omzet -->
    <section class="data-panel" style="padding: 24px; grid-column: 1 / -1;">
        <div class="panel-heading" style="margin-bottom: 20px;">
            <div>
                <small>PERBANDINGAN REVENUE</small>
                <h2 style="font-size: 1.2rem; font-weight: 700; margin: 2px 0 0 0;">Ranking Omzet Bulan Ini ({{ $bulanIni }})</h2>
            </div>
        </div>
        <div style="height: 380px; position: relative;">
            <canvas id="adminUmkmGrowthChart"></canvas>
        </div>
    </section>
</div>

<!-- Growth Comparison Table -->
<section class="data-panel" style="padding: 24px; margin-bottom: 32px;">
    <div class="panel-heading" style="margin-bottom: 20px;">
        <div>
            <small>MATRIKS رشد KINERJA</small>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 2px 0 0 0;">Tabel Evaluasi Growth & Rating UMKM</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ranking / UMKM</th>
                    <th>Kategori Usaha</th>
                    <th>Omzet Bulan Ini ({{ $bulanIni }})</th>
                    <th>Omzet Bulan Lalu ({{ $bulanLalu }})</th>
                    <th>Growth (%)</th>
                    <th>Rating & Ulasan</th>
                    <th>Aksi Strategi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($umkms as $rank => $u)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; font-size: 11px; font-weight: 800; border-radius: 6px; flex-shrink: 0; {{ $rank === 0 ? 'background: #fef3c7; color: #b45309; border: 1px solid #fde68a;' : ($rank === 1 ? 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' : ($rank === 2 ? 'background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa;' : 'background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;')) }}">
                                    #{{ $rank + 1 }}
                                </span>
                                <div>
                                    <strong style="font-size: 1rem; color: #0f172a; display: block;">{{ $u->nama_umkm }}</strong>
                                    <small style="color: #6b7280;">Pemilik: {{ $u->pemilik }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 11px; font-weight: 600; color: #059669; background: #ecfdf5; padding: 3px 8px; border-radius: 999px;">
                                {{ $u->kategori_usaha ?? 'Usaha Desa' }}
                            </span>
                        </td>
                        <td><strong style="color: #111827; font-size: 0.95rem;">Rp{{ number_format((float)$u->omzet_ini, 0, ',', '.') }}</strong></td>
                        <td><span style="color: #6b7280;">Rp{{ number_format((float)$u->omzet_lalu, 0, ',', '.') }}</span></td>
                        <td>
                            @if($u->growth !== null)
                                <span style="font-weight: 800; font-size: 12px; padding: 4px 10px; border-radius: 999px; background: {{ $u->growth >= 0 ? '#d1fae5' : '#fee2e2' }}; color: {{ $u->growth >= 0 ? '#047857' : '#b91c1c' }};">
                                    {{ $u->growth >= 0 ? '▲ +' : '▼ ' }}{{ $u->growth }}%
                                </span>
                            @else
                                <span style="color: #9ca3af; font-size: 12px; font-style: italic;">— (Belum ada pembanding)</span>
                            @endif
                        </td>
                        <td>
                            @if($u->avg_rating > 0)
                                <strong style="color: #d97706;"><i class="bi bi-star-fill"></i> {{ number_format((float)$u->avg_rating, 1) }}</strong>
                                <small style="color: #6b7280;">({{ $u->total_ulasan }})</small>
                            @else
                                <small style="color: #9ca3af;">Belum ada ulasan</small>
                            @endif
                        </td>
                        <td>
                            <a class="button button-secondary" href="{{ route('admin.umkm.rekomendasi.create', $u) }}" style="padding: 6px 12px; font-size: 12px; border-radius: 8px;">
                                <i class="bi bi-lightbulb"></i> Kirim Rekomendasi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-empty-state title="Belum Ada UMKM" text="Belum ada data UMKM untuk dievaluasi." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('adminUmkmGrowthChart');
    if (!canvas) return;

    const umkmData = @json($umkms);
    const labels = umkmData.map(u => u.nama_umkm);
    const omzetData = umkmData.map(u => u.omzet_ini);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Omzet Bulan Ini (Rp)',
                data: omzetData,
                backgroundColor: '#10b981',
                borderColor: '#059669',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
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
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: (v) => 'Rp' + (v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : new Intl.NumberFormat('id-ID').format(v))
                    }
                }
            }
        }
    });
});
</script>
@endpush

@extends('layouts.dashboard')

@section('title', 'Pencairan Dana UMKM (Disbursement)')
@section('eyebrow', 'Administrator')
@section('page_title', 'Disbursement UMKM')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Keuangan Platform</p>
        <h1>Pencairan Saldo & Komisi Platform (10%).</h1>
        <p>Dana pembayaran pembeli masuk terpusat ke Admin. Cairkan pendapatan bersih ke penjual setelah pesanan selesai.</p>
    </div>
</section>

<section class="data-panel" style="margin-bottom: 32px;">
    <div class="panel-heading">
        <div>
            <small style="color: #059669; font-weight: 700;">SALDO UNPAID PENJUAL</small>
            <h2 style="font-size: 1.2rem; font-weight: 700; margin-top: 2px;">Daftar Saldo Penjual Siap Dicairkan</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mitra UMKM</th>
                    <th>Pemilik</th>
                    <th>Pesanan Selesai Belum Dicairkan</th>
                    <th>Pendapatan Bersih Seller</th>
                    <th>Komisi Platform (10%)</th>
                    <th>Aksi Pencairan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($umkmList as $u)
                    <tr>
                        <td><strong>{{ $u->nama_umkm }}</strong></td>
                        <td>{{ $u->pemilik }}</td>
                        <td>
                            <span style="font-weight: 700; color: #1e293b;">{{ $u->total_pesanan_pending }} pesanan</span>
                        </td>
                        <td>
                            <strong style="color: #059669; font-size: 1.05rem;">Rp{{ number_format($u->saldo_pending, 0, ',', '.') }}</strong>
                        </td>
                        <td>
                            <span style="color: #64748b; font-weight: 600;">Rp{{ number_format($u->komisi_admin_pending, 0, ',', '.') }}</span>
                        </td>
                        <td>
                            @if($u->saldo_pending > 0)
                                <form method="post" action="{{ route('admin.disbursement.store', $u) }}" onsubmit="return confirm('Tandai pencairan dana sebesar Rp{{ number_format($u->saldo_pending, 0, ',', '.') }} ke {{ $u->nama_umkm }} sudah ditransfer?')">
                                    @csrf
                                    <button class="button" style="padding: 6px 14px; font-size: 12px; border-radius: 8px;">
                                        <i class="bi bi-cash-stack"></i> Tandai Sudah Dicairkan
                                    </button>
                                </form>
                            @else
                                <small style="color: #9ca3af; font-style: italic;">Tidak ada saldo pending</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af; padding: 16px;">Belum ada data UMKM.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- History Table -->
<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small style="color: #64748b;">RIWAYAT PENCAIRAN</small>
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-top: 2px;">Riwayat Transfer Pencairan Dana</h2>
        </div>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Disbursement</th>
                    <th>UMKM</th>
                    <th>Jumlah Dicairkan</th>
                    <th>Tanggal Transfer</th>
                    <th>Diproses Oleh Admin</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $r)
                    <tr>
                        <td><code>#DISB-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }}</code></td>
                        <td><strong>{{ $r->umkm->nama_umkm ?? '-' }}</strong></td>
                        <td><strong style="color: #059669;">Rp{{ number_format((float)$r->jumlah, 0, ',', '.') }}</strong></td>
                        <td>{{ optional($r->dibayar_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $r->admin->nama_lengkap ?? '-' }}</td>
                        <td><small style="color: #64748b;">{{ $r->catatan ?: '-' }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af; padding: 16px;">Belum ada riwayat pencairan dana.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $riwayat->links() }}</div>
</section>
@endsection

@extends('layouts.dashboard')

@section('title', 'Pesanan Masuk')
@section('eyebrow', 'Mitra UMKM')
@section('page_title', 'Pesanan')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Operasional</p>
        <h1>Pesanan Masuk & Verifikasi Pembayaran</h1>
        <p>Lihat bukti pembayaran transfer/QRIS dari pembeli, verifikasi, dan perbarui status pesanan secara mandiri.</p>
    </div>
</section>

<section class="data-panel">
    <div class="panel-heading">
        <form class="filter-bar" method="get">
            <label>Status Pesanan
                <select name="status">
                    <option value="">Semua</option>
                    @foreach(['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn-primary">Terapkan</button>
        </form>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pesanan</th>
                    <th>Pembeli</th>
                    <th>Produk</th>
                    <th>Pembayaran</th>
                    <th>Total</th>
                    <th>Status & Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <b>#{{ $order->id }}</b><br>
                            <small>{{ optional($order->tanggal_pesan)->format('d/m/Y H:i') }}</small>
                            @if($order->batch_keroyokan_id)
                                <br><small style="color:var(--green-800);font-weight:700;">🤝 Keroyokan #KR-{{ str_pad($order->batch_keroyokan_id,5,'0',STR_PAD_LEFT) }}</small>
                            @endif
                        </td>
                        <td>
                            <b>{{ $order->pembeli->nama_lengkap }}</b><br>
                            <small><i class="bi bi-telephone"></i> {{ $order->no_hp_pembeli }}</small>
                        </td>
                        <td>
                            <b>{{ $order->produk->nama_produk }}</b><br>
                            <small>{{ $order->jumlah }} unit</small>
                        </td>
                        <td>
                            @php $isPaid = $order->isPaid(); @endphp
                            <span class="payment-badge {{ $isPaid ? 'payment-paid' : 'payment-unpaid' }}">
                                <i class="bi {{ $isPaid ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' }}"></i>
                                {{ $isPaid ? 'Sudah Dibayar' : 'Belum Dibayar' }}
                            </span>
                            <br>
                            <small style="color:var(--muted); font-weight:600;">Metode: {{ $order->metode_pembayaran }}</small>

                            @if($order->rekening_bank_snapshot)
                                <br><small style="color:#475569; font-size:11px;">Rek: {{ $order->rekening_bank_snapshot }}</small>
                            @endif

                            @if($order->bukti_pembayaran)
                                <br>
                                <button type="button" class="btn-secondary" style="font-size: 11px; padding: 2px 8px; margin-top: 4px; border-color: #3b82f6; color: #1d4ed8; background: #eff6ff;" onclick="document.getElementById('proof-modal-{{ $order->id }}').showModal()">
                                    <i class="bi bi-eye"></i> Lihat Bukti
                                </button>

                                <!-- Modal Dialog Bukti Pembayaran -->
                                <dialog id="proof-modal-{{ $order->id }}" class="review-dialog" style="max-width: 520px; border-radius: 16px; padding: 20px; border: 1px solid #cbd5e1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                                        <div>
                                            <small style="color: #64748b; font-weight: 700;">VERIFIKASI BUKTI PEMBAYARAN</small>
                                            <h3 style="margin: 0; font-size: 1.1rem; color: #0f172a;">Pesanan #{{ $order->id }} — {{ $order->pembeli->nama_lengkap }}</h3>
                                        </div>
                                        <button type="button" onclick="document.getElementById('proof-modal-{{ $order->id }}').close()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
                                    </div>

                                    <div style="text-align: center; margin-bottom: 16px; background: #f8fafc; border-radius: 12px; padding: 12px; border: 1px dashed #cbd5e1;">
                                        <img src="{{ asset('storage/'.$order->bukti_pembayaran) }}" alt="Bukti Pembayaran #{{ $order->id }}" style="max-width: 100%; max-height: 380px; border-radius: 8px; object-fit: contain;">
                                    </div>

                                    <div style="background: #f1f5f9; padding: 12px; border-radius: 10px; margin-bottom: 16px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">
                                        <div><strong>Total Pembayaran:</strong> Rp{{ number_format((float)$order->total_harga, 0, ',', '.') }}</div>
                                        <div><strong>Metode:</strong> {{ $order->metode_pembayaran }}</div>
                                        @if($order->rekening_bank_snapshot)
                                            <div><strong>Tujuan Rekening:</strong> {{ $order->rekening_bank_snapshot }}</div>
                                        @endif
                                        <div><strong>Status Pembayaran Saat Ini:</strong> <span style="font-weight:700; color: {{ $isPaid ? '#15803d' : '#b91c1c' }}">{{ $isPaid ? 'Sudah Dibayar' : 'Belum Dibayar' }}</span></div>
                                    </div>

                                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                        <button type="button" class="btn-secondary" onclick="document.getElementById('proof-modal-{{ $order->id }}').close()">Tutup</button>
                                        <form method="post" action="{{ route('seller.orders.payment.update', $order) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status_pembayaran" value="{{ $isPaid ? 'Belum Dibayar' : 'Sudah Dibayar' }}">
                                            <button type="submit" class="button" style="background: {{ $isPaid ? '#dc2626' : '#16a34a' }}; border: none; color: #fff;">
                                                <i class="bi {{ $isPaid ? 'bi-x-circle' : 'bi-check-circle' }}"></i>
                                                {{ $isPaid ? 'Tandai Belum Dibayar' : 'Konfirmasi Sudah Dibayar' }}
                                            </button>
                                        </form>
                                    </div>
                                </dialog>
                            @endif
                        </td>
                        <td>Rp{{ number_format((float)$order->total_harga, 0, ',', '.') }}</td>
                        <td>
                            <x-status-badge :status="$order->status" />

                            <form class="action-cluster" method="post" action="{{ route('seller.orders.update', $order) }}" style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                                @csrf
                                @method('PATCH')
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <select name="status" class="form-control" style="flex: 1; font-size: 0.8rem; padding: 4px 6px;">
                                        <option value="" disabled>-- Status Pesanan --</option>
                                        @foreach(['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'] as $s)
                                            <option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <select name="status_pembayaran" class="form-control" style="flex: 1; font-size: 0.8rem; padding: 4px 6px;">
                                        <option value="Belum Dibayar" @selected($order->status_pembayaran === 'Belum Dibayar')>Belum Dibayar</option>
                                        <option value="Sudah Dibayar" @selected($order->status_pembayaran === 'Sudah Dibayar')>Sudah Dibayar</option>
                                    </select>
                                    <button class="btn-primary" style="padding: 4px 10px; font-size: 0.8rem;">Simpan</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state title="Belum ada pesanan" text="Pesanan dari produk UMKM Anda akan muncul di sini." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $orders->links() }}
    </div>
</section>
@endsection

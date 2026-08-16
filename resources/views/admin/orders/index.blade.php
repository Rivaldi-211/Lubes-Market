@extends('layouts.dashboard')
@section('title', 'Pesanan')
@section('eyebrow', 'Administrator')
@section('page_title', 'Pesanan')

@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Transaksi</p>
        <h1>Lihat transaksi lintas mitra dengan konteks lengkap.</h1>
    </div>
</section>

<section class="data-panel">
    <div class="panel-heading">
        <form class="filter-bar">
            <label>Status
                <select name="status">
                    <option value="">Semua</option>
                    @foreach(['Menunggu','Diproses','Selesai','Dibatalkan'] as $s)
                        <option @selected(request('status')===$s)>{{ $s }}</option>
                    @endforeach
                </select>
            </label>
            <label>Metode
                <select name="metode">
                    <option value="">Semua</option>
                    @foreach(['COD','Transfer','QRIS','Moncongloe'] as $m)
                        <option @selected(request('metode')===$m)>{{ $m }}</option>
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
                    <th>No.</th>
                    <th>UMKM / Produk</th>
                    <th>Pembeli</th>
                    <th>Pembayaran</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    @php
                        $isPaid = $order->isPaid();
                    @endphp
                    <tr>
                        <td>
                            #{{ $order->id }}<br>
                            <small>{{ optional($order->tanggal_pesan)->format('d/m/Y') }}</small>
                            @if($order->batch_keroyokan_id)
                                <br>
                                <small style="color:var(--green-800);font-weight:700;">
                                    <i class="bi bi-people-fill"></i> Keroyokan #KR-{{ str_pad($order->batch_keroyokan_id,5,'0',STR_PAD_LEFT) }}
                                </small>
                            @endif
                        </td>
                        <td>
                            <b>{{ $order->produk->nama_produk }}</b><br>
                            <small>{{ $order->produk->umkm->nama_umkm }}</small>
                        </td>
                        <td>{{ $order->pembeli->nama_lengkap }}</td>
                        <td>
                            {{ $order->metode_pembayaran }}
                            @if($order->rekening_bank_snapshot)
                                <br><small style="color:#64748b; font-size:11px;">Rek: {{ $order->rekening_bank_snapshot }}</small>
                            @endif
                            @if($order->bukti_pembayaran)
                                <br>
                                <button type="button" class="btn-secondary" style="font-size: 11px; padding: 2px 8px; margin-top: 4px; border-color: #3b82f6; color: #1d4ed8; background: #eff6ff;" onclick="document.getElementById('admin-proof-modal-{{ $order->id }}').showModal()">
                                    <i class="bi bi-eye"></i> Lihat Bukti
                                </button>

                                <!-- Modal Dialog Bukti Pembayaran Admin -->
                                <dialog id="admin-proof-modal-{{ $order->id }}" class="review-dialog" style="max-width: 520px; border-radius: 16px; padding: 20px; border: 1px solid #cbd5e1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                                        <div>
                                            <small style="color: #64748b; font-weight: 700;">VERIFIKASI BUKTI PEMBAYARAN</small>
                                            <h3 style="margin: 0; font-size: 1.1rem; color: #0f172a;">Pesanan #{{ $order->id }} — {{ $order->pembeli->nama_lengkap }}</h3>
                                        </div>
                                        <button type="button" onclick="document.getElementById('admin-proof-modal-{{ $order->id }}').close()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
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
                                        <div><strong>Status Pembayaran:</strong> <span style="font-weight:700; color: {{ $isPaid ? '#15803d' : '#b91c1c' }}">{{ $isPaid ? 'Sudah Dibayar' : 'Belum Dibayar' }}</span></div>
                                    </div>

                                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                        <button type="button" class="btn-secondary" onclick="document.getElementById('admin-proof-modal-{{ $order->id }}').close()">Tutup</button>
                                        <a href="{{ asset('storage/'.$order->bukti_pembayaran) }}" target="_blank" class="button button-dark" style="font-size: 12px; padding: 6px 14px; text-decoration: none;">
                                            <i class="bi bi-box-arrow-up-right"></i> Buka Gambar Asli
                                        </a>
                                    </div>
                                </dialog>
                            @endif
                        </td>
                        <td>Rp{{ number_format((float)$order->total_harga, 0, ',', '.') }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.orders.update', $order) }}">
                                @csrf
                                @method('PATCH')
                                <select class="form-control" name="status">
                                    @foreach(['Menunggu','Diproses','Selesai','Dibatalkan'] as $s)
                                        <option @selected($order->status === $s)>{{ $s }}</option>
                                    @endforeach
                                </select>
                                <div class="action-cluster" style="margin-top:6px">
                                    <button class="btn-primary">Simpan</button>
                                    <a class="btn-secondary" href="{{ route('receipt.show', $order) }}" target="_blank">Nota</a>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $orders->links() }}
    </div>
</section>
@endsection

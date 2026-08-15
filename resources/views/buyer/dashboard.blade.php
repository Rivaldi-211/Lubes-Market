@extends('layouts.dashboard')
@section('title','Pesanan Saya') @section('eyebrow','Akun Pembeli') @section('page_title','Pesanan Saya')
@section('content')
<section class="dash-intro"><div><p class="eyebrow"><span></span>Riwayat belanja</p><h1>Pantau pesanan tanpa kehilangan detail.</h1><p>Status, pembayaran, nota, dan ulasan ada di satu tempat.</p></div><a class="button" href="{{ route('catalogue') }}"><i class="bi bi-plus-lg"></i> Belanja lagi</a></section>
<div class="metric-grid"><article><small>Total pesanan</small><strong>{{ $stats['total'] }}</strong><span>Semua transaksi</span></article><article><small>Menunggu</small><strong>{{ $stats['menunggu'] }}</strong><span>Perlu tindak lanjut</span></article><article><small>Diproses</small><strong>{{ $stats['diproses'] }}</strong><span>Sedang disiapkan</span></article><article><small>Selesai</small><strong>{{ $stats['selesai'] }}</strong><span>Sudah diterima</span></article></div>
<section class="data-panel"><div class="panel-heading"><div><small>PESANAN TERBARU</small><h2>Riwayat transaksi</h2></div></div>
@if($orders->count())<div class="order-stack">@foreach($orders as $order)<article class="buyer-order"><div class="buyer-order-top"><div><small>#{{ str_pad($order->id,5,'0',STR_PAD_LEFT) }} · {{ optional($order->tanggal_pesan)->format('d M Y, H:i') }}@if($order->batch_keroyokan_id) · <b style="color:var(--green-800)">🤝 Keroyokan #KR-{{ str_pad($order->batch_keroyokan_id,5,'0',STR_PAD_LEFT) }}</b>@endif</small><h3>{{ $order->produk->nama_produk }}</h3><p>{{ $order->produk->umkm->nama_umkm }} · {{ $order->jumlah }} item</p></div><x-status-badge :status="$order->status"/></div>@if($order->produk && $order->produk->stok_status === 'Pre-Order')<div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 8px; padding: 10px 14px; margin: 10px 0; font-size: 12px; display: flex; align-items: center; gap: 8px;"><i class="bi bi-truck" style="font-size: 16px; color: #3b82f6;"></i><span><strong>Informasi Pre-Order:</strong> Barang ini akan dikirim sesuai estimasi skala yang diberikan penjual yaitu <strong>{{ $order->produk->estimasi_po_hari ? $order->produk->estimasi_po_hari . ' Hari' : 'PO' }}</strong> sejak tanggal pesan @if($order->tanggal_pesan && $order->produk->estimasi_po_hari) (estimasi kirim: <strong>{{ \Carbon\Carbon::parse($order->tanggal_pesan)->addDays($order->produk->estimasi_po_hari)->translatedFormat('d M Y') }}</strong>)@endif.</span></div>@endif<div class="buyer-order-meta"><span>Metode <b>{{ $order->metode_pembayaran }}</b></span><span>Total <b>Rp{{ number_format((float)$order->total_harga,0,',','.') }}</b></span><span>Tujuan <b>{{ $order->alamat_pengiriman ?: '-' }}</b></span></div>@if($order->metode_pembayaran === 'Transfer' && $order->rekening_bank_snapshot)<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin: 10px 0; font-size: 12px; display: flex; align-items: center; justify-content: space-between;"><div><span style="color: #64748b; font-weight: 600; display: block;">Rekening Tujuan Transfer (Platform):</span><strong style="color: #0f172a; font-size: 13px;">{{ $order->rekening_bank_snapshot }}</strong></div><button type="button" class="btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="navigator.clipboard.writeText('{{ $order->rekening_bank_snapshot }}'); this.innerText='Tersalin!'; setTimeout(()=>this.innerText='Salin', 2000)"><i class="bi bi-copy"></i> Salin</button></div>@endif<div class="row-actions"><a class="btn-secondary" href="{{ route('receipt.show',$order) }}" target="_blank"><i class="bi bi-printer"></i> Nota</a>
@if($order->status==='Menunggu')<form method="post" action="{{ route('buyer.orders.cancel',$order) }}" onsubmit="return confirm('Batalkan pesanan ini?')">@csrf @method('PATCH')<button class="btn-danger">Batalkan</button></form>@endif
@if(in_array($order->metode_pembayaran,['Transfer','QRIS']) && $order->status!=='Dibatalkan')<form class="proof-form" method="post" enctype="multipart/form-data" action="{{ route('buyer.orders.proof',$order) }}">@csrf<label class="btn-secondary"><i class="bi bi-upload"></i> {{ $order->bukti_pembayaran?'Ganti bukti':'Upload bukti' }}<input type="file" name="bukti_pembayaran" accept="image/jpeg,image/png,image/webp" onchange="const f = this.files[0]; if (f && f.size > 5 * 1024 * 1024) { alert('Ukuran foto terlalu besar (' + (f.size / (1024*1024)).toFixed(1) + ' MB). Maksimal ukuran bukti pembayaran adalah 5 MB.'); this.value = ''; return false; } this.form.submit();"></label></form>@endif
@if($order->status==='Selesai' && !$order->ulasan)<button class="btn-primary" type="button" data-review-open="review-{{ $order->id }}"><i class="bi bi-star"></i> Beri ulasan</button>@elseif($order->ulasan)<span class="review-done"><i class="bi bi-star-fill"></i> {{ $order->ulasan->rating }}/5</span>@endif</div>
@if($order->status==='Selesai' && !$order->ulasan)
<dialog id="review-{{ $order->id }}" class="review-dialog" style="border-radius: 16px; border: 1px solid #e2e8f0; max-width: 480px; width: 90%;">
    <form method="post" action="{{ route('buyer.orders.review',$order) }}" style="padding: 24px;">
        @csrf
        <div class="dialog-head" style="margin-bottom: 20px;">
            <div>
                <small style="color: #64748b; font-weight: 700; font-size: 10px; letter-spacing: 0.05em;">ULAS PRODUK</small>
                <h3 style="margin: 4px 0 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">{{ $order->produk->nama_produk }}</h3>
            </div>
            <button type="button" data-review-close="review-{{ $order->id }}" style="font-size: 24px; color: #94a3b8; line-height: 1; border: none; background: none; cursor: pointer;">&times;</button>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #475569; margin-bottom: 8px;">Rating Pengalaman</label>
            <div class="review-star-picker" style="display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div class="stars-wrap" style="display: flex; gap: 6px; font-size: 26px; cursor: pointer;">
                    @for($s = 1; $s <= 5; $s++)
                        <i class="bi bi-star-fill star-btn" data-val="{{ $s }}" style="color: #f59e0b; transition: transform 0.15s, color 0.15s;" title="{{ $s }} Bintang"></i>
                    @endfor
                </div>
                <span class="rating-text-feedback" style="font-size: 13px; font-weight: 700; color: #166534; margin-left: 4px;">5 — Sangat Puas</span>
            </div>
            <input type="hidden" name="rating" value="5" class="review-rating-input" required>
        </div>

        <label style="display: block; margin-bottom: 20px;">
            <span style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #475569; margin-bottom: 6px;">Komentar</span>
            <textarea name="komentar" rows="4" placeholder="Ceritakan pengalaman Anda membeli produk ini..." style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 12px; font-family: inherit; font-size: 13px; resize: vertical;" required></textarea>
        </label>

        <button class="button wide" style="width: 100%; justify-content: center; font-weight: 700; padding: 12px 20px;">Kirim ulasan</button>
    </form>
</dialog>
@endif
</article>@endforeach</div><div class="pagination-wrap">{{ $orders->links() }}</div>@else<x-empty-state title="Belum ada pesanan" text="Katalog UMKM lokal sudah siap dijelajahi."/>@endif</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const starLabels = {
        1: '1 — Tidak Puas',
        2: '2 — Kurang',
        3: '3 — Cukup',
        4: '4 — Puas',
        5: '5 — Sangat Puas'
    };

    document.querySelectorAll('.review-dialog').forEach(dialog => {
        const starsWrap = dialog.querySelector('.stars-wrap');
        const ratingInput = dialog.querySelector('.review-rating-input');
        const labelFeedback = dialog.querySelector('.rating-text-feedback');
        if (!starsWrap || !ratingInput || !labelFeedback) return;

        const stars = starsWrap.querySelectorAll('.star-btn');

        const updateStars = (val) => {
            stars.forEach(s => {
                const sVal = parseInt(s.dataset.val);
                if (sVal <= val) {
                    s.style.color = '#f59e0b';
                    s.style.transform = 'scale(1.1)';
                } else {
                    s.style.color = '#cbd5e1';
                    s.style.transform = 'scale(1)';
                }
            });
            setTimeout(() => {
                stars.forEach(s => s.style.transform = 'scale(1)');
            }, 150);
            labelFeedback.textContent = starLabels[val] || (val + ' Bintang');
            labelFeedback.style.color = val >= 4 ? '#166534' : (val === 3 ? '#d97706' : '#dc2626');
        };

        stars.forEach(star => {
            star.addEventListener('mouseenter', () => {
                updateStars(parseInt(star.dataset.val));
            });

            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.val);
                ratingInput.value = val;
                updateStars(val);
            });
        });

        starsWrap.addEventListener('mouseleave', () => {
            const currentVal = parseInt(ratingInput.value) || 5;
            updateStars(currentVal);
        });
    });
});
</script>
@endpush


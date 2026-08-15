@extends('layouts.dashboard')
@section('title','Pesanan Saya') @section('eyebrow','Akun Pembeli') @section('page_title','Pesanan Saya')
@section('content')
<section class="dash-intro">
    <div>
        <p class="eyebrow"><span></span>Riwayat belanja</p>
        <h1>Pantau pesanan tanpa kehilangan detail.</h1>
        <p>Status, pembayaran, nota, validasi penerimaan barang, dan ulasan ada di satu tempat.</p>
    </div>
    <a class="button" href="{{ route('catalogue') }}"><i class="bi bi-plus-lg"></i> Belanja lagi</a>
</section>

<div class="metric-grid">
    <article><small>Total pesanan</small><strong>{{ $stats['total'] }}</strong><span>Semua transaksi</span></article>
    <article><small>Menunggu</small><strong>{{ $stats['menunggu'] }}</strong><span>Perlu tindak lanjut</span></article>
    <article><small>Diproses</small><strong>{{ $stats['diproses'] }}</strong><span>Sedang disiapkan / dikirim</span></article>
    <article><small>Selesai</small><strong>{{ $stats['selesai'] }}</strong><span>Sudah diterima</span></article>
</div>

<section class="data-panel">
    <div class="panel-heading">
        <div>
            <small>PESANAN TERBARU</small>
            <h2>Riwayat transaksi</h2>
        </div>
    </div>

    @if($orders->count())
        <div class="order-stack">
            @foreach($orders as $order)
                @php
                    $orderCode = '#' . str_pad($order->id, 5, '0', STR_PAD_LEFT);
                    $buyerName = auth()->user()->nama_lengkap ?? 'Pembeli';
                    $productName = $order->produk?->nama_produk ?? 'Produk UMKM';
                    $umkmName = $order->produk?->umkm?->nama_umkm ?? 'Toko UMKM';
                    $sellerOwner = $order->produk?->umkm?->pemilik ?: ($order->produk?->umkm?->user?->nama_lengkap ?? 'Penjual');

                    // Seller Phone & WhatsApp link
                    $rawSellerPhone = $order->produk?->umkm?->no_hp ?: ($order->produk?->umkm?->user?->no_hp ?? '');
                    $sellerPhoneDigits = preg_replace('/[^0-9]/', '', $rawSellerPhone);
                    if (str_starts_with($sellerPhoneDigits, '0')) {
                        $sellerPhoneFormatted = '62' . substr($sellerPhoneDigits, 1);
                    } else {
                        $sellerPhoneFormatted = $sellerPhoneDigits;
                    }

                    // Admin Phone & WhatsApp link
                    $rawAdminPhone = $adminContact ?? '081234500001';
                    $adminPhoneDigits = preg_replace('/[^0-9]/', '', $rawAdminPhone);
                    if (str_starts_with($adminPhoneDigits, '0')) {
                        $adminPhoneFormatted = '62' . substr($adminPhoneDigits, 1);
                    } else {
                        $adminPhoneFormatted = $adminPhoneDigits;
                    }

                    $sellerWaMsg = "Halo kak {$sellerOwner}, saya {$buyerName} (Pembeli) ingin menanyakan status Pesanan {$orderCode} ({$productName}). Barang pesanan saya belum sampai ke alamat pengiriman. Mohon bantuannya untuk pengecekan status pengiriman. Terima kasih!";
                    $sellerWaUrl = $sellerPhoneFormatted ? "https://wa.me/{$sellerPhoneFormatted}?text=" . rawurlencode($sellerWaMsg) : null;

                    $adminWaMsg = "Halo Admin LUDES-MARKET, saya {$buyerName} ingin meminta bantuan terkait Pesanan {$orderCode} dari UMKM {$umkmName} (Produk: {$productName}). Barang pesanan belum sampai sampai saat ini. Mohon bantuannya. Terima kasih!";
                    $adminWaUrl = "https://wa.me/{$adminPhoneFormatted}?text=" . rawurlencode($adminWaMsg);
                @endphp

                <article class="buyer-order">
                    <div class="buyer-order-top">
                        <div>
                            <small>
                                {{ $orderCode }} · {{ optional($order->tanggal_pesan)->format('d M Y, H:i') }}
                                @if($order->batch_keroyokan_id)
                                    · <b style="color:var(--green-800)"><i class="bi bi-people-fill"></i> Keroyokan #KR-{{ str_pad($order->batch_keroyokan_id,5,'0',STR_PAD_LEFT) }}</b>
                                @endif
                            </small>
                            <h3>{{ $productName }}</h3>
                            <p>{{ $umkmName }} · {{ $order->jumlah }} item</p>
                        </div>
                        <x-status-badge :status="$order->status"/>
                    </div>

                    {{-- Pre-Order Info Notice --}}
                    @if($order->produk && $order->produk->stok_status === 'Pre-Order')
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 8px; padding: 10px 14px; margin: 10px 0; font-size: 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-truck" style="font-size: 16px; color: #3b82f6;"></i>
                            <span>
                                <strong>Informasi Pre-Order:</strong> Barang ini dibuat/disiapkan khusus dengan estimasi <strong>{{ $order->produk->estimasi_po_hari ? $order->produk->estimasi_po_hari . ' Hari' : 'PO' }}</strong> sejak tanggal pesan
                                @if($order->tanggal_pesan && $order->produk->estimasi_po_hari)
                                    (estimasi kirim: <strong>{{ \Carbon\Carbon::parse($order->tanggal_pesan)->addDays($order->produk->estimasi_po_hari)->translatedFormat('d M Y') }}</strong>)
                                @endif.
                            </span>
                        </div>
                    @endif

                    <div class="buyer-order-meta">
                        <span>Metode <b>{{ $order->metode_pembayaran }}</b></span>
                        <span>Total <b>Rp{{ number_format((float)$order->total_harga,0,',','.') }}</b></span>
                        <span>Tujuan <b>{{ $order->alamat_pengiriman ?: '-' }}</b></span>
                    </div>

                    {{-- Bank Snapshot Transfer Info --}}
                    @if($order->metode_pembayaran === 'Transfer' && $order->rekening_bank_snapshot)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin: 10px 0; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <span style="color: #64748b; font-weight: 600; display: block;">Rekening Tujuan Transfer (Platform):</span>
                                <strong style="color: #0f172a; font-size: 13px;">{{ $order->rekening_bank_snapshot }}</strong>
                            </div>
                            <button type="button" class="btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="navigator.clipboard.writeText('{{ $order->rekening_bank_snapshot }}'); this.innerText='Tersalin!'; setTimeout(()=>this.innerText='Salin', 2000)"><i class="bi bi-copy"></i> Salin</button>
                        </div>
                    @endif

                    {{-- FEATURE: Delivery Validation Prompt for 'Diproses' Orders --}}
                    @if($order->status === 'Diproses')
                        <div class="delivery-validation-card">
                            <div class="dvc-main">
                                <div class="dvc-badge">
                                    <span class="pulse-dot"></span>
                                    <i class="bi bi-truck"></i> Pesanan Sedang Diproses / Dikirim
                                </div>
                                <h4 class="dvc-question">Apakah barang pesanan Anda sudah sampai?</h4>
                                <p class="dvc-desc">Pastikan barang telah Anda terima dalam kondisi baik. Jika belum sampai atau ada kendala, hubungi Penjual / Admin.</p>
                            </div>
                            <div class="dvc-buttons">
                                <form method="post" action="{{ route('buyer.orders.confirm-received', $order) }}" onsubmit="return confirm('Apakah Anda yakin barang untuk pesanan {{ $orderCode }} sudah sampai dan Anda terima dengan baik?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-confirm-yes">
                                        <i class="bi bi-check-circle-fill"></i> Sudah Sampai
                                    </button>
                                </form>
                                <button type="button" class="btn-confirm-no" data-contact-open="contact-{{ $order->id }}">
                                    <i class="bi bi-chat-dots-fill"></i> Belum Sampai / Bantuan
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="row-actions">
                        <a class="btn-secondary" href="{{ route('receipt.show',$order) }}" target="_blank"><i class="bi bi-printer"></i> Nota</a>

                        @if($order->status === 'Menunggu')
                            <form method="post" action="{{ route('buyer.orders.cancel',$order) }}" onsubmit="return confirm('Batalkan pesanan ini?')">
                                @csrf
                                @method('PATCH')
                                <button class="btn-danger"><i class="bi bi-x-circle"></i> Batalkan</button>
                            </form>
                        @endif

                        @if(in_array($order->metode_pembayaran,['Transfer','QRIS']) && $order->status !== 'Dibatalkan')
                            <form class="proof-form" method="post" enctype="multipart/form-data" action="{{ route('buyer.orders.proof',$order) }}">
                                @csrf
                                <label class="btn-secondary">
                                    <i class="bi bi-upload"></i> {{ $order->bukti_pembayaran ? 'Ganti bukti' : 'Upload bukti' }}
                                    <input type="file" name="bukti_pembayaran" accept="image/jpeg,image/png,image/webp" onchange="const f = this.files[0]; if (f && f.size > 5 * 1024 * 1024) { alert('Ukuran foto terlalu besar (' + (f.size / (1024*1024)).toFixed(1) + ' MB). Maksimal ukuran bukti pembayaran adalah 5 MB.'); this.value = ''; return false; } this.form.submit();">
                                </label>
                            </form>
                        @endif

                        {{-- Chat / Bantuan Button --}}
                        <button type="button" class="btn-secondary" data-contact-open="contact-{{ $order->id }}" title="Hubungi Penjual atau Admin">
                            <i class="bi bi-chat-left-text-fill"></i> Chat / Bantuan
                        </button>

                        @if($order->status === 'Selesai' && !$order->ulasan)
                            <button class="btn-primary" type="button" data-review-open="review-{{ $order->id }}"><i class="bi bi-star"></i> Beri ulasan</button>
                        @elseif($order->ulasan)
                            <span class="review-done"><i class="bi bi-star-fill"></i> {{ $order->ulasan->rating }}/5</span>
                        @endif
                    </div>

                    {{-- FEATURE: Modal Bantuan & Chat Kontak Penjual / Admin --}}
                    <dialog id="contact-{{ $order->id }}" class="contact-support-dialog" style="border-radius: 18px; border: 1px solid #e2e8f0; max-width: 520px; width: 92%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 0; overflow: hidden;">
                        <div class="cs-dialog-header" style="background: linear-gradient(135deg, #183e2b, #2c5e43); color: white; padding: 20px 24px; position: relative;">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                <div>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; background: rgba(255,255,255,0.18); padding: 3px 10px; border-radius: 999px; color: #fde047;">
                                        <i class="bi bi-shield-check"></i> Layanan Bantuan Pesanan
                                    </span>
                                    <h3 style="margin: 8px 0 2px; font-size: 1.25rem; font-weight: 800; color: #ffffff;">Barang Belum Sampai / Butuh Bantuan?</h3>
                                    <p style="margin: 0; font-size: 12px; color: #d1fae5;">Pesanan {{ $orderCode }} · {{ $productName }}</p>
                                </div>
                                <button type="button" data-contact-close="contact-{{ $order->id }}" style="font-size: 26px; color: #a7f3d0; line-height: 1; border: none; background: none; cursor: pointer; padding: 0 4px;">&times;</button>
                            </div>
                        </div>

                        <div class="cs-dialog-body" style="padding: 20px 24px; background: #fafafa;">
                            {{-- Order Detail Card --}}
                            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 12px; line-height: 1.6;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="color: #6b7280;">Toko UMKM:</span>
                                    <strong style="color: #111827;">{{ $umkmName }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="color: #6b7280;">Status Pesanan:</span>
                                    <span style="font-weight: 700; color: #1e40af;">{{ $order->status }}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="color: #6b7280;">Metode Pembayaran:</span>
                                    <strong style="color: #111827;">{{ $order->metode_pembayaran }} ({{ $order->isPaid() ? 'Lunas' : 'Belum Lunas' }})</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6b7280;">Alamat Pengiriman:</span>
                                    <span style="color: #111827; text-align: right; max-width: 60%; font-weight: 500;">{{ $order->alamat_pengiriman ?: '-' }}</span>
                                </div>
                            </div>

                            <p style="font-size: 12.5px; color: #4b5563; margin: 0 0 14px; line-height: 1.5;">
                                Silakan pilih kontak di bawah untuk menanyakan status pengiriman langsung via <strong>WhatsApp</strong>:
                            </p>

                            {{-- Contact Options Grid --}}
                            <div style="display: grid; gap: 12px;">
                                {{-- Option 1: Pemilik UMKM --}}
                                <div style="background: #ffffff; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                                            <i class="bi bi-shop"></i>
                                        </div>
                                        <div>
                                            <strong style="display: block; font-size: 13px; color: #0f172a;">Pemilik UMKM ({{ $umkmName }})</strong>
                                            <small style="color: #64748b; font-size: 11px;">Penjual: {{ $sellerOwner }} · {{ $rawSellerPhone ?: 'No. HP belum terdaftar' }}</small>
                                        </div>
                                    </div>
                                    @if($sellerWaUrl)
                                        <a href="{{ $sellerWaUrl }}" target="_blank" rel="noopener noreferrer" class="button" style="background: #25D366; color: #ffffff; border: none; font-size: 12.5px; font-weight: 700; border-radius: 8px; padding: 9px 14px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                            <i class="bi bi-whatsapp" style="font-size: 15px;"></i> Chat Pemilik UMKM via WhatsApp
                                        </a>
                                    @else
                                        <button type="button" class="button button-outline" disabled style="font-size: 12px; opacity: 0.6; justify-content: center;">
                                            <i class="bi bi-telephone-x"></i> Kontak Penjual Belum Tersedia
                                        </button>
                                    @endif
                                </div>

                                {{-- Option 2: Admin BUMDes / Platform --}}
                                <div style="background: #ffffff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                                            <i class="bi bi-headset"></i>
                                        </div>
                                        <div>
                                            <strong style="display: block; font-size: 13px; color: #0f172a;">Admin BUMDes (LUDES-MARKET)</strong>
                                            <small style="color: #64748b; font-size: 11px;">Pusat Bantuan & Pengaduan · {{ $rawAdminPhone }}</small>
                                        </div>
                                    </div>
                                    <a href="{{ $adminWaUrl }}" target="_blank" rel="noopener noreferrer" class="button" style="background: #0f766e; color: #ffffff; border: none; font-size: 12.5px; font-weight: 700; border-radius: 8px; padding: 9px 14px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                        <i class="bi bi-whatsapp" style="font-size: 15px;"></i> Chat Admin BUMDes via WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="cs-dialog-footer" style="background: #f1f5f9; padding: 12px 24px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0;">
                            <button type="button" class="btn-secondary" data-contact-close="contact-{{ $order->id }}" style="font-size: 12px; padding: 6px 16px;">Tutup</button>
                        </div>
                    </dialog>

                    {{-- Review Dialog --}}
                    @if($order->status === 'Selesai' && !$order->ulasan)
                        <dialog id="review-{{ $order->id }}" class="review-dialog" style="border-radius: 16px; border: 1px solid #e2e8f0; max-width: 480px; width: 90%;">
                            <form method="post" action="{{ route('buyer.orders.review',$order) }}" style="padding: 24px;">
                                @csrf
                                <div class="dialog-head" style="margin-bottom: 20px;">
                                    <div>
                                        <small style="color: #64748b; font-weight: 700; font-size: 10px; letter-spacing: 0.05em;">ULAS PRODUK</small>
                                        <h3 style="margin: 4px 0 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">{{ $productName }}</h3>
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
                </article>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    @else
        <x-empty-state title="Belum ada pesanan" text="Katalog UMKM lokal sudah siap dijelajahi."/>
    @endif
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Review Dialog logic
    const starLabels = {
        1: '1 — Tidak Puas',
        2: '2 — Kurang',
        3: '3 — Cukup',
        4: '4 — Puas',
        5: '5 — Sangat Puas'
    };

    document.querySelectorAll('[data-review-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-review-open');
            const dialog = document.getElementById(targetId);
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        });
    });

    document.querySelectorAll('[data-review-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-review-close');
            const dialog = document.getElementById(targetId);
            if (dialog && typeof dialog.close === 'function') {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('.review-dialog').forEach(dialog => {
        // Close on click backdrop
        dialog.addEventListener('click', (e) => {
            const rect = dialog.getBoundingClientRect();
            if (
                e.clientX < rect.left ||
                e.clientX > rect.right ||
                e.clientY < rect.top ||
                e.clientY > rect.bottom
            ) {
                dialog.close();
            }
        });

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

    // Contact & Support Dialog logic
    document.querySelectorAll('[data-contact-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-contact-open');
            const dialog = document.getElementById(targetId);
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        });
    });

    document.querySelectorAll('[data-contact-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-contact-close');
            const dialog = document.getElementById(targetId);
            if (dialog && typeof dialog.close === 'function') {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('.contact-support-dialog').forEach(dialog => {
        dialog.addEventListener('click', (e) => {
            const rect = dialog.getBoundingClientRect();
            if (
                e.clientX < rect.left ||
                e.clientX > rect.right ||
                e.clientY < rect.top ||
                e.clientY > rect.bottom
            ) {
                dialog.close();
            }
        });
    });
});
</script>
@endpush

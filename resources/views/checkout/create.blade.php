@extends('layouts.public')

@section('title', 'Checkout — LUDES-MARKET')

@section('content')
<section class="inner-hero compact-hero">
    <div class="shell">
        <div class="eyebrow"><span></span>Checkout</div>
        <div class="inner-hero-row">
            <h1>Detail jelas,<br><em>pesanan tenang.</em></h1>
            <p>Lengkapi kontak, lokasi, dan cara pembayaran. Stok akan diperiksa ulang saat pesanan dikirim.</p>
        </div>
    </div>
</section>

<section class="section checkout-section">
    <div class="shell">
        <x-flash />

        <form method="post" action="{{ route('checkout.store') }}" class="checkout-layout" id="checkoutForm" onsubmit="const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerHTML = 'Memproses pesanan...'; }">
            @csrf
            <div class="checkout-main">
                <section class="form-panel">
                    <div class="form-panel-head">
                        <span>01</span>
                        <div>
                            <h2>Kontak & pengantaran</h2>
                            <p>Informasi ini dipakai penjual untuk menyiapkan pesanan.</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <label>
                            Nomor HP
                            <input type="text" name="no_hp_pembeli" value="{{ old('no_hp_pembeli', $user->no_hp) }}" required>
                        </label>
                        <label class="span-2">
                            Alamat / titik pengambilan
                            <textarea name="alamat_pengiriman" rows="3" required placeholder="Dusun, RT/RW, patokan lokasi, atau titik temu">{{ old('alamat_pengiriman') }}</textarea>
                        </label>
                        <label class="span-2">
                            Catatan pesanan <span>(opsional)</span>
                            <input type="text" name="catatan" value="{{ old('catatan') }}" placeholder="Contoh: saus dipisah atau bungkus terpisah">
                        </label>
                    </div>
                </section>

                <section class="form-panel">
                    <div class="form-panel-head">
                        <span>02</span>
                        <div>
                            <h2>Metode pembayaran</h2>
                            <p>Pilih metode yang paling sesuai untuk pesanan ini.</p>
                        </div>
                    </div>
                    <div class="payment-grid">
                        @foreach([
                            ['COD', 'Bayar saat pesanan diterima', 'bi-cash-coin'],
                            ['Transfer', 'Transfer bank, unggah bukti setelah pesan', 'bi-bank'],
                            ['QRIS', 'Bayar melalui QRIS, lalu unggah bukti', 'bi-qr-code'],
                            ['Moncongloe', 'Ambil / bayar di kawasan Moncongloe Lappara', 'bi-shop']
                        ] as $payment)
                            <label class="payment-option">
                                <input type="radio" name="metode_pembayaran" value="{{ $payment[0] }}" @checked(old('metode_pembayaran', 'COD') === $payment[0]) onchange="document.getElementById('bankAccountSection').style.display = (this.value === 'Transfer') ? 'block' : 'none'">
                                <span class="payment-card">
                                    <i class="bi {{ $payment[2] }}"></i>
                                    <strong>{{ $payment[0] }}</strong>
                                    <small>{{ $payment[1] }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <!-- Rekening Bank Selection Box -->
                    <div id="bankAccountSection" style="{{ old('metode_pembayaran') === 'Transfer' ? 'display: block;' : 'display: none;' }} margin-top: 20px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px;">
                        <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 6px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-bank" style="color: #2563eb; font-size: 1.1rem;"></i> Pilih Rekening Bank Tujuan Transfer (BUMDes)
                        </h3>
                        <p style="font-size: 0.82rem; color: #64748b; margin: 0 0 14px 0;">Pilih rekening BUMDes Berkah tujuan pembayaran Anda:</p>
                        
                        @if(isset($rekeningBankList) && $rekeningBankList->isNotEmpty())
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @foreach($rekeningBankList as $bank)
                                    <label style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; cursor: pointer; transition: border-color 0.2s;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#cbd5e1'">
                                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                            <input type="radio" name="rekening_bank_id" value="{{ $bank->id }}" @checked(old('rekening_bank_id') == $bank->id || $loop->first) style="width: 18px; height: 18px; flex-shrink: 0;">
                                            <div style="min-width: 0;">
                                                <strong style="font-size: 0.95rem; color: #0f172a; display: block; word-break: break-word;">
                                                    {{ $bank->nama_bank }}
                                                    @if($bank->umkm)
                                                        <span style="font-weight: normal; color: #2563eb; font-size: 0.82rem;"> — {{ $bank->umkm->nama_umkm }}</span>
                                                    @endif
                                                </strong>
                                                <small style="color: #64748b; font-size: 0.82rem;">a.n. {{ $bank->atas_nama }}</small>
                                            </div>
                                        </div>
                                        <div>
                                            <code style="font-size: 0.9rem; font-weight: 700; color: #1e3a8a; background: #eff6ff; padding: 4px 8px; border-radius: 6px; white-space: nowrap;">{{ $bank->nomor_rekening }}</code>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">
                                Belum ada rekening bank yang aktif. Silakan hubungi admin.
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <aside class="order-summary">
                <span>Ringkasan pesanan</span>
                <h2>{{ $items->sum('quantity') }} item</h2>
                <div class="checkout-items">
                    @foreach($items as $item)
                        <div>
                            <span>{{ $item['quantity'] }}× {{ $item['product']->nama_produk }}</span>
                            <strong>Rp{{ number_format($item['line_total'], 0, ',', '.') }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="summary-grand">
                    <span>Total produk</span>
                    <strong>Rp{{ number_format($subtotal, 0, ',', '.') }}</strong>
                </div>
                <p>Stok diperiksa lagi saat tombol di bawah ditekan. Jika stok berubah, transaksi dibatalkan tanpa pesanan sebagian.</p>
                <button class="button wide" type="submit">Buat pesanan <i class="bi bi-arrow-right"></i></button>
                <a class="back-cart" href="{{ route('cart.index') }}"><i class="bi bi-arrow-left"></i> Kembali ke keranjang</a>
            </aside>
        </form>
    </div>
</section>
@endsection

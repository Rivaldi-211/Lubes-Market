@extends('layouts.public')

@section('title', 'Checkout — LUDES-MARKET')

@section('content')
<section class="inner-hero compact-hero">
    <div class="shell">
        <div class="eyebrow"><span></span>Checkout</div>
        <div class="inner-hero-row">
            <h1>Detail jelas,<br><em>pesanan tenang.</em></h1>
            <p>Lengkapi kontak, lokasi pengiriman, opsi packing, dan cara pembayaran.</p>
        </div>
    </div>
</section>

<section class="section checkout-section">
    <div class="shell">
        <x-flash />

        <form method="post" action="{{ route('checkout.store') }}" class="checkout-layout" id="checkoutForm" onsubmit="const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.innerHTML = 'Memproses pesanan...'; }">
            @csrf
            <div class="checkout-main">
                <!-- PANEL 1: KONTAK & ALAMAT -->
                <section class="form-panel">
                    <div class="form-panel-head">
                        <span>01</span>
                        <div>
                            <h2>Kontak & pengantaran</h2>
                            <p>Informasi ini dipakai kurir dan penjual untuk mengirimkan pesanan.</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <label>
                            Nomor HP / WhatsApp <span class="required">*</span>
                            <input type="text" name="no_hp_pembeli" value="{{ old('no_hp_pembeli', $user->no_hp) }}" required placeholder="Contoh: 081234567890">
                        </label>

                        @if(!$isKeroyokan && $umkmCount > 1)
                            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; font-size: 0.88rem; color: #1e40af;">
                                <i class="bi bi-shop" style="font-size: 1.2rem; color: #2563eb; flex-shrink: 0;"></i>
                                <div>
                                    <strong>Pesanan dari {{ $umkmCount }} Toko UMKM Berbeda:</strong>
                                    <div style="color: #334155; margin-top: 2px;">{{ $umkmList->pluck('nama_umkm')->implode(', ') }}</div>
                                    <small style="color: #64748b;">Ongkos kirim dihitung per toko pengirim.</small>
                                </div>
                            </div>
                        @elseif($isKeroyokan)
                            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; font-size: 0.88rem; color: #166534;">
                                <i class="bi bi-people-fill" style="font-size: 1.2rem; color: #16a34a; flex-shrink: 0;"></i>
                                <div>
                                    <strong>Paket Pesanan Keroyokan:</strong>
                                    <small style="display: block; color: #15803d;">Pengiriman gabungan satu tujuan (1x tarif ongkir rombongan).</small>
                                </div>
                            </div>
                        @endif

                        <label class="span-2">
                            Zona Pengiriman <span class="required">*</span>
                            <select name="zona_pengiriman" id="zonaSelect" required onchange="updateCalculations()" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                <option value="" disabled selected>— Pilih zona sesuai lokasi pengiriman Anda —</option>
                                @foreach($zonaPengiriman as $zona)
                                    <option value="{{ $zona->nama_zona }}"
                                            data-biaya="{{ $zona->biaya }}"
                                            data-keterangan="{{ $zona->keterangan }}"
                                            @selected(old('zona_pengiriman', $user->zona_pengiriman) === $zona->nama_zona)>
                                        @if(!$isKeroyokan && $umkmCount > 1)
                                            {{ $zona->nama_zona }} — Rp{{ number_format($zona->biaya, 0, ',', '.') }} / toko (Total: Rp{{ number_format($zona->biaya * $umkmCount, 0, ',', '.') }})
                                        @else
                                            {{ $zona->nama_zona }} — Rp{{ number_format($zona->biaya, 0, ',', '.') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <div id="keteranganZona" style="display:none; grid-column: 1 / -1; margin-top: -6px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 0.88rem; color: #14532d;">
                            <i class="bi bi-info-circle-fill" style="color: #059669;"></i>
                            <span id="teksKeteranganZona"></span>
                        </div>

                        <label class="span-2">
                            Alamat Lengkap / Patokan Pengiriman <span class="required">*</span>
                            <textarea name="alamat_pengiriman" rows="3" required placeholder="Dusun, RT/RW, nomor rumah, atau patokan lokasi pengiriman">{{ old('alamat_pengiriman', $user->alamat_utama) }}</textarea>
                        </label>
                        <label class="span-2">
                            Catatan Pesanan <span>(opsional)</span>
                            <input type="text" name="catatan" value="{{ old('catatan') }}" placeholder="Contoh: saus dipisah atau titip di pos sekuriti">
                        </label>
                    </div>
                </section>

                <!-- PANEL 2: OPSI PACKING -->
                <section class="form-panel">
                    <div class="form-panel-head">
                        <span>02</span>
                        <div>
                            <h2>Opsi Packing / Kemasan</h2>
                            <p>Pilih perlindungan kemasan yang Anda inginkan untuk produk ini.</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
                        @foreach($opsiPacking as $packing)
                            <label style="cursor: pointer; position: relative;">
                                <input type="radio" name="opsi_packing" value="{{ $packing->nama }}"
                                       data-biaya="{{ $packing->biaya }}"
                                       @checked(old('opsi_packing', $loop->first ? $packing->nama : '') === $packing->nama)
                                       onchange="updateCalculations()" style="display: none;" class="packing-radio">
                                <div class="packing-card-box" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 14px 10px; text-align: center; background: #fff; transition: all 0.2s;">
                                    <strong style="display: block; color: #0f172a; font-size: 0.95rem; margin-bottom: 2px;">{{ $packing->nama }}</strong>
                                    <small style="color: #64748b; display: block; font-size: 0.78rem; margin-bottom: 6px; min-height: 28px;">{{ $packing->deskripsi }}</small>
                                    <span style="font-weight: 800; color: #059669; font-size: 0.88rem;">
                                        {{ $packing->biaya > 0 ? '+Rp' . number_format($packing->biaya, 0, ',', '.') : 'Gratis' }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </section>

                <!-- PANEL 3: METODE PEMBAYARAN -->
                <section class="form-panel">
                    <div class="form-panel-head">
                        <span>03</span>
                        <div>
                            <h2>Metode Pembayaran</h2>
                            <p>Pembayaran akan diteruskan terpusat ke Admin Platform LUDES-MARKET.</p>
                        </div>
                    </div>
                    <div class="payment-grid">
                        @foreach([
                            ['COD', 'Bayar tunai saat pesanan diterima', 'bi-cash-coin'],
                            ['Transfer', 'Transfer bank platform, unggah bukti setelah pesan', 'bi-bank'],
                            ['QRIS', 'Bayar instan via QRIS All Payment', 'bi-qr-code']
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

                    <!-- Rekening Bank Admin Selection Box -->
                    <div id="bankAccountSection" style="{{ old('metode_pembayaran') === 'Transfer' ? 'display: block;' : 'display: none;' }} margin-top: 20px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px;">
                        <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 6px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-bank" style="color: #2563eb; font-size: 1.1rem;"></i> Rekening Bank Tujuan Transfer (Platform)
                        </h3>
                        <p style="font-size: 0.82rem; color: #64748b; margin: 0 0 14px 0;">Pilih rekening Bank Admin Platform tujuan transfer Anda:</p>

                        @if(isset($rekeningBankList) && $rekeningBankList->isNotEmpty())
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @foreach($rekeningBankList as $bank)
                                    <label style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; cursor: pointer; transition: border-color 0.2s;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#cbd5e1'">
                                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                                            <input type="radio" name="rekening_bank_id" value="{{ $bank->id }}" @checked(old('rekening_bank_id') == $bank->id || $loop->first) style="width: 18px; height: 18px; flex-shrink: 0;">
                                            <div style="min-width: 0;">
                                                <strong style="font-size: 0.95rem; color: #0f172a; display: block; word-break: break-word;">
                                                    {{ $bank->nama_bank }}
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
                                Belum ada rekening bank admin yang aktif. Silakan hubungi pengelola platform.
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <!-- ASIDE: RINGKASAN BIAYA & PESANAN -->
            <aside class="order-summary">
                <span>Ringkasan pesanan</span>
                <h2>{{ $items->sum('quantity') }} item</h2>
                <div class="checkout-items">
                    @foreach($items as $item)
                        <div>
                            <span>{{ $item['quantity'] }}× {{ $item['product']->nama_produk }} <small style="color:#64748b; font-size:0.75rem;">({{ $item['product']->umkm->nama_umkm }})</small></span>
                            <strong>Rp{{ number_format($item['line_total'], 0, ',', '.') }}</strong>
                        </div>
                    @endforeach
                </div>

                <div style="border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 14px 0; margin: 16px 0; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
                    <div style="display: flex; justify-content: space-between; color: #475569;">
                        <span>Subtotal Produk</span>
                        <strong id="displaySubtotal">Rp{{ number_format($subtotal, 0, ',', '.') }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: #475569;" id="rowOngkir">
                        <span>Ongkos Kirim @if(!$isKeroyokan && $umkmCount > 1) <small style="color:#64748b; font-weight:600;">({{ $umkmCount }} Toko)</small> @elseif($isKeroyokan) <small style="color:#16a34a; font-weight:600;">(Keroyokan)</small> @endif</span>
                        <strong id="displayOngkir" style="color: #059669;">Rp0</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: #475569;" id="rowPacking">
                        <span>Biaya Packing</span>
                        <strong id="displayPacking" style="color: #059669;">Rp0</strong>
                    </div>
                </div>

                <div class="summary-grand" style="margin-top: 0;">
                    <span>Total Tagihan</span>
                    <strong id="displayGrandTotal" style="font-size: 1.35rem; color: #123825;">Rp{{ number_format($subtotal, 0, ',', '.') }}</strong>
                </div>

                <p style="font-size: 0.78rem; color: #64748b; margin-top: 12px;">Stok diperiksa lagi saat tombol di bawah ditekan. Jika stok berubah, transaksi dibatalkan tanpa pesanan sebagian.</p>
                <button class="button wide" type="submit" style="margin-top: 12px;">Buat pesanan <i class="bi bi-arrow-right"></i></button>
                <a class="back-cart" href="{{ route('cart.index') }}"><i class="bi bi-arrow-left"></i> Kembali ke keranjang</a>
            </aside>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
const baseSubtotal = {{ $subtotal }};
const umkmMultiplier = {{ $isKeroyokan ? 1 : ($umkmCount ?: 1) }};

function updateCalculations() {
    const zonaSelect = document.getElementById('zonaSelect');
    let ongkirPerToko = 0;
    if (zonaSelect && zonaSelect.selectedIndex > 0) {
        const opt = zonaSelect.options[zonaSelect.selectedIndex];
        ongkirPerToko = parseFloat(opt.dataset.biaya || 0);

        const ketBox = document.getElementById('keteranganZona');
        const ketTeks = document.getElementById('teksKeteranganZona');
        if (opt.dataset.keterangan) {
            ketBox.style.display = 'block';
            ketTeks.textContent = opt.dataset.keterangan;
        } else {
            ketBox.style.display = 'none';
        }
    }

    const totalOngkir = ongkirPerToko * umkmMultiplier;

    const activePackingRadio = document.querySelector('.packing-radio:checked');
    let packingBiaya = 0;
    if (activePackingRadio) {
        packingBiaya = parseFloat(activePackingRadio.dataset.biaya || 0);
    }

    // Highlight packing cards
    document.querySelectorAll('.packing-card-box').forEach(box => {
        const radio = box.previousElementSibling;
        if (radio && radio.checked) {
            box.style.borderColor = '#059669';
            box.style.background = '#f0fdf4';
        } else {
            box.style.borderColor = '#e2e8f0';
            box.style.background = '#fff';
        }
    });

    const grandTotal = baseSubtotal + totalOngkir + packingBiaya;

    document.getElementById('displayOngkir').textContent = 'Rp' + totalOngkir.toLocaleString('id-ID');
    document.getElementById('displayPacking').textContent = packingBiaya > 0 ? 'Rp' + packingBiaya.toLocaleString('id-ID') : 'Gratis';
    document.getElementById('displayGrandTotal').textContent = 'Rp' + grandTotal.toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', function() {
    updateCalculations();
});
</script>
@endpush

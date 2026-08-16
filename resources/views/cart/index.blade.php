@extends('layouts.public')

@section('title', 'Keranjang — LUDES-MARKET')

@push('head')
<style>
    .cart-summary {
        position: static !important;
        top: auto !important;
        align-self: flex-start !important;
    }
    .selection-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        user-select: none;
    }
    .custom-check {
        width: 18px;
        height: 18px;
        accent-color: #15803d;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<section class="inner-hero compact-hero">
    <div class="shell">
        <div class="eyebrow"><span></span>Keranjang</div>
        <div class="inner-hero-row">
            <h1>Pesanan yang<br><em>sedang disiapkan.</em></h1>
            <p>Pilih produk yang ingin Anda checkout sekarang atau simpan untuk pesanan berikutnya.</p>
        </div>
    </div>
</section>

<section class="section cart-section">
    <div class="shell">
        <x-flash />

        @if($items->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bag"></i>
                <h3>Keranjang masih kosong.</h3>
                <p>Mulai dari katalog dan pilih produk yang ingin Anda pesan.</p>
                <a class="button" href="{{ route('catalogue') }}">Buka katalog</a>
            </div>
        @else
            <form method="get" action="{{ route('checkout.create') }}" id="checkoutSelectionForm">
                <div class="cart-layout">
                    <div class="cart-list" style="display: flex; flex-direction: column; gap: 20px; border-top: none;">
                        
                        <!-- MASTER SELECT ALL BAR -->
                        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
                            <label class="selection-badge" style="color: #0f172a;">
                                <input type="checkbox" id="selectAllCheckbox" checked onchange="toggleSelectAll(this)" class="custom-check">
                                <span>Pilih Semua Produk</span>
                            </label>
                            <span id="selectedCountText" style="font-size: 12px; font-weight: 700; color: #15803d;">Semua dipilih</span>
                        </div>

                        {{-- 1. TAMPILAN PAKET KEROYOKAN (JIKA ADA DI KERANJANG) --}}
                        @if(!empty($isKeroyokan) && $keroyokanItems->isNotEmpty())
                            <article style="background: #fff; border: 2px solid var(--green-700); border-radius: 16px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 16px;">
                                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                                        <input type="checkbox" name="select_keroyokan" value="1" id="checkKeroyokan" checked onchange="recalculateCartSelection()" class="custom-check" data-price="{{ $keroyokanSubtotal }}" style="margin-top: 4px;">
                                        <div>
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                                <span style="font-size: 11px; font-weight: 800; color: #ffffff; background: #15803d; padding: 3px 10px; border-radius: 999px; text-transform: uppercase;">
                                                    <i class="bi bi-people-fill"></i> PAKET KEROYOKAN RESMI
                                                </span>
                                                @if($kelompok?->kategori)
                                                    <span style="font-size: 11px; font-weight: 700; color: #475569; background: #f1f5f9; padding: 3px 8px; border-radius: 6px;">
                                                        {{ $kelompok->kategori->nama_kategori }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">
                                                {{ $kelompok?->nama_kelompok ?? 'Paket Keroyokan Pilihan' }}
                                            </h2>
                                            <p style="color: #64748b; font-size: 0.88rem; margin: 0;">
                                                Konsolidasi pesanan gabungan mitra UMKM desa Moncongloe Lappara.
                                            </p>
                                        </div>
                                    </div>

                                    <div style="text-align: right;">
                                        <span style="font-size: 12px; font-weight: 700; color: #64748b; display: block; text-transform: uppercase;">Jumlah Pesanan</span>
                                        <strong style="font-size: 1.4rem; color: #15803d;">
                                            {{ $keroyokanContext['jumlah_box'] ?? $keroyokanItems->sum('quantity') }} Box
                                        </strong>
                                    </div>
                                </div>

                                <!-- DETAIL BOX SUMMARY -->
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px;">
                                    <div>
                                        <small style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block;">Estimasi Harga per Box</small>
                                        @php
                                            $boxCount = max(1, (int)($keroyokanContext['jumlah_box'] ?? 1));
                                            $pricePerBox = (float)($keroyokanContext['box_price'] ?? ($keroyokanSubtotal / $boxCount));
                                        @endphp
                                        <strong style="font-size: 1.1rem; color: #0f172a;">Rp{{ number_format($pricePerBox, 0, ',', '.') }} / box</strong>
                                    </div>
                                    <div>
                                        <small style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block;">Total Isi Semua Box</small>
                                        <strong style="font-size: 1.1rem; color: #0f172a;">{{ number_format($keroyokanItems->sum('quantity')) }} pcs kue/item</strong>
                                    </div>
                                    <div>
                                        <small style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block;">Subtotal Paket Keroyokan</small>
                                        <strong style="font-size: 1.2rem; color: #15803d;">Rp{{ number_format($keroyokanSubtotal, 0, ',', '.') }}</strong>
                                    </div>
                                </div>

                                <!-- RINCIAN PRODUK DI DALAM BOX -->
                                <div>
                                    <h3 style="font-size: 0.92rem; font-weight: 800; color: #334155; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.03em;">
                                        <i class="bi bi-box-seam" style="color: #059669;"></i> Rincian Komposisi Isi Paket Box
                                    </h3>

                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        @foreach($keroyokanItems as $item)
                                            @php
                                                $prod = $item['product'];
                                                $pcsPerBox = $item['pcs_per_box'] ?? (int)round($item['quantity'] / $boxCount);
                                                if ($pcsPerBox < 1) $pcsPerBox = 1;
                                            @endphp
                                            <div style="display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; gap: 10px; flex-wrap: wrap;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    @if($prod->foto)
                                                        <img src="{{ asset('storage/'.$prod->foto) }}" alt="{{ $prod->nama_produk }}" style="width: 36px; height: 36px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0;">
                                                    @else
                                                        <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: #64748b;">
                                                            {{ strtoupper(substr($prod->nama_produk, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <strong style="font-size: 0.92rem; color: #0f172a; display: block;">{{ $prod->nama_produk }}</strong>
                                                        <small style="color: #64748b; font-size: 0.78rem;">{{ $prod->umkm->nama_umkm }} · Rp{{ number_format((float)$prod->harga, 0, ',', '.') }}/pcs</small>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <span style="font-size: 12.5px; font-weight: 700; color: #059669; display: block;">
                                                        {{ $pcsPerBox }} pcs / box
                                                    </span>
                                                    <small style="color: #64748b; font-size: 0.75rem;">Total dialokasikan: <b>{{ number_format($item['quantity']) }} unit</b></small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- PENGEMASAN SATU PINTU BANNER -->
                                <div style="margin-top: 18px; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 12px 16px; font-size: 0.85rem; color: #14532d; display: flex; align-items: center; gap: 10px;">
                                    <i class="bi bi-patch-check-fill" style="color: #16a34a; font-size: 20px; flex-shrink: 0;"></i>
                                    <span>Seluruh produk mitra di atas akan disatukan &amp; dikemas rapi ke dalam <strong>{{ $boxCount }} Box berlabel resmi LUDES-MARKET</strong> di Platform Hub. (Hemat 1x ongkir zona &amp; 1x biaya packing kemasan).</span>
                                </div>

                                <div style="margin-top: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 14px;">
                                    @if($kelompok)
                                        <a href="{{ route('keroyokan.show', $kelompok) }}" class="outline-link" style="font-size: 0.88rem; font-weight: 700;">
                                            <i class="bi bi-sliders"></i> Atur Ulang Isi / Jumlah Box
                                        </a>
                                    @else
                                        <a href="{{ route('keroyokan.index') }}" class="outline-link" style="font-size: 0.88rem; font-weight: 700;">
                                            <i class="bi bi-arrow-left"></i> Katalog Keroyokan
                                        </a>
                                    @endif

                                    <button type="button" onclick="document.getElementById('formRemoveKeroyokan').submit()" style="background: none; border: none; color: #dc2626; font-size: 0.88rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                        <i class="bi bi-trash3"></i> Hapus Paket Keroyokan
                                    </button>
                                </div>
                            </article>
                        @endif

                        {{-- 2. TAMPILAN PRODUK REGULER / TAMBAHAN --}}
                        @if($regularItems->isNotEmpty())
                            <div style="background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                                @if(!empty($isKeroyokan) && $keroyokanItems->isNotEmpty())
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                        <span style="font-size: 11px; font-weight: 800; color: #ffffff; background: #166534; padding: 3px 10px; border-radius: 999px; text-transform: uppercase;">
                                            <i class="bi bi-bag-plus-fill"></i> PRODUK REGULER TAMBAHAN
                                        </span>
                                    </div>
                                @endif

                                @foreach($regularItems as $item)
                                    @php($product = $item['product'])
                                    <article class="cart-row" style="padding: 12px 0; display: grid; grid-template-columns: 30px 80px 1fr 100px 90px 110px 30px; gap: 12px; align-items: center;">
                                        <div>
                                            <input type="checkbox" name="selected_products[]" value="{{ $product->id }}" class="cart-selector-item custom-check" checked data-price="{{ $item['line_total'] }}" onchange="recalculateCartSelection()">
                                        </div>
                                        <div class="cart-thumb" style="height: 70px;">
                                            @if($product->foto)
                                                <img src="{{ asset('storage/'.$product->foto) }}" alt="{{ $product->nama_produk }}" style="border-radius: 8px;">
                                            @else
                                                <div class="product-placeholder" style="border-radius: 8px;">
                                                    <span>{{ strtoupper(substr($product->nama_produk, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="cart-product">
                                            <small>{{ $product->umkm->nama_umkm }}</small>
                                            <h3 style="font-size: 15px; margin: 2px 0 4px 0;"><a href="{{ route('products.show', $product) }}">{{ $product->nama_produk }}</a></h3>
                                            <x-stock-badge :product="$product" />
                                        </div>
                                        <div class="cart-price">Rp{{ number_format((float)$product->harga, 0, ',', '.') }}</div>
                                        <label class="cart-qty">
                                            Jumlah
                                            <input type="number" form="regularCartForm" name="jumlah_cart[{{ $product->id }}]" min="0" max="{{ $product->stok_jumlah }}" value="{{ $item['quantity'] }}">
                                        </label>
                                        <div class="cart-total">Rp{{ number_format((float)$item['line_total'], 0, ',', '.') }}</div>
                                        <button class="icon-danger" type="submit" form="remove-{{ $product->id }}" aria-label="Hapus {{ $product->nama_produk }}">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </article>
                                @endforeach

                                <div class="cart-actions" style="margin-top: 16px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                                    <a class="outline-link" href="{{ route('catalogue') }}"><i class="bi bi-plus-lg"></i> Tambah Produk Lain</a>
                                    <button class="outline-link" type="submit" form="regularCartForm"><i class="bi bi-arrow-repeat"></i> Perbarui Jumlah</button>
                                </div>
                            </div>
                        @endif

                    </div>

                    <!-- RINGKASAN KERANJANG (ASIDE) -->
                    <aside class="cart-summary" style="position: static !important; top: auto !important; align-self: flex-start !important;">
                        <span>Ringkasan</span>
                        <h2>Keranjang Anda</h2>
                        
                        <div style="display: flex; flex-direction: column; gap: 8px; margin: 12px 0 16px 0;">
                            @if(!empty($isKeroyokan) && $keroyokanItems->isNotEmpty())
                                <div class="summary-line" id="summaryKeroyokanRow" style="margin: 0;">
                                    <span>Paket Keroyokan</span>
                                    <strong>Rp{{ number_format($keroyokanSubtotal, 0, ',', '.') }}</strong>
                                </div>
                            @endif

                            @if($regularItems->isNotEmpty())
                                <div class="summary-line" id="summaryRegularRow" style="margin: 0;">
                                    <span id="summaryRegularLabel">{{ $regularItems->sum('quantity') }} Produk Reguler</span>
                                    <strong id="summaryRegularPrice">Rp{{ number_format($regularSubtotal, 0, ',', '.') }}</strong>
                                </div>
                            @endif

                            <div class="summary-line" style="border-top: 2px solid var(--line); padding-top: 10px; margin-top: 4px;">
                                <strong style="color: #ffffff;">Subtotal Terpilih</strong>
                                <strong id="displaySelectedSubtotal" style="font-size: 1.25rem; color: #4ade80;">Rp{{ number_format($subtotal, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <p>Harga belum termasuk biaya pengantaran bila ada. Opsi packing dan metode pembayaran dipilih pada langkah checkout.</p>
                        
                        @auth
                            @if(auth()->user()->isBuyer())
                                <button class="button button-dark wide" type="submit" id="btnProceedCheckout" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700;">
                                    Lanjut checkout <i class="bi bi-arrow-right"></i>
                                </button>
                            @else
                                <a class="button button-dark wide" href="{{ route('login') }}">Gunakan akun pembeli</a>
                            @endif
                        @else
                            <a class="button button-dark wide" href="{{ route('login') }}?next=checkout">Masuk untuk checkout <i class="bi bi-arrow-right"></i></a>
                        @endauth

                        <div id="selectionWarning" style="display: none; color: #fca5a5; font-size: 11px; font-weight: 600; text-align: center; margin-top: 8px;">
                            Pilih minimal 1 produk untuk checkout.
                        </div>

                        <a class="button button-light wide back-catalogue-btn" href="{{ route('catalogue') }}"><i class="bi bi-arrow-left"></i> Kembali ke Katalog</a>

                        <button class="clear-cart" type="button" onclick="if(confirm('Kosongkan semua isi keranjang?')) document.getElementById('formClearAll').submit()">Kosongkan semua keranjang</button>
                    </aside>
                </div>
            </form>

            {{-- FORMS DI LUAR UNTUK UPDATE & HAPUS --}}
            <form method="post" action="{{ route('cart.update') }}" id="regularCartForm" style="display:none;">
                @csrf
                @method('PATCH')
            </form>

            <form method="post" action="{{ route('cart.remove-keroyokan') }}" id="formRemoveKeroyokan" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <form method="post" action="{{ route('cart.clear') }}" id="formClearAll" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            @if($regularItems->isNotEmpty())
                @foreach($regularItems as $item)
                    <form id="remove-{{ $item['product']->id }}" method="post" action="{{ route('cart.remove', $item['product']) }}" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        @endif
    </div>
</section>

@push('scripts')
<script>
    function toggleSelectAll(masterEl) {
        const checked = masterEl.checked;
        const checkKeroyokan = document.getElementById('checkKeroyokan');
        if (checkKeroyokan) {
            checkKeroyokan.checked = checked;
        }
        document.querySelectorAll('.cart-selector-item').forEach(el => {
            el.checked = checked;
        });
        recalculateCartSelection();
    }

    function recalculateCartSelection() {
        let total = 0;
        let selectedCount = 0;

        const checkKeroyokan = document.getElementById('checkKeroyokan');
        const summaryKeroyokanRow = document.getElementById('summaryKeroyokanRow');
        if (checkKeroyokan) {
            if (checkKeroyokan.checked) {
                total += parseFloat(checkKeroyokan.dataset.price || 0);
                selectedCount += 1;
                if (summaryKeroyokanRow) summaryKeroyokanRow.style.display = 'flex';
            } else {
                if (summaryKeroyokanRow) summaryKeroyokanRow.style.display = 'none';
            }
        }

        let regularSubtotal = 0;
        let regularCount = 0;
        document.querySelectorAll('.cart-selector-item').forEach(el => {
            if (el.checked) {
                const price = parseFloat(el.dataset.price || 0);
                total += price;
                regularSubtotal += price;
                regularCount += 1;
                selectedCount += 1;
            }
        });

        const summaryRegularRow = document.getElementById('summaryRegularRow');
        const summaryRegularLabel = document.getElementById('summaryRegularLabel');
        const summaryRegularPrice = document.getElementById('summaryRegularPrice');
        if (summaryRegularRow) {
            if (regularCount > 0) {
                summaryRegularRow.style.display = 'flex';
                if (summaryRegularLabel) summaryRegularLabel.innerText = regularCount + ' Produk Reguler';
                if (summaryRegularPrice) summaryRegularPrice.innerText = 'Rp' + regularSubtotal.toLocaleString('id-ID');
            } else {
                summaryRegularRow.style.display = 'none';
            }
        }

        const displaySubtotal = document.getElementById('displaySelectedSubtotal');
        if (displaySubtotal) {
            displaySubtotal.innerText = 'Rp' + total.toLocaleString('id-ID');
        }

        const countText = document.getElementById('selectedCountText');
        if (countText) {
            countText.innerText = selectedCount + ' produk/paket dipilih';
        }

        const btnCheckout = document.getElementById('btnProceedCheckout');
        const warning = document.getElementById('selectionWarning');
        if (btnCheckout) {
            if (selectedCount === 0) {
                btnCheckout.disabled = true;
                btnCheckout.style.opacity = '0.5';
                btnCheckout.style.cursor = 'not-allowed';
                if (warning) warning.style.display = 'block';
            } else {
                btnCheckout.disabled = false;
                btnCheckout.style.opacity = '1';
                btnCheckout.style.cursor = 'pointer';
                if (warning) warning.style.display = 'none';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        recalculateCartSelection();
    });
</script>
@endpush
@endsection



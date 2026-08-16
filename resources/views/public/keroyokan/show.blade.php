@extends('layouts.public')

@section('title', 'Keroyokan — ' . $kelompok->nama_kelompok)

@section('content')
@push('head')
<style>
    .box-builder-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 24px; }
    .box-item-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #f1f5f9; gap: 16px; flex-wrap: wrap; }
    .box-item-row:last-child { border-bottom: none; }
    .qty-stepper { display: inline-flex; align-items: center; border: 1.5px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #fff; }
    .qty-stepper button { width: 36px; height: 36px; border: none; background: #f8fafc; color: #0f172a; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
    .qty-stepper button:hover { background: #e2e8f0; }
    .qty-stepper input { width: 48px; height: 36px; border: none; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; text-align: center; font-weight: 700; font-size: 14px; color: #0f172a; }
    .quick-pill { padding: 6px 14px; border: 1.5px solid #cbd5e1; border-radius: 999px; background: #fff; font-size: 12.5px; font-weight: 700; color: #334155; cursor: pointer; transition: all 0.2s; }
    .quick-pill:hover, .quick-pill.active { background: #059669; color: #fff; border-color: #059669; }
    .alt-card { border: 1.5px solid #fed7aa; background: #fffaf5; border-radius: 12px; padding: 14px 16px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; }
    .keroyokan-sim-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .keroyokan-sim-table { width: 100%; border-collapse: collapse; min-width: 460px; }
</style>
@endpush

<section class="public-hero" style="background:linear-gradient(135deg, var(--green-950), var(--green-900)); color:var(--white); padding: 44px 0;">
    <div class="shell">
        <a href="{{ route('keroyokan.index') }}" style="color:var(--gold); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; margin-bottom:12px;">
            <i class="bi bi-arrow-left"></i> Kembali ke Katalog Keroyokan
        </a>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <span class="eyebrow" style="color:var(--gold); font-weight:700; margin:0;"><i class="bi bi-people-fill"></i> {{ $kelompok->kategori->nama_kategori }}</span>
            <span style="background: rgba(255,255,255,0.15); color: #fff; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">Min. 15 Box</span>
        </div>
        <h1 style="font-size: 2.2rem; margin:0 0 10px; font-family:var(--display);">{{ $kelompok->nama_kelompok }}</h1>
        <p style="opacity:0.9; max-width:680px; margin:0; font-size: 1rem; line-height: 1.5;">
            {{ $kelompok->deskripsi ?: 'Paket pesanan gabungan mitra UMKM desa Moncongloe Lappara dengan pengemasan 1 box terpadu berlabel resmi LUDES-MARKET.' }}
        </p>
    </div>
</section>

<section class="section" style="padding-top: 36px;">
    <div class="shell" style="max-width:960px;">
        <x-flash/>

        @if($totalStock === 0)
            <div style="background:#f8d7da; color:#721c24; padding:20px; border-radius:14px; margin-bottom:24px; border:1px solid #f5c6cb;">
                <h4 style="margin:0 0 6px; font-weight:700;"><i class="bi bi-exclamation-triangle-fill"></i> Stok Kelompok Keroyokan Sedang Habis</h4>
                <p style="margin:0;">Seluruh UMKM anggota untuk kelompok ini sedang kehabisan stok. Pemesanan Keroyokan akan aktif kembali setelah stok diperbarui.</p>
            </div>
        @endif

        {{-- CUSTOM BOX BUILDER FORM --}}
        <form method="post" action="{{ route('keroyokan.simulate', $kelompok) }}" id="boxBuilderForm">
            @csrf

            <!-- STEP 1: JUMLAH BOX -->
            <div class="box-builder-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #059669; letter-spacing: 0.05em; text-transform: uppercase;">LANGKAH 1 DARI 2</span>
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 2px 0 0 0;">Tentukan Jumlah Box Pesanan</h2>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @foreach([15, 25, 50, 100] as $presetBox)
                            <button type="button" class="quick-pill {{ (int)old('jumlah_box', $jumlahBox ?? 15) === $presetBox ? 'active' : '' }}" onclick="document.getElementById('inputJumlahBox').value = {{ $presetBox }}; document.querySelectorAll('.quick-pill').forEach(p=>p.classList.remove('active')); this.classList.add('active'); updateBoxCalculations();">
                                {{ $presetBox }} Box
                            </button>
                        @endforeach
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 220px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">
                            Jumlah Box yang Dibutuhkan (Minimal 15 Box) <span style="color: #dc2626;">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="number" name="jumlah_box" id="inputJumlahBox" value="{{ old('jumlah_box', $jumlahBox ?? 15) }}" min="15" max="10000" required style="width: 100%; padding: 12px 16px; border: 2px solid #cbd5e1; border-radius: 12px; font-size: 1.1rem; font-weight: 800; color: #0f172a;" oninput="updateBoxCalculations()">
                            <span style="font-weight: 700; color: #64748b; font-size: 1rem;">Box</span>
                        </div>
                        <small style="color: #64748b; display: block; margin-top: 4px;">Pemesanan Keroyokan diperuntukkan untuk kebutuhan acara/massal dengan minimal 15 box.</small>
                    </div>
                </div>
            </div>

            <!-- STEP 2: KUSTOMISASI ISI PER BOX -->
            <div class="box-builder-card">
                <div style="margin-bottom: 16px;">
                    <span style="font-size: 11px; font-weight: 800; color: #059669; letter-spacing: 0.05em; text-transform: uppercase;">LANGKAH 2 DARI 2</span>
                    <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 2px 0 0 0;">Atur Isi di Dalam 1 Box (Pcs per Box)</h2>
                    <p style="color: #64748b; font-size: 0.88rem; margin: 4px 0 0 0;">Tentukan jumlah pcs masing-masing produk yang ingin dimasukkan ke dalam setiap box kemasan.</p>
                </div>

                <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 0 16px; background: #fff;">
                    @foreach($allProducts as $p)
                        @php
                            $currentPcs = (int) (old("box_items.{$p->id}", $boxItems[$p->id] ?? 1));
                            $pAvailable = ($p->stok_status !== 'Habis' && $p->stok_jumlah > 0);
                        @endphp
                        <div class="box-item-row" data-product-id="{{ $p->id }}" data-price="{{ $p->harga }}">
                            <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 220px;">
                                @if($p->foto)
                                    <img src="{{ asset('storage/' . $p->foto) }}" alt="{{ $p->nama_produk }}" style="width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                @else
                                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #94a3b8; flex-shrink: 0;">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                @endif
                                <div>
                                    <strong style="font-size: 0.98rem; color: #0f172a; display: block;">{{ $p->nama_produk }}</strong>
                                    <small style="color: #64748b; font-size: 0.82rem;">{{ $p->umkm->nama_umkm }} · <b>Rp{{ number_format((float)$p->harga, 0, ',', '.') }}</b> / pcs</small>
                                    <div style="margin-top: 2px;">
                                        @if($pAvailable)
                                            <span style="font-size: 11px; font-weight: 700; color: #166534; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">Stok: {{ number_format($p->stok_jumlah) }} unit</span>
                                        @else
                                            <span style="font-size: 11px; font-weight: 700; color: #991b1b; background: #fee2e2; padding: 2px 8px; border-radius: 6px;">Stok Habis</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="text-align: right; margin-right: 4px;">
                                    <span style="display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Porsi Box</span>
                                    <span style="font-size: 13px; font-weight: 700; color: #059669;" id="subtotalItemBox_{{ $p->id }}">Rp{{ number_format((float)$p->harga * $currentPcs, 0, ',', '.') }}</span>
                                </div>
                                <div class="qty-stepper">
                                    <button type="button" onclick="changeItemPcs({{ $p->id }}, -1)">−</button>
                                    <input type="number" name="box_items[{{ $p->id }}]" id="itemQty_{{ $p->id }}" value="{{ $currentPcs }}" min="0" max="50" oninput="updateBoxCalculations()" readonly>
                                    <button type="button" onclick="changeItemPcs({{ $p->id }}, 1)">+</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- LIVE ESTIMATION SUMMARY CARD --}}
                <div style="margin-top: 18px; padding: 16px 20px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: center;">
                    <div>
                        <small style="color: #64748b; display: block; font-size: 11px; font-weight: 700; text-transform: uppercase;">Total Isi per 1 Box</small>
                        <strong id="displayTotalPcsInBox" style="font-size: 1.15rem; color: #0f172a;">— pcs kue/item</strong>
                    </div>
                    <div>
                        <small style="color: #64748b; display: block; font-size: 11px; font-weight: 700; text-transform: uppercase;">Estimasi Harga per Box</small>
                        <strong id="displayPricePerBox" style="font-size: 1.15rem; color: #059669;">Rp0</strong>
                    </div>
                    <div>
                        <small style="color: #64748b; display: block; font-size: 11px; font-weight: 700; text-transform: uppercase;">Total Estimasi Subtotal</small>
                        <strong id="displayTotalEstimate" style="font-size: 1.25rem; color: #15803d;">Rp0</strong>
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="button" style="padding: 14px 28px; font-weight: 700; font-size: 1rem; width: 100%; justify-content: center;">
                        <i class="bi bi-calculator-fill"></i> Hitung &amp; Cek Ketersediaan Stok Keroyokan
                    </button>
                </div>
            </div>
        </form>

        {{-- SIMULATION / ALLOCATION RESULTS --}}
        @if(isset($simulation))
            <div style="background:#fff; border: 2px solid {{ $simulation['status'] === 'success' ? 'var(--green-700)' : '#f97316' }}; border-radius: 16px; padding: 28px; box-shadow: 0 6px 18px rgba(0,0,0,0.04); margin-bottom: 36px;">
                
                {{-- CASE 1: HAS SHORTAGE & SMART PRODUCT ALTERNATIVES --}}
                @if($simulation['status'] === 'has_shortage')
                    <div style="margin-bottom: 24px;">
                        <div style="display: flex; align-items: flex-start; gap: 14px; background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 18px 20px; color: #9a3412;">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; color: #ea580c; flex-shrink: 0; margin-top: 2px;"></i>
                            <div>
                                <h3 style="margin: 0 0 4px; font-size: 1.1rem; font-weight: 800; color: #9a3412;">Stok Beberapa Produk Belum Mencukupi untuk {{ $simulation['jumlah_box'] }} Box</h3>
                                <p style="margin: 0; font-size: 0.9rem; color: #c2410c;">Sistem LUDES-MARKET telah menyiapkan <strong>Rekomendasi Produk Alternatif</strong> dari mitra UMKM lain agar pesanan Anda tetap lengkap 100%.</p>
                            </div>
                        </div>

                        <!-- SHORTAGE DETAILS & ALTERNATIVE SUGGESTIONS -->
                        <div style="margin-top: 20px;">
                            @foreach($simulation['shortages'] as $shortage)
                                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                        <div>
                                            <strong style="font-size: 1rem; color: #0f172a;">{{ $shortage['product']->nama_produk }} ({{ $shortage['product']->umkm->nama_umkm }})</strong>
                                            <div style="color: #64748b; font-size: 0.85rem; margin-top: 2px;">
                                                Kebutuhan: <b>{{ $shortage['required'] }} unit</b> · Stok Toko: <b>{{ $shortage['available'] }} unit</b> · <span style="color: #dc2626; font-weight: 700;">Kurang {{ $shortage['shortage'] }} unit</span>
                                            </div>
                                        </div>
                                        <span style="font-size: 12px; font-weight: 800; color: #ea580c; background: #ffedd5; padding: 4px 10px; border-radius: 999px;">
                                            Perlu Alternatif
                                        </span>
                                    </div>

                                    @if(!empty($shortage['alternatives']) && $shortage['alternatives']->count() > 0)
                                        <div style="margin-top: 14px;">
                                            <small style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">
                                                <i class="bi bi-lightbulb-fill" style="color: #f59e0b;"></i> Rekomendasi Produk Alternatif Pengganti:
                                            </small>

                                            @foreach($shortage['alternatives'] as $alt)
                                                <div class="alt-card">
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <i class="bi bi-patch-check-fill" style="color: #16a34a; font-size: 20px;"></i>
                                                        <div>
                                                            <strong style="font-size: 0.95rem; color: #0f172a;">{{ $alt->nama_produk }}</strong>
                                                            <small style="display: block; color: #64748b;">{{ $alt->umkm->nama_umkm }} · Rp{{ number_format((float)$alt->harga, 0, ',', '.') }}/pcs · <b>Stok: {{ number_format($alt->stok_jumlah) }} unit</b></small>
                                                        </div>
                                                    </div>

                                                    <form method="post" action="{{ route('keroyokan.simulate', $kelompok) }}" style="margin: 0;">
                                                        @csrf
                                                        <input type="hidden" name="jumlah_box" value="{{ $simulation['jumlah_box'] }}">
                                                        @foreach($simulation['box_items'] as $pId => $qty)
                                                            <input type="hidden" name="box_items[{{ $pId }}]" value="{{ $qty }}">
                                                        @endforeach
                                                        @foreach($substitutions as $origId => $subId)
                                                            <input type="hidden" name="substitutions[{{ $origId }}]" value="{{ $subId }}">
                                                        @endforeach
                                                        <input type="hidden" name="substitutions[{{ $shortage['product_id'] }}]" value="{{ $alt->id }}">
                                                        <button type="submit" class="button" style="padding: 8px 16px; font-size: 12.5px; font-weight: 700; background: #059669;">
                                                            <i class="bi bi-arrow-repeat"></i> Gunakan Produk Alternatif Ini
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div style="margin-top: 10px; color: #94a3b8; font-size: 12px; font-style: italic;">
                                            Belum ada produk alternatif pengganti yang memiliki stok mencukupi saat ini.
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                {{-- CASE 2: SUCCESS FULL ALLOCATION --}}
                @elseif($simulation['status'] === 'success')
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 16px;">
                            <div>
                                <span style="font-size: 11px; font-weight: 800; color: #15803d; background: #dcfce7; padding: 4px 10px; border-radius: 999px; text-transform: uppercase;">
                                    ✓ 100% SIAP DIPESAN
                                </span>
                                <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 8px 0 0 0;">Rencana Alokasi Paket Keroyokan ({{ $simulation['jumlah_box'] }} Box)</h2>
                            </div>
                            <div style="text-align: right;">
                                <small style="display: block; color: #64748b; font-size: 11px; font-weight: 700;">TOTAL TAGIHAN PRODUK</small>
                                <strong style="font-size: 1.6rem; color: #15803d;">Rp{{ number_format($simulation['grand_total'], 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <!-- ALLOCATION TABLE -->
                        <div class="keroyokan-sim-table-wrap" style="background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                            <table class="keroyokan-sim-table">
                                <thead>
                                    <tr style="border-bottom: 2px solid #cbd5e1; text-align: left; font-size: 12px; color: #475569;">
                                        <th style="padding: 8px 0;">Produk &amp; UMKM Mitra</th>
                                        <th style="padding: 8px 0; text-align: center;">Porsi / Box</th>
                                        <th style="padding: 8px 0; text-align: center;">Total Unit</th>
                                        <th style="padding: 8px 0; text-align: right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($simulation['allocations'] as $alloc)
                                        <tr style="border-bottom: 1px solid #e2e8f0; font-size: 13.5px;">
                                            <td style="padding: 10px 0;">
                                                <strong style="color: #0f172a;">{{ $alloc['product']->nama_produk }}</strong>
                                                @if(!empty($alloc['is_substitution']))
                                                    <span style="font-size: 10.5px; font-weight: 700; color: #b45309; background: #fef3c7; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">
                                                        Alternatif Pengganti
                                                    </span>
                                                @endif
                                                <small style="display: block; color: #64748b;">{{ $alloc['product']->umkm->nama_umkm }} (Rp{{ number_format($alloc['unit_price'], 0, ',', '.') }}/unit)</small>
                                            </td>
                                            <td style="padding: 10px 0; text-align: center;">
                                                <b>{{ $alloc['pcs_per_box'] }} pcs</b>
                                            </td>
                                            <td style="padding: 10px 0; text-align: center;">
                                                <strong style="color: #059669;">{{ number_format($alloc['quantity']) }} pcs</strong>
                                            </td>
                                            <td style="padding: 10px 0; text-align: right;">
                                                <strong style="color: #0f172a;">Rp{{ number_format($alloc['line_total'], 0, ',', '.') }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; font-size: 0.88rem; color: #14532d;">
                            <i class="bi bi-box-seam-fill" style="color: #16a34a; font-size: 18px;"></i>
                            <strong>Pengemasan Satu Pintu:</strong> Seluruh {{ number_format($simulation['target_quantity']) }} unit makanan akan disatukan dan dikemas rapi ke dalam <strong>{{ $simulation['jumlah_box'] }} Box berlabel resmi LUDES-MARKET</strong> (Hemat 1x ongkir zona &amp; 1x biaya kemasan box).
                        </div>

                        <!-- ACTION CONFIRM BUTTON -->
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                            <div>
                                <small style="color: #64748b; font-size: 12px; display: block;">Mitra yang berkontribusi:</small>
                                <strong style="color: #0f172a; font-size: 0.95rem;">{{ $simulation['distinct_umkms_count'] }} UMKM Desa</strong>
                            </div>

                            @auth
                                @if(auth()->user()->isBuyer())
                                    <form method="post" action="{{ route('keroyokan.confirm', $kelompok) }}">
                                        @csrf
                                        <input type="hidden" name="jumlah_box" value="{{ $simulation['jumlah_box'] }}">
                                        @foreach($simulation['box_items'] as $pId => $qty)
                                            <input type="hidden" name="box_items[{{ $pId }}]" value="{{ $qty }}">
                                        @endforeach
                                        @foreach($substitutions as $origId => $subId)
                                            <input type="hidden" name="substitutions[{{ $origId }}]" value="{{ $subId }}">
                                        @endforeach
                                        <button type="submit" class="button" style="padding: 14px 28px; font-size: 1rem; font-weight: 700;">
                                            <i class="bi bi-cart-check-fill"></i> Lanjutkan ke Keranjang &amp; Checkout &rarr;
                                        </button>
                                    </form>
                                @else
                                    <p style="color:var(--muted); margin:0;">Silakan login sebagai akun <strong>Pembeli</strong> untuk memesan.</p>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="button">
                                    <i class="bi bi-box-arrow-in-right"></i> Login Pembeli untuk Memesan
                                </a>
                            @endauth
                        </div>
                    </div>

                {{-- OTHER ERROR STATES --}}
                @else
                    <div style="background:#fee2e2; color:#991b1b; padding:18px 20px; border-radius:12px;">
                        <h4 style="margin: 0 0 6px; font-weight: 800;"><i class="bi bi-exclamation-octagon-fill"></i> Tidak Dapat Memproses Alokasi</h4>
                        <p style="margin: 0;">{{ $simulation['message'] ?? 'Terjadi kendala saat menghitung alokasi Keroyokan.' }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
function changeItemPcs(productId, delta) {
    const input = document.getElementById('itemQty_' + productId);
    if (!input) return;
    let val = parseInt(input.value) || 0;
    val = Math.max(0, val + delta);
    input.value = val;
    updateBoxCalculations();
}

function updateBoxCalculations() {
    const jumlahBoxInput = document.getElementById('inputJumlahBox');
    const jumlahBox = Math.max(15, parseInt(jumlahBoxInput ? jumlahBoxInput.value : 15) || 15);

    let totalPcsInBox = 0;
    let pricePerBox = 0;

    document.querySelectorAll('.box-item-row').forEach(row => {
        const pId = row.dataset.productId;
        const price = parseFloat(row.dataset.price || 0);
        const input = document.getElementById('itemQty_' + pId);
        const pcs = parseInt(input ? input.value : 0) || 0;

        totalPcsInBox += pcs;
        const itemSubtotal = pcs * price;
        pricePerBox += itemSubtotal;

        const subtotalSpan = document.getElementById('subtotalItemBox_' + pId);
        if (subtotalSpan) {
            subtotalSpan.textContent = 'Rp' + itemSubtotal.toLocaleString('id-ID');
        }
    });

    const totalEstimate = jumlahBox * pricePerBox;

    const displayPcs = document.getElementById('displayTotalPcsInBox');
    if (displayPcs) displayPcs.textContent = totalPcsInBox + ' pcs / box';

    const displayPrice = document.getElementById('displayPricePerBox');
    if (displayPrice) displayPrice.textContent = 'Rp' + pricePerBox.toLocaleString('id-ID');

    const displayTotal = document.getElementById('displayTotalEstimate');
    if (displayTotal) displayTotal.textContent = 'Rp' + totalEstimate.toLocaleString('id-ID') + ' (' + jumlahBox + ' Box)';
}

document.addEventListener('DOMContentLoaded', function() {
    updateBoxCalculations();
});
</script>
@endpush

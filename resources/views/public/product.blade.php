@extends('layouts.public')

@section('title', $produk->nama_produk . ' — LUDES-MARKET')

@section('content')
<section class="product-detail-section">
    <div class="shell">
        <x-flash />
        <div class="breadcrumbs">
            <a href="{{ route('home') }}">Beranda</a>
            <span>/</span>
            <a href="{{ route('catalogue') }}">Katalog</a>
            <span>/</span>
            <span>{{ $produk->nama_produk }}</span>
        </div>
        <div class="product-detail-grid">
            <div class="product-detail-media">
                @if($produk->foto)
                    <img src="{{ asset('storage/'.$produk->foto) }}" alt="{{ $produk->nama_produk }}">
                @else
                    <div class="product-placeholder detail-placeholder">
                        <span>{{ strtoupper(substr($produk->nama_produk, 0, 1)) }}</span>
                        <small>Produk Lokal LUDES-MARKET</small>
                    </div>
                @endif
            </div>

            <div class="product-detail-info">
                <div class="eyebrow"><span></span>{{ $produk->kategori->nama_kategori }}</div>
                <h1>{{ $produk->nama_produk }}</h1>
                <p class="seller-line">Dibuat oleh <a href="{{ route('catalogue', ['q' => $produk->umkm->nama_umkm]) }}">{{ $produk->umkm->nama_umkm }}</a></p>
                
                <div class="rating-line">
                    @if($produk->ulasan_count)
                        <span><i class="bi bi-star-fill"></i> {{ number_format((float)$produk->ulasan_avg_rating, 1) }}</span>
                        <span>{{ $produk->ulasan_count }} ulasan</span>
                    @else
                        <span>Belum ada ulasan</span>
                    @endif
                </div>

                <div class="price-line">Rp{{ number_format((float)$produk->harga, 0, ',', '.') }}</div>
                <x-stock-badge :product="$produk" />
                @if($produk->stok_status === 'Pre-Order')
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 10px; padding: 12px 16px; margin: 16px 0; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-clock-history" style="font-size: 1.25rem; color: #3b82f6;"></i>
                        <div>
                            <strong>Produk Pre-Order:</strong> Estimasi pengerjaan & pengiriman <strong>{{ $produk->estimasi_po_hari ? $produk->estimasi_po_hari . ' Hari Kerja' : 'sesuai jadwal penjual' }}</strong> setelah pemesanan.
                        </div>
                    </div>
                @endif
                <p class="product-description">{{ $produk->deskripsi }}</p>

                @if($produk->isAvailable())
                    <form class="buy-box" action="{{ route('cart.add', $produk) }}" method="post" style="display: flex; flex-direction: column; gap: 16px;">
                        @csrf
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <label style="margin: 0; min-width: 110px;">
                                Jumlah
                                <input type="number" name="jumlah" min="1" max="{{ $produk->stok_jumlah }}" value="1">
                            </label>
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; width: 100%;">
                            <button class="button button-dark" type="submit" style="flex: 1; min-width: 170px; justify-content: center;"><i class="bi bi-bag-plus"></i> + Keranjang</button>
                            <button class="button" type="submit" name="direct_checkout" value="1" style="flex: 1; min-width: 170px; justify-content: center; background: var(--gold); color: var(--green-950);"><i class="bi bi-lightning-fill"></i> Pesan Langsung</button>
                        </div>
                    </form>
                @else
                    <div class="unavailable-note">Produk sedang tidak tersedia. Cek kembali katalog untuk pilihan lain.</div>
                @endif

                <a href="{{ route('umkm.show', $produk->umkm) }}" class="seller-card" style="text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <div class="seller-avatar">{{ strtoupper(substr($produk->umkm->nama_umkm, 0, 1)) }}</div>
                    <div>
                        <small style="color: #059669; font-weight: 700;">Mitra UMKM Moncongloe Lappara</small>
                        <strong>{{ $produk->umkm->nama_umkm }}</strong>
                        <p style="margin: 2px 0 0 0;">{{ $produk->umkm->alamat }}</p>
                        <span style="font-size: 11px; font-weight: 700; color: #059669; display: inline-flex; align-items: center; gap: 4px; margin-top: 4px;">Kunjungi Toko <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="section reviews-section">
    <div class="shell">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span>Pengalaman Pembeli</div>
                <h2>Ulasan & Rating Produk</h2>
            </div>
        </div>

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <!-- 1. Summary Rating Box (ON TOP - Full Width) -->
            <div style="background: #f9fafb; padding: 24px 28px; border-radius: 16px; border: 1px solid #f3f4f6; margin-bottom: 32px; display: flex; gap: 36px; align-items: center; flex-wrap: wrap;">
                <!-- Left: Big Score & Stars -->
                <div style="text-align: center; padding-right: 32px; border-right: 1px solid #e5e7eb; min-width: 140px;">
                    <div style="font-size: 3.2rem; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($avgRating, 1) }}</div>
                    <div style="color: #f59e0b; font-size: 1.25rem; margin: 6px 0;" class="rating-display">
                        @for($i=1; $i<=5; $i++)
                            <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }}"></i>
                        @endfor
                    </div>
                    <small style="color: #6b7280; font-weight: 600; font-size: 0.88rem;">{{ $totalUlasan }} ulasan</small>
                </div>

                <!-- Right: Star Distribution Progress Bars -->
                <div style="flex: 1; min-width: 240px; display: flex; flex-direction: column; gap: 8px;">
                    @foreach([5, 4, 3, 2, 1] as $star)
                        @php $d = $ratingDistribusi[$star] ?? ['pct' => 0, 'count' => 0]; @endphp
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 13px; color: #4b5563;">
                            <span style="width: 16px; font-weight: 700; text-align: right;">{{ $star }}</span>
                            <i class="bi bi-star-fill" style="color: #f59e0b; font-size: 12px;"></i>
                            <div style="flex: 1; height: 10px; background: #e5e7eb; border-radius: 999px; overflow: hidden;">
                                <div style="width: {{ $d['pct'] }}%; height: 100%; background: #f59e0b; border-radius: 999px;"></div>
                            </div>
                            <span style="width: 40px; text-align: right; color: #9ca3af; font-size: 12px; font-weight: 600;">{{ $d['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 2. Review Cards Grid (BELOW RATING BOX) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                @forelse($produk->ulasan as $review)
                    <article style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 14px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <strong style="font-size: 0.95rem; color: #111827;">{{ $review->pembeli->nama_lengkap }}</strong>
                                <div style="color: #f59e0b; font-size: 0.85rem;" class="rating-display">
                                    @for($i=1; $i<=5; $i++)
                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <small style="color: #6b7280; display: block; margin-bottom: 8px; font-size: 0.78rem;">{{ $review->created_at->translatedFormat('d M Y') }}</small>
                            <p style="color: #4b5563; font-size: 0.9rem; margin: 0; line-height: 1.55;">"{{ $review->komentar ?: 'Pembeli memberi penilaian tanpa komentar.' }}"</p>
                        </div>
                    </article>
                @empty
                    <p style="color: #9ca3af; font-style: italic; grid-column: 1 / -1;">Belum ada ulasan untuk produk ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>

@if($related->isNotEmpty())
    <section class="section section-muted">
        <div class="shell">
            <div class="section-heading">
                <div>
                    <div class="eyebrow"><span></span>Masih satu kategori</div>
                    <h2>Produk lain yang mungkin cocok.</h2>
                </div>
            </div>
            <div class="product-grid compact-grid">
                @foreach($related as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection

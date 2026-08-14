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
                <p class="product-description">{{ $produk->deskripsi }}</p>

                @if($produk->isAvailable())
                    <form class="buy-box" action="{{ route('cart.add', $produk) }}" method="post">
                        @csrf
                        <label>
                            Jumlah
                            <input type="number" name="jumlah" min="1" max="{{ $produk->stok_jumlah }}" value="1">
                        </label>
                        <button class="button button-dark" type="submit"><i class="bi bi-bag-plus"></i> Tambah ke Keranjang</button>
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
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 36px;">
                <!-- Summary Rating & Progress Bars -->
                <div style="display: flex; gap: 24px; align-items: center; background: #f9fafb; padding: 24px; border-radius: 16px; border: 1px solid #f3f4f6; align-self: start;">
                    <div style="text-align: center; padding-right: 24px; border-right: 1px solid #e5e7eb; min-width: 110px;">
                        <div style="font-size: 2.8rem; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($avgRating, 1) }}</div>
                        <div style="color: #f59e0b; font-size: 1.1rem; margin: 6px 0;">
                            @for($i=1; $i<=5; $i++)
                                <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }}"></i>
                            @endfor
                        </div>
                        <small style="color: #6b7280; font-weight: 600;">{{ $totalUlasan }} ulasan</small>
                    </div>

                    <!-- Star Distribution Progress Bars -->
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                        @foreach([5, 4, 3, 2, 1] as $star)
                            @php $d = $ratingDistribusi[$star] ?? ['pct' => 0, 'count' => 0]; @endphp
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #4b5563;">
                                <span style="width: 14px; font-weight: 700;">{{ $star }}</span>
                                <i class="bi bi-star-fill" style="color: #f59e0b; font-size: 11px;"></i>
                                <div style="flex: 1; height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden;">
                                    <div style="width: {{ $d['pct'] }}%; height: 100%; background: #f59e0b; border-radius: 999px;"></div>
                                </div>
                                <span style="width: 32px; text-align: right; color: #9ca3af; font-size: 11px;">{{ $d['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Review Cards List -->
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    @forelse($produk->ulasan as $review)
                        <article style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 14px; padding: 18px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <strong style="font-size: 0.95rem; color: #111827;">{{ $review->pembeli->nama_lengkap }}</strong>
                                <div style="color: #f59e0b; font-size: 0.85rem;">
                                    @for($i=1; $i<=5; $i++)
                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <small style="color: #6b7280; display: block; margin-bottom: 8px; font-size: 0.78rem;">{{ $review->created_at->translatedFormat('d M Y') }}</small>
                            <p style="color: #4b5563; font-size: 0.9rem; margin: 0; line-height: 1.5;">"{{ $review->komentar ?: 'Pembeli memberi penilaian tanpa komentar.' }}"</p>
                        </article>
                    @empty
                        <div style="text-align: center; padding: 32px; background: #f9fafb; border-radius: 14px; color: #9ca3af; font-style: italic;">
                            Belum ada ulasan untuk produk ini.
                        </div>
                    @endforelse
                </div>
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

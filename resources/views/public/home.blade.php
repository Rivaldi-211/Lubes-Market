@extends('layouts.public')

@section('title', 'LUDES-MARKET — Produk Lokal Moncongloe Lappara')
@section('body_class', 'home-page')

@section('content')
<section class="hero-home">
    <div class="hero-photo" aria-hidden="true"></div>
    <div class="hero-shade" aria-hidden="true"></div>
    <div class="shell hero-content">
        <div class="eyebrow light"><span></span> Dari desa, untuk lebih banyak meja</div>
        <h1>Produk lokal yang<br><em>punya cerita.</em></h1>
        <p>Temukan kuliner, oleh-oleh, dan kerajinan dari UMKM Moncongloe Lappara — dikumpulkan dalam satu katalog yang mudah diakses.</p>
        <div class="hero-actions">
            <a class="button button-light" href="{{ route('catalogue') }}">Jelajahi Katalog <i class="bi bi-arrow-right"></i></a>
            <a class="text-link light" href="#tentang">Kenal LUDES-MARKET <i class="bi bi-arrow-down"></i></a>
        </div>
    </div>
    <div class="hero-facts shell">
        <div><strong>{{ $totalProducts }}</strong><span>produk tercatat</span></div>
        <div><strong>{{ $totalUmkm }}</strong><span>mitra UMKM aktif</span></div>
        <div class="hero-note">Dikelola bersama untuk memperluas jangkauan usaha warga.</div>
    </div>
</section>

<section class="section intro-section" id="tentang">
    <div class="shell intro-grid">
        <div>
            <div class="eyebrow"><span></span>LUDES-MARKET</div>
            <h2>Bukan sekadar etalase.<br>Ini jalan masuk ke usaha warga.</h2>
        </div>
        <div class="intro-copy">
            <p>LUDES-MARKET membantu produk-produk warga Moncongloe Lappara hadir lebih rapi, mudah ditemukan, dan lebih dekat dengan pembeli.</p>
            <p>Katalog ini mempertahankan karakter tiap UMKM: siapa pembuatnya, apa produknya, bagaimana stoknya, dan bagaimana pembeli bisa memesan.</p>
            <a class="text-link" href="{{ route('catalogue') }}">Lihat semua produk <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</section>

@if(isset($topProduct) && $topProduct)
<section class="section top-seller-section" style="background: #faf8f5; border-top: 1px solid #ece7de; border-bottom: 1px solid #ece7de; padding: 64px 0;">
    <div class="shell">
        <div style="background: #ffffff; border: 1px solid #e6e1d6; border-radius: 20px; padding: 36px; box-shadow: 0 8px 30px rgba(24, 46, 33, 0.04);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px; align-items: center;">
                <div style="position: relative;">
                    @if($topProduct->foto)
                        <img src="{{ asset('storage/' . $topProduct->foto) }}" alt="{{ $topProduct->nama_produk }}" style="width: 100%; max-height: 280px; object-fit: cover; border-radius: 14px; border: 1px solid #e8e3d8;">
                    @else
                        <div style="width: 100%; height: 260px; background: #f5f1e7; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #205037; border: 1px solid #e8e3d8;">
                            <i class="bi bi-bag"></i>
                        </div>
                    @endif
                    <div style="position: absolute; top: 12px; left: 12px; background: #ffffff; color: #205037; padding: 5px 14px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; border: 1px solid #d9c9ac; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="bi bi-trophy-fill" style="color: #c79b42;"></i> PRODUK TERLARIS NO. 1 DESA
                    </div>
                </div>
                <div>
                    <div class="eyebrow" style="margin-bottom: 8px;"><span></span> Pilihan Utama Warga</div>
                    <h2 style="font-family: 'Manrope', sans-serif; font-size: 1.85rem; font-weight: 700; margin-bottom: 12px; color: #173d2b; line-height: 1.2;">{{ $topProduct->nama_produk }}</h2>
                    <p style="color: #6e736c; font-size: 0.95rem; margin-bottom: 24px; line-height: 1.6;">
                        {{ Str::limit($topProduct->deskripsi, 150) }}
                    </p>
                    <div style="display: flex; gap: 28px; margin-bottom: 28px; padding: 16px 0; border-top: 1px solid #ece7de; border-bottom: 1px solid #ece7de; flex-wrap: wrap;">
                        <div>
                            <span style="font-size: 0.78rem; color: #6e736c; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Mitra UMKM</span>
                            <strong style="color: #173d2b; font-weight: 600; font-size: 0.95rem;">{{ $topProduct->umkm->nama_umkm ?? '-' }}</strong>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: #6e736c; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Total Terjual</span>
                            <strong style="color: #205037; font-weight: 700; font-size: 1.05rem;">
                                {{ number_format($topProduct->total_terjual ?? 0, 0, ',', '.') }} porsi
                            </strong>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: #6e736c; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Harga</span>
                            <strong style="color: #173d2b; font-weight: 700; font-size: 1.05rem;">
                                Rp{{ number_format($topProduct->harga, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('products.show', $topProduct->id) }}" class="button">
                            Lihat Detail & Beli <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<section class="section section-muted">
    <div class="shell">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span>Pilihan terbaru</div>
                <h2>Belanja dari yang<br>memang dibuat di sini.</h2>
            </div>
            <a class="outline-link" href="{{ route('catalogue') }}">Semua produk <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="product-grid">
            @forelse($featured as $product)
                <x-product-card :product="$product" />
            @empty
                <p>Belum ada produk.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="section categories-section">
    <div class="shell category-layout">
        <div class="category-copy">
            <div class="eyebrow"><span></span>Cari berdasarkan kebutuhan</div>
            <h2>Tiga kelompok produk, satu desa yang aktif berkarya.</h2>
            <p>Dari camilan hangat di Moncongloe Lappara sampai kerajinan yang dikerjakan dengan tangan.</p>
        </div>
        <div class="category-list">
            @foreach($categories as $i => $category)
                <a href="{{ route('catalogue', ['kategori' => $category->id]) }}">
                    <span class="cat-index">0{{ $i + 1 }}</span>
                    <span>
                        <strong>{{ $category->nama_kategori }}</strong>
                        <small>{{ $category->produk_count }} produk</small>
                    </span>
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section producers-section" id="mitra">
    <div class="shell">
        <div class="section-heading light">
            <div>
                <div class="eyebrow light"><span></span>Wajah di balik produk</div>
                <h2>Mitra UMKM yang<br>menghidupkan katalog.</h2>
            </div>
            <p>Setiap produk terhubung langsung dengan usaha yang memproduksinya.</p>
        </div>
        <div class="producer-grid">
            @foreach($producers as $producer)
                <article>
                    <div class="producer-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                    <h3>{{ $producer->nama_umkm }}</h3>
                    <p>{{ Str::limit($producer->deskripsi, 110) }}</p>
                    <div>
                        <span>{{ $producer->pemilik }}</span>
                        <span>{{ $producer->produk_count }} produk</span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section location-section">
    <div class="shell location-grid">
        <div class="location-card">
            <div class="eyebrow" style="color: #173322; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;"><span style="background: #173322;"></span>TEMUKAN KAMI</div>
            <h2 style="color: #173322; margin-top: 14px; margin-bottom: 20px; font-weight: 700; line-height: 1.05;">Moncongloe<br>Lappara,<br>Maros.</h2>
            <p style="color: #1c3826; font-size: 1.02rem; margin-bottom: 36px; line-height: 1.6;">Kawasan Kuliner Moncongloe Lappara menjadi salah satu titik aktivitas UMKM dan kuliner warga.</p>
            <div>
                <a href="https://maps.google.com/?q=Moncongloe+Lappara+Maros" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; color: #173322; padding: 14px 28px; border-radius: 999px; font-weight: 700; font-size: 0.95rem; box-shadow: 0 4px 14px rgba(0,0,0,0.06); transition: all 0.2s ease;">
                    Buka Google Maps <i class="bi bi-geo-alt" style="font-size: 1.05rem;"></i>
                </a>
            </div>
        </div>
        <div class="map-art" aria-label="Peta lokasi Moncongloe Lappara">
            <iframe 
                src="https://maps.google.com/maps?q=Moncongloe+Lappara+Maros&t=&z=14&ie=UTF8&iwloc=&output=embed" 
                width="100%" 
                height="100%" 
                style="border:0;width:100%;height:100%;min-height:480px;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade" 
                title="Peta Lokasi Moncongloe Lappara">
            </iframe>
        </div>
    </div>
</section>
@endsection

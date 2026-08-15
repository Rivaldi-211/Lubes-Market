@extends('layouts.public')

@section('title', 'LUDES-MARKET — Produk Lokal Moncongloe Lappara')
@section('body_class', 'home-page')

@section('content')
<section class="hero-home" id="beranda">
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
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div>
                <div class="eyebrow"><span></span>LUDES-MARKET</div>
                <h2>Bukan sekadar etalase.<br>Ini jalan masuk ke usaha warga.</h2>
            </div>
        </div>
        <div class="intro-copy">
            <p>LUDES-MARKET membantu produk-produk warga Moncongloe Lappara hadir lebih rapi, mudah ditemukan, dan lebih dekat dengan pembeli.</p>
            <p>Katalog ini mempertahankan karakter tiap UMKM: siapa pembuatnya, apa produknya, bagaimana stoknya, dan bagaimana pembeli bisa memesan.</p>
            <a class="text-link" href="{{ route('catalogue') }}">Lihat semua produk <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="section keroyokan-cta-section" style="background: linear-gradient(135deg, var(--green-950), var(--green-900)); color: var(--white); padding: 56px 0;">
    <div class="shell" style="display: flex; justify-content: space-between; align-items: center; gap: 32px; flex-wrap: wrap;">
        <div style="max-width: 620px;">
            <div class="eyebrow light" style="color: var(--gold); margin-bottom: 8px;"><span></span>PROGRAM UNGGULAN DESA</div>
            <h2 style="font-family: var(--display); font-size: 2.2rem; margin: 0 0 12px; color: var(--white);">LUDES KEROYOKAN</h2>
            <p style="font-size: 1.15rem; margin: 0 0 12px; color: var(--gold); font-weight: 600;">Satu Pesanan. Banyak UMKM. Satu Kekuatan Desa.</p>
            <p style="opacity: 0.85; margin: 0; line-height: 1.6;">
                Butuh produk dalam jumlah besar? Beberapa UMKM Moncongloe dapat memenuhi kebutuhan Anda bersama.
            </p>
        </div>
        <div>
            <a href="{{ route('keroyokan.index') }}" class="button button-light" style="padding: 16px 32px; font-size: 1.05rem;">
                COBA LUDES KEROYOKAN <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>


@if(isset($topPerKategori) && $topPerKategori->isNotEmpty())
<section class="section" style="padding: 64px 0; background: #fff;">
    <div class="shell">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span>Terfavorit di Tiap Kategori</div>
                <h2>Produk terlaris<br>dari setiap kelompok.</h2>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
            @foreach($topPerKategori as $kategori)
                @if($kategori->top_produk)
                    @php $p = $kategori->top_produk; @endphp
                    <div style="border: 1px solid #e6e1d6; border-radius: 16px; overflow: hidden; background: #faf8f5; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="padding: 12px 16px; background: var(--green-950); color: #fff; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.5px;">
                                🏆 TERLARIS — {{ strtoupper($kategori->nama_kategori) }}
                            </div>
                            @if($p->foto)
                                <img src="{{ asset('storage/' . $p->foto) }}" alt="{{ $p->nama_produk }}"
                                     style="width:100%; height:180px; object-fit:cover;">
                            @else
                                <div style="width:100%; height:180px; background:#f5f1e7; display:flex; align-items:center; justify-content:center; font-size:2.5rem; color:#205037;">
                                    <i class="bi bi-bag"></i>
                                </div>
                            @endif
                            <div style="padding: 16px;">
                                <p style="font-size:0.78rem; color:#6e736c; margin:0 0 4px;">{{ $p->umkm->nama_umkm ?? '-' }}</p>
                                <h3 style="font-size:1rem; font-weight:700; color:#173d2b; margin:0 0 8px; line-height:1.3;">{{ $p->nama_produk }}</h3>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem;">
                                    <span style="color:#205037; font-weight:700;">Rp{{ number_format($p->harga, 0, ',', '.') }}</span>
                                    <span style="color:#9ca3af;">{{ number_format($p->total_terjual ?? 0, 0, ',', '.') }} terjual</span>
                                </div>
                            </div>
                        </div>
                        <div style="padding: 0 16px 16px 16px;">
                            <a href="{{ route('products.show', $p->id) }}" class="button" style="width:100%; text-align:center; display:block;">
                                Lihat Produk <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
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

@if(isset($ulasanTerbaru) && $ulasanTerbaru->isNotEmpty())
<section class="section" style="background:#faf8f5;">
    <div class="shell">
        <div class="section-heading">
            <div>
                <div class="eyebrow"><span></span>Suara Pelanggan</div>
                <h2>Apa kata mereka yang<br>sudah mencoba.</h2>
            </div>
            <a class="outline-link" href="{{ route('umkm.index') }}">
                Lihat semua toko <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px;">
            @foreach($ulasanTerbaru as $ulasan)
            <article style="background:#fff; border:1px solid #e6e1d6; border-radius:14px; padding:24px;">
                <div style="display:flex; gap:4px; margin-bottom:10px;">
                    @for($i=1;$i<=5;$i++)
                        <i class="bi bi-star-fill" style="color:{{ $i<=$ulasan->rating?'#f59e0b':'#e5e7eb' }};"></i>
                    @endfor
                </div>
                <p style="color:#374151; font-size:0.92rem; line-height:1.6; margin-bottom:14px;">
                    "{{ Str::limit($ulasan->komentar, 120) }}"
                </p>
                <div style="font-size:0.82rem; color:#6b7280;">
                    <strong>{{ $ulasan->pembeli?->nama_lengkap }}</strong> ·
                    {{ $ulasan->produk?->nama_produk }}
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif

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

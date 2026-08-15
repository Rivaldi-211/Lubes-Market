@extends('layouts.public')

@section('title', $umkm->nama_umkm . ' — Profil Toko LUDES-MARKET')

@section('content')
<div class="shell" style="padding-top: 36px; padding-bottom: 64px;">
    
    <!-- Header Toko (Redesigned: Clean, Warm, High-End Card) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; margin-bottom: 40px; box-shadow: 0 4px 20px -4px rgba(0,0,0,0.04); position: relative;">
        
        <!-- Top Accent Bar -->
        <div style="height: 5px; background: linear-gradient(90deg, #123825 0%, #2d6a4f 100%);"></div>

        <div style="padding: 32px;">
            <div style="display: flex; gap: 28px; align-items: flex-start; flex-wrap: wrap;">
                
                <!-- Store Avatar / Monogram -->
                <div style="width: 76px; height: 76px; background: #f4efe6; border: 2px solid #d4af37; color: #123825; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800; box-shadow: inset 0 0 8px rgba(0,0,0,0.03); flex-shrink: 0;">
                    {{ strtoupper(substr($umkm->nama_umkm, 0, 1)) }}
                </div>

                <div style="flex: 1; min-width: 260px;">
                    <!-- Badges Row -->
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                        <span style="font-size: 11px; font-weight: 700; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 3px 10px; border-radius: 6px;">
                            {{ $umkm->kategori_usaha ?? 'Mitra UMKM' }}
                        </span>
                        @if($umkm->tahun_berdiri)
                            <span style="font-size: 11px; font-weight: 600; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; padding: 3px 10px; border-radius: 6px;">
                                <i class="bi bi-calendar3"></i> Berdiri {{ $umkm->tahun_berdiri }}
                            </span>
                        @endif
                        @if($avgRating > 0)
                            <span style="font-size: 11px; font-weight: 700; color: #d97706; background: #fffbeb; border: 1px solid #fde68a; padding: 3px 10px; border-radius: 6px;">
                                <i class="bi bi-star-fill"></i> {{ number_format($avgRating, 1) }} ({{ $totalUlasan }} ulasan)
                            </span>
                        @endif
                    </div>

                    <!-- Store Title & Owner -->
                    <h1 style="font-size: 2.1rem; font-weight: 800; margin: 0 0 4px 0; color: #0f172a; line-height: 1.25;">
                        {{ $umkm->nama_umkm }}
                    </h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0 0 12px 0;">
                        Pemilik: <strong style="color: #1e293b;">{{ $umkm->pemilik }}</strong>
                    </p>

                    <!-- Description -->
                    <p style="color: #475569; font-size: 0.98rem; line-height: 1.6; margin: 0 0 20px 0; max-width: 760px;">
                        {{ $umkm->deskripsi }}
                    </p>

                    <!-- Contact & Location Footer Row -->
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 13px; color: #475569; border-top: 1px dashed #e2e8f0; padding-top: 16px;">
                        <span><i class="bi bi-geo-alt-fill" style="color: #10b981;"></i> {{ $umkm->alamat }}</span>
                        
                        @if($umkm->no_hp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $umkm->no_hp) }}" target="_blank" rel="noopener" style="color: #059669; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="bi bi-whatsapp" style="color: #25d366;"></i> {{ $umkm->no_hp }}
                            </a>
                        @endif

                        @if($umkm->instagram)
                            <a href="https://instagram.com/{{ ltrim($umkm->instagram, '@') }}" target="_blank" rel="noopener" style="color: #475569; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="bi bi-instagram" style="color: #e1306c;"></i> {{ $umkm->instagram }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Promo Aktif (Redesigned: Sophisticated & Elegant Card Container) -->
    @if($produkPromo->isNotEmpty())
    <section style="margin-bottom: 44px; background: #ffffff; border: 1px solid #fee2e2; border-radius: 20px; padding: 30px; box-shadow: 0 4px 16px -4px rgba(220, 38, 38, 0.05);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 12px;">
            <div>
                <span style="background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="bi bi-fire"></i> Penawaran Spesial Toko
                </span>
                <h2 style="font-size: 1.4rem; font-weight: 800; margin: 6px 0 0 0; color: #1e293b;">Promo &amp; Diskon Berlangsung</h2>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
            @foreach($produkPromo as $p)
                <div style="background: #fafafa; border: 1px solid #fee2e2; border-radius: 14px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    @if($p->label_promo)
                        <span style="position: absolute; top: 12px; right: 12px; background: #dc2626; color: #fff; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">{{ $p->label_promo }}</span>
                    @endif
                    <div>
                        <h4 style="margin: 0 0 8px 0; font-size: 1.05rem; font-weight: 700; padding-right: 60px;">
                            <a href="{{ route('products.show', $p) }}" style="color: #0f172a; text-decoration: none;">{{ $p->nama_produk }}</a>
                        </h4>
                        <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 14px;">
                            <strong style="color: #dc2626; font-size: 1.25rem;">Rp{{ number_format((float)$p->harga_promo, 0, ',', '.') }}</strong>
                            <del style="color: #94a3b8; font-size: 0.85rem;">Rp{{ number_format((float)$p->harga, 0, ',', '.') }}</del>
                            @if($p->diskonPersen() > 0)
                                <span style="background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 800; padding: 2px 6px; border-radius: 4px;">-{{ $p->diskonPersen() }}%</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('products.show', $p) }}" class="button" style="padding: 8px 16px; font-size: 13px; text-align: center; border-radius: 8px; background: #dc2626; border-color: #dc2626; font-weight: 700; color: #fff;">
                        Lihat Promo →
                    </a>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Catalog Produk Toko -->
    <div style="margin-bottom: 48px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <h2 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0;">Katalog Produk ({{ $produk->count() }})</h2>
        </div>

        @if($produk->isEmpty())
            <x-empty-state title="Belum Ada Produk" text="Toko ini belum menambahkan produk ke katalog." />
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
                @foreach($produk as $p)
                    <x-product-card :product="$p" />
                @endforeach
            </div>
        @endif
    </di    <!-- Section Ulasan Pelanggan Toko -->
    <section style="background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); margin-top:48px;">
        <h2 style="font-size: 1.35rem; font-weight: 800; margin: 0 0 24px 0; color: #0f172a;">Ulasan Pelanggan Toko</h2>

        <!-- Summary Rating Box (ON TOP - Full Width) -->
        <div style="background: #f9fafb; padding: 24px 28px; border-radius: 16px; border: 1px solid #f3f4f6; margin-bottom: 32px; display: flex; gap: 36px; align-items: center; flex-wrap: wrap;">
            <!-- Left: Big Score & Stars -->
            <div style="text-align: center; padding-right: 32px; min-width: 120px;">
                <div style="font-size: 3.2rem; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($avgRating, 1) }}</div>
                <div style="color: #f59e0b; font-size: 1.25rem; margin: 6px 0;">
                    @for($i=1; $i<=5; $i++)
                        <i class="bi bi-star{{ $i <= round($avgRating) ? '-fill' : '' }}"></i>
                    @endfor
                </div>
                <small style="color: #6b7280; font-weight: 600; font-size: 0.88rem;">{{ $totalUlasan }} ulasan</small>
            </div>

            <!-- Right: Star Distribution Progress Bars -->
            <div style="flex: 1; min-width: 240px; display: flex; flex-direction: column; gap: 8px;">
                @foreach([5, 4, 3, 2, 1] as $star)
                    @php $d = $ratingDistribusi[$star]; @endphp
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

        <!-- Review Cards Grid (BELOW RATING BOX) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            @forelse($ulasan as $u)
                <article style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 14px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <strong style="font-size: 0.95rem; color: #111827;">{{ $u->pembeli->nama_lengkap }}</strong>
                            <div style="color: #f59e0b; font-size: 0.85rem;">
                                @for($i=1; $i<=5; $i++)
                                    <i class="bi bi-star{{ $i <= $u->rating ? '-fill' : '' }}"></i>
                                @endfor
                            </div>
                        </div>
                        <small style="color: #059669; font-weight: 600; display: block; margin-bottom: 8px;">Membeli: {{ $u->produk?->nama_produk }}</small>
                        <p style="color: #4b5563; font-size: 0.9rem; margin: 0; line-height: 1.55;">"{{ $u->komentar }}"</p>
                    </div>
                </article>
            @empty
                <p style="color: #9ca3af; font-style: italic; grid-column: 1 / -1;">Belum ada ulasan untuk toko ini.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection

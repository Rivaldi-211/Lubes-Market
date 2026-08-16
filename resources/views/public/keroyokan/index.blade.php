@extends('layouts.public')

@section('title', 'LUDES KEROYOKAN — Satu Pesanan. Banyak UMKM. Satu Kekuatan Desa.')

@section('content')
<section class="public-hero" style="background:linear-gradient(135deg, var(--green-950), var(--green-900)); color:var(--white); padding: 60px 0;">
    <div class="shell">
        <span class="eyebrow" style="color:var(--gold); display:inline-flex; align-items:center; gap:6px; margin-bottom:12px; font-weight:700;"><i class="bi bi-people-fill"></i> PROGRAM UNGGULAN DESA</span>
        <h1 style="font-size: 2.8rem; margin:0 0 16px; font-family:var(--display);">LUDES KEROYOKAN</h1>
        <p style="font-size: 1.25rem; max-width:640px; margin:0 0 24px; opacity:0.9; line-height:1.6;">
            Satu Pesanan. Banyak UMKM. Satu Kekuatan Desa.
        </p>
        <p style="max-width:640px; opacity:0.75; font-size:0.95rem; margin:0;">
            LUDES Keroyokan membantu memenuhi kebutuhan dalam jumlah besar dengan menggabungkan stok produk setara dari beberapa UMKM lokal dalam satu proses pemesanan.
        </p>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <div>
                <small>KELOMPOK PRODUK SETARA</small>
                <h2>Pilihan Keroyokan Tersedia</h2>
            </div>
        </div>

        @if(count($groups))
            <div class="product-grid">
                @foreach($groups as $group)
                    @php
                        $allProd = $group->produk->where('stok_status', '!=', 'nonaktif');
                        $eligible = $group->produk->where('stok_status', '!=', 'Habis')->where('stok_jumlah', '>', 0);
                        $totalStok = $eligible->sum('stok_jumlah');
                        $umkmCount = $allProd->pluck('umkm_id')->unique()->count();
                        $minPrice = $allProd->min('harga') ?: 0;
                        $isOutOfStock = ($totalStok === 0);
                    @endphp
                    <article class="product-card" style="display:flex; flex-direction:column; justify-content:space-between; height:100%; border:1px solid var(--line); border-radius:var(--radius); padding:24px; background:#fff; position:relative;">
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap: wrap;">
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <span class="badge" style="background:var(--cream); color:var(--green-900); padding:4px 10px; border-radius:12px; font-size:12px; font-weight:700;">
                                        {{ $group->kategori->nama_kategori }}
                                    </span>
                                    <span style="background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:700;">
                                        Min. 15 Box
                                    </span>
                                </div>
                                @if($isOutOfStock)
                                    <span class="badge" style="background:#f8d7da; color:#721c24; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:700;">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Stok Habis
                                    </span>
                                @else
                                    <span class="badge" style="background:#d4edda; color:#155724; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:700;">
                                        Stok Tersedia
                                    </span>
                                @endif
                            </div>

                            <h3 style="margin:16px 0 8px; font-size:1.4rem;">{{ $group->nama_kelompok }}</h3>
                            <p style="color:var(--muted); font-size:0.9rem; margin-bottom:16px;">
                                {{ Str::limit($group->deskripsi ?: 'Kelompok produk setara gabungan UMKM Moncongloe Lappara.', 100) }}
                            </p>

                            <div style="background:#f8f9fa; border-radius:12px; padding:12px 16px; margin-bottom:20px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.88rem;">
                                    <span>Mitra UMKM:</span>
                                    <strong>{{ $umkmCount }} UMKM terdaftar</strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.88rem;">
                                    <span>Total stok gabungan:</span>
                                    @if($isOutOfStock)
                                        <strong style="color:#d9534f;">0 unit (Stok Habis)</strong>
                                    @else
                                        <strong>{{ number_format($totalStok) }} unit</strong>
                                    @endif
                                </div>
                                <div style="display:flex; justify-content:space-between; font-size:0.88rem;">
                                    <span>Harga mulai:</span>
                                    <strong style="color:var(--green-800)">Rp{{ number_format((float)$minPrice, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('keroyokan.show', $group) }}" class="button wide" style="text-align:center; {{ $isOutOfStock ? 'background:var(--muted);' : '' }}">
                            <i class="bi bi-box-arrow-in-right"></i> {{ $isOutOfStock ? 'Lihat Detail (Stok Habis)' : 'Buat Pesanan Keroyokan' }}
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <x-empty-state title="Belum Ada Kelompok Keroyokan" text="Saat ini belum ada kelompok produk Keroyokan yang terdaftar."/>
        @endif
    </div>
</section>
@endsection

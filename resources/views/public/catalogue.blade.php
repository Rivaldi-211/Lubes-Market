@extends('layouts.public')

@section('title', 'Katalog Produk — LUDES-MARKET')

@section('content')
<section class="inner-hero">
    <div class="shell">
        <div class="eyebrow"><span></span>Katalog BUMDes</div>
        <div class="inner-hero-row">
            <h1>Produk warga,<br><em>siap ditemukan.</em></h1>
            <p>Telusuri kuliner, oleh-oleh, dan kerajinan dari mitra UMKM Moncongloe Lappara.</p>
        </div>
    </div>
</section>

<section class="section catalogue-section">
    <div class="shell">
        <x-flash />

        <form class="catalogue-toolbar" method="get">
            <label class="search-box">
                <i class="bi bi-search"></i>
                <input name="q" value="{{ request('q') }}" placeholder="Cari produk atau nama UMKM...">
            </label>

            <div class="select-wrapper">
                <select name="kategori">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string)request('kategori') === (string)$category->id)>
                            {{ $category->nama_kategori }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="select-wrapper">
                <select name="sort">
                    <option value="terbaru" @selected(request('sort', 'terbaru') === 'terbaru')>Terbaru</option>
                    <option value="harga_asc" @selected(request('sort') === 'harga_asc')>Harga terendah</option>
                    <option value="harga_desc" @selected(request('sort') === 'harga_desc')>Harga tertinggi</option>
                    <option value="rating" @selected(request('sort') === 'rating')>Rating</option>
                </select>
            </div>

            <button class="button button-dark" type="submit">Terapkan</button>
        </form>

        <div class="results-head">
            <p><strong>{{ $products->total() }}</strong> produk ditemukan</p>
            @if(request()->hasAny(['q', 'kategori', 'sort']))
                <a href="{{ route('catalogue') }}">Reset filter</a>
            @endif
        </div>

        <div class="product-grid">
            @forelse($products as $product)
                <x-product-card :product="$product" />
            @empty
                <div class="empty-state">
                    <i class="bi bi-basket"></i>
                    <h3>Belum menemukan produk yang cocok.</h3>
                    <p>Coba ganti kata pencarian atau pilih kategori lain.</p>
                    <a class="button" href="{{ route('catalogue') }}">Lihat semua produk</a>
                </div>
            @endforelse
        </div>

        <div class="pagination-wrap">
            {{ $products->links() }}
        </div>
    </div>
</section>
@endsection

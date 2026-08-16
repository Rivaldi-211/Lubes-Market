@extends('layouts.public')

@section('title', 'Katalog Produk — LUDES-MARKET')

@section('content')
<section class="inner-hero">
    <div class="shell">
        <div class="eyebrow"><span></span>Katalog LUDES-MARKET</div>
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
            @if(request('kategori'))
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
            @endif

            <label class="search-box">
                <i class="bi bi-search"></i>
                <input name="q" value="{{ request('q') }}" placeholder="Cari produk atau nama UMKM...">
            </label>

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

        <div class="category-pills-bar" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; margin-top: 8px;">
            <a href="{{ route('catalogue', array_merge(request()->except('kategori', 'page'), [])) }}" 
               class="category-pill {{ !request('kategori') ? 'active' : '' }}" 
               style="padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.15s ease-out; display: inline-flex; align-items: center; gap: 6px; {{ !request('kategori') ? 'background: #123825; color: #fff; box-shadow: 0 4px 12px rgba(18,56,37,0.25);' : 'background: #f1f5f9; color: #475569;' }}">
               <i class="bi bi-grid-fill"></i> Semua
            </a>
            @foreach($categories as $category)
                <a href="{{ route('catalogue', array_merge(request()->except('page'), ['kategori' => $category->id])) }}" 
                   class="category-pill {{ (string)request('kategori') === (string)$category->id ? 'active' : '' }}" 
                   style="padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.15s ease-out; display: inline-flex; align-items: center; gap: 6px; {{ (string)request('kategori') === (string)$category->id ? 'background: #123825; color: #fff; box-shadow: 0 4px 12px rgba(18,56,37,0.25);' : 'background: #f1f5f9; color: #475569;' }}">
                   {{ $category->nama_kategori }}
                </a>
            @endforeach
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

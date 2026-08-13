@props(['product'])

<article class="product-card">
    <a class="product-media" href="{{ route('products.show', $product) }}" aria-label="Lihat {{ $product->nama_produk }}">
        @if($product->foto)
            <img src="{{ asset('storage/'.$product->foto) }}" alt="{{ $product->nama_produk }}" loading="lazy">
        @else
            <div class="product-placeholder">
                <span>{{ strtoupper(substr($product->nama_produk, 0, 1)) }}</span>
                <small>Produk Lokal</small>
            </div>
        @endif
        <div class="product-media-top">
            <x-stock-badge :product="$product" />
        </div>
    </a>
    
    <div class="product-body">
        <div class="product-meta">
            <span>{{ $product->kategori?->nama_kategori ?? 'Produk Lokal' }}</span>
            @if(($product->ulasan_count ?? 0) > 0)
                <span><i class="bi bi-star-fill"></i> {{ number_format((float)$product->ulasan_avg_rating, 1) }}</span>
            @endif
        </div>
        
        <h3><a href="{{ route('products.show', $product) }}">{{ $product->nama_produk }}</a></h3>
        <p class="product-seller">oleh {{ $product->umkm?->nama_umkm }}</p>
        
        <div class="product-footer">
            <strong>Rp{{ number_format((float)$product->harga, 0, ',', '.') }}</strong>
            <div class="product-footer-actions">
                <button type="button" class="add-cart-btn" aria-label="Tambah {{ $product->nama_produk }} ke keranjang"
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ $product->nama_produk }}"
                    data-product-umkm="{{ $product->umkm?->nama_umkm }}"
                    data-product-foto="{{ $product->foto ? asset('storage/'.$product->foto) : '' }}"
                    data-product-harga="{{ (float)$product->harga }}"
                    data-product-stok="{{ $product->stok_jumlah }}"
                    data-product-available="{{ $product->isAvailable() ? '1' : '0' }}"
                    data-product-url="{{ route('cart.add', $product) }}"
                    data-product-initial="{{ strtoupper(substr($product->nama_produk, 0, 1)) }}"
                >
                    <i class="bi bi-plus"></i>
                </button>
                <a class="round-link" href="{{ route('products.show', $product) }}"><i class="bi bi-arrow-up-right"></i></a>
            </div>
        </div>
    </div>
</article>

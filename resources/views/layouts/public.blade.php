<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LUDES-MARKET — Produk Lokal Moncongloe Lappara')</title>
    <meta name="description" content="Platform Pemasaran Digital Produk Lokal BUMDes untuk Memperluas Akses Pasar dan Mendorong Kemandirian Ekonomi Desa Moncongloe Lappara.">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    @stack('head')
</head>
<body class="public-page @yield('body_class')">
    <header class="site-header {{ request()->routeIs('home') ? 'site-header-overlay' : 'site-header-solid' }}" id="siteHeader">
        <div class="shell header-inner">
            <a class="brand" href="{{ route('home') }}">
                <img src="{{ asset('assets/img/logo-mark.png') }}" alt="Logo LUDES-MARKET" class="brand-mark">
                <span><b>LUDES</b>-MARKET<small>Moncongloe Lappara</small></span>
            </a>
            
            <nav class="desktop-nav" aria-label="Navigasi utama">
                <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" data-nav="beranda">Beranda</a>
                <a href="{{ route('home') }}#tentang" data-nav="tentang">Tentang</a>
                <a href="{{ route('home') }}#mitra" data-nav="mitra">Mitra UMKM</a>
                <a href="{{ route('home') }}#kontak" data-nav="kontak">Kontak</a>
                <a class="{{ request()->routeIs('catalogue') || request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('catalogue') }}">Katalog</a>
                <a class="{{ request()->routeIs('umkm.*') ? 'active' : '' }}" href="{{ route('umkm.index') }}">Toko UMKM</a>
                <a class="{{ request()->routeIs('keroyokan.*') ? 'active' : '' }}" href="{{ route('keroyokan.index') }}">Keroyokan</a>
            </nav>

            <div class="header-actions">
                <a class="cart-link" href="{{ route('cart.index') ?? '#' }}" aria-label="Keranjang">
                    <i class="bi bi-bag"></i>
                    <span>{{ array_sum(session('cart', [])) }}</span>
                </a>

                @auth
                    <a class="account-link" href="{{ auth()->user()->dashboardPath() }}">{{ Str::limit(auth()->user()->nama_lengkap, 18) }}</a>
                @else
                    <a class="login-link" href="{{ route('login') }}">Masuk</a>
                    <a class="button button-small" href="{{ route('register') }}">Daftar</a>
                @endauth

                <button class="menu-toggle" data-menu-toggle aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>

        <div class="mobile-menu" data-mobile-menu>
            <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" data-nav="beranda">Beranda</a>
            <a class="{{ request()->routeIs('catalogue') || request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('catalogue') }}">Katalog</a>
            <a class="{{ request()->routeIs('umkm.*') ? 'active' : '' }}" href="{{ route('umkm.index') }}">Toko UMKM</a>
            <a class="{{ request()->routeIs('keroyokan.*') ? 'active' : '' }}" href="{{ route('keroyokan.index') }}">Keroyokan</a>
            <a href="{{ route('home') }}#tentang" data-nav="tentang">Tentang</a>
            <a href="{{ route('home') }}#mitra" data-nav="mitra">Mitra UMKM</a>
            <a href="{{ route('home') }}#kontak" data-nav="kontak">Kontak</a>
            @auth
                <div class="mobile-auth-box">
                    <a class="mobile-user-card" href="{{ auth()->user()->dashboardPath() }}">
                        <span class="mobile-user-avatar">{{ strtoupper(substr(auth()->user()->nama_lengkap, 0, 1)) }}</span>
                        <div>
                            <strong>{{ auth()->user()->nama_lengkap }}</strong>
                            <small>{{ auth()->user()->isAdmin() ? 'Administrator' : (auth()->user()->isSeller() ? 'Mitra UMKM' : 'Pembeli') }}</small>
                        </div>
                        <i class="bi bi-chevron-right" style="margin-left: auto; color: #858b86;"></i>
                    </a>
                    <a class="mobile-menu-link" href="{{ auth()->user()->dashboardPath() }}">
                        <i class="bi bi-speedometer2"></i> Dashboard Saya
                    </a>
                    @if(auth()->user()->isSeller())
                        <a class="mobile-menu-link" href="{{ route('seller.profile.edit') }}">
                            <i class="bi bi-shop"></i> Profil & Akun Penjual
                        </a>
                    @elseif(auth()->user()->isBuyer())
                        <a class="mobile-menu-link" href="{{ route('buyer.profile.edit') }}">
                            <i class="bi bi-person-gear"></i> Pengaturan Akun
                        </a>
                    @endif
                    <form method="post" action="{{ route('logout') }}" style="margin: 4px 0 0 0;">
                        @csrf
                        <button type="submit" class="mobile-logout-btn">
                            <i class="bi bi-box-arrow-right"></i> Keluar
                        </button>
                    </form>
                </div>
            @else
                <div class="mobile-guest-box">
                    <a class="button button-outline button-small" href="{{ route('login') }}" style="justify-content:center; width:100%;">Masuk</a>
                    <a class="button button-small" href="{{ route('register') }}" style="justify-content:center; width:100%;">Daftar</a>
                </div>
            @endauth
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer" id="kontak">
        <div class="shell footer-grid">
            <div>
                <a class="brand footer-brand" href="{{ route('home') }}">
                    <img src="{{ asset('assets/img/logo-mark.png') }}" alt="Logo LUDES-MARKET" class="brand-mark">
                    <span><b>LUDES</b>-MARKET<small>Moncongloe Lappara</small></span>
                </a>
                <p>Platform Pemasaran Digital Produk Lokal BUMDes untuk Memperluas Akses Pasar dan Mendorong Kemandirian Ekonomi Desa Moncongloe Lappara.</p>
            </div>

            <div>
                <h4>Jelajahi</h4>
                <a href="{{ route('catalogue') }}">Katalog Produk</a>
                <a href="{{ route('home') }}#mitra">Mitra UMKM</a>
                <a href="{{ route('register') }}">Jadi Mitra</a>
            </div>

            <div>
                <h4>Lokasi & Kontak Admin</h4>
                <p>Desa Moncongloe Lappara<br>Kabupaten Maros, Sulawesi Selatan</p>
                <p><i class="bi bi-geo-alt"></i> Kawasan Kuliner Moncongloe Lappara</p>
                <p><i class="bi bi-telephone-fill" style="color:var(--gold)"></i> <a href="https://wa.me/6281234500001" target="_blank" rel="noopener" style="color:inherit">0812-3450-0001 (Admin)</a></p>
                <p><i class="bi bi-envelope-fill" style="color:var(--gold)"></i> <a href="mailto:admin@bumdesberkah.id" style="color:inherit">admin@bumdesberkah.id</a></p>
            </div>
        </div>

        <div class="shell footer-bottom">
            <span>© {{ date('Y') }} LUDES-MARKET</span>
            <span>Produk lokal. Dikelola lebih rapi.</span>
        </div>
    </footer>

    {{-- Add to Cart Modal --}}
    <div class="modal-overlay" id="addToCartModal">
        <div class="modal-container">
            <button type="button" class="modal-close" id="modalClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            <div class="modal-body">
                <div class="modal-product-photo" id="modalPhoto"></div>
                <div class="modal-product-info">
                    <p class="modal-umkm" id="modalUmkm"></p>
                    <h3 class="modal-product-name" id="modalName"></h3>
                    <div class="modal-price" id="modalPrice"></div>
                    <div class="modal-stock" id="modalStock"></div>
                    
                    <form method="post" id="modalForm">
                        @csrf
                        <div class="modal-qty-row">
                            <label>Jumlah</label>
                            <div class="modal-qty-control">
                                <button type="button" class="qty-btn" id="qtyMinus">−</button>
                                <input type="number" name="jumlah" id="qtyInput" value="1" min="1" readonly>
                                <button type="button" class="qty-btn" id="qtyPlus">+</button>
                            </div>
                        </div>
                        <div class="modal-subtotal-row">
                            <span>Subtotal</span>
                            <strong id="modalSubtotal"></strong>
                        </div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px;">
                            <button type="submit" class="button button-dark" id="modalSubmit" style="flex: 1; min-width: 140px; justify-content: center;"><i class="bi bi-bag-plus"></i> + Keranjang</button>
                            <button type="submit" name="direct_checkout" value="1" class="button" id="modalDirectCheckout" style="flex: 1; min-width: 140px; justify-content: center; background: var(--gold); color: var(--green-950);"><i class="bi bi-lightning-fill"></i> Pesan Langsung</button>
                        </div>
                    </form>

                    <div class="modal-unavailable" id="modalUnavailable" style="display:none">
                        <i class="bi bi-exclamation-triangle"></i> Produk sedang tidak tersedia.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

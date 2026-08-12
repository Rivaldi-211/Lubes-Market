<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','BUMDes Berkah — Produk Lokal Moncongloe Lappara')</title>
<meta name="description" content="Katalog produk lokal dan UMKM BUMDes Berkah Desa Moncongloe Lappara.">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
@stack('head')
</head>
<body class="public-page @yield('body_class')">
<header class="site-header {{ request()->routeIs('home') ? 'site-header-overlay' : 'site-header-solid' }}" id="siteHeader">
    <div class="shell header-inner">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark"><i class="bi bi-leaf-fill"></i></span><span><b>BUMDes</b> Berkah<small>Moncongloe Lappara</small></span></a>
        <nav class="desktop-nav" aria-label="Navigasi utama">
            <a class="{{ request()->routeIs('home')?'active':'' }}" href="{{ route('home') }}">Beranda</a>
            <a class="{{ request()->routeIs('catalogue')||request()->routeIs('products.*')?'active':'' }}" href="{{ route('catalogue') }}">Katalog</a>
            <a href="{{ route('home') }}#tentang">Tentang</a><a href="{{ route('home') }}#mitra">Mitra UMKM</a><a href="{{ route('home') }}#kontak">Kontak</a>
        </nav>
        <div class="header-actions">
            <a class="cart-link" href="{{ route('cart.index') ?? '#' }}" aria-label="Keranjang"><i class="bi bi-bag"></i><span>{{ array_sum(session('cart',[])) }}</span></a>
            @auth
                <a class="account-link" href="{{ auth()->user()->dashboardPath() }}">{{ Str::limit(auth()->user()->nama_lengkap,18) }}</a>
            @else
                <a class="login-link" href="{{ route('login') }}">Masuk</a><a class="button button-small" href="{{ route('register') }}">Daftar</a>
            @endauth
            <button class="menu-toggle" data-menu-toggle aria-label="Buka menu"><i class="bi bi-list"></i></button>
        </div>
    </div>
    <div class="mobile-menu" data-mobile-menu>
        <a href="{{ route('home') }}">Beranda</a><a href="{{ route('catalogue') }}">Katalog</a><a href="{{ route('home') }}#tentang">Tentang</a><a href="{{ route('home') }}#mitra">Mitra UMKM</a><a href="{{ route('home') }}#kontak">Kontak</a>
        @guest<a href="{{ route('login') }}">Masuk</a><a href="{{ route('register') }}">Daftar</a>@endguest
    </div>
</header>
<main>@yield('content')</main>
<footer class="site-footer" id="kontak"><div class="shell footer-grid"><div><a class="brand footer-brand" href="{{ route('home') }}"><span class="brand-mark"><i class="bi bi-leaf-fill"></i></span><span><b>BUMDes</b> Berkah<small>Moncongloe Lappara</small></span></a><p>Ruang digital untuk mempertemukan produk lokal, pelaku UMKM, dan pembeli dengan cara yang lebih sederhana.</p></div><div><h4>Jelajahi</h4><a href="{{ route('catalogue') }}">Katalog Produk</a><a href="{{ route('home') }}#mitra">Mitra UMKM</a><a href="{{ route('register') }}">Jadi Mitra</a></div><div><h4>Lokasi</h4><p>Desa Moncongloe Lappara<br>Kabupaten Maros, Sulawesi Selatan</p><p><i class="bi bi-geo-alt"></i> Kawasan Kuliner Savana Lappara (Savala)</p></div></div><div class="shell footer-bottom"><span>© {{ date('Y') }} BUMDes Berkah</span><span>Produk lokal. Dikelola lebih rapi.</span></div></footer>
<script src="{{ asset('assets/js/app.js') }}"></script>@stack('scripts')
</body></html>

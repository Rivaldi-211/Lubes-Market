<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — LUDES-MARKET</title>
    
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-photo">
            <div class="auth-photo-shade"></div>
            <a class="brand auth-brand" href="{{ route('home') }}">
                <img src="{{ asset('assets/img/logo-mark.png') }}" alt="Logo LUDES-MARKET" class="brand-mark">
                <span><b>LUDES</b>-MARKET<small>Moncongloe Lappara</small></span>
            </a>
            <div class="auth-story">
                <div style="margin-bottom: 20px;">
                    </div>
                <p class="eyebrow light"><span></span>Produk lokal, akun nyata</p>
                <h1>Belanja dan kelola usaha dari satu sistem.</h1>
                <p>Dirancang untuk alur kerja BUMDes, penjual, dan pembeli—tanpa dekorasi yang mengganggu tugas utama.</p>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-panel-inner">
                <a class="auth-back" href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a>
                <x-flash />
                @yield('content')
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — LUDES-MARKET</title>
    
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
                <span class="brand-mark">LM</span>
                <span><b>LUDES</b>-MARKET<small>Moncongloe Lappara</small></span>
            </a>
            <div class="auth-story">
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

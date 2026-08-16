<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi - LUDES-MARKET</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
            color: #334155;
        }
        .email-card {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .email-header {
            background: #123825;
            padding: 30px 24px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.5px;
            font-weight: 800;
        }
        .email-header p {
            margin: 6px 0 0;
            font-size: 13px;
            color: #eab308;
            font-weight: 600;
        }
        .email-body {
            padding: 32px 28px;
            line-height: 1.6;
        }
        .email-body h2 {
            margin-top: 0;
            color: #0f172a;
            font-size: 18px;
        }
        .btn-reset {
            display: inline-block;
            background: #123825;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            font-weight: 700;
            font-size: 14px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 6px rgba(18, 56, 37, 0.25);
        }
        .email-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 28px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-card">
        <div class="email-header">
            <h1>LUDES-MARKET</h1>
            <p>Pusat Belanja &amp; UMKM BUMDes Berkah Moncongloe</p>
        </div>
        <div class="email-body">
            <h2>Halo, {{ $user->nama_lengkap ?? 'Pengguna LUDES-MARKET' }}</h2>
            <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Silakan klik tombol di bawah ini untuk membuat kata sandi baru:</p>
            
            <div style="text-align: center;">
                <a href="{{ $url ?? url('/') }}" class="btn-reset" target="_blank">Atur Ulang Kata Sandi Saya</a>
            </div>

            <p style="font-size: 13px; color: #64748b;">Tautan pengaturan ulang kata sandi ini hanya berlaku selama 60 menit. Jika Anda tidak pernah meminta perubahan kata sandi, abaikan email ini dan akun Anda tetap aman.</p>
        </div>
        <div class="email-footer">
            &copy; {{ date('Y') }} LUDES-MARKET. Desa Moncongloe Lappara, Kec. Moncongloe, Kab. Maros.
        </div>
    </div>
</body>
</html>

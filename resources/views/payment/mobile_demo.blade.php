<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran QRIS Mobile Demo — LUDES-MARKET</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg-gradient: #f7f4ed;
            --card-bg: #ffffff;
            --accent-green: #205037;
            --accent-gold: #c79b42;
            --text-main: #173d2b;
            --text-muted: #6e736c;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }

        .mobile-card {
            width: 100%;
            max-width: 400px;
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(24, 46, 33, 0.05);
            border: 1px solid #e6e0d2;
        }

        .merchant-head {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #ece7de;
        }

        .merchant-logo {
            width: 50px;
            height: 50px;
            background: #173d2b;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-weight: 700;
            font-size: 1.2rem;
            color: #f5f1e7;
            box-shadow: 0 4px 12px rgba(23, 61, 43, 0.15);
        }

        .merchant-title {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text-main);
        }

        .merchant-sub {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .amount-box {
            text-align: center;
            background: #faf7f0;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 20px;
            border: 1px solid #e6e0d2;
        }

        .amount-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .amount-val {
            font-size: 2rem;
            font-weight: 700;
            color: #173d2b;
        }

        .order-details {
            margin-bottom: 24px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            padding: 8px 0;
            border-bottom: 1px solid #f0eae0;
        }

        .detail-label {
            color: var(--text-muted);
        }

        .detail-val {
            font-weight: 600;
            color: var(--text-main);
            text-align: right;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #f5f1e7;
            color: #173d2b;
            border: 1px solid #d9c9ac;
        }

        .badge-paid {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }

        .btn-pay {
            width: 100%;
            background: #173d2b;
            color: #ffffff;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(23, 61, 43, 0.2);
            transition: all 0.2s ease;
        }

        .btn-pay:active {
            transform: scale(0.98);
        }

        .success-box {
            text-align: center;
            padding: 20px 0;
        }

        .success-icon {
            width: 70px;
            height: 70px;
            background: rgba(16, 185, 129, 0.15);
            border: 2px solid #10b981;
            color: #34d399;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 16px;
        }

        .demo-notice {
            margin-top: 16px;
            text-align: center;
            font-size: 0.75rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="mobile-card">
        <div class="merchant-head">
            <img src="{{ asset('assets/img/logo-mark.png') }}" alt="Logo LUDES-MARKET" style="width: 60px; height: 60px; border-radius: 50%; object-fit: contain; margin: 0 auto 12px; display: block; box-shadow: 0 4px 12px rgba(23, 61, 43, 0.12);">
            <div class="merchant-title">LUDES-MARKET</div>
            <div class="merchant-sub">Pembayaran Digital QRIS (Simulasi Demo)</div>
        </div>

        @if($payment->status === 'PENDING')
            <div class="amount-box">
                <div class="amount-label">Total Tagihan</div>
                <div class="amount-val">Rp{{ number_format($payment->amount, 0, ',', '.') }}</div>
                <div style="margin-top: 8px;">
                    <span class="status-badge badge-pending">
                        <i class="bi bi-clock"></i> MENUNGGU PEMBAYARAN
                    </span>
                </div>
            </div>

            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">No. Referensi</span>
                    <span class="detail-val">{{ $payment->reference_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode</span>
                    <span class="detail-val">QRIS</span>
                </div>
                @if($payment->pesanan->isNotEmpty())
                    <div class="detail-row">
                        <span class="detail-label">Produk</span>
                        <span class="detail-val">
                            @foreach($payment->pesanan as $p)
                                {{ $p->produk->nama_produk ?? 'Produk' }} ({{ $p->jumlah }}x)<br>
                            @endforeach
                        </span>
                    </div>
                @endif
            </div>

            <form action="{{ route('payment.qris.demo_mobile_pay', $payment->reference_id) }}" method="POST" onsubmit="this.querySelector('button').disabled = true;">
                @csrf
                <button type="submit" class="btn-pay">
                    <i class="bi bi-check-circle-fill"></i> KONFIRMASI & BAYAR SEKARANG
                </button>
            </form>
        @else
            <div class="success-box">
                <div class="success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2 style="font-size: 1.4rem; color: #34d399; margin-bottom: 6px;">Pembayaran Berhasil!</h2>
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px;">
                    Transaksi telah dikonfirmasi LUNAS. Status di layar laptop akan otomatis ter-update.
                </p>
                <div class="amount-box">
                    <div class="amount-label">Nominal Lunas</div>
                    <div class="amount-val">Rp{{ number_format($payment->amount, 0, ',', '.') }}</div>
                </div>
                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-val" style="color: #34d399;">PAID (LUNAS)</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Referensi</span>
                        <span class="detail-val">{{ $payment->reference_id }}</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="demo-notice">
            <i class="bi bi-shield-check"></i> Lingkungan Pengujian Simulasi Demo — LUDES-MARKET
        </div>
    </div>
</body>
</html>

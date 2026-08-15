<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nota #{{ $order->id }} — LUDES-MARKET</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1f2a22;
            margin: 0;
            background: #eee;
        }

        .receipt {
            width: 760px;
            max-width: calc(100% - 32px);
            margin: 32px auto;
            background: #fff;
            padding: 42px;
        }

        .head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            border-bottom: 2px solid #1f5139;
            padding-bottom: 22px;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
            color: #173d2b;
        }

        .head small, .muted {
            color: #68726b;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 28px 0;
        }

        .grid h4 {
            margin: 0 0 6px;
            font-size: 11px;
            text-transform: uppercase;
            color: #728078;
        }

        .grid p {
            margin: 3px 0;
            word-break: break-word;
        }

        .item {
            display: grid;
            grid-template-columns: 1fr 100px 150px;
            gap: 8px;
            border-top: 1px solid #ddd;
            padding: 18px 0;
            align-items: center;
        }

        .item:last-of-type {
            border-bottom: 1px solid #ddd;
        }

        .total {
            display: flex;
            justify-content: flex-end;
            gap: 50px;
            padding: 22px 0;
            font-size: 20px;
        }

        .foot {
            margin-top: 32px;
            font-size: 12px;
            color: #6a746d;
            word-break: break-word;
        }

        .print {
            display: block;
            margin: 0 auto 32px;
            padding: 11px 18px;
            background: #173d2b;
            color: #fff;
            border: 0;
            cursor: pointer;
            font-size: 14px;
            border-radius: 6px;
        }

        /* --- Mobile Responsive --- */
        @media (max-width: 600px) {
            .receipt {
                padding: 24px 18px;
                max-width: calc(100% - 16px);
                margin: 16px auto;
            }

            .head {
                flex-direction: column;
                gap: 8px;
            }

            .head > div:last-child {
                text-align: left;
            }

            .brand {
                font-size: 18px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 20px;
                margin: 20px 0;
            }

            .item {
                grid-template-columns: 1fr;
                gap: 6px;
                padding: 14px 0;
            }

            .item > div:last-child {
                text-align: left;
                font-size: 16px;
            }

            .total {
                gap: 20px;
                font-size: 18px;
                flex-wrap: wrap;
            }

            .foot {
                margin-top: 20px;
            }
        }

        /* --- Print --- */
        @media print {
            body {
                background: #fff;
            }

            .receipt {
                width: auto;
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="head">
            <div>
                <div class="brand">LUDES-MARKET</div>
                <small>Moncongloe Lappara · Produk Lokal</small>
            </div>
            <div style="text-align:right">
                <b>NOTA PESANAN</b><br>
                <small>#{{ str_pad($order->id,5,'0',STR_PAD_LEFT) }} · {{ optional($order->tanggal_pesan)->format('d/m/Y H:i') }}</small>
            </div>
        </div>

        <div class="grid">
            <div>
                <h4>Pembeli</h4>
                <p><b>{{ $order->pembeli->nama_lengkap }}</b></p>
                <p>{{ $order->no_hp_pembeli }}</p>
                <p>{{ $order->alamat_pengiriman }}</p>
            </div>
            <div>
                <h4>Penjual</h4>
                <p><b>{{ $order->produk->umkm->nama_umkm }}</b></p>
                <p>{{ $order->produk->umkm->pemilik }}</p>
                <p>{{ $order->produk->umkm->no_hp }}</p>
            </div>
        </div>

        <div class="item">
            <div>
                <b>{{ $order->produk->nama_produk }}</b><br>
                <span class="muted">{{ $order->jumlah }} × Rp{{ number_format((float)$order->produk->harga,0,',','.') }}</span>
            </div>
            <div>{{ $order->status }}</div>
            <div style="text-align:right">
                <b>Rp{{ number_format((float)$order->total_harga,0,',','.') }}</b>
            </div>
        </div>

        <div class="total">
            <span>Total</span>
            <b>Rp{{ number_format((float)$order->total_harga,0,',','.') }}</b>
        </div>

        <p class="foot">
            Metode pembayaran: <b>{{ $order->metode_pembayaran }}</b>@if($order->catatan) · Catatan: {{ $order->catatan }}@endif<br>
            Nota ini dibuat oleh sistem LUDES-MARKET.
        </p>
    </div>

    <button class="print" onclick="window.print()">Cetak nota</button>
</body>
</html>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nota {{ $order->batch_keroyokan_id ? '#KR-' . str_pad($order->batch_keroyokan_id, 5, '0', STR_PAD_LEFT) : '#' . $order->id }} — LUDES-MARKET</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1f2a22;
            margin: 0;
            background: #eee;
        }

        .receipt-wrapper {
            width: 780px;
            max-width: calc(100% - 32px);
            margin: 28px auto;
        }

        .view-mode-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .view-mode-btn {
            background: #ffffff;
            border: 1.5px solid #c8c2b4;
            color: #173d2b;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease-out;
        }

        .view-mode-btn.active {
            background: #173d2b;
            color: #ffffff;
            border-color: #173d2b;
            box-shadow: 0 4px 12px rgba(23, 61, 43, 0.2);
        }

        .receipt {
            background: #fff;
            padding: 42px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
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
            margin: 24px 0;
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

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .item {
            display: grid;
            grid-template-columns: 1fr 110px 150px;
            gap: 12px;
            border-top: 1px solid #e2e8f0;
            padding: 16px 0;
            align-items: center;
        }

        .item:last-of-type {
            border-bottom: 1px solid #e2e8f0;
        }

        .status-pill {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            text-align: center;
        }

        .status-Selesai { background: #dcfce7; color: #15803d; }
        .status-Diproses { background: #fef9c3; color: #854d0e; }
        .status-Menunggu { background: #e0f2fe; color: #0369a1; }
        .status-Dibatalkan { background: #fee2e2; color: #b91c1c; }

        .total-box {
            margin: 16px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
            font-size: 13px;
            color: #475569;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .total-box > div {
            display: flex;
            justify-content: space-between;
        }

        .total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0 8px;
            font-size: 20px;
            color: #0f172a;
        }

        .total strong {
            color: #123825;
            font-size: 22px;
        }

        .foot {
            margin-top: 24px;
            font-size: 12px;
            color: #6a746d;
            word-break: break-word;
            line-height: 1.5;
        }

        /* --- Individual Product Cards --- */
        .individual-cards-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .individual-order-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px 26px;
            position: relative;
        }

        .individual-order-card:hover {
            border-color: #cbd5e1;
        }

        .sub-receipt-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            background: #f4efe6;
            color: #173d2b;
            padding: 3px 10px;
            border-radius: 6px;
            border: 1px solid #d9c9ac;
            margin-bottom: 10px;
        }

        /* --- Print Mode --- */
        @media print {
            body {
                background: #fff !important;
            }

            .receipt-wrapper {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .receipt {
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 20px 0 !important;
                page-break-after: always;
            }

            .individual-order-card {
                page-break-inside: avoid;
                border: 1px solid #ccc !important;
            }

            .no-print, .view-mode-tabs, .action-bar, .btn-back, .btn-print {
                display: none !important;
            }

            #section-total, #section-separated {
                display: block !important;
            }
        }

        /* --- Mobile Responsive --- */
        @media (max-width: 600px) {
            .receipt {
                padding: 24px 18px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .item {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .item > div:last-child {
                text-align: left;
            }

            .total {
                font-size: 17px;
            }

            .total strong {
                font-size: 19px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-wrapper">
        
        @if($isKeroyokanBatch)
            <!-- View Mode Switcher -->
            <div class="view-mode-tabs no-print">
                <button type="button" class="view-mode-btn active" id="tabBtnTotal" onclick="switchReceiptView('total')">
                    <i class="bi bi-file-text-fill"></i> Nota Total Konsolidasi
                </button>
                <button type="button" class="view-mode-btn" id="tabBtnSeparated" onclick="switchReceiptView('separated')">
                    <i class="bi bi-boxes"></i> Nota Terpisah Per-Produk ({{ $batchOrders->count() }})
                </button>
                <button type="button" class="view-mode-btn" id="tabBtnAll" onclick="switchReceiptView('all')">
                    <i class="bi bi-layers-fill"></i> Tampilkan Keduanya
                </button>
            </div>
        @endif

        @php
            $batchOrdersCollection = $isKeroyokanBatch ? $batchOrders : collect([$order]);
            $totalSubtotalProduk = $batchOrdersCollection->sum(fn($o) => (float)$o->produk->harga * $o->jumlah);
            $totalOngkir = $batchOrdersCollection->sum('ongkos_kirim');
            $totalPacking = $batchOrdersCollection->sum('biaya_packing');
            $grandTotal = $batchOrdersCollection->sum('total_harga');
        @endphp

        <!-- ==========================================
             1. MASTER CONSOLIDATED RECEIPT (NOTA TOTAL)
             ========================================== -->
        <div class="receipt" id="section-total">
            <div class="head">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <img src="{{ asset('assets/img/logo-mark.png') }}" alt="Logo LM" style="width: 48px; height: 48px; border-radius: 50%; object-fit: contain; flex-shrink: 0;">
                    <div>
                        <div class="brand">LUDES-MARKET</div>
                        <small>Moncongloe Lappara · Produk Lokal</small>
                    </div>
                </div>
                <div style="text-align:right">
                    <b>@if($order->batch_keroyokan_id) NOTA PAKET KEROYOKAN @else NOTA PESANAN @endif</b><br>
                    <small>
                        @if($order->batch_keroyokan_id)
                            #KR-{{ str_pad($order->batch_keroyokan_id, 5, '0', STR_PAD_LEFT) }}
                        @else
                            #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                        @endif
                        · {{ optional($order->tanggal_pesan)->format('d/m/Y H:i') }}
                    </small>
                </div>
            </div>

            <div class="grid">
                <div>
                    <h4>Pembeli & Pengiriman</h4>
                    <p><b>{{ $order->pembeli->nama_lengkap }}</b></p>
                    <p><i class="bi bi-telephone"></i> {{ $order->no_hp_pembeli }}</p>
                    <p><i class="bi bi-geo-alt"></i> {{ $order->alamat_pengiriman }}</p>
                </div>
                <div>
                    @if($isKeroyokanBatch)
                        <h4>Mitra UMKM Terlibat ({{ $batchOrders->pluck('produk.umkm.nama_umkm')->unique()->count() }} Toko)</h4>
                        <div style="display: flex; flex-direction: column; gap: 4px; font-size: 13px;">
                            @foreach($batchOrders->pluck('produk.umkm')->unique('id') as $umkm)
                                <div><i class="bi bi-shop"></i> <b>{{ $umkm->nama_umkm }}</b> ({{ $umkm->pemilik }})</div>
                            @endforeach
                        </div>
                    @else
                        <h4>Penjual</h4>
                        <p><b>{{ $order->produk->umkm->nama_umkm }}</b></p>
                        <p>{{ $order->produk->umkm->pemilik }}</p>
                        <p><i class="bi bi-telephone"></i> {{ $order->produk->umkm->no_hp }}</p>
                    @endif
                </div>
            </div>

            @if($order->batch_keroyokan_id)
                <div style="margin: 16px 0; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 12px; color: #166534; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 700; color: #15803d; text-transform: uppercase; font-size: 11px; background: #dcfce7; padding: 2px 8px; border-radius: 4px;">Paket Keroyokan</span>
                        <span>Dikemas terpadu dalam 1 box kemasan berlabel resmi LUDES-MARKET.</span>
                    </div>
                    <span style="font-weight: 700; color: #166534;">{{ $batchOrdersCollection->count() }} Produk Digabung</span>
                </div>
            @endif

            <!-- Itemized List -->
            <div style="margin-top: 10px;">
                @foreach($batchOrdersCollection as $itemOrder)
                    <div class="item">
                        <div>
                            <b>{{ $itemOrder->produk->nama_produk }}</b>
                            @if($isKeroyokanBatch)
                                <span style="font-size: 12px; color: #059669; font-weight: 600;"> · {{ $itemOrder->produk->umkm->nama_umkm }}</span>
                            @endif
                            <br>
                            <span class="muted">{{ $itemOrder->jumlah }} × Rp{{ number_format((float)$itemOrder->produk->harga, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="status-pill status-{{ str_replace(' ', '', $itemOrder->status) }}">
                                {{ $itemOrder->status }}
                            </span>
                        </div>
                        <div style="text-align:right">
                            <b>Rp{{ number_format((float)$itemOrder->produk->harga * $itemOrder->jumlah, 0, ',', '.') }}</b>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Total Breakdown -->
            <div class="total-box">
                <div>
                    <span>Subtotal Produk ({{ $batchOrdersCollection->sum('jumlah') }} item):</span>
                    <span>Rp{{ number_format((float)$totalSubtotalProduk, 0, ',', '.') }}</span>
                </div>
                @if($totalOngkir > 0)
                    <div>
                        <span>Ongkos Kirim @if($order->batch_keroyokan_id)<small>(Ongkir Tunggal 1 Paket)</small>@endif:</span>
                        <span>Rp{{ number_format((float)$totalOngkir, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($totalPacking > 0)
                    <div>
                        <span>Biaya Kemasan Box ({{ $order->opsi_packing ?? 'Standar' }}):</span>
                        <span>Rp{{ number_format((float)$totalPacking, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>

            <div class="total">
                <span>Total Tagihan @if($isKeroyokanBatch)<small style="font-size:13px; color:#64748b;">(Seluruh Produk)</small>@endif</span>
                <strong>Rp{{ number_format((float)$grandTotal, 0, ',', '.') }}</strong>
            </div>

            <p class="foot">
                Metode pembayaran: <b>{{ $order->metode_pembayaran }}</b>@if($order->catatan) · Catatan: {{ $order->catatan }}@endif<br>
                Nota ini dibuat secara resmi dan sah oleh sistem LUDES-MARKET.
            </p>
        </div>


        <!-- ==========================================
             2. PER-PRODUCT SEPARATED SUB-RECEIPTS
             ========================================== -->
        @if($isKeroyokanBatch)
            <div id="section-separated" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-size: 16px; color: #123825; font-weight: 800;">
                        <i class="bi bi-receipt"></i> Rincian Nota Tiap Produk ({{ $batchOrders->count() }} Produk)
                    </h3>
                    <small style="color: #64748b;">Tiap item memiliki catatan toko dan produsen terpisah</small>
                </div>

                <div class="individual-cards-grid">
                    @foreach($batchOrders as $idx => $itemOrder)
                        <div class="receipt individual-order-card" style="margin-bottom: 18px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 14px;">
                                <div>
                                    <div class="sub-receipt-badge">
                                        <i class="bi bi-bag-check-fill"></i> Produk {{ $idx + 1 }} dari {{ $batchOrders->count() }}
                                    </div>
                                    <h3 style="margin: 0 0 4px; font-size: 18px; color: #0f172a; font-weight: 800;">
                                        {{ $itemOrder->produk->nama_produk }}
                                    </h3>
                                    <small style="color: #64748b;">ID Pesanan Item: #{{ str_pad($itemOrder->id, 5, '0', STR_PAD_LEFT) }}</small>
                                </div>
                                <div style="text-align: right;">
                                    <span class="status-pill status-{{ str_replace(' ', '', $itemOrder->status) }}">
                                        {{ $itemOrder->status }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid" style="margin: 14px 0 18px;">
                                <div>
                                    <h4>Produsen / Toko UMKM</h4>
                                    <p><i class="bi bi-shop"></i> <b>{{ $itemOrder->produk->umkm->nama_umkm }}</b></p>
                                    <p>Pemilik: {{ $itemOrder->produk->umkm->pemilik }}</p>
                                    <p><i class="bi bi-telephone"></i> {{ $itemOrder->produk->umkm->no_hp }}</p>
                                </div>
                                <div>
                                    <h4>Rincian Produk</h4>
                                    <p>Harga Satuan: <b>Rp{{ number_format((float)$itemOrder->produk->harga, 0, ',', '.') }}</b></p>
                                    <p>Jumlah: <b>{{ $itemOrder->jumlah }} unit / porsi</b></p>
                                    <p>Subtotal Item: <b style="color: #123825;">Rp{{ number_format((float)$itemOrder->produk->harga * $itemOrder->jumlah, 0, ',', '.') }}</b></p>
                                </div>
                            </div>

                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
                                <span>Total Alokasi Tagihan Item Ini:</span>
                                <strong style="font-size: 16px; color: #123825;">Rp{{ number_format((float)$itemOrder->total_harga, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="action-bar no-print" style="display: flex !important; justify-content: center !important; align-items: center !important; gap: 14px !important; margin: 24px auto 40px !important; text-align: center !important;">
            <button type="button" class="btn-back" onclick="if (document.referrer && document.referrer !== window.location.href) { window.location.href = document.referrer; } else { window.history.back(); }" style="padding: 12px 24px !important; background: #e2ded4 !important; color: #173d2b !important; border: 1px solid #c8c2b4 !important; cursor: pointer !important; font-size: 14px !important; font-weight: 700 !important; border-radius: 8px !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 2px 6px rgba(0,0,0,0.06) !important;">
                &larr; Kembali
            </button>
            <button type="button" class="btn-print" onclick="window.print()" style="padding: 12px 24px !important; background: #173d2b !important; color: #ffffff !important; border: 0 !important; cursor: pointer !important; font-size: 14px !important; font-weight: 700 !important; border-radius: 8px !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 2px 6px rgba(0,0,0,0.1) !important;">
                <i class="bi bi-printer-fill"></i> Cetak Nota
            </button>
        </div>

    </div>

    <script>
        function switchReceiptView(mode) {
            const secTotal = document.getElementById('section-total');
            const secSeparated = document.getElementById('section-separated');
            const btnTotal = document.getElementById('tabBtnTotal');
            const btnSeparated = document.getElementById('tabBtnSeparated');
            const btnAll = document.getElementById('tabBtnAll');

            if (btnTotal) btnTotal.classList.remove('active');
            if (btnSeparated) btnSeparated.classList.remove('active');
            if (btnAll) btnAll.classList.remove('active');

            if (mode === 'total') {
                if (secTotal) secTotal.style.display = 'block';
                if (secSeparated) secSeparated.style.display = 'none';
                if (btnTotal) btnTotal.classList.add('active');
            } else if (mode === 'separated') {
                if (secTotal) secTotal.style.display = 'none';
                if (secSeparated) secSeparated.style.display = 'block';
                if (btnSeparated) btnSeparated.classList.add('active');
            } else if (mode === 'all') {
                if (secTotal) secTotal.style.display = 'block';
                if (secSeparated) secSeparated.style.display = 'block';
                if (btnAll) btnAll.classList.add('active');
            }
        }
    </script>
</body>
</html>

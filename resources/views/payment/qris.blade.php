@extends('layouts.public')

@section('title', 'Pembayaran QRIS — LUDES-MARKET')

@section('content')
<section class="inner-hero compact-hero">
    <div class="shell">
        <div class="eyebrow"><span></span>Pembayaran Digital</div>
        <div class="inner-hero-row">
            <h1>Pembayaran QRIS<br><em>Transaksi Cepat & Aman</em></h1>
            <p>Scan kode QRIS menggunakan aplikasi e-wallet atau mobile banking pilihan Anda.</p>
        </div>
    </div>
</section>

<section class="section checkout-section">
    <div class="shell">
        <x-flash />

        <div class="checkout-layout">
            <div class="checkout-main">
                <section class="form-panel">
                    <div class="form-panel-head">
                        <span><i class="bi bi-qr-code"></i></span>
                        <div>
                            <h2>Status Pembayaran</h2>
                            <p>Referensi Pembayaran: <strong>{{ $payment->reference_id }}</strong></p>
                        </div>
                    </div>

                    @if($payment->status === 'PENDING')
                        <div style="text-align: center; padding: 20px 0;">
                            <div style="margin-bottom: 16px;">
                                <span class="badge" style="background: #fef3c7; color: #92400e; padding: 6px 16px; font-weight: 600; border-radius: 20px;">
                                    <i class="bi bi-clock-history"></i> MENUNGGU PEMBAYARAN
                                </span>
                            </div>

                            @if($qrDataUri)
                                <div style="display: inline-block; margin: 16px 0; background: #ffffff; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                                    <img src="{{ $qrDataUri }}" alt="Kode QRIS Pembayaran" style="max-width: 280px; width: 100%; height: auto; display: block; margin: 0 auto;">
                                </div>
                            @endif

                            <div style="margin-top: 12px;">
                                <h3 style="font-size: 1.5rem; margin-bottom: 4px; color: var(--text-dark, #1e293b);">
                                    Rp{{ number_format($payment->amount, 0, ',', '.') }}
                                </h3>
                                <p style="color: #64748b; font-size: 0.95rem;">Total Nominal Pembayaran</p>
                            </div>

                            @if($payment->expires_at)
                                <div id="countdownBox" style="margin-top: 20px; padding: 12px 20px; background: #f8fafc; border-radius: 12px; display: inline-block; border: 1px solid #e2e8f0;">
                                    <span style="font-size: 0.9rem; color: #64748b;">Berlaku hingga: </span>
                                    <strong style="color: #0f172a;">{{ $payment->expires_at->format('d M Y, H:i') }} WITA</strong>
                                    <div id="countdownTimer" style="font-size: 1.1rem; font-weight: 700; color: #d97706; margin-top: 4px;"></div>
                                </div>
                            @endif

                            @if(app()->environment(['local', 'testing']) && $payment->payment_method === 'QRIS' && !empty($payment->xendit_payment_request_id) && ($payment->expires_at === null || $payment->expires_at->isFuture()))
                                <div style="margin-top: 20px; padding: 16px; background: #f0fdf4; border: 1px dashed #10b981; border-radius: 12px; text-align: center;">
                                    <small style="color: #047857; display: block; margin-bottom: 8px; font-weight: 600;">
                                        <i class="bi bi-phone-vibrate-fill"></i> MODE PRESENTASI DEMO (SCAN KAMERA HP)
                                    </small>
                                    <p style="font-size: 0.85rem; color: #065f46; margin-bottom: 12px;">
                                        Kode QR di atas dikodekan untuk <strong>Scan Kamera HP (Google Lens / iPhone Camera)</strong>. Saat di-scan dengan kamera HP, halaman simulasi mobile akan terbuka di HP Anda!
                                    </p>
                                    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                                        <form action="{{ route('payment.qris.simulate', $payment->reference_id) }}" method="POST" onsubmit="this.querySelector('button').disabled = true;">
                                            @csrf
                                            <button type="submit" class="button button-outline" style="border-color: #059669; color: #047857; font-size: 0.88rem;">
                                                <i class="bi bi-play-circle-fill"></i> Simulasikan Pembayaran (Test Mode)
                                            </button>
                                        </form>
                                        @if(request()->query('qr_mode') === 'raw')
                                            <a href="{{ route('payment.qris.show', $payment->reference_id) }}" class="button button-outline" style="border-color: #64748b; color: #475569; font-size: 0.88rem;">
                                                <i class="bi bi-qr-code-scan"></i> Aktifkan Mobile Demo Scan
                                            </a>
                                        @else
                                            <a href="{{ route('payment.qris.show', [$payment->reference_id, 'qr_mode' => 'raw']) }}" class="button button-outline" style="border-color: #64748b; color: #475569; font-size: 0.88rem;">
                                                <i class="bi bi-code-slash"></i> Tampilkan Xendit Raw QR String
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div style="margin-top: 24px; padding: 20px; background: #f1f5f9; border-radius: 12px;">
                            <h4 style="margin-bottom: 12px; font-size: 1rem;"><i class="bi bi-info-circle"></i> Cara Pembayaran:</h4>
                            <ol style="margin-left: 20px; line-height: 1.6; color: #334155; font-size: 0.95rem;">
                                <li>Buka aplikasi e-wallet (GoPay, OVO, Dana, ShopeePay) atau Mobile Banking (BCA, Mandiri, BRI, BNI).</li>
                                <li>Pilih menu <strong>Scan / Bayar / QRIS</strong>.</li>
                                <li>Arahkan kamera ke kode QRIS di atas.</li>
                                <li>Periksa nama pedagang dan nominal Rp{{ number_format($payment->amount, 0, ',', '.') }}.</li>
                                <li>Selesaikan pembayaran di aplikasi Anda.</li>
                            </ol>
                        </div>

                    @elseif($payment->status === 'CREATING')
                        <div style="text-align: center; padding: 40px 20px;">
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 8px 20px; font-size: 1rem; border-radius: 20px;">
                                <i class="bi bi-hourglass-split"></i> PEMBAYARAN SEDANG DISIAPKAN
                            </span>
                            <p style="margin-top: 16px; color: #475569;">Sistem sedang menghubungkan permintaan ke penyedia pembayaran QRIS. Silakan muat ulang halaman ini dalam beberapa saat.</p>
                        </div>

                    @elseif($payment->status === 'CREATION_UNKNOWN')
                        <div style="text-align: center; padding: 40px 20px;">
                            <span class="badge" style="background: #fef3c7; color: #b45309; padding: 8px 20px; font-size: 1rem; border-radius: 20px;">
                                <i class="bi bi-exclamation-triangle"></i> MENUNGGU VERIFIKASI
                            </span>
                            <p style="margin-top: 16px; color: #475569;">Status pembuatan pembayaran sedang diverifikasi oleh sistem. Silakan periksa kembali beberapa saat lagi.</p>
                        </div>

                    @elseif($payment->status === 'PAID')
                        <div style="text-align: center; padding: 40px 20px;">
                            <span class="badge" style="background: #dcfce7; color: #15803d; padding: 8px 20px; font-size: 1rem; border-radius: 20px;">
                                <i class="bi bi-check-circle-fill"></i> PEMBAYARAN LUNAS
                            </span>
                            <h3 style="margin-top: 20px; font-size: 1.4rem;">Terima Kasih! Pembayaran Telah Diterima</h3>
                            <p style="color: #475569; margin-top: 8px;">Pesanan Anda sedang diproses oleh penjual.</p>
                            <div style="margin-top: 24px;">
                                <a href="{{ route('buyer.dashboard') }}" class="button wide"><i class="bi bi-speedometer2"></i> Ke Dashboard Pembeli</a>
                            </div>
                        </div>

                    @elseif($payment->status === 'FAILED')
                        <div style="text-align: center; padding: 40px 20px;">
                            <span class="badge" style="background: #fee2e2; color: #b91c1c; padding: 8px 20px; font-size: 1rem; border-radius: 20px;">
                                <i class="bi bi-x-circle-fill"></i> PEMBAYARAN GAGAL
                            </span>
                            <p style="margin-top: 16px; color: #475569;">Permintaan pembayaran ditolak atau tidak dapat diproses oleh provider.</p>
                            <div style="margin-top: 24px;">
                                <a href="{{ route('buyer.dashboard') }}" class="button button-outline wide"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                            </div>
                        </div>

                    @elseif($payment->status === 'EXPIRED')
                        <div style="text-align: center; padding: 40px 20px;">
                            <span class="badge" style="background: #f1f5f9; color: #475569; padding: 8px 20px; font-size: 1rem; border-radius: 20px;">
                                <i class="bi bi-clock"></i> PEMBAYARAN KADALUARSA
                            </span>
                            <p style="margin-top: 16px; color: #475569;">Masa berlaku kode QRIS ini telah berakhir.</p>
                            <div style="margin-top: 24px;">
                                <a href="{{ route('buyer.dashboard') }}" class="button button-outline wide"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                            </div>
                        </div>
                    @endif
                </section>
            </div>

            <aside class="order-summary">
                <span>Ringkasan Transaksi</span>
                <h2>{{ $payment->pesanan->count() }} Pesanan</h2>
                <div class="checkout-items">
                    @foreach($payment->pesanan as $p)
                        <div>
                            <span>{{ $p->jumlah }}× {{ $p->produk->nama_produk ?? 'Produk' }}</span>
                            <strong>Rp{{ number_format((float)$p->total_harga, 0, ',', '.') }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="summary-grand">
                    <span>Total Tagihan</span>
                    <strong>Rp{{ number_format($payment->amount, 0, ',', '.') }}</strong>
                </div>
                <div style="margin-top: 20px;">
                    <a class="back-cart" href="{{ route('buyer.dashboard') }}"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                </div>
            </aside>
        </div>
    </div>
</section>

@if($payment->status === 'PENDING' && $payment->expires_at)
@push('scripts')
<script>
    (function() {
        const expiresAt = new Date("{{ $payment->expires_at->toIso8601String() }}").getTime();
        const timerEl = document.getElementById('countdownTimer');
        if (!timerEl) return;

        function updateTimer() {
            const now = new Date().getTime();
            const diff = expiresAt - now;

            if (diff <= 0) {
                timerEl.innerText = "Perkiraan masa berlaku QR telah berakhir. Status pembayaran sedang menunggu konfirmasi sistem.";
                timerEl.style.fontSize = "0.9rem";
                timerEl.style.color = "#dc2626";
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            timerEl.innerText = `Sisa waktu: ${hours}j ${minutes}m ${seconds}d`;
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    })();
</script>
@endpush
@endif

@if(in_array($payment->status, ['CREATING', 'PENDING', 'CREATION_UNKNOWN'], true))
@push('scripts')
<script>
    (function() {
        const statusUrl = "{{ route('payment.qris.status', $payment->reference_id) }}";
        const initialStatus = "{{ $payment->status }}";
        let pollInterval = null;

        async function checkStatus() {
            if (document.visibilityState !== 'visible') return;

            try {
                const response = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store'
                });

                if (response.status === 401 || response.status === 403) {
                    if (pollInterval) clearInterval(pollInterval);
                    return;
                }

                if (!response.ok) return;

                const data = await response.json();
                if (data && data.status && data.status !== initialStatus) {
                    if (pollInterval) clearInterval(pollInterval);
                    window.location.reload();
                }
            } catch (err) {
                // Ignore network error, retry on next interval
            }
        }

        pollInterval = setInterval(checkStatus, 5000);

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                checkStatus();
            }
        });
    })();
</script>
@endpush
@endif
@endsection

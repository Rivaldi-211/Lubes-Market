/**
 * Real-time payment notifications for Seller (Mitra UMKM)
 * LUDES-MARKET
 */
(() => {
    const NOTIF_ENDPOINT = '/penjual/pesanan/notifikasi-pembayaran';
    const STORAGE_KEY = 'seller_seen_paid_orders_v1';
    const POLL_INTERVAL_MS = 20000; // 20 seconds

    function getSeenOrders() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch {
            return [];
        }
    }

    function markOrderAsSeen(orderId) {
        try {
            const seen = getSeenOrders();
            if (!seen.includes(orderId)) {
                seen.push(orderId);
                if (seen.length > 100) seen.shift();
                localStorage.setItem(STORAGE_KEY, JSON.stringify(seen));
            }
        } catch {
        }
    }

    function playNotificationSound() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const now = ctx.currentTime;

            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(523.25, now);
            gain1.gain.setValueAtTime(0.15, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.25);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(783.99, now + 0.12);
            gain2.gain.setValueAtTime(0.2, now + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.12);
            osc2.stop(now + 0.5);
        } catch {
        }
    }

    function showPaymentPopup(order) {
        playNotificationSound();

        const methodBadge = order.payment_method === 'QRIS'
            ? '<span style="display:inline-block;padding:3px 8px;border-radius:4px;background:#eef3eb;color:#173d2b;font-weight:700;font-size:11px;">⚡ QRIS (Lunas Otomatis)</span>'
            : '<span style="display:inline-block;padding:3px 8px;border-radius:4px;background:#fef3c7;color:#92400e;font-weight:700;font-size:11px;">📄 Transfer (Bukti Diunggah)</span>';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<span style="color:#173d2b;font-size:20px;font-weight:800;">🎉 Pembayaran Diterima!</span>',
                html: `
                    <div style="text-align:left;font-size:14px;color:#2c3531;line-height:1.6;margin-top:10px;">
                        <p style="margin:0 0 10px 0;">Pesanan <strong>#${order.id}</strong> telah dibayar oleh pembeli:</p>
                        <div style="background:#f8f9f6;border:1px solid #e2ded4;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                <span style="color:#64748b;">Pembeli:</span>
                                <strong>${order.buyer_name}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                <span style="color:#64748b;">Produk:</span>
                                <strong>${order.product_name}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                <span style="color:#64748b;">Total:</span>
                                <strong style="color:#173d2b;font-size:15px;">${order.amount_formatted}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="color:#64748b;">Metode:</span>
                                ${methodBadge}
                            </div>
                        </div>
                    </div>
                `,
                icon: 'success',
                iconColor: '#173d2b',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-receipt"></i> Buka Pesanan',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#173d2b',
                cancelButtonColor: '#788078',
                customClass: {
                    popup: 'seller-payment-popup-card'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = order.order_url;
                }
            });
        }
    }

    async function checkNewPayments(isInitialLoad = false) {
        try {
            const res = await fetch(NOTIF_ENDPOINT, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) return;

            const data = await res.json();
            if (!data.success || !Array.isArray(data.notifications)) return;

            const seen = getSeenOrders();

            if (isInitialLoad && seen.length === 0) {
                data.notifications.forEach(n => markOrderAsSeen(n.id));
                return;
            }

            const newOrders = data.notifications.filter(n => !seen.includes(n.id));

            if (newOrders.length > 0) {
                const latestNewOrder = newOrders[0];
                showPaymentPopup(latestNewOrder);

                newOrders.forEach(n => markOrderAsSeen(n.id));
            }
        } catch {
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkNewPayments(true);

        setInterval(() => {
            checkNewPayments(false);
        }, POLL_INTERVAL_MS);
    });
})();

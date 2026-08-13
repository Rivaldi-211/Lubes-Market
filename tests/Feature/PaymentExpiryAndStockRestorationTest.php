<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentExpiryAndStockRestorationTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private Produk $produk;
    private string $webhookToken = 'dummy_webhook_token_12345';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BumdesDemoSeeder::class);
        config([
            'services.xendit.secret_key' => 'xnd_development_dummy_key_12345',
            'services.xendit.webhook_token' => $this->webhookToken,
        ]);
        Http::preventStrayRequests();

        $this->buyer = User::where('role', 'pembeli')->firstOrFail();
        $seller = User::where('role', 'penjual')->firstOrFail();
        $umkm = $seller->umkm()->firstOrFail();

        $this->produk = Produk::create([
            'umkm_id' => $umkm->id,
            'kategori_id' => 1,
            'nama_produk' => 'Oli Mesin Test',
            'harga' => 50000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 10,
            'deskripsi' => 'Deskripsi test',
            'is_tersedia' => true,
        ]);
    }

    private function createPendingPaymentWithPesanan(int $quantity = 2): array
    {
        $initialStock = 10;
        $remainingStock = $initialStock - $quantity;
        $this->produk->update(['stok_jumlah' => $remainingStock]);

        $pesanan = Pesanan::create([
            'pembeli_id' => $this->buyer->id,
            'produk_id' => $this->produk->id,
            'jumlah' => $quantity,
            'total_harga' => $this->produk->harga * $quantity,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Menunggu',
            'tanggal_pesan' => now(),
        ]);

        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-EXP-' . uniqid(),
            'xendit_payment_request_id' => 'pr_exp_' . uniqid(),
            'amount' => $pesanan->total_harga,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
            'expires_at' => now()->addHours(48),
        ]);

        $payment->pesanan()->attach($pesanan->id);

        return [$payment, $pesanan];
    }

    public function test_1_payment_capture_transitions_pesanan_status_to_diproses(): void
    {
        [$payment, $pesanan] = $this->createPendingPaymentWithPesanan(2);

        $payload = [
            'event' => 'payment.capture',
            'data' => [
                'payment_id' => 'py_capture_123',
                'payment_request_id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'request_amount' => $payment->amount,
                'status' => 'SUCCEEDED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ],
        ];

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('Diproses', $pesanan->fresh()->status);
        $this->assertNull($payment->fresh()->stock_restored_at);
        $this->assertSame(8, $this->produk->fresh()->stok_jumlah);
    }

    public function test_2_payment_request_expiry_transitions_payment_to_expired_and_pesanan_to_dibatalkan_and_restores_stock(): void
    {
        [$payment, $pesanan] = $this->createPendingPaymentWithPesanan(3);

        $payload = [
            'event' => 'payment_request.expiry',
            'data' => [
                'id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'status' => 'EXPIRED',
            ],
        ];

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertSame('Dibatalkan', $pesanan->fresh()->status);
        $this->assertNotNull($payment->fresh()->stock_restored_at);
        $this->assertSame(10, $this->produk->fresh()->stok_jumlah); // Restored from 7 back to 10!
    }

    public function test_3_duplicate_expiry_webhook_is_idempotent_and_does_not_restore_stock_twice(): void
    {
        [$payment, $pesanan] = $this->createPendingPaymentWithPesanan(4); // Remaining stock = 6

        $payload = [
            'event' => 'payment_request.expiry',
            'data' => [
                'id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'status' => 'EXPIRED',
            ],
        ];

        // First expiry callback
        $res1 = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);
        $res1->assertStatus(200);
        $this->assertSame(10, $this->produk->fresh()->stok_jumlah);
        $firstRestoredAt = $payment->fresh()->stock_restored_at->toDateTimeString();

        // Duplicate expiry callback
        $res2 = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);
        $res2->assertStatus(200);

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertSame(10, $this->produk->fresh()->stok_jumlah); // Stock stays 10, NOT 14!
        $this->assertSame($firstRestoredAt, $payment->fresh()->stock_restored_at->toDateTimeString());
    }

    public function test_4_payment_failure_transitions_to_failed_dibatalkan_and_restores_stock(): void
    {
        [$payment, $pesanan] = $this->createPendingPaymentWithPesanan(2); // Remaining stock = 8

        $payload = [
            'event' => 'payment.failure',
            'data' => [
                'payment_id' => 'py_fail_999',
                'payment_request_id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'request_amount' => $payment->amount,
                'status' => 'FAILED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ],
        ];

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('FAILED', $payment->fresh()->status);
        $this->assertSame('Dibatalkan', $pesanan->fresh()->status);
        $this->assertNotNull($payment->fresh()->stock_restored_at);
        $this->assertSame(10, $this->produk->fresh()->stok_jumlah);
    }

    public function test_5_late_expiry_for_already_paid_payment_is_ignored_without_restoring_stock(): void
    {
        [$payment, $pesanan] = $this->createPendingPaymentWithPesanan(2);

        // Mark PAID via capture first
        $capturePayload = [
            'event' => 'payment.capture',
            'data' => [
                'payment_id' => 'py_capture_888',
                'payment_request_id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'request_amount' => $payment->amount,
                'status' => 'SUCCEEDED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ],
        ];
        $this->postJson('/webhooks/xendit/payment', $capturePayload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('Diproses', $pesanan->fresh()->status);
        $this->assertSame(8, $this->produk->fresh()->stok_jumlah);

        // Late expiry callback arrives
        $expiryPayload = [
            'event' => 'payment_request.expiry',
            'data' => [
                'id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'status' => 'EXPIRED',
            ],
        ];
        $lateResponse = $this->postJson('/webhooks/xendit/payment', $expiryPayload, ['x-callback-token' => $this->webhookToken]);

        $lateResponse->assertStatus(200);
        $this->assertSame('PAID', $payment->fresh()->status); // Remains PAID!
        $this->assertSame('Diproses', $pesanan->fresh()->status); // Remains Diproses!
        $this->assertNull($payment->fresh()->stock_restored_at);
        $this->assertSame(8, $this->produk->fresh()->stok_jumlah); // Stock remains 8!
    }
}

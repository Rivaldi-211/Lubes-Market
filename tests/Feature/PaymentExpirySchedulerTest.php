<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentExpirySchedulerTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private Produk $produk1;
    private Produk $produk2;
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

        $this->produk1 = Produk::create([
            'umkm_id' => $umkm->id,
            'kategori_id' => 1,
            'nama_produk' => 'Oli Mesin Test 1',
            'harga' => 50000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 10,
            'deskripsi' => 'Deskripsi test 1',
            'is_tersedia' => true,
        ]);

        $this->produk2 = Produk::create([
            'umkm_id' => $umkm->id,
            'kategori_id' => 1,
            'nama_produk' => 'Oli Mesin Test 2',
            'harga' => 30000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 15,
            'deskripsi' => 'Deskripsi test 2',
            'is_tersedia' => true,
        ]);
    }

    private function createPaymentRecord(string $method = 'QRIS', string $status = 'PENDING', ?int $expiresAtOffsetSeconds = -60, int $qty1 = 2, int $qty2 = 0): Payment
    {
        $totalAmount = 0;
        $pesananIds = [];

        if ($qty1 > 0) {
            $this->produk1->decrement('stok_jumlah', $qty1);
            $p1 = Pesanan::create([
                'pembeli_id' => $this->buyer->id,
                'produk_id' => $this->produk1->id,
                'jumlah' => $qty1,
                'total_harga' => $this->produk1->harga * $qty1,
                'metode_pembayaran' => $method,
                'status' => 'Menunggu',
                'tanggal_pesan' => now(),
            ]);
            $pesananIds[] = $p1->id;
            $totalAmount += $p1->total_harga;
        }

        if ($qty2 > 0) {
            $this->produk2->decrement('stok_jumlah', $qty2);
            $p2 = Pesanan::create([
                'pembeli_id' => $this->buyer->id,
                'produk_id' => $this->produk2->id,
                'jumlah' => $qty2,
                'total_harga' => $this->produk2->harga * $qty2,
                'metode_pembayaran' => $method,
                'status' => 'Menunggu',
                'tanggal_pesan' => now(),
            ]);
            $pesananIds[] = $p2->id;
            $totalAmount += $p2->total_harga;
        }

        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-SCHED-' . uniqid(),
            'xendit_payment_request_id' => 'pr_sched_' . uniqid(),
            'amount' => $totalAmount,
            'payment_method' => $method,
            'status' => $status,
            'expires_at' => $expiresAtOffsetSeconds !== null ? now()->addSeconds($expiresAtOffsetSeconds) : null,
        ]);

        $payment->pesanan()->attach($pesananIds);

        return $payment;
    }

    public function test_1_pending_stale_qris_payment_is_expired_and_stock_restored(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', -60, 2, 0); // initial stock 10 -> 8

        Artisan::call('payments:expire-stale');

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->stock_restored_at);
        $this->assertSame('Dibatalkan', $payment->pesanan->first()->fresh()->status);
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah); // Restored from 8 back to 10!
    }

    public function test_2_pending_unexpired_qris_payment_remains_untouched(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', 3600, 2, 0); // expires in 1h

        Artisan::call('payments:expire-stale');

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->stock_restored_at);
        $this->assertSame('Menunggu', $payment->pesanan->first()->fresh()->status);
        $this->assertSame(8, $this->produk1->fresh()->stok_jumlah);
    }

    public function test_3_paid_stale_payment_remains_untouched(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PAID', -60, 2, 0);

        Artisan::call('payments:expire-stale');

        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->stock_restored_at);
        $this->assertSame(8, $this->produk1->fresh()->stok_jumlah);
    }

    public function test_4_failed_stale_payment_does_not_double_restore_stock(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'FAILED', -60, 2, 0);
        $payment->update(['stock_restored_at' => now()->subMinute()]);
        $this->produk1->update(['stok_jumlah' => 10]); // Restored previously

        Artisan::call('payments:expire-stale');

        $this->assertSame('FAILED', $payment->fresh()->status);
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah); // Stock stays 10, not 12!
    }

    public function test_5_expired_stale_payment_does_not_double_restore_stock(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'EXPIRED', -60, 2, 0);
        $payment->update(['stock_restored_at' => now()->subMinute()]);
        $this->produk1->update(['stok_jumlah' => 10]);

        Artisan::call('payments:expire-stale');

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah);
    }

    public function test_6_duplicate_scheduler_execution_is_idempotent(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', -60, 3, 0); // Remaining stock = 7

        Artisan::call('payments:expire-stale');
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah);
        $firstRestoredAt = $payment->fresh()->stock_restored_at->toDateTimeString();

        // Second run
        Artisan::call('payments:expire-stale');
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah); // Still 10, not 13!
        $this->assertSame($firstRestoredAt, $payment->fresh()->stock_restored_at->toDateTimeString());
    }

    public function test_7_expiry_webhook_and_scheduler_interaction_is_idempotent(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', -60, 4, 0); // Remaining stock = 6

        // Webhook arrives first
        $payload = [
            'event' => 'payment_request.expiry',
            'data' => [
                'id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'status' => 'EXPIRED',
            ],
        ];
        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah);

        // Scheduler runs afterwards
        Artisan::call('payments:expire-stale');
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah); // Still 10!
    }

    public function test_8_capture_paid_and_scheduler_race_protection(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', -60, 2, 0); // Remaining stock = 8

        // Webhook capture arrives first
        $payload = [
            'event' => 'payment.capture',
            'data' => [
                'payment_id' => 'py_race_999',
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
        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame('PAID', $payment->fresh()->status);

        // Scheduler runs after capture
        Artisan::call('payments:expire-stale');
        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->stock_restored_at);
        $this->assertSame(8, $this->produk1->fresh()->stok_jumlah);
    }

    public function test_9_multiple_products_quantities_restored_exactly(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', -60, 3, 4); // p1 stock 10->7, p2 stock 15->11

        Artisan::call('payments:expire-stale');

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah); // Restored 7 -> 10
        $this->assertSame(15, $this->produk2->fresh()->stok_jumlah); // Restored 11 -> 15
    }

    public function test_10_payment_with_expires_at_null_remains_untouched(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', null, 2, 0);

        Artisan::call('payments:expire-stale');

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->stock_restored_at);
    }

    public function test_11_non_qris_payment_remains_untouched(): void
    {
        $payment = $this->createPaymentRecord('COD', 'PENDING', -60, 2, 0);

        Artisan::call('payments:expire-stale');

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->stock_restored_at);
    }

    public function test_12_pesanan_status_not_menunggu_handled_safely(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', -60, 2, 0);
        $pesanan = $payment->pesanan->first();
        $pesanan->update(['status' => 'Diproses']);

        Artisan::call('payments:expire-stale');

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertSame('Diproses', $pesanan->fresh()->status); // Order status preserved if not Menunggu!
        $this->assertSame(10, $this->produk1->fresh()->stok_jumlah); // Stock still restored idempotently!
    }

    public function test_13_boundary_expires_at_now_minus_1_sec_is_expired(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', -1, 2, 0);

        Artisan::call('payments:expire-stale');

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->stock_restored_at);
    }

    public function test_14_boundary_expires_at_now_plus_1_sec_is_untouched(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', 1, 2, 0);

        Artisan::call('payments:expire-stale');

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->stock_restored_at);
    }

    public function test_15_boundary_expires_at_exact_now_is_expired(): void
    {
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', 0, 2, 0);

        Artisan::call('payments:expire-stale');

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->stock_restored_at);
    }

    public function test_16_timezone_offset_handling_in_query(): void
    {
        // Set expires_at in past with explicit UTC offset
        $pastUtc = now()->subMinutes(5)->setTimezone('UTC');
        $payment = $this->createPaymentRecord('QRIS', 'PENDING', null, 2, 0);
        $payment->update(['expires_at' => $pastUtc]);

        Artisan::call('payments:expire-stale');

        $this->assertSame('EXPIRED', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->stock_restored_at);
    }
}

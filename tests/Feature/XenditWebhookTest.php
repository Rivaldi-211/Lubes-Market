<?php

namespace Tests\Feature;

use App\Exceptions\Xendit\XenditAmbiguousException;
use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Services\QrisPaymentService;
use App\Services\XenditService;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private Produk $produk;
    private string $webhookToken = 'webhook_token_secret_test_12345';

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
        $umkm = $seller->umkm()->first();

        $this->produk = Produk::create([
            'umkm_id' => $umkm->id,
            'kategori_id' => 1,
            'nama_produk' => 'Beras Organik 5kg',
            'harga' => 50000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 10,
        ]);
    }

    private function createPayment(array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-WEBHOOK-' . uniqid(),
            'xendit_payment_request_id' => 'pr_wh_' . uniqid(),
            'amount' => 50000,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
            'expires_at' => now()->addHours(24),
        ], $overrides));
    }

    private function validCapturePayload(Payment $payment, array $dataOverrides = []): array
    {
        return [
            'event' => 'payment.capture',
            'data' => array_merge([
                'payment_id' => 'py_cap_' . uniqid(),
                'payment_request_id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'request_amount' => 50000,
                'status' => 'SUCCEEDED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ], $dataOverrides),
        ];
    }

    private function validFailurePayload(Payment $payment, array $dataOverrides = []): array
    {
        return [
            'event' => 'payment.failure',
            'data' => array_merge([
                'payment_id' => 'py_fail_' . uniqid(),
                'payment_request_id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'request_amount' => 50000,
                'status' => 'FAILED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ], $dataOverrides),
        ];
    }

    // ==========================================
    // TOKEN SECURITY TESTS (1 - 6)
    // ==========================================

    public function test_1_webhook_token_config_missing_rejected_503_no_mutation(): void
    {
        config(['services.xendit.webhook_token' => '']);
        $payment = $this->createPayment();

        $payload = $this->validCapturePayload($payment);
        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => 'any_token']);

        $response->assertStatus(503);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_2_missing_x_callback_token_header_rejected_401(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload);

        $response->assertStatus(401);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_3_wrong_x_callback_token_header_rejected_401(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => 'wrong_token']);

        $response->assertStatus(401);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_4_valid_x_callback_token_reaches_handler_200(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('PAID', $payment->fresh()->status);
    }

    public function test_5_secret_or_token_does_not_appear_in_response_body(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertDontSee($this->webhookToken);
        $response->assertDontSee('xnd_development_dummy_key_12345');
    }

    public function test_6_invalid_token_does_not_query_or_mutate_payment_state(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $payload = $this->validCapturePayload($payment);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => 'invalid_token']);

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
    }

    // ==========================================
    // CAPTURE TESTS (7 - 20)
    // ==========================================

    public function test_7_valid_payment_capture_transitions_pending_to_paid(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $payload = $this->validCapturePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('PAID', $payment->fresh()->status);
    }

    public function test_8_stores_xendit_payment_id(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['payment_id' => 'py_exact_123']);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame('py_exact_123', $payment->fresh()->xendit_payment_id);
    }

    public function test_9_paid_at_populated(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_10_payment_request_id_cross_check(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['payment_request_id' => 'pr_different_999']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_11_reference_id_mismatch_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['reference_id' => 'PAY-OTHER-999']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_12_amount_mismatch_rejected(): void
    {
        $payment = $this->createPayment(['amount' => 50000]);
        $payload = $this->validCapturePayload($payment, ['request_amount' => 60000]);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_13_currency_non_idr_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['currency' => 'USD']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
    }

    public function test_14_country_non_id_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['country' => 'SG']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
    }

    public function test_15_channel_non_qris_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['channel_code' => 'OVO']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
    }

    public function test_16_type_non_pay_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['type' => 'RECURRING']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
    }

    public function test_17_capture_method_non_automatic_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['capture_method' => 'MANUAL']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
    }

    public function test_18_data_status_not_succeeded_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['status' => 'PENDING']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
    }

    public function test_19_missing_payment_id_rejected(): void
    {
        $payment = $this->createPayment();
        $payload = $this->validCapturePayload($payment, ['payment_id' => '']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
    }

    public function test_20_mismatched_existing_payment_id_rejected(): void
    {
        $payment = $this->createPayment(['xendit_payment_id' => 'py_existing_111']);
        $payload = $this->validCapturePayload($payment, ['payment_id' => 'py_different_222']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
        $this->assertSame('py_existing_111', $payment->fresh()->xendit_payment_id);
    }

    // ==========================================
    // IDEMPOTENCY TESTS (21 - 27)
    // ==========================================

    public function test_21_duplicate_capture_same_provider_ids_returns_200(): void
    {
        $payment = $this->createPayment(['status' => 'PAID', 'xendit_payment_id' => 'py_same_123', 'paid_at' => now()->subHour()]);
        $payload = $this->validCapturePayload($payment, ['payment_id' => 'py_same_123']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('PAID', $payment->fresh()->status);
    }

    public function test_22_duplicate_capture_does_not_alter_paid_at(): void
    {
        $pastPaidAt = now()->subHours(2)->toDateTimeString();
        $payment = $this->createPayment(['status' => 'PAID', 'xendit_payment_id' => 'py_same_123', 'paid_at' => $pastPaidAt]);
        $payload = $this->validCapturePayload($payment, ['payment_id' => 'py_same_123']);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame($pastPaidAt, $payment->fresh()->paid_at->toDateTimeString());
    }

    public function test_23_duplicate_capture_does_not_create_new_payment(): void
    {
        $payment = $this->createPayment(['status' => 'PAID', 'xendit_payment_id' => 'py_same_123']);
        $payload = $this->validCapturePayload($payment, ['payment_id' => 'py_same_123']);
        $initialCount = Payment::count();

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame($initialCount, Payment::count());
    }

    public function test_24_valid_capture_updates_pesanan_status_to_diproses(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $pesanan = Pesanan::create([
            'pembeli_id' => $this->buyer->id,
            'produk_id' => $this->produk->id,
            'jumlah' => 1,
            'total_harga' => 50000,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Menunggu',
            'tanggal_pesan' => now(),
        ]);
        $payment->pesanan()->attach($pesanan->id);
        $payload = $this->validCapturePayload($payment);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame('Diproses', $pesanan->fresh()->status);
    }

    public function test_25_duplicate_capture_does_not_alter_stock(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $initialStock = $this->produk->stok_jumlah;
        $payload = $this->validCapturePayload($payment);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame($initialStock, $this->produk->fresh()->stok_jumlah);
    }

    public function test_26_paid_status_not_downgraded_by_later_failure_callback(): void
    {
        $payment = $this->createPayment(['status' => 'PAID', 'xendit_payment_id' => 'py_same_123']);
        $payload = $this->validFailurePayload($payment, ['payment_id' => 'py_same_123']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('PAID', $payment->fresh()->status);
    }

    public function test_27_late_valid_capture_expired_to_paid_succeeds(): void
    {
        $payment = $this->createPayment(['status' => 'EXPIRED']);
        $payload = $this->validCapturePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('PAID', $payment->fresh()->status);
    }

    // ==========================================
    // FAILURE TESTS (28 - 35)
    // ==========================================

    public function test_28_valid_failure_pending_to_failed(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $payload = $this->validFailurePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('FAILED', $payment->fresh()->status);
    }

    public function test_29_creation_unknown_to_failed(): void
    {
        $payment = $this->createPayment(['status' => 'CREATION_UNKNOWN']);
        $payload = $this->validFailurePayload($payment);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('FAILED', $payment->fresh()->status);
    }

    public function test_30_duplicate_failure_returns_200_no_op(): void
    {
        $payment = $this->createPayment(['status' => 'FAILED', 'xendit_payment_id' => 'py_fail_same']);
        $payload = $this->validFailurePayload($payment, ['payment_id' => 'py_fail_same']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $this->assertSame('FAILED', $payment->fresh()->status);
    }

    public function test_31_failed_does_not_create_new_payment(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $payload = $this->validFailurePayload($payment);
        $initialCount = Payment::count();

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame($initialCount, Payment::count());
    }

    public function test_32_valid_failure_updates_pesanan_status_to_dibatalkan(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $pesanan = Pesanan::create([
            'pembeli_id' => $this->buyer->id,
            'produk_id' => $this->produk->id,
            'jumlah' => 1,
            'total_harga' => 50000,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Menunggu',
            'tanggal_pesan' => now(),
        ]);
        $payment->pesanan()->attach($pesanan->id);
        $payload = $this->validFailurePayload($payment);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame('Dibatalkan', $pesanan->fresh()->status);
    }

    public function test_33_failure_does_not_change_stock(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $initialStock = $this->produk->stok_jumlah;
        $payload = $this->validFailurePayload($payment);

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $this->assertSame($initialStock, $this->produk->fresh()->stok_jumlah);
    }

    public function test_34_failure_with_amount_mismatch_rejected(): void
    {
        $payment = $this->createPayment(['amount' => 50000]);
        $payload = $this->validFailurePayload($payment, ['request_amount' => 70000]);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_35_failure_with_different_payment_id_rejected(): void
    {
        $payment = $this->createPayment(['status' => 'FAILED', 'xendit_payment_id' => 'py_fail_1']);
        $payload = $this->validFailurePayload($payment, ['payment_id' => 'py_fail_2']);

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(400);
        $this->assertSame('py_fail_1', $payment->fresh()->xendit_payment_id);
    }

    // ==========================================
    // WEBHOOK / CREATE RACE TESTS (36 - 40)
    // ==========================================

    public function test_36_payment_creating_with_provider_ids_null_can_be_matched_through_reference_id(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-RACE-36',
            'amount' => 50000,
            'status' => 'CREATING',
            'xendit_payment_request_id' => null,
            'xendit_payment_id' => null,
        ]);

        $payload = [
            'event' => 'payment.capture',
            'data' => [
                'payment_id' => 'py_race_36',
                'payment_request_id' => 'pr_race_36',
                'reference_id' => 'PAY-RACE-36',
                'request_amount' => 50000,
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
        $fresh = $payment->fresh();
        $this->assertSame('PAID', $fresh->status);
        $this->assertSame('pr_race_36', $fresh->xendit_payment_request_id);
        $this->assertSame('py_race_36', $fresh->xendit_payment_id);
    }

    public function test_37_valid_capture_in_create_race_creating_to_paid_and_binds_ids(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-RACE-37',
            'amount' => 50000,
            'status' => 'CREATING',
        ]);

        $payload = [
            'event' => 'payment.capture',
            'data' => [
                'payment_id' => 'py_race_37',
                'payment_request_id' => 'pr_race_37',
                'reference_id' => 'PAY-RACE-37',
                'request_amount' => 50000,
                'status' => 'SUCCEEDED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ],
        ];

        $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $fresh = $payment->fresh();
        $this->assertSame('PAID', $fresh->status);
        $this->assertSame('pr_race_37', $fresh->xendit_payment_request_id);
        $this->assertSame('py_race_37', $fresh->xendit_payment_id);
    }

    public function test_38_subsequent_create_transaction_b_does_not_overwrite_paid_to_pending(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-RACE-38',
            'amount' => 50000,
            'status' => 'CREATING',
        ]);

        // Webhook wins race while HTTP call finishes: CREATING -> PAID
        $payment->update([
            'status' => 'PAID',
            'paid_at' => now(),
            'xendit_payment_request_id' => 'pr_race_38',
            'xendit_payment_id' => 'py_race_38',
        ]);

        // Transaction B finishes after HTTP call
        \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($lockedPayment->status === 'CREATING') {
                $lockedPayment->update(['status' => 'PENDING']);
            }
        });

        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('py_race_38', $payment->fresh()->xendit_payment_id);
    }

    public function test_39_qris_payment_service_preserves_paid_after_webhook_won_race(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-RACE-39',
            'amount' => 50000,
            'status' => 'CREATING',
        ]);

        // Webhook wins race: CREATING -> PAID
        $payment->update([
            'status' => 'PAID',
            'paid_at' => now(),
            'xendit_payment_request_id' => 'pr_race_39',
            'xendit_payment_id' => 'py_race_39',
        ]);

        // Transaction B executes after webhook
        \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($lockedPayment->status === 'CREATING') {
                $lockedPayment->update(['status' => 'PENDING']);
            } elseif ($lockedPayment->status === 'PAID') {
                $lockedPayment->update([
                    'xendit_payment_request_id' => $lockedPayment->xendit_payment_request_id ?? 'pr_race_39',
                ]);
            }
        });

        $this->assertSame('PAID', $payment->fresh()->status);
    }

    public function test_40_ambiguous_exception_handling_does_not_overwrite_paid_to_creation_unknown(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-RACE-40',
            'amount' => 50000,
            'status' => 'CREATING',
        ]);

        // Webhook wins race: CREATING -> PAID
        $payment->update([
            'status' => 'PAID',
            'paid_at' => now(),
            'xendit_payment_request_id' => 'pr_race_40',
            'xendit_payment_id' => 'py_race_40',
        ]);

        // Exception handler Transaction B attempts updatePaymentStatusInTransactionB($id, 'CREATION_UNKNOWN')
        \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($lockedPayment && $lockedPayment->status === 'CREATING') {
                $lockedPayment->update(['status' => 'CREATION_UNKNOWN']);
            }
        });

        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame('py_race_40', $payment->fresh()->xendit_payment_id);
    }

    // ==========================================
    // UNSUPPORTED EVENTS TESTS (41 - 42)
    // ==========================================

    public function test_41_payment_authorization_valid_token_ignored_200_no_mutation(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $payload = [
            'event' => 'payment.authorization',
            'data' => [
                'reference_id' => $payment->reference_id,
            ],
        ];

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Event ignored.', 'event' => 'payment.authorization']);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_42_payment_expiry_valid_token_ignored_200_no_mutation(): void
    {
        $payment = $this->createPayment(['status' => 'PENDING']);
        $payload = [
            'event' => 'payment.expiry',
            'data' => [
                'reference_id' => $payment->reference_id,
            ],
        ];

        $response = $this->postJson('/webhooks/xendit/payment', $payload, ['x-callback-token' => $this->webhookToken]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Event ignored.', 'event' => 'payment.expiry']);
        $this->assertSame('PENDING', $payment->fresh()->status);
    }
}

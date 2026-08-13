<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QrisPaymentSimulationTest extends TestCase
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
            'nama_produk' => 'Kopi Bubuk Murni 250g',
            'harga' => 25000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 20,
        ]);
    }

    private function createPendingPayment(array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-SIM-APP-' . uniqid(),
            'amount' => 25000,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
            'xendit_payment_request_id' => 'pr_sim_app_' . uniqid(),
            'expires_at' => now()->addHours(24),
        ], $overrides));
    }

    public function test_18_owner_can_simulate_own_pending_payment_in_testing(): void
    {
        $payment = $this->createPendingPayment();

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated successfully',
            ], 200),
        ]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('success');
    }

    public function test_19_customer_b_gets_404_when_simulating_customer_a_payment(): void
    {
        $otherBuyer = User::create([
            'username' => 'buyer_sim_b',
            'nama_lengkap' => 'Buyer Sim B',
            'email' => 'buyer_sim_b@example.com',
            'password' => 'secret',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        $payment = $this->createPendingPayment();

        $response = $this->actingAs($otherBuyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertStatus(404);
        Http::assertNothingSent();
    }

    public function test_20_unknown_reference_simulation_returns_404(): void
    {
        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/PAY-UNKNOWN-SIM-999/simulate');

        $response->assertStatus(404);
        Http::assertNothingSent();
    }

    public function test_21_unauthenticated_simulation_rejected(): void
    {
        $payment = $this->createPendingPayment();

        $response = $this->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/login');
        Http::assertNothingSent();
    }

    public function test_22_non_pembeli_role_denied(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();
        $payment = $this->createPendingPayment(['user_id' => $seller->id]);

        $response = $this->actingAs($seller)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertStatus(403);
        Http::assertNothingSent();
    }

    public function test_23_creating_status_rejected_before_xendit(): void
    {
        $payment = $this->createPendingPayment(['status' => 'CREATING']);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_24_creation_unknown_status_rejected_before_xendit(): void
    {
        $payment = $this->createPendingPayment(['status' => 'CREATION_UNKNOWN']);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_25_paid_status_rejected_before_xendit(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PAID', 'paid_at' => now()]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_26_failed_status_rejected_before_xendit(): void
    {
        $payment = $this->createPendingPayment(['status' => 'FAILED']);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_27_expired_status_rejected_before_xendit(): void
    {
        $payment = $this->createPendingPayment(['status' => 'EXPIRED']);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_28_missing_payment_request_id_rejected_before_xendit(): void
    {
        $payment = $this->createPendingPayment(['xendit_payment_request_id' => null]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_29_stale_pending_rejected_before_xendit(): void
    {
        $payment = $this->createPendingPayment(['expires_at' => now()->subMinute()]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_30_simulate_success_redirects_payment_page(): void
    {
        $payment = $this->createPendingPayment();

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated',
            ], 200),
        ]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
    }

    public function test_31_local_payment_remains_pending_after_accepted_simulation(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PENDING']);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated',
            ], 200),
        ]);

        $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_32_simulation_success_does_not_set_paid_at(): void
    {
        $payment = $this->createPendingPayment(['paid_at' => null]);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated',
            ], 200),
        ]);

        $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $this->assertNull($payment->fresh()->paid_at);
    }

    public function test_33_simulation_success_does_not_alter_pesanan(): void
    {
        $payment = $this->createPendingPayment();
        $pesanan = Pesanan::create([
            'pembeli_id' => $this->buyer->id,
            'produk_id' => $this->produk->id,
            'jumlah' => 1,
            'total_harga' => 25000,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Menunggu',
            'tanggal_pesan' => now(),
        ]);
        $payment->pesanan()->attach($pesanan->id);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated',
            ], 200),
        ]);

        $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $this->assertSame('Menunggu', $pesanan->fresh()->status);
    }

    public function test_34_simulation_success_does_not_alter_stock(): void
    {
        $payment = $this->createPendingPayment();
        $initialStock = $this->produk->stok_jumlah;

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated',
            ], 200),
        ]);

        $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $this->assertSame($initialStock, $this->produk->fresh()->stok_jumlah);
    }

    public function test_35_button_visible_on_eligible_pending_in_testing(): void
    {
        $payment = $this->createPendingPayment();

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_36_button_absent_on_paid(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PAID', 'paid_at' => now()]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_37_button_absent_on_failed(): void
    {
        $payment = $this->createPendingPayment(['status' => 'FAILED']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_38_button_absent_on_expired(): void
    {
        $payment = $this->createPendingPayment(['status' => 'EXPIRED']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_39_simulation_controller_unavailable_in_production_environment(): void
    {
        $payment = $this->createPendingPayment();
        App::detectEnvironment(fn() => 'production');

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
            ->actingAs($this->buyer)
            ->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertStatus(404);
    }

    public function test_40_zero_outgoing_xendit_request_in_production_guard_case(): void
    {
        $payment = $this->createPendingPayment();
        App::detectEnvironment(fn() => 'production');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
            ->actingAs($this->buyer)
            ->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        Http::assertNothingSent();
    }

    public function test_41_full_local_end_to_end_automated_flow(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PENDING']);

        // Step 1: Fake simulation endpoint response HTTP 200
        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([
                'status' => 'PENDING',
                'message' => 'Simulated successfully',
            ], 200),
        ]);

        // Step 2: POST simulation route
        $simResponse = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');
        $simResponse->assertRedirect('/pembayaran/qris/' . $payment->reference_id);

        // Assert local Payment is STILL PENDING
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);

        // Step 3: Webhook callback sends payment.capture
        $webhookPayload = [
            'event' => 'payment.capture',
            'data' => [
                'payment_id' => 'py_e2e_test_999',
                'payment_request_id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'request_amount' => 25000,
                'status' => 'SUCCEEDED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ],
        ];

        $webhookResponse = $this->postJson('/webhooks/xendit/payment', $webhookPayload, ['x-callback-token' => $this->webhookToken]);
        $webhookResponse->assertStatus(200);

        // Assert local Payment is now PAID!
        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);

        // Step 4: STEP 8 Status Polling Endpoint reads PAID from DB
        $statusResponse = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');
        $statusResponse->assertStatus(200);
        $statusResponse->assertJsonPath('status', 'PAID');
    }

    public function test_42_duplicate_webhook_after_simulation_remains_paid_and_idempotent(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PENDING']);

        $webhookPayload = [
            'event' => 'payment.capture',
            'data' => [
                'payment_id' => 'py_e2e_dup_777',
                'payment_request_id' => $payment->xendit_payment_request_id,
                'reference_id' => $payment->reference_id,
                'request_amount' => 25000,
                'status' => 'SUCCEEDED',
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
            ],
        ];

        // First callback
        $this->postJson('/webhooks/xendit/payment', $webhookPayload, ['x-callback-token' => $this->webhookToken]);
        $firstPaidAt = $payment->fresh()->paid_at->toDateTimeString();

        // Duplicate callback
        $dupResponse = $this->postJson('/webhooks/xendit/payment', $webhookPayload, ['x-callback-token' => $this->webhookToken]);
        $dupResponse->assertStatus(200);

        $this->assertSame('PAID', $payment->fresh()->status);
        $this->assertSame($firstPaidAt, $payment->fresh()->paid_at->toDateTimeString());
    }

    public function test_43_controller_handles_provider_4xx_rejected_redirects_with_error_and_preserves_pending(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PENDING']);
        $pesanan = Pesanan::create([
            'pembeli_id' => $this->buyer->id,
            'produk_id' => $this->produk->id,
            'jumlah' => 1,
            'total_harga' => 25000,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Menunggu',
            'tanggal_pesan' => now(),
        ]);
        $payment->pesanan()->attach($pesanan->id);
        $initialStock = $this->produk->stok_jumlah;

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response(['message' => 'Rejected'], 400),
        ]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertNull($payment->fresh()->xendit_payment_id);
        $this->assertSame('Menunggu', $pesanan->fresh()->status);
        $this->assertSame($initialStock, $this->produk->fresh()->stok_jumlah);
    }

    public function test_44_controller_handles_provider_5xx_ambiguous_redirects_with_error_and_preserves_pending(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PENDING']);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response([], 500),
        ]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
    }

    public function test_45_controller_handles_connection_exception_redirects_with_error_and_preserves_pending(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PENDING']);

        Http::fake(['https://api.xendit.co/v3/payment_requests/*' => Http::failedConnection()]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_46_controller_handles_malformed_response_redirects_with_error_and_preserves_pending(): void
    {
        $payment = $this->createPendingPayment(['status' => 'PENDING']);

        Http::fake([
            'https://api.xendit.co/v3/payment_requests/' . $payment->xendit_payment_request_id . '/simulate' => Http::response(['status' => 'WRONG'], 200),
        ]);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_47_controller_handles_configuration_missing_redirects_with_error_without_exposing_secret(): void
    {
        config(['services.xendit.secret_key' => '']);
        $payment = $this->createPendingPayment(['status' => 'PENDING']);

        $response = $this->actingAs($this->buyer)->post('/pembayaran/qris/' . $payment->reference_id . '/simulate');

        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $response->assertSessionHas('error');
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_48_button_absent_on_pending_without_payment_request_id(): void
    {
        $payment = $this->createPendingPayment(['xendit_payment_request_id' => null]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_49_button_absent_on_stale_pending(): void
    {
        $payment = $this->createPendingPayment(['expires_at' => now()->subMinute()]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_50_button_absent_on_non_qris_payment(): void
    {
        $payment = $this->createPendingPayment(['payment_method' => 'COD']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_51_button_absent_on_creating(): void
    {
        $payment = $this->createPendingPayment(['status' => 'CREATING']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }

    public function test_52_button_absent_on_creation_unknown(): void
    {
        $payment = $this->createPendingPayment(['status' => 'CREATION_UNKNOWN']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('Simulasikan Pembayaran (Test Mode)');
    }
}

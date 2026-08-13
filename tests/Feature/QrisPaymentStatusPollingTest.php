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

class QrisPaymentStatusPollingTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BumdesDemoSeeder::class);
        config(['services.xendit.secret_key' => 'xnd_development_dummy_key_12345']);
        Http::preventStrayRequests();

        $this->buyer = User::where('role', 'pembeli')->firstOrFail();
        $seller = User::where('role', 'penjual')->firstOrFail();
        $umkm = $seller->umkm()->first();

        $this->produk = Produk::create([
            'umkm_id' => $umkm->id,
            'kategori_id' => 1,
            'nama_produk' => 'Teh Celup Lokal 25s',
            'harga' => 12000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 15,
        ]);
    }

    private function createTestPayment(array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-STATUS-' . uniqid(),
            'amount' => 12000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
            'xendit_payment_request_id' => 'pr_status_test_123',
            'raw_response' => ['raw_key' => 'raw_value'],
            'expires_at' => now()->addHours(24),
        ], $overrides));
    }

    public function test_1_owner_receives_status_json_200(): void
    {
        $payment = $this->createTestPayment();

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertStatus(200);
        $response->assertJson([
            'reference_id' => $payment->reference_id,
            'status' => 'PENDING',
        ]);
    }

    public function test_2_customer_b_cannot_view_customer_a_payment_status_404(): void
    {
        $otherBuyer = User::create([
            'username' => 'buyer_status_b',
            'nama_lengkap' => 'Buyer Status B',
            'email' => 'buyer_status_b@example.com',
            'password' => 'secret',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        $payment = $this->createTestPayment();

        $response = $this->actingAs($otherBuyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertStatus(404);
    }

    public function test_3_unknown_reference_status_404(): void
    {
        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/PAY-UNKNOWN-REF-9999/status');

        $response->assertStatus(404);
    }

    public function test_4_unauthenticated_request_follows_auth_behavior(): void
    {
        $payment = $this->createTestPayment();

        $response = $this->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertStatus(401);
    }

    public function test_5_non_pembeli_role_denied(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();
        $payment = $this->createTestPayment(['user_id' => $seller->id]);

        $response = $this->actingAs($seller)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertStatus(403);
    }

    public function test_6_response_has_canonical_status(): void
    {
        $payment = $this->createTestPayment(['status' => 'PENDING']);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('status', 'PENDING');
    }

    public function test_7_response_has_reference_id(): void
    {
        $payment = $this->createTestPayment();

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('reference_id', $payment->reference_id);
    }

    public function test_8_response_timestamp_nullable_iso_contract_is_valid(): void
    {
        $payment = $this->createTestPayment([
            'paid_at' => null,
            'expires_at' => '2026-08-15 10:00:00',
        ]);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('paid_at', null);
        $response->assertJsonPath('expires_at', $payment->expires_at->toIso8601String());
        $this->assertNotNull($response->json('server_time'));
    }

    public function test_9_response_does_not_contain_qr_string(): void
    {
        $payment = $this->createTestPayment();

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonMissingPath('qr_string');
        $response->assertDontSee($payment->qr_string);
    }

    public function test_10_response_does_not_contain_raw_response(): void
    {
        $payment = $this->createTestPayment();

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonMissingPath('raw_response');
        $response->assertDontSee('raw_key');
        $response->assertDontSee('raw_value');
    }

    public function test_11_response_does_not_contain_xendit_payment_request_id(): void
    {
        $payment = $this->createTestPayment();

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonMissingPath('xendit_payment_request_id');
        $response->assertDontSee('pr_status_test_123');
    }

    public function test_12_response_does_not_contain_xendit_payment_id(): void
    {
        $payment = $this->createTestPayment(['xendit_payment_id' => 'py_status_test_999']);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonMissingPath('xendit_payment_id');
        $response->assertDontSee('py_status_test_999');
    }

    public function test_13_response_does_not_contain_provider_request_started_at(): void
    {
        $payment = $this->createTestPayment(['provider_request_started_at' => now()]);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonMissingPath('provider_request_started_at');
    }

    public function test_14_response_does_not_contain_secret_or_token(): void
    {
        $payment = $this->createTestPayment();
        $secretKey = 'xnd_development_dummy_key_12345';

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertDontSee($secretKey);
        $response->assertDontSee('token');
        $response->assertDontSee('secret');
    }

    public function test_15_get_status_does_not_mutate_payment_status(): void
    {
        $payment = $this->createTestPayment(['status' => 'PENDING']);

        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_16_get_status_does_not_mutate_paid_at(): void
    {
        $payment = $this->createTestPayment(['paid_at' => null]);

        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $this->assertNull($payment->fresh()->paid_at);
    }

    public function test_17_get_status_does_not_create_new_payment(): void
    {
        $payment = $this->createTestPayment();
        $initialCount = Payment::count();

        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $this->assertSame($initialCount, Payment::count());
    }

    public function test_18_get_status_does_not_mutate_pesanan(): void
    {
        $payment = $this->createTestPayment();
        $pesanan = Pesanan::create([
            'pembeli_id' => $this->buyer->id,
            'produk_id' => $this->produk->id,
            'jumlah' => 1,
            'total_harga' => 12000,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Menunggu',
            'tanggal_pesan' => now(),
        ]);
        $payment->pesanan()->attach($pesanan->id);

        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $this->assertSame('Menunggu', $pesanan->fresh()->status);
    }

    public function test_19_get_status_does_not_mutate_stock(): void
    {
        $payment = $this->createTestPayment();
        $initialStock = $this->produk->stok_jumlah;

        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $this->assertSame($initialStock, $this->produk->fresh()->stok_jumlah);
    }

    public function test_20_repeated_polling_does_not_perform_db_mutation(): void
    {
        $payment = $this->createTestPayment(['status' => 'PENDING']);
        $initialUpdatedAt = $payment->fresh()->updated_at->toDateTimeString();

        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');
        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');
        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $this->assertSame($initialUpdatedAt, $payment->fresh()->updated_at->toDateTimeString());
    }

    public function test_21_polling_status_does_not_send_http_request_to_xendit(): void
    {
        $payment = $this->createTestPayment();

        $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        Http::assertNothingSent();
    }

    public function test_22_repeated_polling_remains_zero_xendit_calls(): void
    {
        $payment = $this->createTestPayment();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');
        }

        Http::assertNothingSent();
    }

    public function test_23_response_has_cache_control_no_store(): void
    {
        $payment = $this->createTestPayment();

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_24_creating_status_reflected_correctly(): void
    {
        $payment = $this->createTestPayment(['status' => 'CREATING']);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('status', 'CREATING');
    }

    public function test_25_pending_status_reflected_correctly(): void
    {
        $payment = $this->createTestPayment(['status' => 'PENDING']);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('status', 'PENDING');
    }

    public function test_26_creation_unknown_status_reflected_correctly(): void
    {
        $payment = $this->createTestPayment(['status' => 'CREATION_UNKNOWN']);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('status', 'CREATION_UNKNOWN');
    }

    public function test_27_paid_status_reflected_correctly(): void
    {
        $payment = $this->createTestPayment(['status' => 'PAID', 'paid_at' => now()]);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('status', 'PAID');
        $this->assertNotNull($response->json('paid_at'));
    }

    public function test_28_failed_status_reflected_correctly(): void
    {
        $payment = $this->createTestPayment(['status' => 'FAILED']);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('status', 'FAILED');
    }

    public function test_29_expired_status_reflected_correctly(): void
    {
        $payment = $this->createTestPayment(['status' => 'EXPIRED']);

        $response = $this->actingAs($this->buyer)->getJson('/pembayaran/qris/' . $payment->reference_id . '/status');

        $response->assertJsonPath('status', 'EXPIRED');
    }

    public function test_30_pending_page_has_status_endpoint_url(): void
    {
        $payment = $this->createTestPayment(['status' => 'PENDING']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertSee('/pembayaran/qris/' . $payment->reference_id . '/status');
    }

    public function test_31_creating_page_has_polling_behavior(): void
    {
        $payment = $this->createTestPayment(['status' => 'CREATING']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertSee('/pembayaran/qris/' . $payment->reference_id . '/status');
        $response->assertSee('checkStatus');
    }

    public function test_32_creation_unknown_page_has_polling_behavior(): void
    {
        $payment = $this->createTestPayment(['status' => 'CREATION_UNKNOWN']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertSee('/pembayaran/qris/' . $payment->reference_id . '/status');
        $response->assertSee('checkStatus');
    }

    public function test_33_paid_page_does_not_run_polling_timer(): void
    {
        $payment = $this->createTestPayment(['status' => 'PAID', 'paid_at' => now()]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('pollInterval = setInterval');
    }

    public function test_34_failed_page_does_not_run_polling_timer(): void
    {
        $payment = $this->createTestPayment(['status' => 'FAILED']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('pollInterval = setInterval');
    }

    public function test_35_expired_page_does_not_run_polling_timer(): void
    {
        $payment = $this->createTestPayment(['status' => 'EXPIRED']);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertDontSee('pollInterval = setInterval');
    }
}

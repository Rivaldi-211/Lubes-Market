<?php

namespace Tests\Feature;

use App\Exceptions\Payment\QrisPaymentValidationException;
use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QrisCheckoutAndPaymentPageTest extends TestCase
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
            'nama_produk' => 'Minyak Goreng Sawit 1L',
            'harga' => 15000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 20,
        ]);
    }

    private function setSessionCart(array $cartItems): void
    {
        session(['cart' => $cartItems]);
    }

    public function test_1_existing_non_qris_checkout_behavior_unchanged(): void
    {
        $this->setSessionCart([$this->produk->id => 2]);
        $initialStock = $this->produk->stok_jumlah;

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'COD',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $response->assertRedirect('/pembeli');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pesanan', [
            'pembeli_id' => $this->buyer->id,
            'produk_id' => $this->produk->id,
            'jumlah' => 2,
            'metode_pembayaran' => 'COD',
            'status' => 'Menunggu',
        ]);

        $this->assertSame($initialStock - 2, $this->produk->fresh()->stok_jumlah);
        $this->assertSame([], session('cart', []));
    }

    public function test_2_qris_checkout_amount_less_than_or_equal_10m_succeeds(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-qris-test-2',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $payment = Payment::latest('id')->firstOrFail();
        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $this->assertSame('PENDING', $payment->status);
    }

    public function test_3_authoritative_total_greater_than_10m_rejected_before_commit(): void
    {
        $expensive = Produk::create([
            'umkm_id' => $this->produk->umkm_id,
            'kategori_id' => 1,
            'nama_produk' => 'Barang Mahal',
            'harga' => 10000001,
            'stok_status' => 'Ready',
            'stok_jumlah' => 10,
        ]);
        $this->setSessionCart([$expensive->id => 1]);
        $initialStock = $expensive->stok_jumlah;

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $response->assertSessionHasErrors('cart');
        $this->assertDatabaseMissing('pesanan', ['produk_id' => $expensive->id]);
        $this->assertSame($initialStock, $expensive->fresh()->stok_jumlah);
        $this->assertSame([$expensive->id => 1], session('cart'));
    }

    public function test_4_fractional_authoritative_qris_price_rejected(): void
    {
        DB::table('produk')->where('id', $this->produk->id)->update(['harga' => 15000.50]);
        $this->setSessionCart([$this->produk->id => 1]);
        $initialStock = $this->produk->stok_jumlah;

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $response->assertSessionHasErrors('cart');
        $this->assertDatabaseMissing('pesanan', ['produk_id' => $this->produk->id]);
        $this->assertSame($initialStock, $this->produk->fresh()->stok_jumlah);
        $this->assertSame([$this->produk->id => 1], session('cart'));
    }

    public function test_5_browser_does_not_provide_authoritative_amount(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-qris-test-5',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        // Attempting to send spoofed amount parameters in post request body
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
            'amount' => 100, // Spoofed low amount
            'total_harga' => 100,
        ]);

        $payment = Payment::latest('id')->firstOrFail();
        $this->assertSame(15000, $payment->amount); // DB authoritative price enforced
    }

    public function test_6_qris_successful_checkout_creates_pesanan_only_once(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-qris-test-6',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $initialCount = Pesanan::where('pembeli_id', $this->buyer->id)->count();

        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $this->assertSame($initialCount + 1, Pesanan::where('pembeli_id', $this->buyer->id)->count());
    }

    public function test_7_qris_success_redirects_payment_qris_show(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-qris-test-7',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $payment = Payment::latest('id')->firstOrFail();
        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
    }

    public function test_8_provider_rejected_after_order_creation_does_not_redirect_checkout(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['message' => 'Channel disabled'], 422)]);

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $payment = Payment::latest('id')->firstOrFail();
        $this->assertSame('FAILED', $payment->status);
        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $this->assertNotEquals(url('/checkout'), $response->headers->get('Location'));
    }

    public function test_9_provider_unknown_after_order_creation_does_not_redirect_checkout(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $payment = Payment::latest('id')->firstOrFail();
        $this->assertSame('CREATION_UNKNOWN', $payment->status);
        $response->assertRedirect('/pembayaran/qris/' . $payment->reference_id);
        $this->assertNotEquals(url('/checkout'), $response->headers->get('Location'));
    }

    public function test_10_qris_payment_validation_exception_after_created_orders_does_not_redirect_checkout(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        $mockQrisService = $this->createMock(\App\Services\QrisPaymentService::class);
        $mockQrisService->expects($this->once())
            ->method('initiateQrisPayment')
            ->willThrowException(new QrisPaymentValidationException('Invalid payment parameters.'));

        $this->app->instance(\App\Services\QrisPaymentService::class, $mockQrisService);

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $response->assertRedirect('/pembeli');
        $response->assertSessionHas('error', 'Invalid payment parameters.');
    }

    public function test_11_cart_is_cleared_only_after_successful_checkout_service_commit(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-qris-test-11',
                'reference_id' => $req['reference_id'],
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $req['request_amount'],
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr_code_val']],
                'created' => '2026-08-13T10:00:00.000Z',
            ], 201);
        }]);

        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 12',
            'no_hp_pembeli' => '081234567890',
        ]);

        $this->assertSame([], session('cart', []));
    }

    public function test_12_owner_can_view_payment_page(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-OWNER-CAN-VIEW',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertSee('PAY-OWNER-CAN-VIEW');
    }

    public function test_13_customer_b_cannot_view_customer_a_payment_404(): void
    {
        $otherBuyer = User::create([
            'username' => 'buyer_b',
            'nama_lengkap' => 'Buyer B',
            'email' => 'buyer_b@example.com',
            'password' => 'secret',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        $paymentA = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-CUSTOMER-A-ONLY',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($otherBuyer)->get('/pembayaran/qris/' . $paymentA->reference_id);

        $response->assertStatus(404);
    }

    public function test_14_unauthenticated_user_redirected_to_login(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-UNAUTH-CHECK',
            'amount' => 15000,
            'status' => 'PENDING',
        ]);

        $response = $this->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertRedirect('/login');
    }

    public function test_15_non_pembeli_role_denied_by_existing_middleware(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();

        $payment = Payment::create([
            'user_id' => $seller->id,
            'reference_id' => 'PAY-SELLER-ROLE-CHECK',
            'amount' => 15000,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($seller)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(403);
    }

    public function test_16_unknown_reference_404(): void
    {
        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/PAY-UNKNOWN-99999');

        $response->assertStatus(404);
    }

    public function test_17_pending_renders_qr(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-PENDING-RENDERS-QR',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW01189360091100000000005204531153033605802ID5911LUBESMARKET6015MONCONGLOE LAPP61059055462070703A01630467A0',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertSee('Kode QRIS Pembayaran');
    }

    public function test_18_qr_is_generated_from_qr_string(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-GEN-FROM-STRING',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertStatus(200);
        $response->assertSee('data:image/svg+xml;base64,', false);
    }

    public function test_19_generated_qr_output_is_server_side_data_uri(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-SERVER-SIDE-DATA-URI',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertSee('<img src="data:image/svg+xml;base64,', false);
    }

    public function test_20_page_html_does_not_contain_xendit_secret(): void
    {
        $secretKey = 'xnd_development_dummy_key_12345';
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-NO-SECRET-IN-HTML',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertDontSee($secretKey);
    }

    public function test_21_page_html_does_not_contain_webhook_token(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-NO-WEBHOOK-TOKEN',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertDontSee('webhook_token');
        $response->assertDontSee('xendit_callback_token');
    }

    public function test_22_raw_response_is_not_printed(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-NO-RAW-RESPONSE',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
            'raw_response' => ['secret_provider_internal_id' => 'secret_98765'],
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertDontSee('secret_provider_internal_id');
        $response->assertDontSee('secret_98765');
    }

    public function test_23_creating_does_not_render_qr(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-CREATING-NO-QR',
            'amount' => 15000,
            'status' => 'CREATING',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertSee('PEMBAYARAN SEDANG DISIAPKAN');
        $response->assertDontSee('Kode QRIS Pembayaran');
    }

    public function test_24_creation_unknown_does_not_render_qr(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-UNKNOWN-NO-QR',
            'amount' => 15000,
            'status' => 'CREATION_UNKNOWN',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertSee('MENUNGGU VERIFIKASI');
        $response->assertDontSee('Kode QRIS Pembayaran');
    }

    public function test_25_paid_does_not_render_active_qr(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-PAID-NO-QR',
            'amount' => 15000,
            'status' => 'PAID',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertSee('PEMBAYARAN LUNAS');
        $response->assertDontSee('Kode QRIS Pembayaran');
    }

    public function test_26_failed_does_not_render_active_qr(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-FAILED-NO-QR',
            'amount' => 15000,
            'status' => 'FAILED',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertSee('PEMBAYARAN GAGAL');
        $response->assertDontSee('Kode QRIS Pembayaran');
    }

    public function test_27_expired_does_not_render_active_qr(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-EXPIRED-NO-QR',
            'amount' => 15000,
            'status' => 'EXPIRED',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $response = $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $response->assertSee('PEMBAYARAN KADALUARSA');
        $response->assertDontSee('Kode QRIS Pembayaran');
    }

    public function test_28_get_show_does_not_mutate_payment_status(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-READONLY-CHECK',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_29_refresh_get_does_not_create_another_payment(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-REFRESH-NO-NEW-ROW',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $initialCount = Payment::count();

        $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);
        $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        $this->assertSame($initialCount, Payment::count());
    }

    public function test_30_show_page_never_calls_xendit_api(): void
    {
        $payment = Payment::create([
            'user_id' => $this->buyer->id,
            'reference_id' => 'PAY-NO-XENDIT-HTTP-CALL',
            'amount' => 15000,
            'status' => 'PENDING',
            'qr_string' => '00020101021226670016COM.XENDIT.WWW',
        ]);

        $this->actingAs($this->buyer)->get('/pembayaran/qris/' . $payment->reference_id);

        Http::assertNothingSent();
    }
}

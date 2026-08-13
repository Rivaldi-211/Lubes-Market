<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QrisDoubleSubmitHardeningTest extends TestCase
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
            'nama_produk' => 'Beras Kepala 5kg',
            'harga' => 65000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 10,
        ]);
    }

    private function setSessionCart(array $cartItems): void
    {
        session(['cart' => $cartItems]);
    }

    public function test_1_post_checkout_with_empty_cart_rejected_server_side(): void
    {
        $this->setSessionCart([]);
        $initialOrdersCount = Pesanan::count();
        $initialStock = $this->produk->stok_jumlah;

        $response = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'COD',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);

        $response->assertSessionHasErrors('cart');
        $this->assertSame($initialOrdersCount, Pesanan::count());
        $this->assertSame($initialStock, $this->produk->fresh()->stok_jumlah);
    }

    public function test_2_empty_qris_cart_does_not_create_payment(): void
    {
        $this->setSessionCart([]);
        $initialPaymentCount = Payment::count();

        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);

        $this->assertSame($initialPaymentCount, Payment::count());
    }

    public function test_3_empty_qris_cart_does_not_send_http_xendit(): void
    {
        $this->setSessionCart([]);

        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);

        Http::assertNothingSent();
    }

    public function test_4_route_checkout_store_has_session_blocking_configured(): void
    {
        $route = Route::getRoutes()->getByName('checkout.store');
        $this->assertNotNull($route);
        $this->assertSame(30, $route->locksFor());
        $this->assertSame(35, $route->waitsFor());
    }

    public function test_5_sequential_duplicate_request_after_first_request_clears_cart_does_not_create_second_order(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);
        $initialOrdersCount = Pesanan::count();

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-double-submit-5',
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

        // Request 1: succeeds, creates order, clears cart
        $response1 = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $response1->assertSessionHasNoErrors();
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());

        // Request 2: duplicate submit with now-empty cart
        $response2 = $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $response2->assertSessionHasErrors('cart');
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());
    }

    public function test_6_sequential_duplicate_qris_request_payment_count_does_not_increase(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);
        $initialPaymentCount = Payment::count();

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-double-submit-6',
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
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialPaymentCount + 1, Payment::count());

        // Duplicate submit
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialPaymentCount + 1, Payment::count());
    }

    public function test_7_sequential_duplicate_qris_request_xendit_outgoing_request_count_is_one(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);

        Http::fake(['https://api.xendit.co/v3/payment_requests' => function ($req) {
            return Http::response([
                'payment_request_id' => 'pr-double-submit-7',
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
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);

        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);

        Http::assertSentCount(1);
    }

    public function test_8_sequential_duplicate_cod_request_does_not_create_two_orders(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);
        $initialOrdersCount = Pesanan::count();

        // Request 1: COD checkout
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'COD',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());

        // Request 2: Duplicate COD checkout
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'COD',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());
    }

    public function test_9_provider_failed_on_first_request_second_checkout_does_not_create_new_order(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);
        $initialOrdersCount = Pesanan::count();

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(['message' => 'Channel inactive'], 422)]);

        // First request: order created, provider fails
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());

        // Second request
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());
    }

    public function test_10_provider_creation_unknown_on_first_request_second_checkout_does_not_create_new_order(): void
    {
        $this->setSessionCart([$this->produk->id => 1]);
        $initialOrdersCount = Pesanan::count();

        Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::failedConnection()]);

        // First request: order created, provider unknown
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());

        // Second request
        $this->actingAs($this->buyer)->post('/checkout', [
            'metode_pembayaran' => 'QRIS',
            'alamat_pengiriman' => 'Jalan Moncongloe Lappara No 5',
            'no_hp_pembeli' => '081234567890',
        ]);
        $this->assertSame($initialOrdersCount + 1, Pesanan::count());
    }
}

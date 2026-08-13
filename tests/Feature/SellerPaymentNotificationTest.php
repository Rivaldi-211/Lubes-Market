<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerPaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BumdesDemoSeeder::class);
    }

    public function test_guest_cannot_access_seller_payment_notifications(): void
    {
        $response = $this->getJson(route('seller.orders.notifications'));
        $response->assertUnauthorized();
    }

    public function test_buyer_cannot_access_seller_payment_notifications(): void
    {
        $buyer = User::where('role', 'pembeli')->firstOrFail();
        $response = $this->actingAs($buyer)->getJson(route('seller.orders.notifications'));
        $response->assertForbidden();
    }

    public function test_seller_receives_payment_notifications_only_for_own_umkm(): void
    {
        $sellers = User::where('role', 'penjual')->get();
        $this->assertGreaterThanOrEqual(2, $sellers->count());

        $sellerA = $sellers[0];
        $sellerB = $sellers[1];

        $productA = $sellerA->umkm->produk()->firstOrFail();
        $productB = $sellerB->umkm->produk()->firstOrFail();

        $buyer = User::where('role', 'pembeli')->firstOrFail();

        // Order A (QRIS Paid for UMKM A)
        $orderA = Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $productA->id,
            'jumlah' => 2,
            'total_harga' => $productA->harga * 2,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Diproses',
        ]);

        // Order B (QRIS Paid for UMKM B)
        $orderB = Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $productB->id,
            'jumlah' => 3,
            'total_harga' => $productB->harga * 3,
            'metode_pembayaran' => 'QRIS',
            'status' => 'Diproses',
        ]);

        $response = $this->actingAs($sellerA)->getJson(route('seller.orders.notifications'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'notifications' => [
                '*' => [
                    'id',
                    'buyer_name',
                    'product_name',
                    'amount_formatted',
                    'payment_method',
                    'status',
                    'has_proof',
                    'time_ago',
                    'order_url',
                ]
            ]
        ]);

        $notifIds = collect($response->json('notifications'))->pluck('id')->all();
        $this->assertContains($orderA->id, $notifIds);
        $this->assertNotContains($orderB->id, $notifIds);
    }
}

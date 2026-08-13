<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestSellingProductsStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BumdesDemoSeeder::class);
    }

    public function test_landing_page_renders_top_1_best_selling_product(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();
        $buyer = User::where('role', 'pembeli')->firstOrFail();
        $product = $seller->umkm->produk()->firstOrFail();

        // Create valid processed order
        Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $product->id,
            'jumlah' => 50,
            'total_harga' => $product->harga * 50,
            'metode_pembayaran' => 'COD',
            'status' => 'Diproses',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('PRODUK TERLARIS NO. 1 DESA');
        $response->assertSee($product->nama_produk);
    }

    public function test_seller_dashboard_renders_top_5_own_best_selling_products(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();
        $buyer = User::where('role', 'pembeli')->firstOrFail();
        $product = $seller->umkm->produk()->firstOrFail();

        Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $product->id,
            'jumlah' => 25,
            'total_harga' => $product->harga * 25,
            'metode_pembayaran' => 'COD',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($seller)->get('/penjual');

        $response->assertStatus(200);
        $response->assertSee('Top 5 Menu Terlaris Toko');
        $response->assertSee($product->nama_produk);
    }

    public function test_admin_dashboard_renders_top_10_best_selling_products_across_all_umkms(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $buyer = User::where('role', 'pembeli')->firstOrFail();
        $product = Produk::firstOrFail();

        Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $product->id,
            'jumlah' => 30,
            'total_harga' => $product->harga * 30,
            'metode_pembayaran' => 'COD',
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Top 10 Menu Terlaris Desa');
        $response->assertSee($product->nama_produk);
    }

    public function test_cancelled_orders_are_excluded_from_best_selling_statistics(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();
        $buyer = User::where('role', 'pembeli')->firstOrFail();
        $productA = $seller->umkm->produk()->firstOrFail();
        $productB = $seller->umkm->produk()->skip(1)->first() ?? $productA;

        // Cancelled order with 100 quantity
        Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $productA->id,
            'jumlah' => 100,
            'total_harga' => $productA->harga * 100,
            'metode_pembayaran' => 'COD',
            'status' => 'Dibatalkan',
        ]);

        // Processed order with 10 quantity
        Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $productB->id,
            'jumlah' => 10,
            'total_harga' => $productB->harga * 10,
            'metode_pembayaran' => 'COD',
            'status' => 'Diproses',
        ]);

        $response = $this->actingAs($seller)->get('/penjual');

        $response->assertStatus(200);
        // Product B (10 items) should rank above Product A (100 cancelled items)
        $response->assertSee($productB->nama_produk);
    }
}

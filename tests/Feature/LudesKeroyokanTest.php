<?php

namespace Tests\Feature;

use App\Models\BatchKeroyokan;
use App\Models\Kategori;
use App\Models\KelompokKeroyokan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use App\Services\CartService;
use App\Services\KeroyokanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LudesKeroyokanTest extends TestCase
{
    use RefreshDatabase;

    private Kategori $kategoriKuliner;
    private Kategori $kategoriKerajinan;
    private KelompokKeroyokan $kelompokSnack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kategoriKuliner = Kategori::create(['nama_kategori' => 'Kuliner']);
        $this->kategoriKerajinan = Kategori::create(['nama_kategori' => 'Kerajinan']);

        $this->kelompokSnack = KelompokKeroyokan::create([
            'kategori_id' => $this->kategoriKuliner->id,
            'nama_kelompok' => 'Snack Box Standar',
            'deskripsi' => 'Paket snack box untuk acara desa',
            'aktif' => true,
        ]);
    }

    private function createSellerWithProduct(string $umkmName, string $productName, float $price, int $stock, ?KelompokKeroyokan $group = null, string $umkmStatus = 'aktif', string $stockStatus = 'Ready'): array
    {
        $user = User::create([
            'username' => 'seller_' . uniqid(),
            'password' => 'password123',
            'nama_lengkap' => 'Mitra ' . $umkmName,
            'email' => 'seller_' . uniqid() . '@example.com',
            'role' => 'penjual',
            'status' => 'aktif',
        ]);

        $umkm = Umkm::create([
            'user_id' => $user->id,
            'nama_umkm' => $umkmName,
            'pemilik' => 'Pemilik ' . $umkmName,
            'alamat' => 'Moncongloe',
            'no_hp' => '08123456789',
            'status' => $umkmStatus,
        ]);

        $product = Produk::create([
            'umkm_id' => $umkm->id,
            'kategori_id' => $group ? $group->kategori_id : $this->kategoriKuliner->id,
            'kelompok_keroyokan_id' => $group?->id,
            'nama_produk' => $productName,
            'harga' => $price,
            'stok_status' => $stockStatus,
            'stok_jumlah' => $stock,
        ]);

        return [$user, $umkm, $product];
    }

    public function test_57_normal_flow_multi_umkm_allocation(): void
    {
        [,, $pA] = $this->createSellerWithProduct('UMKM A', 'Snack A', 15000, 100, $this->kelompokSnack);
        [,, $pB] = $this->createSellerWithProduct('UMKM B', 'Snack B', 15000, 80, $this->kelompokSnack);
        [,, $pC] = $this->createSellerWithProduct('UMKM C', 'Snack C', 16000, 70, $this->kelompokSnack);

        $service = app(KeroyokanService::class);
        $result = $service->calculateAllocation($this->kelompokSnack, 250);

        $this::assertEquals('success', $result['status']);
        $this::assertEquals(250, $result['target_quantity']);
        $this::assertGreaterThanOrEqual(2, $result['distinct_umkms_count']);

        $totalAllocated = collect($result['allocations'])->sum('quantity');
        $this::assertEquals(250, $totalAllocated);

        foreach ($result['allocations'] as $alloc) {
            $this::assertLessThanOrEqual($alloc['product']->stok_jumlah, $alloc['quantity']);
        }
    }

    public function test_58_single_umkm_sufficient_does_not_trigger_keroyokan(): void
    {
        $this->createSellerWithProduct('UMKM A', 'Snack Super A', 15000, 300, $this->kelompokSnack);
        $this->createSellerWithProduct('UMKM B', 'Snack B', 15000, 100, $this->kelompokSnack);

        $service = app(KeroyokanService::class);
        $result = $service->calculateAllocation($this->kelompokSnack, 250);

        $this::assertEquals('single_umkm_sufficient', $result['status']);
    }

    public function test_59_insufficient_aggregate_stock(): void
    {
        $this->createSellerWithProduct('UMKM A', 'Snack A', 15000, 100, $this->kelompokSnack);
        $this->createSellerWithProduct('UMKM B', 'Snack B', 15000, 80, $this->kelompokSnack);

        $service = app(KeroyokanService::class);
        $result = $service->calculateAllocation($this->kelompokSnack, 250);

        $this::assertEquals('insufficient_stock', $result['status']);
        $this::assertEquals(180, $result['available']);
        $this::assertEquals(70, $result['shortage']);
    }

    public function test_60_inactive_umkm_stock_ignored(): void
    {
        $this->createSellerWithProduct('UMKM Aktif A', 'Snack A', 15000, 100, $this->kelompokSnack, 'aktif');
        $this->createSellerWithProduct('UMKM Nonaktif B', 'Snack B', 15000, 500, $this->kelompokSnack, 'nonaktif');
        $this->createSellerWithProduct('UMKM Aktif C', 'Snack C', 15000, 80, $this->kelompokSnack, 'aktif');

        $service = app(KeroyokanService::class);
        $result = $service->calculateAllocation($this->kelompokSnack, 150);

        // 500 stock of nonaktif UMKM must be ignored. Total available active = 180.
        // For 150 target, A gives 100, C gives 50. Total = 150.
        $this::assertEquals('success', $result['status']);
        $allocatedProducts = collect($result['allocations'])->pluck('product_id')->toArray();
        $this::assertNotContains(Produk::where('nama_produk', 'Snack B')->value('id'), $allocatedProducts);
    }

    public function test_61_out_of_stock_product_not_eligible(): void
    {
        $this->createSellerWithProduct('UMKM A', 'Snack Ready', 15000, 100, $this->kelompokSnack, 'aktif', 'Ready');
        $this->createSellerWithProduct('UMKM B', 'Snack Habis 1', 15000, 0, $this->kelompokSnack, 'aktif', 'Ready');
        $this->createSellerWithProduct('UMKM C', 'Snack Habis 2', 15000, 100, $this->kelompokSnack, 'aktif', 'Habis');

        $service = app(KeroyokanService::class);
        $eligible = $service->getEligibleProducts($this->kelompokSnack);

        $this::assertCount(1, $eligible);
        $this::assertEquals('Snack Ready', $eligible->first()->nama_produk);
    }

    public function test_62_different_group_product_excluded(): void
    {
        $otherGroup = KelompokKeroyokan::create([
            'kategori_id' => $this->kategoriKuliner->id,
            'nama_kelompok' => 'Kelompok Lain',
            'aktif' => true,
        ]);

        $this->createSellerWithProduct('UMKM A', 'Snack Group A', 15000, 100, $this->kelompokSnack);
        $this->createSellerWithProduct('UMKM B', 'Snack Group B', 15000, 100, $otherGroup);

        $service = app(KeroyokanService::class);
        $eligible = $service->getEligibleProducts($this->kelompokSnack);

        $this::assertCount(1, $eligible);
        $this::assertEquals('Snack Group A', $eligible->first()->nama_produk);
    }

    public function test_63_admin_category_mismatch_validation_rejected(): void
    {
        $admin = User::create([
            'username' => 'admin_test',
            'password' => 'password123',
            'nama_lengkap' => 'Administrator',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        [,, $product] = $this->createSellerWithProduct('UMKM Kerajinan', 'Anyaman Bamboo', 50000, 10);
        $product->update(['kategori_id' => $this->kategoriKerajinan->id]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'umkm_id' => $product->umkm_id,
            'kategori_id' => $this->kategoriKerajinan->id,
            'kelompok_keroyokan_id' => $this->kelompokSnack->id, // Category is Kuliner, mismatch!
            'nama_produk' => $product->nama_produk,
            'harga' => $product->harga,
            'stok_status' => 'Ready',
            'stok_jumlah' => 10,
        ]);

        $response->assertSessionHasErrors(['kelompok_keroyokan_id']);
    }

    public function test_65_existing_cart_not_empty_rejects_keroyokan(): void
    {
        $buyer = User::create([
            'username' => 'buyer_test',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Test',
            'email' => 'buyer@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        [,, $pOrdinary] = $this->createSellerWithProduct('UMKM Ordinary', 'Produk Biasa', 10000, 50);

        // Put an item in cart
        $cart = app(CartService::class);
        $cart->add($pOrdinary, 2);

        $this->createSellerWithProduct('UMKM A', 'Snack A', 15000, 100, $this->kelompokSnack);
        $this->createSellerWithProduct('UMKM B', 'Snack B', 15000, 80, $this->kelompokSnack);

        $response = $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), [
            'target_jumlah' => 150,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Keranjang Anda masih berisi produk. Selesaikan atau kosongkan keranjang sebelum membuat pesanan Keroyokan.');
    }

    public function test_66_67_checkout_creates_batch_and_decrements_stock(): void
    {
        $buyer = User::create([
            'username' => 'buyer_keroyokan',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Keroyokan',
            'email' => 'buyer_k@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        [,, $pA] = $this->createSellerWithProduct('UMKM A', 'Snack A', 15000, 100, $this->kelompokSnack);
        [,, $pB] = $this->createSellerWithProduct('UMKM B', 'Snack B', 15000, 80, $this->kelompokSnack);

        $response = $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), [
            'target_jumlah' => 150,
        ]);

        $response->assertRedirect(route('checkout.create'));

        $checkoutResponse = $this->actingAs($buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'alamat_pengiriman' => 'Jl. Moncongloe No. 10',
            'no_hp_pembeli' => '081234567890',
            'catatan' => 'Pesanan Keroyokan Acara Desa',
        ]);

        $checkoutResponse->assertRedirect(route('buyer.dashboard'));

        $this::assertEquals(1, BatchKeroyokan::count());
        $batch = BatchKeroyokan::first();
        $this::assertEquals($buyer->id, $batch->pembeli_id);
        $this::assertEquals(150, $batch->target_jumlah);

        $orders = Pesanan::where('batch_keroyokan_id', $batch->id)->get();
        $this::assertCount(2, $orders);

        // Check stock decrements
        $pA->refresh();
        $pB->refresh();
        $this::assertEquals(0, $pA->stok_jumlah); // 100 - 100 = 0
        $this::assertEquals(30, $pB->stok_jumlah); // 80 - 50 = 30
    }

    public function test_69_normal_checkout_keeps_batch_keroyokan_id_null(): void
    {
        $buyer = User::create([
            'username' => 'buyer_normal',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Normal',
            'email' => 'buyer_n@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        [,, $pOrdinary] = $this->createSellerWithProduct('UMKM Biasa', 'Produk Biasa', 20000, 50);

        $cart = app(CartService::class);
        $cart->add($pOrdinary, 2);

        $response = $this->actingAs($buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'alamat_pengiriman' => 'Jl. Merdeka No. 1',
            'no_hp_pembeli' => '081122334455',
        ]);

        $response->assertRedirect(route('buyer.dashboard'));

        $this::assertEquals(1, Pesanan::count());
        $pesanan = Pesanan::first();
        $this::assertNull($pesanan->batch_keroyokan_id);
    }

    public function test_71_cancellation_restores_stock_only_once(): void
    {
        $buyer = User::create([
            'username' => 'buyer_cancel',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Cancel',
            'email' => 'buyer_c@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        [,, $pA] = $this->createSellerWithProduct('UMKM A', 'Snack A', 15000, 100, $this->kelompokSnack);
        [,, $pB] = $this->createSellerWithProduct('UMKM B', 'Snack B', 15000, 80, $this->kelompokSnack);

        $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), ['target_jumlah' => 150]);
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'alamat_pengiriman' => 'Jl. Moncongloe',
            'no_hp_pembeli' => '08123456789',
        ]);

        $orderA = Pesanan::where('produk_id', $pA->id)->first();
        $initialStockA = $pA->fresh()->stok_jumlah; // Should be 0

        $this->actingAs($buyer)->patch(route('buyer.orders.cancel', $orderA));

        $this::assertEquals('Dibatalkan', $orderA->fresh()->status);
        $this::assertEquals(100, $pA->fresh()->stok_jumlah); // 0 + 100 = 100 restored

        // Restoring order B should not restore order A again
        $orderB = Pesanan::where('produk_id', $pB->id)->first();
        $this->actingAs($buyer)->patch(route('buyer.orders.cancel', $orderB));

        $this::assertEquals(100, $pA->fresh()->stok_jumlah); // Still 100!
        $this::assertEquals(80, $pB->fresh()->stok_jumlah); // 30 + 50 = 80 restored
    }

    public function test_out_of_stock_keroyokan_group_shows_up_on_index_with_indicator(): void
    {
        $outGroup = KelompokKeroyokan::create([
            'kategori_id' => $this->kategoriKuliner->id,
            'nama_kelompok' => 'Kelompok Habis Stok',
            'aktif' => true,
        ]);

        $this->createSellerWithProduct('UMKM A', 'Produk Habis A', 10000, 0, $outGroup, 'aktif', 'Habis');
        $this->createSellerWithProduct('UMKM B', 'Produk Habis B', 12000, 0, $outGroup, 'aktif', 'Habis');

        $response = $this->get(route('keroyokan.index'));

        $response->assertOk();
        $response->assertSee('Kelompok Habis Stok');
        $response->assertSee('Stok Habis');
    }
}

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

        \App\Models\ZonaPengiriman::create([
            'nama_zona' => 'Dalam Desa',
            'biaya' => 2000,
            'keterangan' => 'Moncongloe Lappara',
            'aktif' => true,
            'urutan' => 1,
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

    public function test_65_existing_cart_allows_adding_keroyokan_package(): void
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

        // Put a regular item in cart
        $cart = app(CartService::class);
        $cart->add($pOrdinary, 2);

        $this->createSellerWithProduct('UMKM A', 'Snack A', 15000, 100, $this->kelompokSnack);
        $this->createSellerWithProduct('UMKM B', 'Snack B', 15000, 80, $this->kelompokSnack);

        // Adding Keroyokan should succeed without blocking
        $response = $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), [
            'target_jumlah' => 150,
        ]);

        $response->assertRedirect(route('checkout.create'));
        $response->assertSessionHas('success');

        // Verify cart contains both the regular item and the keroyokan items
        $this->assertTrue($cart->isKeroyokan());
        $this->assertCount(1, $cart->regularItems());
        $this->assertEquals($pOrdinary->id, $cart->regularItems()->first()['product']->id);
        $this->assertGreaterThanOrEqual(1, $cart->keroyokanItems()->count());
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
            'zona_pengiriman' => 'Dalam Desa',
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
            'zona_pengiriman' => 'Dalam Desa',
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
            'zona_pengiriman' => 'Dalam Desa',
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

    public function test_keroyokan_shipping_and_packing_fee_calculation_with_3_percent_commission(): void
    {
        \App\Models\ZonaPengiriman::create([
            'nama_zona' => 'Dalam Desa',
            'biaya' => 2000,
            'keterangan' => 'Moncongloe Lappara',
            'aktif' => true,
            'urutan' => 1,
        ]);

        \App\Models\OpsiPacking::create([
            'nama' => 'Kemasan Box Kardus',
            'biaya' => 3000,
            'deskripsi' => 'Box eksklusif LUDES-MARKET',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $buyer = User::create([
            'username' => 'buyer_keroyokan_calc',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Keroyokan Calc',
            'email' => 'buyer_calc@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        [,, $pA] = $this->createSellerWithProduct('UMKM A', 'Snack A', 2500, 100, $this->kelompokSnack);
        [,, $pB] = $this->createSellerWithProduct('UMKM B', 'Snack B', 2500, 80, $this->kelompokSnack);

        // 1. Confirm allocation for 150 items (100 from A, 50 from B)
        $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), [
            'target_jumlah' => 150,
        ])->assertRedirect(route('checkout.create'));

        // 2. Checkout
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'opsi_packing' => 'Kemasan Box Kardus',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ])->assertRedirect(route('buyer.dashboard'));

        $orders = Pesanan::where('pembeli_id', $buyer->id)->get();
        $this->assertCount(2, $orders);

        $orderA = $orders->where('produk_id', $pA->id)->first();
        $orderB = $orders->where('produk_id', $pB->id)->first();

        // Subtotals
        $subtotalA = 100 * 2500; // 250.000
        $subtotalB = 50 * 2500;  // 125.000
        $totalSubtotal = $subtotalA + $subtotalB; // 375.000

        // Single shipping fee: Rp2.000 total (divided 1.000 each)
        $totalShippingCharged = $orders->sum('ongkos_kirim');
        $this->assertEquals(2000, $totalShippingCharged);

        // Single packing fee: Rp3.000 total (divided 1.500 each)
        $totalPackingCharged = $orders->sum('biaya_packing');
        $this->assertEquals(3000, $totalPackingCharged);

        // Total Tagihan = Subtotal (375.000) + Ongkir (2.000) + Packing (3.000) = 380.000
        $totalOrderAmount = $orders->sum('total_harga');
        $this->assertEquals(380000, $totalOrderAmount);

        // 3% Admin Commission & 97% Seller Net Revenue
        $expectedKomisiA = round($subtotalA * 0.03);
        $expectedKomisiB = round($subtotalB * 0.03);
        $this->assertEquals($expectedKomisiA, $orderA->komisi_admin);
        $this->assertEquals($expectedKomisiB, $orderB->komisi_admin);

        $this->assertEquals($subtotalA - $expectedKomisiA, $orderA->pendapatan_penjual);
        $this->assertEquals($subtotalB - $expectedKomisiB, $orderB->pendapatan_penjual);
    }

    public function test_keroyokan_receipt_and_dashboard_display_consolidated_packaging_badge(): void
    {
        $buyer = User::create([
            'username' => 'buyer_visual',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Visual',
            'email' => 'buyer_v@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        [,, $pA] = $this->createSellerWithProduct('UMKM A', 'Snack A', 2500, 100, $this->kelompokSnack);
        [,, $pB] = $this->createSellerWithProduct('UMKM B', 'Snack B', 2500, 80, $this->kelompokSnack);

        $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), ['target_jumlah' => 150]);
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ]);

        $orderA = Pesanan::where('produk_id', $pA->id)->firstOrFail();

        // 1. Check Receipt View
        $receiptRes = $this->actingAs($buyer)->get(route('receipt.show', $orderA));
        $receiptRes->assertOk();
        $receiptRes->assertSee('Paket Keroyokan');
        $receiptRes->assertSee('1 box kemasan berlabel resmi LUDES-MARKET');

        // 2. Check Buyer Dashboard
        $dashboardRes = $this->actingAs($buyer)->get(route('buyer.dashboard'));
        $dashboardRes->assertOk();
        $dashboardRes->assertSee('Keroyokan #KR-');
        $dashboardRes->assertSee('Paket Keroyokan:');
    }

    public function test_admin_keroyokan_index_shows_batch_monitoring(): void
    {
        $admin = User::create([
            'username' => 'admin_keroyokan',
            'password' => 'password123',
            'nama_lengkap' => 'Admin LUDES',
            'email' => 'admin_k@example.com',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $buyer = User::create([
            'username' => 'buyer_batch',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Batch',
            'email' => 'buyer_b@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        $this->createSellerWithProduct('UMKM A', 'Snack A', 2500, 100, $this->kelompokSnack);
        $this->createSellerWithProduct('UMKM B', 'Snack B', 2500, 80, $this->kelompokSnack);

        $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), ['target_jumlah' => 150]);
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ]);

        $adminRes = $this->actingAs($admin)->get(route('admin.keroyokan.index'));
        $adminRes->assertOk();
        $adminRes->assertSee('Monitoring Batch Pesanan Keroyokan');
        $adminRes->assertSee('Pembeli Batch');
        $adminRes->assertSee('150 unit');
    }

    public function test_keroyokan_minimum_15_boxes_rule(): void
    {
        $buyer = User::create([
            'username' => 'buyer_min15',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Min 15',
            'email' => 'buyer_min15@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        $this->createSellerWithProduct('UMKM A', 'Snack A', 2500, 100, $this->kelompokSnack);

        // Submitting < 15 boxes must fail validation
        $responseFail = $this->actingAs($buyer)->post(route('keroyokan.simulate', $this->kelompokSnack), [
            'jumlah_box' => 10,
        ]);
        $responseFail->assertSessionHasErrors(['jumlah_box']);

        // Submitting >= 15 boxes must pass
        $responsePass = $this->actingAs($buyer)->post(route('keroyokan.simulate', $this->kelompokSnack), [
            'jumlah_box' => 15,
        ]);
        $responsePass->assertOk();
        $responsePass->assertSee('15 Box');
    }

    public function test_keroyokan_custom_box_item_quantities_calculation(): void
    {
        $buyer = User::create([
            'username' => 'buyer_custom_box',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Custom Box',
            'email' => 'buyer_cb@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        [,, $pA] = $this->createSellerWithProduct('UMKM A', 'Snack A', 5000, 100, $this->kelompokSnack);
        [,, $pB] = $this->createSellerWithProduct('UMKM B', 'Snack B', 10000, 100, $this->kelompokSnack);

        // Buyer customizes box: 2 pcs Snack A + 1 pcs Snack B per box, for 20 boxes
        $boxItems = [
            $pA->id => 2,
            $pB->id => 1,
        ];

        $service = app(KeroyokanService::class);
        $result = $service->calculateCustomBoxAllocation($this->kelompokSnack, 20, $boxItems);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(20, $result['jumlah_box']);
        $this->assertEquals(3, $result['total_pcs_in_box']); // 2 + 1 = 3 pcs
        $this->assertEquals(20000, $result['box_price']); // 2*5000 + 1*10000 = 20.000
        $this->assertEquals(60, $result['target_quantity']); // 20 * 3 = 60 units
        $this->assertEquals(400000, $result['grand_total']); // 20 * 20.000 = 400.000

        // Confirm to checkout
        $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), [
            'jumlah_box' => 20,
            'box_items' => $boxItems,
        ])->assertRedirect(route('checkout.create'));

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ])->assertRedirect(route('buyer.dashboard'));

        $orders = Pesanan::where('pembeli_id', $buyer->id)->get();
        $this->assertCount(2, $orders);

        $orderA = $orders->where('produk_id', $pA->id)->first();
        $orderB = $orders->where('produk_id', $pB->id)->first();

        $this->assertEquals(40, $orderA->jumlah); // 20 * 2 = 40
        $this->assertEquals(20, $orderB->jumlah); // 20 * 1 = 20
    }

    public function test_keroyokan_smart_alternative_product_suggestion_on_shortage(): void
    {
        $buyer = User::create([
            'username' => 'buyer_alt',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Alt',
            'email' => 'buyer_alt@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
        ]);

        // UMKM A only has 10 stock of Snack A
        [,, $pA] = $this->createSellerWithProduct('UMKM A', 'Snack A Terbatas', 5000, 10, $this->kelompokSnack);
        // UMKM C has plenty of Snack C in the same category
        [,, $pC] = $this->createSellerWithProduct('UMKM C', 'Snack C Alternatif', 5000, 50, $this->kelompokSnack);

        $service = app(KeroyokanService::class);

        // Request 15 boxes with 1 pcs Snack A per box (needs 15 pcs, shortage = 5 pcs)
        $shortageResult = $service->calculateCustomBoxAllocation($this->kelompokSnack, 15, [$pA->id => 1]);

        $this->assertEquals('has_shortage', $shortageResult['status']);
        $this->assertCount(1, $shortageResult['shortages']);
        $this->assertEquals(5, $shortageResult['shortages'][0]['shortage']);
        $this->assertTrue(collect($shortageResult['shortages'][0]['alternatives'])->pluck('id')->contains($pC->id));

        // Now apply substitution: replace shortage with Snack C
        $subResult = $service->calculateCustomBoxAllocation(
            $this->kelompokSnack,
            15,
            [$pA->id => 1],
            [$pA->id => $pC->id]
        );

        $this->assertEquals('success', $subResult['status']);
        $allocations = collect($subResult['allocations']);
        $this->assertEquals(10, $allocations->where('product_id', $pA->id)->first()['quantity']);
        $this->assertEquals(5, $allocations->where('product_id', $pC->id)->first()['quantity']);

        // Confirm substituted order
        $this->actingAs($buyer)->post(route('keroyokan.confirm', $this->kelompokSnack), [
            'jumlah_box' => 15,
            'box_items' => [$pA->id => 1],
            'substitutions' => [$pA->id => $pC->id],
        ])->assertRedirect(route('checkout.create'));
    }
}

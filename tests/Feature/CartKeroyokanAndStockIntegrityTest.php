<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\KelompokKeroyokan;
use App\Models\Produk;
use App\Models\UMKM;
use App\Models\User;
use App\Models\ZonaPengiriman;
use App\Models\OpsiPacking;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartKeroyokanAndStockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private Kategori $kategori;
    private UMKM $umkmA;
    private UMKM $umkmB;
    private Produk $produkA;
    private Produk $produkB;
    private KelompokKeroyokan $kelompok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::create([
            'username' => 'buyer_cart_test',
            'password' => 'password123',
            'nama_lengkap' => 'Pembeli Keranjang',
            'email' => 'buyer_cart@example.com',
            'role' => 'pembeli',
            'status' => 'aktif',
            'no_hp' => '081234567890',
            'alamat_utama' => 'Dusun Moncongloe',
        ]);

        $sellerUserA = User::create([
            'username' => 'seller_a',
            'password' => 'password123',
            'nama_lengkap' => 'Penjual A',
            'email' => 'seller_a@example.com',
            'role' => 'penjual',
            'status' => 'aktif',
        ]);

        $sellerUserB = User::create([
            'username' => 'seller_b',
            'password' => 'password123',
            'nama_lengkap' => 'Penjual B',
            'email' => 'seller_b@example.com',
            'role' => 'penjual',
            'status' => 'aktif',
        ]);

        $this->umkmA = UMKM::create([
            'user_id' => $sellerUserA->id,
            'nama_umkm' => 'UMKM Kue Mawar',
            'pemilik' => 'Ibu Mawar',
            'alamat' => 'Moncongloe',
            'status' => 'aktif',
        ]);

        $this->umkmB = UMKM::create([
            'user_id' => $sellerUserB->id,
            'nama_umkm' => 'UMKM Berkah Snack',
            'pemilik' => 'Pak Berkah',
            'alamat' => 'Moncongloe',
            'status' => 'aktif',
        ]);

        $this->kategori = Kategori::create([
            'nama_kategori' => 'Kuliner',
        ]);

        $this->kelompok = KelompokKeroyokan::create([
            'kategori_id' => $this->kategori->id,
            'nama_kelompok' => 'Paket Snack Box Hajatan',
            'deskripsi' => 'Paket snack box aneka kue desa',
            'aktif' => true,
        ]);

        $this->produkA = Produk::create([
            'umkm_id' => $this->umkmA->id,
            'kategori_id' => $this->kategori->id,
            'kelompok_keroyokan_id' => $this->kelompok->id,
            'nama_produk' => 'Jalangkote Renyah',
            'harga' => 3000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 100,
        ]);

        $this->produkB = Produk::create([
            'umkm_id' => $this->umkmB->id,
            'kategori_id' => $this->kategori->id,
            'kelompok_keroyokan_id' => $this->kelompok->id,
            'nama_produk' => 'Bolu Peca Legit',
            'harga' => 4000,
            'stok_status' => 'Ready',
            'stok_jumlah' => 100,
        ]);

        ZonaPengiriman::create([
            'nama_zona' => 'Dalam Desa',
            'biaya' => 5000,
            'keterangan' => 'Pengantaran di wilayah desa',
            'aktif' => true,
            'urutan' => 1,
        ]);

        OpsiPacking::create([
            'nama' => 'Standard Box LUDES',
            'biaya' => 2000,
            'deskripsi' => 'Box kemasan food-grade',
            'aktif' => true,
            'urutan' => 1,
        ]);
    }

    public function test_adding_product_to_cart_does_not_reduce_database_stock(): void
    {
        $initialStock = $this->produkA->stok_jumlah; // 100

        // 1. Add 10 items to cart
        $response = $this->actingAs($this->buyer)->post(route('cart.add', $this->produkA), [
            'jumlah' => 10,
        ]);
        $response->assertRedirect();

        // Database stock MUST remain 100 (untouched)
        $this->assertEquals($initialStock, $this->produkA->fresh()->stok_jumlah);

        // 2. Update cart to 25 items
        $updateResponse = $this->actingAs($this->buyer)->patch(route('cart.update'), [
            'jumlah_cart' => [
                $this->produkA->id => 25,
            ],
        ]);
        $updateResponse->assertRedirect(route('cart.index'));

        // Database stock MUST STILL remain 100
        $this->assertEquals($initialStock, $this->produkA->fresh()->stok_jumlah);

        // 3. Clear cart
        $clearResponse = $this->actingAs($this->buyer)->delete(route('cart.clear'));
        $clearResponse->assertRedirect(route('cart.index'));

        // Database stock MUST STILL remain 100
        $this->assertEquals($initialStock, $this->produkA->fresh()->stok_jumlah);
    }

    public function test_cart_displays_consolidated_keroyokan_package_card(): void
    {
        // Buyer creates a Keroyokan order of 20 boxes (1 pcs Jalangkote + 2 pcs Bolu Peca per box)
        $confirmResponse = $this->actingAs($this->buyer)->post(route('keroyokan.confirm', $this->kelompok), [
            'jumlah_box' => 20,
            'box_items' => [
                $this->produkA->id => 1,
                $this->produkB->id => 2,
            ],
        ]);
        $confirmResponse->assertRedirect(route('checkout.create'));

        // Stock in database MUST remain 100 for both products (untouched while in cart)
        $this->assertEquals(100, $this->produkA->fresh()->stok_jumlah);
        $this->assertEquals(100, $this->produkB->fresh()->stok_jumlah);

        // Visit cart page
        $cartResponse = $this->actingAs($this->buyer)->get(route('cart.index'));
        $cartResponse->assertOk();

        // Assert consolidated Keroyokan package presentation
        $cartResponse->assertSee('PAKET KEROYOKAN RESMI');
        $cartResponse->assertSee('Paket Snack Box Hajatan');
        $cartResponse->assertSee('20 Box');
        $cartResponse->assertSee('Rincian Komposisi Isi Paket Box');
        $cartResponse->assertSee('Jalangkote Renyah');
        $cartResponse->assertSee('Bolu Peca Legit');
        $cartResponse->assertSee('Hapus Paket Keroyokan');
        
        // Assert individual raw quantity input form is NOT shown for keroyokan
        $cartResponse->assertDontSee('name="jumlah_cart[' . $this->produkA->id . ']"', false);
    }

    public function test_stock_is_only_decremented_when_checkout_is_submitted(): void
    {
        // 1. Order 20 boxes of Keroyokan: (20 * 1 = 20 Jalangkote, 20 * 2 = 40 Bolu Peca)
        $this->actingAs($this->buyer)->post(route('keroyokan.confirm', $this->kelompok), [
            'jumlah_box' => 20,
            'box_items' => [
                $this->produkA->id => 1,
                $this->produkB->id => 2,
            ],
        ]);

        $this->assertEquals(100, $this->produkA->fresh()->stok_jumlah);
        $this->assertEquals(100, $this->produkB->fresh()->stok_jumlah);

        // 2. Submit checkout
        $checkoutResponse = $this->actingAs($this->buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ]);
        $checkoutResponse->assertRedirect(route('buyer.dashboard'));

        // 3. NOW stock in database is properly decremented
        $this->assertEquals(80, $this->produkA->fresh()->stok_jumlah); // 100 - 20 = 80
        $this->assertEquals(60, $this->produkB->fresh()->stok_jumlah); // 100 - 40 = 60
    }

    public function test_buyer_can_add_keroyokan_package_when_cart_already_has_regular_items(): void
    {
        // 1. Add regular product (Bolu Peca 3 pcs)
        $this->actingAs($this->buyer)->post(route('cart.add', $this->produkB), ['jumlah' => 3]);

        // 2. Add Keroyokan package (15 boxes of 1 pcs Jalangkote)
        $confirmResponse = $this->actingAs($this->buyer)->post(route('keroyokan.confirm', $this->kelompok), [
            'jumlah_box' => 15,
            'box_items' => [
                $this->produkA->id => 1,
            ],
        ]);

        $confirmResponse->assertRedirect(route('checkout.create'));

        // Cart page must show both Keroyokan package and regular product without breaking layout
        $cartResponse = $this->actingAs($this->buyer)->get(route('cart.index'));
        $cartResponse->assertOk();
        $cartResponse->assertSee('PAKET KEROYOKAN RESMI');
        $cartResponse->assertSee('15 Box');
        $cartResponse->assertSee('PRODUK REGULER TAMBAHAN');
        $cartResponse->assertSee('Bolu Peca Legit');

        // Check checkout
        $checkoutResponse = $this->actingAs($this->buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ]);
        $checkoutResponse->assertRedirect(route('buyer.dashboard'));

        $this->assertEquals(85, $this->produkA->fresh()->stok_jumlah); // 100 - 15 = 85
        $this->assertEquals(97, $this->produkB->fresh()->stok_jumlah); // 100 - 3 = 97
    }

    public function test_buyer_can_add_regular_products_after_adding_keroyokan_package(): void
    {
        // 1. Add Keroyokan package (15 boxes of 1 pcs Jalangkote)
        $this->actingAs($this->buyer)->post(route('keroyokan.confirm', $this->kelompok), [
            'jumlah_box' => 15,
            'box_items' => [
                $this->produkA->id => 1,
            ],
        ]);

        // 2. Add regular product (Bolu Peca 5 pcs) from catalog
        $this->actingAs($this->buyer)->post(route('cart.add', $this->produkB), ['jumlah' => 5]);

        $cart = app(CartService::class);
        $this->assertTrue($cart->isKeroyokan());
        $this->assertCount(1, $cart->keroyokanItems());
        $this->assertCount(1, $cart->regularItems());

        // 3. Cart page displays both
        $cartResponse = $this->actingAs($this->buyer)->get(route('cart.index'));
        $cartResponse->assertOk();
        $cartResponse->assertSee('15 Box');
        $cartResponse->assertSee('PRODUK REGULER TAMBAHAN');
    }

    public function test_buyer_can_selectively_checkout_only_chosen_products(): void
    {
        // 1. Add Keroyokan package (15 boxes)
        $this->actingAs($this->buyer)->post(route('keroyokan.confirm', $this->kelompok), [
            'jumlah_box' => 15,
            'box_items' => [$this->produkA->id => 1],
        ]);

        // 2. Add regular product (Bolu Peca 4 pcs)
        $this->actingAs($this->buyer)->post(route('cart.add', $this->produkB), ['jumlah' => 4]);

        // 3. Buyer chooses to ONLY checkout the regular product (Bolu Peca), leaving Keroyokan in cart
        $checkoutPageResponse = $this->actingAs($this->buyer)->get(route('checkout.create', [
            'select_keroyokan' => 0,
            'selected_products' => [$this->produkB->id],
        ]));
        $checkoutPageResponse->assertOk();
        $checkoutPageResponse->assertSee('Bolu Peca Legit');

        // Submit checkout
        $submitResponse = $this->actingAs($this->buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ]);
        $submitResponse->assertRedirect(route('buyer.dashboard'));

        // Stock of Bolu Peca decremented: 100 - 4 = 96
        $this->assertEquals(96, $this->produkB->fresh()->stok_jumlah);
        // Stock of Jalangkote UNTOUCHED: 100
        $this->assertEquals(100, $this->produkA->fresh()->stok_jumlah);

        // Cart now contains the unselected Keroyokan package still intact
        $cart = app(CartService::class);
        $this->assertTrue($cart->isKeroyokan());
        $this->assertCount(0, $cart->regularItems());
    }

    public function test_keroyokan_package_counts_as_one_and_same_product_can_be_added_as_regular(): void
    {
        // 1. Add Keroyokan package (15 boxes of Jalangkote 1 pcs and Bolu Peca 2 pcs = 45 pcs total)
        $this->actingAs($this->buyer)->post(route('keroyokan.confirm', $this->kelompok), [
            'jumlah_box' => 15,
            'box_items' => [
                $this->produkA->id => 1,
                $this->produkB->id => 2,
            ],
        ]);

        $cart = app(CartService::class);
        // Cart count must be EXACTLY 1 (counting 1 package, not 45 items!)
        $this->assertEquals(1, $cart->count());

        // Layout navbar badge displays 1
        $pageResponse = $this->actingAs($this->buyer)->get(route('cart.index'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('<span>1</span>', false);

        // 2. Buyer adds the SAME product (Jalangkote 3 pcs) as a regular item from catalog
        $this->actingAs($this->buyer)->post(route('cart.add', $this->produkA), ['jumlah' => 3]);

        // Cart count is now 2 (1 Keroyokan Package + 1 Regular Item)
        $this->assertEquals(2, $cart->count());
        $this->assertCount(2, $cart->keroyokanItems());
        $this->assertCount(1, $cart->regularItems());

        $pageResponse2 = $this->actingAs($this->buyer)->get(route('cart.index'));
        $pageResponse2->assertOk();
        $pageResponse2->assertSee('<span>2</span>', false);
        $pageResponse2->assertSee('PRODUK REGULER TAMBAHAN');
        $pageResponse2->assertSee('name="jumlah_cart[' . $this->produkA->id . ']"', false);

        // 3. Checkout everything
        $checkoutResponse = $this->actingAs($this->buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ]);
        $checkoutResponse->assertRedirect(route('buyer.dashboard'));

        // Stock of Jalangkote decremented by 15 (keroyokan) + 3 (regular) = 18 -> 100 - 18 = 82
        $this->assertEquals(82, $this->produkA->fresh()->stok_jumlah);
        // Stock of Bolu Peca decremented by 30 (keroyokan) -> 100 - 30 = 70
        $this->assertEquals(70, $this->produkB->fresh()->stok_jumlah);

        // Cart is now completely empty (0)
        $this->assertEquals(0, $cart->count());
    }

    public function test_buyer_dashboard_consolidates_keroyokan_into_one_transaction_and_metric(): void
    {
        // 1. Order a Keroyokan package with 2 distinct products (15 boxes = 45 units total)
        $this->actingAs($this->buyer)->post(route('keroyokan.confirm', $this->kelompok), [
            'jumlah_box' => 15,
            'box_items' => [
                $this->produkA->id => 1,
                $this->produkB->id => 2,
            ],
        ]);

        // Checkout Keroyokan
        $this->actingAs($this->buyer)->post(route('checkout.store'), [
            'metode_pembayaran' => 'COD',
            'zona_pengiriman' => 'Dalam Desa',
            'alamat_pengiriman' => 'Dusun Moncongloe',
            'no_hp_pembeli' => '081234567890',
        ]);

        // Verify buyer dashboard:
        $dashboardResponse = $this->actingAs($this->buyer)->get(route('buyer.dashboard'));
        $dashboardResponse->assertOk();

        // 1 Keroyokan package must count as 1 in stats metric (NOT 2 or 45!)
        $stats = $dashboardResponse->viewData('stats');
        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['menunggu']);

        // Transactions list must contain exactly 1 transaction
        $transactions = $dashboardResponse->viewData('transactions');
        $this->assertCount(1, $transactions);
        $this->assertEquals('keroyokan', $transactions->first()['type']);
        $this->assertEquals(15, $transactions->first()['box_count']);

        // Verify HTML displays the single consolidated card
        $dashboardResponse->assertSee('PAKET KEROYOKAN RESMI');
        $dashboardResponse->assertSee('15 Box · Total 45 item');
        $dashboardResponse->assertSee('Jalangkote Renyah');
        $dashboardResponse->assertSee('Bolu Peca Legit');

        // 2. Buyer confirms receipt on the batch ("Sudah Sampai")
        $batch = \App\Models\BatchKeroyokan::firstOrFail();
        $batch->pesanan()->update(['status' => 'Diproses']);

        $confirmResponse = $this->actingAs($this->buyer)->patch(route('buyer.orders.confirm-received-batch', $batch));
        $confirmResponse->assertRedirect(route('buyer.dashboard'));

        // All pesanan in the batch are now 'Selesai'
        $this->assertEquals(2, \App\Models\Pesanan::where('batch_keroyokan_id', $batch->id)->where('status', 'Selesai')->count());

        $freshStats = $this->actingAs($this->buyer)->get(route('buyer.dashboard'))->viewData('stats');
        $this->assertEquals(1, $freshStats['total']);
        $this->assertEquals(1, $freshStats['selesai']);
        $this->assertEquals(0, $freshStats['diproses']);
    }
}

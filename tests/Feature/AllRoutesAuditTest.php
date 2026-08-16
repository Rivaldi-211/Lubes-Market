<?php

namespace Tests\Feature;

use App\Models\BatchKeroyokan;
use App\Models\KelompokKeroyokan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\RekeningBank;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllRoutesAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_public_routes_render_successfully(): void
    {
        $publicRoutes = [
            '/',
            route('catalogue'),
            route('umkm.index'),
            route('keroyokan.index'),
            route('cart.index'),
            route('login'),
            route('register'),
            route('password.request'),
        ];

        foreach ($publicRoutes as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $this->assertNotEmpty(trim($response->getContent()), "Route $url rendered empty content!");
        }

        // Single product page
        $product = Produk::firstOrFail();
        $resProduct = $this->get(route('products.show', $product));
        $resProduct->assertOk();

        // Single toko page
        $umkm = Umkm::firstOrFail();
        $resToko = $this->get(route('umkm.show', $umkm));
        $resToko->assertOk();

        // Single kelompok keroyokan
        $kelompok = KelompokKeroyokan::firstOrFail();
        $resKelompok = $this->get(route('keroyokan.show', $kelompok));
        $resKelompok->assertOk();
    }

    public function test_all_buyer_routes_render_successfully(): void
    {
        $buyer = User::where('role', 'pembeli')->firstOrFail();

        $routes = [
            route('buyer.dashboard'),
            route('buyer.profile.edit'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($buyer)->get($url);
            $response->assertOk();
            $this->assertNotEmpty(trim($response->getContent()), "Buyer route $url rendered empty content!");
        }

        // Checkout page with item in cart
        $product = Produk::firstOrFail();
        $this->actingAs($buyer)->post(route('cart.add', $product), ['jumlah' => 1]);
        $resCheckout = $this->actingAs($buyer)->get(route('checkout.create'));
        $resCheckout->assertOk();
        $this->assertNotEmpty(trim($resCheckout->getContent()));
    }

    public function test_all_seller_routes_render_successfully(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();
        $umkm = Umkm::where('user_id', $seller->id)->firstOrFail();

        $routes = [
            route('seller.dashboard'),
            route('seller.profile.edit'),
            route('seller.orders.index'),
            route('seller.products.index'),
            route('seller.products.create'),
            route('seller.analytics'),
            route('seller.saldo.index'),
            route('seller.reports.index'),
            route('seller.onboarding.waiting'),
            route('seller.onboarding.rejected'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($seller)->get($url);
            $response->assertOk();
            $this->assertNotEmpty(trim($response->getContent()), "Seller route $url rendered empty content!");
        }

        // Product edit
        $sellerProduct = Produk::where('umkm_id', $umkm->id)->firstOrFail();
        $resProdEdit = $this->actingAs($seller)->get(route('seller.products.edit', $sellerProduct));
        $resProdEdit->assertOk();

        // Onboarding form for newly registered seller
        $newSeller = User::create([
            'username' => 'new_seller_audit',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'nama_lengkap' => 'New Seller Audit',
            'role' => 'penjual',
            'status' => 'aktif',
        ]);
        Umkm::create([
            'user_id' => $newSeller->id,
            'nama_umkm' => 'New UMKM Audit',
            'pemilik' => 'New Owner',
            'status' => 'aktif',
            'status_verifikasi' => 'pending',
        ]);
        $resOnboarding = $this->actingAs($newSeller)->get(route('seller.onboarding'));
        $resOnboarding->assertOk();
        $this->assertNotEmpty(trim($resOnboarding->getContent()));
    }

    public function test_all_admin_routes_render_successfully(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $umkm = Umkm::firstOrFail();

        $routes = [
            route('admin.dashboard'),
            route('admin.profile.edit'),
            route('admin.umkm.index'),
            route('admin.umkm.create'),
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.keroyokan.index'),
            route('admin.keroyokan.create'),
            route('admin.rekening-bank.index'),
            route('admin.rekening-bank.create'),
            route('admin.users.index'),
            route('admin.orders.index'),
            route('admin.reports.index'),
            route('admin.umkm.analytics'),
            route('admin.umkm.rekomendasi.create', $umkm),
            route('admin.verifikasi-penjual.index'),
            route('admin.disbursement.index'),
            route('admin.zona-pengiriman.index'),
            route('admin.logs.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            $this->assertNotEmpty(trim($response->getContent()), "Admin route $url rendered empty content!");
        }
    }
}

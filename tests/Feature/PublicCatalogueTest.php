<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_catalogue_are_public(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $this->get('/')->assertOk()->assertSee('BUMDes Berkah');
        $this->get('/katalog')->assertOk()->assertSee('Jalangkote Isi Sayur');
    }

    public function test_catalogue_hides_products_from_inactive_umkm(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        Umkm::first()->update(['status' => 'nonaktif']);
        $this->get('/katalog')->assertDontSee('Jalangkote Isi Sayur');
    }

    public function test_catalogue_can_filter_by_keyword_and_category(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $category = Kategori::where('nama_kategori', 'Kerajinan / Kreatif')->firstOrFail();
        $this->get('/katalog?q=Anyaman&kategori='.$category->id)
            ->assertOk()->assertSee('Anyaman Tas Bambu')->assertDontSee('Kripik Pisang Original');
    }

    public function test_product_detail_shows_seller_and_reviews(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $product = Produk::findOrFail(3);
        $this->get('/produk/'.$product->id)
            ->assertOk()->assertSee($product->nama_produk)->assertSee('Pisang Epe & Bakso Bakar Pak Baso')->assertSee('masih hangat');
    }
}

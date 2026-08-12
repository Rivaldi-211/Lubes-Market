<?php
namespace Tests\Feature;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class NavigationAndUploadSecurityTest extends TestCase
{
    use RefreshDatabase;
    public function test_role_navigation_shows_only_relevant_operational_links(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $admin=User::where('role','admin')->firstOrFail(); $seller=User::where('role','penjual')->firstOrFail(); $buyer=User::where('role','pembeli')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('UMKM')->assertSee('Pengguna')->assertDontSee('Profil UMKM');
        $this->actingAs($seller)->get(route('seller.dashboard'))->assertSee('Profil UMKM')->assertSee('Laporan')->assertDontSee('Pengguna');
        $this->actingAs($buyer)->get(route('buyer.dashboard'))->assertSee('Pesanan Saya')->assertSee('Keranjang')->assertDontSee('Log Aktivitas');
    }
    public function test_product_upload_rejects_non_image_files(): void
    {
        Storage::fake('public'); $this->seed(BumdesDemoSeeder::class); $seller=User::where('role','penjual')->firstOrFail();
        $this->actingAs($seller)->post(route('seller.products.store'),['kategori_id'=>1,'nama_produk'=>'Upload Test','harga'=>10000,'stok_status'=>'Ready','stok_jumlah'=>5,'foto'=>UploadedFile::fake()->create('payload.php',10,'application/x-php')])->assertSessionHasErrors('foto');
        $this->assertDatabaseMissing('produk',['nama_produk'=>'Upload Test']);
    }
    public function test_uploaded_images_are_referenced_through_public_storage_path(): void
    {
        Storage::fake('public'); $this->seed(BumdesDemoSeeder::class); $seller=User::where('role','penjual')->firstOrFail();
        $this->actingAs($seller)->post(route('seller.products.store'),['kategori_id'=>1,'nama_produk'=>'Foto Aman','harga'=>10000,'stok_status'=>'Ready','stok_jumlah'=>5,'foto'=>UploadedFile::fake()->image('foto.jpg')])->assertRedirect();
        $product=Produk::where('nama_produk','Foto Aman')->firstOrFail(); $this->assertStringStartsWith('products/',$product->foto); Storage::disk('public')->assertExists($product->foto);
    }
}

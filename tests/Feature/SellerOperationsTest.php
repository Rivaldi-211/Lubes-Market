<?php
namespace Tests\Feature;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SellerOperationsTest extends TestCase
{
    use RefreshDatabase;
    private function seller(): User { return User::where('username','umkm_wawa')->firstOrFail(); }
    public function test_seller_dashboard_only_counts_own_orders(): void
    {
        $this->seed(BumdesDemoSeeder::class); $seller=$this->seller();
        $own=Pesanan::whereHas('produk',fn($q)=>$q->where('umkm_id',$seller->umkm->id))->count();
        $this->actingAs($seller)->get(route('seller.dashboard'))->assertOk()->assertViewHas('stats',fn($s)=>$s['orders']===$own);
    }
    public function test_seller_can_update_own_profile_and_product_but_not_foreign_product(): void
    {
        $this->seed(BumdesDemoSeeder::class); $seller=$this->seller();
        $this->actingAs($seller)->patch(route('seller.profile.update'),['nama_umkm'=>'Jalangkote Bu Sari Baru','pemilik'=>'Ibu Sari','alamat'=>'Moncongloe Lappara','no_hp'=>'08123','deskripsi'=>'Segar setiap hari'])->assertRedirect();
        $this->assertDatabaseHas('umkm',['id'=>$seller->umkm->id,'nama_umkm'=>'Jalangkote Bu Sari Baru']);
        $product=$seller->umkm->produk()->firstOrFail();
        $this->actingAs($seller)->patch(route('seller.products.update',$product),['kategori_id'=>$product->kategori_id,'nama_produk'=>'Jalangkote Premium','harga'=>7000,'stok_status'=>'Ready','stok_jumlah'=>20,'deskripsi'=>'Lebih renyah'])->assertRedirect();
        $this->assertDatabaseHas('produk',['id'=>$product->id,'nama_produk'=>'Jalangkote Premium']);
        $foreign=Produk::where('umkm_id','!=',$seller->umkm->id)->firstOrFail();
        $this->actingAs($seller)->get(route('seller.products.edit',$foreign))->assertForbidden();
    }
    public function test_seller_can_create_and_delete_own_product(): void
    {
        $this->seed(BumdesDemoSeeder::class); $seller=$this->seller();
        $this->actingAs($seller)->post(route('seller.products.store'),['kategori_id'=>1,'nama_produk'=>'Pastel Mini','harga'=>8000,'stok_status'=>'Ready','stok_jumlah'=>15,'deskripsi'=>'Camilan baru'])->assertRedirect(route('seller.products.index'));
        $product=Produk::where('nama_produk','Pastel Mini')->firstOrFail();
        $this->assertSame($seller->umkm->id,$product->umkm_id);
        $this->actingAs($seller)->delete(route('seller.products.destroy',$product))->assertRedirect(route('seller.products.index'));
        $this->assertDatabaseMissing('produk',['id'=>$product->id]);
    }
    public function test_seller_can_update_only_own_order_status(): void
    {
        $this->seed(BumdesDemoSeeder::class); $seller=$this->seller();
        $product=$seller->umkm->produk()->firstOrFail(); $buyer=User::where('role','pembeli')->firstOrFail();
        $own=Pesanan::create(['pembeli_id'=>$buyer->id,'produk_id'=>$product->id,'jumlah'=>1,'total_harga'=>$product->harga,'metode_pembayaran'=>'COD','status'=>'Menunggu']);
        $this->actingAs($seller)->patch(route('seller.orders.update',$own),['status'=>'Diproses'])->assertRedirect();
        $foreignProduct=Produk::where('umkm_id','!=',$seller->umkm->id)->firstOrFail();
        $foreign=Pesanan::create(['pembeli_id'=>$buyer->id,'produk_id'=>$foreignProduct->id,'jumlah'=>1,'total_harga'=>$foreignProduct->harga,'metode_pembayaran'=>'COD','status'=>'Menunggu']);
        $this->actingAs($seller)->patch(route('seller.orders.update',$foreign),['status'=>'Selesai'])->assertForbidden();
    }
    public function test_seller_report_and_csv_include_only_own_sales(): void
    {
        $this->seed(BumdesDemoSeeder::class); $seller=$this->seller();
        $this->actingAs($seller)->get(route('seller.reports.index',['tgl_mulai'=>'2020-01-01','tgl_selesai'=>'2030-12-31']))->assertOk();
        $this->actingAs($seller)->get(route('seller.reports.csv',['tgl_mulai'=>'2020-01-01','tgl_selesai'=>'2030-12-31']))->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
    }
}

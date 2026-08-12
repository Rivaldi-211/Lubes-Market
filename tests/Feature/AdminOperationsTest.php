<?php
namespace Tests\Feature;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;
    private function admin(): User { return User::where('username','admin')->firstOrFail(); }
    public function test_admin_dashboard_exposes_global_totals(): void
    {
        $this->seed(BumdesDemoSeeder::class); $admin=$this->admin();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertViewHas('stats',fn($s)=>$s['products']===Produk::count() && $s['umkm']===Umkm::count());
    }
    public function test_admin_can_create_umkm_with_new_seller_account_and_update_it(): void
    {
        $this->seed(BumdesDemoSeeder::class); $admin=$this->admin();
        $this->actingAs($admin)->post(route('admin.umkm.store'),[
            'nama_umkm'=>'Dapur Baru','pemilik'=>'Ani','alamat'=>'Moncongloe','no_hp'=>'0812999','status'=>'aktif',
            'nama_lengkap'=>'Ani Seller','username'=>'ani_seller','email'=>'ani@example.test','password'=>'password123','password_confirmation'=>'password123',
        ])->assertRedirect(route('admin.umkm.index'));
        $seller=User::where('username','ani_seller')->firstOrFail(); $this->assertSame('penjual',$seller->role); $this->assertSame('Dapur Baru',$seller->umkm->nama_umkm);
        $this->actingAs($admin)->patch(route('admin.umkm.update',$seller->umkm),['nama_umkm'=>'Dapur Ani','pemilik'=>'Ani','alamat'=>'Savala','no_hp'=>'0812999','status'=>'aktif'])->assertRedirect(route('admin.umkm.index'));
        $this->assertDatabaseHas('umkm',['id'=>$seller->umkm->id,'nama_umkm'=>'Dapur Ani']);
    }
    public function test_admin_can_create_and_update_global_product(): void
    {
        $this->seed(BumdesDemoSeeder::class); $admin=$this->admin(); $umkm=Umkm::firstOrFail(); $cat=Kategori::firstOrFail();
        $this->actingAs($admin)->post(route('admin.products.store'),['umkm_id'=>$umkm->id,'kategori_id'=>$cat->id,'nama_produk'=>'Produk Admin','harga'=>11000,'stok_status'=>'Ready','stok_jumlah'=>9])->assertRedirect(route('admin.products.index'));
        $p=Produk::where('nama_produk','Produk Admin')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.products.update',$p),['umkm_id'=>$umkm->id,'kategori_id'=>$cat->id,'nama_produk'=>'Produk Admin Revisi','harga'=>12000,'stok_status'=>'Ready','stok_jumlah'=>8])->assertRedirect(route('admin.products.index'));
        $this->assertSame('Produk Admin Revisi',$p->fresh()->nama_produk);
    }
    public function test_admin_can_toggle_user_status_but_cannot_disable_self(): void
    {
        $this->seed(BumdesDemoSeeder::class); $admin=$this->admin(); $buyer=User::where('role','pembeli')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.users.status',$buyer),['status'=>'nonaktif'])->assertRedirect();
        $this->assertSame('nonaktif',$buyer->fresh()->status);
        $this->actingAs($admin)->patch(route('admin.users.status',$admin),['status'=>'nonaktif'])->assertStatus(422);
    }
    public function test_admin_can_update_order_status_and_access_reports_logs(): void
    {
        $this->seed(BumdesDemoSeeder::class); $admin=$this->admin(); $order=Pesanan::where('status','Menunggu')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.orders.update',$order),['status'=>'Diproses'])->assertRedirect();
        $this->assertSame('Diproses',$order->fresh()->status);
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.csv'))->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
        $this->actingAs($admin)->get(route('admin.logs.index'))->assertOk();
        $this->assertGreaterThan(0,LogAktivitas::count());
    }
}

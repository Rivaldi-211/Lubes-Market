<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\RekomendasiStrategi;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisiV2FeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BumdesDemoSeeder::class);
    }

    public function test_public_can_view_toko_umkm_index_page(): void
    {
        $response = $this->get(route('umkm.index'));

        $response->assertStatus(200);
        $response->assertSee('Produsen Lokal Desa');
        $response->assertSee('Jalangkote Bu Sari');
        $response->assertSee('Moammar Donat Shop');
    }

    public function test_public_can_view_single_toko_profile_page(): void
    {
        $umkm = Umkm::where('status', 'aktif')->firstOrFail();

        $response = $this->get(route('umkm.show', $umkm));

        $response->assertStatus(200);
        $response->assertSee($umkm->nama_umkm);
        $response->assertSee($umkm->pemilik);
        $response->assertSee('Katalog Produk');
        $response->assertSee('Ulasan Pelanggan Toko');
    }

    public function test_seller_can_access_analitik_usaha_page(): void
    {
        $seller = User::where('role', 'penjual')->firstOrFail();

        $response = $this->actingAs($seller)->get(route('seller.analytics'));

        $response->assertStatus(200);
        $response->assertSee('Analitik &amp; Akselerasi Usaha', false);
        $response->assertSee('Omzet 6 Bulan Terakhir');
        $response->assertSee('Tren Kepuasan Pelanggan');
    }

    public function test_admin_can_access_analitik_umkm_overview(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.umkm.analytics'));

        $response->assertStatus(200);
        $response->assertSee('Analitik Akselerasi UMKM');
        $response->assertSee('Ranking Omzet Bulan Ini');
        $response->assertSee('Jalangkote Bu Sari');
    }

    public function test_admin_can_send_rekomendasi_strategi_to_umkm(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $umkm = Umkm::firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.umkm.rekomendasi.store', $umkm), [
            'judul' => 'Uji Coba Rekomendasi Strategi',
            'isi' => 'Tingkatkan stok dan varian rasa produk pada akhir pekan.',
            'tipe' => 'promosi',
            'periode' => date('Y-m'),
        ]);

        $response->assertRedirect(route('admin.umkm.analytics'));

        $this->assertDatabaseHas('rekomendasi_strategi', [
            'umkm_id' => $umkm->id,
            'judul' => 'Uji Coba Rekomendasi Strategi',
            'tipe' => 'promosi',
            'dibaca' => false,
        ]);
    }
}

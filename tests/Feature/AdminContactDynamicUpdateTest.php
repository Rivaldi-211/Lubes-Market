<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactDynamicUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_admin_profile_updates_contact_info_across_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();

        // 1. Initial check on homepage
        $resInitial = $this->get('/');
        $resInitial->assertOk();
        $resInitial->assertSee($admin->email);
        $resInitial->assertSee('wa.me/6281234500001');

        // 2. Admin updates profile with new phone and email
        $newPhone = '082199887766';
        $newEmail = 'admin_baru@ludesmarket.id';

        $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'nama_lengkap' => 'Pengelola Baru Platform',
            'username'     => $admin->username,
            'email'        => $newEmail,
            'no_hp'        => $newPhone,
        ])->assertRedirect();

        $admin->refresh();
        $this->assertSame($newPhone, $admin->no_hp);
        $this->assertSame($newEmail, $admin->email);

        // 3. Check public pages now reflect new admin phone and email dynamically
        $resHome = $this->get('/');
        $resHome->assertOk();
        $resHome->assertSee($newPhone);
        $resHome->assertSee($newEmail);
        $resHome->assertSee('wa.me/6282199887766');

        // 4. Check catalogue page
        $resCatalogue = $this->get(route('catalogue'));
        $resCatalogue->assertOk();
        $resCatalogue->assertSee($newPhone);
        $resCatalogue->assertSee($newEmail);

        // 5. Check buyer dashboard with orders has updated admin contact
        $buyer = User::where('role', 'pembeli')->firstOrFail();
        $product = \App\Models\Produk::firstOrFail();
        \App\Models\Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $product->id,
            'jumlah' => 1,
            'total_harga' => $product->harga,
            'metode_pembayaran' => 'COD',
            'status' => 'Diproses',
            'alamat_pengiriman' => 'Moncongloe',
            'no_hp_pembeli' => '081234500006',
            'tanggal_pesan' => now(),
        ]);

        $resBuyer = $this->actingAs($buyer)->get(route('buyer.dashboard'));
        $resBuyer->assertOk();
        $resBuyer->assertSee($newPhone);
        $resBuyer->assertSee('wa.me/6282199887766');
    }

    public function test_admin_can_view_profile_page_and_update_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();

        // 1. GET /admin/profil
        $res = $this->actingAs($admin)->get(route('admin.profile.edit'));
        $res->assertOk();
        $res->assertSee('Profil &amp; Pengaturan Akun Admin', false);
        $res->assertSee('Data Profil &amp; Kontak Admin', false);
        $res->assertSee($admin->nama_lengkap);
        $res->assertSee($admin->username);

        // 2. PATCH /admin/profil/password
        $pwdRes = $this->actingAs($admin)->patch(route('admin.profile.password'), [
            'current_password'      => 'password123',
            'password'              => 'NewAdminPass123!',
            'password_confirmation' => 'NewAdminPass123!',
        ]);
        $pwdRes->assertRedirect(route('admin.profile.edit', ['tab' => 'keamanan']));

        $admin->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewAdminPass123!', $admin->password));
    }
}

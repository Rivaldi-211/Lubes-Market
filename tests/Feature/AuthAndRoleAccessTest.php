<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_login_with_documented_password(): void
    {
        $this->seed(BumdesDemoSeeder::class);

        $response = $this->post('/login', ['username' => 'admin', 'password' => 'password123']);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs(User::where('username', 'admin')->first());
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'username' => 'inactive', 'password' => Hash::make('password123'),
            'nama_lengkap' => 'Tidak Aktif', 'role' => 'pembeli', 'status' => 'nonaktif',
        ]);

        $this->post('/login', ['username' => 'inactive', 'password' => 'password123'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_roles_are_isolated_from_foreign_dashboards(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('role', 'pembeli')->first();
        $seller = User::where('role', 'penjual')->first();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($buyer)->get('/admin')->assertForbidden();
        $this->actingAs($buyer)->get('/penjual')->assertForbidden();
        $this->actingAs($seller)->get('/pembeli')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_seller_registration_creates_linked_umkm(): void
    {
        $this->post('/register', [
            'nama_lengkap' => 'Sinta', 'username' => 'sinta_umkm', 'email' => 'sinta@example.test',
            'no_hp' => '081234567899', 'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'penjual', 'nama_umkm' => 'Dapur Sinta', 'alamat' => 'Moncongloe Lappara',
        ])->assertRedirect('/penjual');

        $seller = User::where('username', 'sinta_umkm')->firstOrFail();
        $this->assertSame('penjual', $seller->role);
        $this->assertSame('Dapur Sinta', $seller->umkm->nama_umkm);
    }
}

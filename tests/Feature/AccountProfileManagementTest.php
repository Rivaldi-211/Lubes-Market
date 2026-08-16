<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        return User::where('role', 'penjual')->firstOrFail();
    }

    private function buyer(): User
    {
        return User::where('username', 'budi_pembeli')->firstOrFail();
    }

    public function test_guest_cannot_access_profile_pages(): void
    {
        $this->get(route('seller.profile.edit'))->assertRedirect(route('login'));
        $this->get(route('buyer.profile.edit'))->assertRedirect(route('login'));
    }

    public function test_seller_can_view_profile_page(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = $this->seller();

        $response = $this->actingAs($seller)->get(route('seller.profile.edit'));
        $response->assertOk()
            ->assertSee('Profil &amp; Akun Penjual', false)
            ->assertSee($seller->nama_lengkap)
            ->assertSee($seller->umkm->nama_umkm);
    }

    public function test_seller_can_update_umkm_profile(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = $this->seller();

        $response = $this->actingAs($seller)->patch(route('seller.profile.update'), [
            'nama_umkm' => 'Jalangkote Bu Sari Spesial',
            'pemilik'   => 'Ibu Sari',
            'alamat'    => 'Moncongloe Lappara No. 12',
            'no_hp'     => '081299998888',
            'deskripsi' => 'Renyah dan lezat setiap hari.',
        ]);

        $response->assertRedirect(route('seller.profile.edit', ['tab' => 'umkm']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('umkm', [
            'id'        => $seller->umkm->id,
            'nama_umkm' => 'Jalangkote Bu Sari Spesial',
            'alamat'    => 'Moncongloe Lappara No. 12',
        ]);
    }

    public function test_seller_can_update_account_info(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = $this->seller();

        $response = $this->actingAs($seller)->patch(route('seller.profile.account'), [
            'nama_lengkap' => 'Ibu Hj. Sari',
            'username'     => 'umkm_jalangkote_baru',
            'email'        => 'sari_baru@gmail.com',
            'no_hp'        => '081234567890',
        ]);

        $response->assertRedirect(route('seller.profile.edit', ['tab' => 'akun']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'           => $seller->id,
            'nama_lengkap' => 'Ibu Hj. Sari',
            'username'     => 'umkm_jalangkote_baru',
            'email'        => 'sari_baru@gmail.com',
        ]);
    }

    public function test_seller_cannot_use_duplicate_username_or_email(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = $this->seller();
        $buyer = $this->buyer();

        // Try using existing buyer's username
        $response = $this->actingAs($seller)->patch(route('seller.profile.account'), [
            'nama_lengkap' => 'Ibu Sari',
            'username'     => $buyer->username,
            'email'        => 'unique_email@gmail.com',
            'no_hp'        => '081234567890',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_seller_can_update_password_with_correct_current_password(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = $this->seller();

        $response = $this->actingAs($seller)->patch(route('seller.profile.password'), [
            'current_password'      => 'password123',
            'password'              => 'new_secret_pass_123',
            'password_confirmation' => 'new_secret_pass_123',
        ]);

        $response->assertRedirect(route('seller.profile.edit', ['tab' => 'keamanan']))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new_secret_pass_123', $seller->fresh()->password));
    }

    public function test_seller_cannot_update_password_with_wrong_current_password(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = $this->seller();

        $response = $this->actingAs($seller)->patch(route('seller.profile.password'), [
            'current_password'      => 'wrongpassword',
            'password'              => 'new_secret_pass_123',
            'password_confirmation' => 'new_secret_pass_123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_buyer_can_view_profile_page(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = $this->buyer();

        $response = $this->actingAs($buyer)->get(route('buyer.profile.edit'));
        $response->assertOk()
            ->assertSee('Kelola Profil Akun Pembeli')
            ->assertSee($buyer->nama_lengkap)
            ->assertSee($buyer->username);
    }

    public function test_buyer_can_update_account_info(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = $this->buyer();

        $response = $this->actingAs($buyer)->patch(route('buyer.profile.update'), [
            'nama_lengkap' => 'Andi Pratama Updated',
            'username'     => 'andi_pratama_new',
            'email'        => 'andi_new@gmail.com',
            'no_hp'        => '085299887766',
        ]);

        $response->assertRedirect(route('buyer.profile.edit', ['tab' => 'akun']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'           => $buyer->id,
            'nama_lengkap' => 'Andi Pratama Updated',
            'username'     => 'andi_pratama_new',
            'email'        => 'andi_new@gmail.com',
            'no_hp'        => '085299887766',
        ]);
    }

    public function test_buyer_can_update_password_with_correct_current_password(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = $this->buyer();

        $response = $this->actingAs($buyer)->patch(route('buyer.profile.password'), [
            'current_password'      => 'password123',
            'password'              => 'new_buyer_pass_123',
            'password_confirmation' => 'new_buyer_pass_123',
        ]);

        $response->assertRedirect(route('buyer.profile.edit', ['tab' => 'keamanan']))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new_buyer_pass_123', $buyer->fresh()->password));
    }

    public function test_buyer_cannot_update_password_with_wrong_current_password(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = $this->buyer();

        $response = $this->actingAs($buyer)->patch(route('buyer.profile.password'), [
            'current_password'      => 'wrongpassword',
            'password'              => 'new_buyer_pass_123',
            'password_confirmation' => 'new_buyer_pass_123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }
}

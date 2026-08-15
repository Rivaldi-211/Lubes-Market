<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\RekeningBank;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekeningBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_rekening_bank(): void
    {
        $admin = User::create([
            'username' => 'admin_bank_test',
            'nama_lengkap' => 'Admin Bank Test',
            'email' => 'adminbank@bumdes.id',
            'password' => bcrypt('password'),
            'no_hp' => '081299991111',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        // Create
        $response = $this->actingAs($admin)->post('/admin/rekening-bank', [
            'nama_bank' => 'Bank BCA',
            'nomor_rekening' => '8830192831',
            'atas_nama' => 'BUMDes Berkah',
            'aktif' => '1',
            'urutan' => 1,
        ]);
        $response->assertRedirect('/admin/rekening-bank');
        $this->assertDatabaseHas('rekening_bank', [
            'nama_bank' => 'Bank BCA',
            'nomor_rekening' => '8830192831',
        ]);

        $bank = RekeningBank::first();

        // Toggle status
        $this->actingAs($admin)->patch('/admin/rekening-bank/' . $bank->id . '/status')
            ->assertRedirect();
        $this->assertFalse($bank->fresh()->aktif);

        // Update
        $this->actingAs($admin)->patch('/admin/rekening-bank/' . $bank->id, [
            'nama_bank' => 'Bank BCA Syariah',
            'nomor_rekening' => '8830192831',
            'atas_nama' => 'BUMDes Berkah Moncongloe',
            'aktif' => '1',
            'urutan' => 2,
        ])->assertRedirect('/admin/rekening-bank');

        $this->assertDatabaseHas('rekening_bank', [
            'nama_bank' => 'Bank BCA Syariah',
        ]);
    }

    public function test_checkout_with_transfer_requires_active_rekening_bank(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('role', 'pembeli')->firstOrFail();
        $product = Produk::where('stok_status', 'Ready')->firstOrFail();
        $bank = RekeningBank::whereNull('umkm_id')->where('aktif', true)->firstOrFail();

        \App\Models\ZonaPengiriman::create([
            'nama_zona' => 'Moncongloe Lappara',
            'biaya' => 0,
            'aktif' => true,
        ]);

        // Put item in cart
        $this->actingAs($buyer)->post('/keranjang/tambah/' . $product->id, ['jumlah' => 1]);

        // Checkout Transfer without bank selection -> validation error
        $response = $this->actingAs($buyer)->post('/checkout', [
            'metode_pembayaran' => 'Transfer',
            'alamat_pengiriman' => 'Moncongloe Lappara',
            'zona_pengiriman' => 'Moncongloe Lappara',
            'no_hp_pembeli' => '08123456789',
        ]);
        $response->assertSessionHasErrors(['rekening_bank_id']);

        // Checkout Transfer with valid active bank -> success
        $responseSuccess = $this->actingAs($buyer)->post('/checkout', [
            'metode_pembayaran' => 'Transfer',
            'rekening_bank_id' => $bank->id,
            'alamat_pengiriman' => 'Moncongloe Lappara',
            'zona_pengiriman' => 'Moncongloe Lappara',
            'no_hp_pembeli' => '08123456789',
        ]);
        $responseSuccess->assertRedirect('/pembeli');

        $this->assertDatabaseHas('pesanan', [
            'pembeli_id' => $buyer->id,
            'metode_pembayaran' => 'Transfer',
            'rekening_bank_id' => $bank->id,
            'rekening_bank_snapshot' => $bank->formatted_snapshot,
        ]);
    }
}

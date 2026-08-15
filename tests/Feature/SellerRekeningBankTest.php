<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\RekeningBank;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerRekeningBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_platform_rekening_bank(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();

        // Admin adds platform bank account
        $response = $this->actingAs($admin)->post('/admin/rekening-bank', [
            'nama_bank' => 'Bank Sulselbar Platform',
            'nomor_rekening' => '9988-7766-55',
            'atas_nama' => 'Admin LUDES-MARKET',
            'aktif' => '1',
            'urutan' => 1,
        ]);
        $response->assertRedirect('/admin/rekening-bank');

        $this->assertDatabaseHas('rekening_bank', [
            'umkm_id' => null,
            'nama_bank' => 'Bank Sulselbar Platform',
            'nomor_rekening' => '9988-7766-55',
        ]);
    }
}

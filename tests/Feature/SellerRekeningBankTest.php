<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\RekeningBank;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerRekeningBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_manage_their_own_rekening_bank(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = User::where('role', 'penjual')->firstOrFail();
        $umkm = $seller->umkm;

        // Seller adds bank account
        $response = $this->actingAs($seller)->post('/penjual/rekening-bank', [
            'nama_bank' => 'Bank Sulselbar',
            'nomor_rekening' => '9988-7766-55',
            'atas_nama' => $umkm->pemilik,
            'aktif' => '1',
            'urutan' => 1,
        ]);
        $response->assertRedirect('/penjual/rekening-bank');

        $this->assertDatabaseHas('rekening_bank', [
            'umkm_id' => $umkm->id,
            'nama_bank' => 'Bank Sulselbar',
            'nomor_rekening' => '9988-7766-55',
        ]);

        $bank = RekeningBank::where('nomor_rekening', '9988-7766-55')->firstOrFail();

        // Toggle status
        $this->actingAs($seller)->patch('/penjual/rekening-bank/' . $bank->id . '/status')
            ->assertRedirect();
        $this->assertFalse($bank->fresh()->aktif);

        // Edit
        $this->actingAs($seller)->put('/penjual/rekening-bank/' . $bank->id, [
            'nama_bank' => 'Bank Sulselbar Syariah',
            'nomor_rekening' => '9988-7766-55',
            'atas_nama' => $umkm->pemilik,
            'aktif' => '1',
            'urutan' => 2,
        ])->assertRedirect('/penjual/rekening-bank');

        $this->assertDatabaseHas('rekening_bank', [
            'id' => $bank->id,
            'nama_bank' => 'Bank Sulselbar Syariah',
        ]);
    }

    public function test_seller_can_update_payment_status_for_their_order(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = User::where('role', 'penjual')->firstOrFail();
        $umkm = $seller->umkm;

        $order = Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))->firstOrFail();
        $this->assertEquals('Belum Dibayar', $order->status_pembayaran);

        // Seller verifies payment and updates payment status
        $response = $this->actingAs($seller)->patch('/penjual/pesanan/' . $order->id . '/pembayaran', [
            'status_pembayaran' => 'Sudah Dibayar',
        ]);
        $response->assertRedirect();

        $this->assertEquals('Sudah Dibayar', $order->fresh()->status_pembayaran);
        $this->assertTrue($order->fresh()->isPaid());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Disbursement;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\RekeningBank;
use App\Models\Umkm;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerDisbursementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BumdesDemoSeeder::class);
    }

    private function getSeller(): User
    {
        return User::where('username', 'umkm_jalangkote')->firstOrFail();
    }

    private function getAdmin(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }

    public function test_seller_can_view_saldo_page(): void
    {
        $seller = $this->getSeller();
        $umkm = $seller->umkm;

        $response = $this->actingAs($seller)->get(route('seller.saldo.index'));

        $response->assertOk();
        $response->assertViewIs('seller.saldo.index');
        $response->assertViewHasAll(['saldoTersedia', 'saldoDiajukan', 'saldoDicairkan', 'rekeningBankList', 'riwayat']);
    }

    public function test_seller_can_add_bank_account(): void
    {
        $seller = $this->getSeller();
        $umkm = $seller->umkm;

        $response = $this->actingAs($seller)->post(route('seller.saldo.rekening.store'), [
            'nama_bank'      => 'Bank BCA',
            'nomor_rekening' => '7788990011',
            'atas_nama'      => 'Ibu Sari Jalangkote',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rekening_bank', [
            'umkm_id'        => $umkm->id,
            'nama_bank'      => 'Bank BCA',
            'nomor_rekening' => '7788990011',
            'atas_nama'      => 'Ibu Sari Jalangkote',
            'aktif'          => true,
        ]);
    }

    public function test_seller_cannot_disburse_without_eligible_orders(): void
    {
        $seller = $this->getSeller();
        $umkm = $seller->umkm;

        // Ensure all orders are not Selesai or are already disbursed
        Pesanan::whereHas('produk', fn($q) => $q->where('umkm_id', $umkm->id))
            ->update(['status' => 'Diproses']);

        $bank = RekeningBank::where('umkm_id', $umkm->id)->firstOrFail();

        $response = $this->actingAs($seller)->post(route('seller.saldo.ajukan'), [
            'rekening_bank_id' => $bank->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('disbursements', [
            'umkm_id' => $umkm->id,
            'status'  => 'diajukan',
        ]);
    }

    public function test_seller_cannot_use_foreign_bank_account(): void
    {
        $seller = $this->getSeller();
        $umkm = $seller->umkm;

        // Create another UMKM and bank
        $otherUmkm = Umkm::where('id', '!=', $umkm->id)->firstOrFail();
        $otherBank = RekeningBank::create([
            'umkm_id'        => $otherUmkm->id,
            'nama_bank'      => 'Bank Mandiri Lain',
            'nomor_rekening' => '99998888',
            'atas_nama'      => 'Orang Lain',
            'aktif'          => true,
        ]);

        $response = $this->actingAs($seller)->post(route('seller.saldo.ajukan'), [
            'rekening_bank_id' => $otherBank->id,
        ]);

        $response->assertSessionHas('error', 'Rekening bank tujuan tidak valid atau belum diaktifkan.');
    }

    public function test_seller_can_submit_disbursement_request_and_lock_orders(): void
    {
        $seller = $this->getSeller();
        $umkm = $seller->umkm;
        $bank = RekeningBank::where('umkm_id', $umkm->id)->firstOrFail();

        // Create completed order for this UMKM
        $product = $umkm->produk()->firstOrFail();
        $buyer = User::where('role', 'pembeli')->firstOrFail();

        $pesanan = Pesanan::create([
            'pembeli_id'         => $buyer->id,
            'produk_id'          => $product->id,
            'jumlah'             => 2,
            'total_harga'        => 50000,
            'komisi_admin'       => 5000,
            'pendapatan_penjual' => 45000,
            'metode_pembayaran'  => 'Transfer',
            'status'             => 'Selesai',
        ]);

        $response = $this->actingAs($seller)->post(route('seller.saldo.ajukan'), [
            'rekening_bank_id' => $bank->id,
            'catatan'          => 'Tolong segera diproses min',
        ]);

        $response->assertRedirect(route('seller.saldo.index'));
        $response->assertSessionHas('success');

        $disbursement = Disbursement::where('umkm_id', $umkm->id)
            ->where('status', 'diajukan')
            ->firstOrFail();

        $this->assertEquals($bank->id, $disbursement->rekening_bank_id);
        $this->assertEquals($bank->nama_bank, $disbursement->rekening_bank_snapshot['nama_bank']);
        $this->assertEquals($bank->nomor_rekening, $disbursement->rekening_bank_snapshot['nomor_rekening']);
        $this->assertEquals($seller->id, $disbursement->requested_by);
        $this->assertTrue($disbursement->pesanan->contains($pesanan->id));
        $this->assertNotNull($disbursement->diajukan_at);

        // Attempting a second submission while pending must be rejected
        $response2 = $this->actingAs($seller)->post(route('seller.saldo.ajukan'), [
            'rekening_bank_id' => $bank->id,
        ]);

        $response2->assertSessionHas('error');
    }

    public function test_admin_can_approve_disbursement_request(): void
    {
        $seller = $this->getSeller();
        $umkm = $seller->umkm;
        $bank = RekeningBank::where('umkm_id', $umkm->id)->firstOrFail();
        $admin = $this->getAdmin();

        $product = $umkm->produk()->firstOrFail();
        $buyer = User::where('role', 'pembeli')->firstOrFail();

        $pesanan = Pesanan::create([
            'pembeli_id'         => $buyer->id,
            'produk_id'          => $product->id,
            'jumlah'             => 1,
            'total_harga'        => 30000,
            'komisi_admin'       => 3000,
            'pendapatan_penjual' => 27000,
            'metode_pembayaran'  => 'Transfer',
            'status'             => 'Selesai',
        ]);

        $disbursement = Disbursement::create([
            'umkm_id'                => $umkm->id,
            'rekening_bank_id'       => $bank->id,
            'rekening_bank_snapshot' => [
                'nama_bank'      => $bank->nama_bank,
                'nomor_rekening' => $bank->nomor_rekening,
                'atas_nama'      => $bank->atas_nama,
            ],
            'jumlah'                 => 27000,
            'status'                 => 'diajukan',
            'requested_by'           => $seller->id,
            'diajukan_at'            => now(),
        ]);
        $disbursement->pesanan()->attach($pesanan->id);

        $response = $this->actingAs($admin)->post(route('admin.disbursement.approve', $disbursement), [
            'catatan' => 'Sudah ditransfer via BRI Mobile',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $disbursement->refresh();
        $this->assertEquals('dibayar', $disbursement->status);
        $this->assertEquals($admin->id, $disbursement->admin_id);
        $this->assertNotNull($disbursement->dibayar_at);
        $this->assertEquals('Sudah ditransfer via BRI Mobile', $disbursement->catatan);
    }

    public function test_admin_can_reject_disbursement_request_and_free_orders(): void
    {
        $seller = $this->getSeller();
        $umkm = $seller->umkm;
        $bank = RekeningBank::where('umkm_id', $umkm->id)->firstOrFail();
        $admin = $this->getAdmin();

        $product = $umkm->produk()->firstOrFail();
        $buyer = User::where('role', 'pembeli')->firstOrFail();

        $pesanan = Pesanan::create([
            'pembeli_id'         => $buyer->id,
            'produk_id'          => $product->id,
            'jumlah'             => 1,
            'total_harga'        => 30000,
            'komisi_admin'       => 3000,
            'pendapatan_penjual' => 27000,
            'metode_pembayaran'  => 'Transfer',
            'status'             => 'Selesai',
        ]);

        $disbursement = Disbursement::create([
            'umkm_id'                => $umkm->id,
            'rekening_bank_id'       => $bank->id,
            'rekening_bank_snapshot' => [
                'nama_bank'      => $bank->nama_bank,
                'nomor_rekening' => $bank->nomor_rekening,
                'atas_nama'      => $bank->atas_nama,
            ],
            'jumlah'                 => 27000,
            'status'                 => 'diajukan',
            'requested_by'           => $seller->id,
            'diajukan_at'            => now(),
        ]);
        $disbursement->pesanan()->attach($pesanan->id);

        $response = $this->actingAs($admin)->post(route('admin.disbursement.reject', $disbursement), [
            'alasan_penolakan' => 'Nomor rekening salah',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $disbursement->refresh();
        $this->assertEquals('ditolak', $disbursement->status);
        $this->assertEquals($admin->id, $disbursement->admin_id);
        $this->assertNotNull($disbursement->ditolak_at);
        $this->assertStringContainsString('Nomor rekening salah', $disbursement->catatan);

        // Orders must be detached and free again
        $this->assertCount(0, $disbursement->pesanan);

        // Seller is now able to submit again
        $response2 = $this->actingAs($seller)->post(route('seller.saldo.ajukan'), [
            'rekening_bank_id' => $bank->id,
        ]);

        $response2->assertSessionHas('success');
    }
}

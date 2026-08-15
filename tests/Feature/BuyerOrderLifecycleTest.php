<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Ulasan;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuyerOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $buyer, string $status = 'Menunggu', string $method = 'Transfer'): Pesanan
    {
        $product = Produk::firstOrFail();
        return Pesanan::create([
            'pembeli_id' => $buyer->id,
            'produk_id' => $product->id,
            'jumlah' => 1,
            'total_harga' => $product->harga,
            'metode_pembayaran' => $method,
            'status' => $status,
            'alamat_pengiriman' => 'Moncongloe',
            'no_hp_pembeli' => '081234500006',
            'tanggal_pesan' => now(),
        ]);
    }

    public function test_buyer_can_cancel_own_waiting_order_and_stock_is_restored_once(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $order = $this->createOrder($buyer, 'Menunggu');
        $product = $order->produk;
        $before = $product->stok_jumlah;

        $this->actingAs($buyer)->patch(route('buyer.orders.cancel', $order))->assertRedirect(route('buyer.dashboard'));

        $this->assertSame('Dibatalkan', $order->fresh()->status);
        $this->assertSame($before + $order->jumlah, $product->fresh()->stok_jumlah);
        $this->actingAs($buyer)->patch(route('buyer.orders.cancel', $order), [], ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertSame($before + $order->jumlah, $product->fresh()->stok_jumlah);
    }

    public function test_buyer_cannot_cancel_foreign_or_non_waiting_order(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $other = User::create(['username'=>'other','password'=>'password123','nama_lengkap'=>'Other','role'=>'pembeli','status'=>'aktif']);
        $foreign = $this->createOrder($buyer, 'Menunggu');
        $completed = $this->createOrder($buyer, 'Selesai');

        $this->actingAs($other)->patch(route('buyer.orders.cancel', $foreign))->assertForbidden();
        $this->actingAs($buyer)->patch(route('buyer.orders.cancel', $completed), [], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_transfer_or_qris_payment_proof_is_stored_for_own_order_only(): void
    {
        Storage::fake('public');
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $order = $this->createOrder($buyer, 'Menunggu', 'Transfer');

        $this->actingAs($buyer)->post(route('buyer.orders.proof', $order), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.jpg', 500, 'image/jpeg'),
        ])->assertRedirect(route('buyer.dashboard'));

        $path = $order->fresh()->bukti_pembayaran;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_payment_proof_rejects_cod_and_oversized_or_non_image_file(): void
    {
        Storage::fake('public');
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $cod = $this->createOrder($buyer, 'Menunggu', 'COD');
        $transfer = $this->createOrder($buyer, 'Menunggu', 'Transfer');

        $this->actingAs($buyer)->post(route('buyer.orders.proof', $cod), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->actingAs($buyer)->post(route('buyer.orders.proof', $transfer), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('bukti_pembayaran');
    }

    public function test_receipt_is_visible_to_owner_admin_and_product_seller_but_not_foreign_buyer(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();
        $order = $this->createOrder($buyer, 'Menunggu');
        $seller = $order->produk->umkm->user;
        $other = User::create(['username'=>'other2','password'=>'password123','nama_lengkap'=>'Other 2','role'=>'pembeli','status'=>'aktif']);

        $this->actingAs($buyer)->get(route('receipt.show', $order))->assertOk();
        $this->actingAs($admin)->get(route('receipt.show', $order))->assertOk();
        $this->actingAs($seller)->get(route('receipt.show', $order))->assertOk();
        $this->actingAs($other)->get(route('receipt.show', $order))->assertForbidden();
    }

    public function test_review_is_only_for_own_completed_order_and_only_once(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $order = $this->createOrder($buyer, 'Selesai', 'COD');

        $this->actingAs($buyer)->post(route('buyer.orders.review', $order), [
            'rating'=>4,'komentar'=>'Produk bagus dan rapi.',
        ])->assertRedirect(route('buyer.dashboard'));

        $this->assertDatabaseHas('ulasan', ['pesanan_id'=>$order->id,'pembeli_id'=>$buyer->id,'rating'=>4]);
        $this->actingAs($buyer)->post(route('buyer.orders.review', $order), ['rating'=>5], ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertSame(1, Ulasan::where('pesanan_id', $order->id)->count());
    }

    public function test_buyer_can_confirm_received_order_when_status_is_diproses(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $order = $this->createOrder($buyer, 'Diproses', 'COD');

        $this->actingAs($buyer)
            ->patch(route('buyer.orders.confirm-received', $order))
            ->assertRedirect(route('buyer.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame('Selesai', $order->fresh()->status);
        $this->assertSame('Sudah Dibayar', $order->fresh()->status_pembayaran);
    }

    public function test_buyer_cannot_confirm_received_foreign_order(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $other = User::create(['username'=>'other_buyer','password'=>'password123','nama_lengkap'=>'Other Buyer','role'=>'pembeli','status'=>'aktif']);
        $order = $this->createOrder($buyer, 'Diproses');

        $this->actingAs($other)
            ->patch(route('buyer.orders.confirm-received', $order))
            ->assertForbidden();

        $this->assertSame('Diproses', $order->fresh()->status);
    }

    public function test_buyer_cannot_confirm_received_order_if_status_is_not_diproses(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $orderWaiting = $this->createOrder($buyer, 'Menunggu');

        $this->actingAs($buyer)
            ->patch(route('buyer.orders.confirm-received', $orderWaiting), [], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertSame('Menunggu', $orderWaiting->fresh()->status);
    }
}

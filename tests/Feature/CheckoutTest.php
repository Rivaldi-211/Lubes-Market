<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_buyer_can_open_checkout(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $seller = User::where('role', 'penjual')->firstOrFail();
        $this->actingAs($seller)->withSession(['cart' => [1 => 1]])->get('/checkout')->assertForbidden();
    }

    public function test_successful_checkout_creates_orders_deducts_stock_and_clears_cart(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $p1 = Produk::findOrFail(1); $p2 = Produk::findOrFail(6);
        $s1 = $p1->stok_jumlah; $s2 = $p2->stok_jumlah;

        $this->actingAs($buyer)->withSession(['cart' => [$p1->id => 2, $p2->id => 1]])
            ->post('/checkout', [
                'metode_pembayaran' => 'Transfer', 'alamat_pengiriman' => 'Dusun Moncongloe Lappara',
                'no_hp_pembeli' => '081234500006', 'catatan' => 'Tolong rapi',
            ])->assertRedirect('/pembeli');

        $this->assertSame(2, Pesanan::where('pembeli_id', $buyer->id)->where('catatan', 'Tolong rapi')->count());
        $this->assertSame($s1 - 2, $p1->fresh()->stok_jumlah);
        $this->assertSame($s2 - 1, $p2->fresh()->stok_jumlah);
        $this->assertSame([], session('cart'));
    }

    public function test_stale_cart_stock_rejection_creates_no_partial_new_order(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $buyer = User::where('username', 'budi_pembeli')->firstOrFail();
        $p1 = Produk::findOrFail(1); $p2 = Produk::findOrFail(2);
        $before = Pesanan::count();
        $p2->update(['stok_jumlah' => 1]);

        $this->actingAs($buyer)->withSession(['cart' => [$p1->id => 1, $p2->id => 2]])
            ->post('/checkout', [
                'metode_pembayaran' => 'COD', 'alamat_pengiriman' => 'Moncongloe', 'no_hp_pembeli' => '0812',
            ])->assertSessionHasErrors('cart');

        $this->assertSame($before, Pesanan::count());
        $this->assertSame(1, $p2->fresh()->stok_jumlah);
    }
}

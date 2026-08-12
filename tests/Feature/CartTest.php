<?php

namespace Tests\Feature;

use App\Models\Produk;
use Database\Seeders\BumdesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_update_remove_and_clear_cart(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $product = Produk::findOrFail(1);

        $this->post('/keranjang/tambah/'.$product->id, ['jumlah' => 2])
            ->assertRedirect('/keranjang')->assertSessionHas('cart.'.$product->id, 2);

        $this->withSession(['cart' => [$product->id => 2]])
            ->patch('/keranjang', ['jumlah_cart' => [$product->id => 3]])
            ->assertSessionHas('cart.'.$product->id, 3);

        $this->withSession(['cart' => [$product->id => 3]])
            ->delete('/keranjang/'.$product->id)->assertSessionMissing('cart.'.$product->id);

        $this->withSession(['cart' => [$product->id => 1]])
            ->delete('/keranjang')->assertSessionHas('cart', []);
    }

    public function test_cart_rejects_unavailable_or_excess_quantity(): void
    {
        $this->seed(BumdesDemoSeeder::class);
        $product = Produk::findOrFail(1);
        $product->update(['stok_jumlah' => 2]);

        $this->post('/keranjang/tambah/'.$product->id, ['jumlah' => 3])->assertSessionHasErrors('jumlah');

        $product->update(['stok_status' => 'Habis']);
        $this->post('/keranjang/tambah/'.$product->id, ['jumlah' => 1])->assertSessionHasErrors('jumlah');
    }
}

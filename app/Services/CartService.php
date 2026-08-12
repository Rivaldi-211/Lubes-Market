<?php
namespace App\Services;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
class CartService
{
    public function raw(): array { return Session::get('cart', []); }
    public function items(): Collection
    {
        $cart=$this->raw();
        if ($cart===[]) return collect();
        $products=Produk::with(['umkm','kategori'])->whereIn('id',array_keys($cart))->get()->keyBy('id');
        return collect($cart)->map(function($quantity,$id) use ($products) {
            $product=$products->get((int)$id);
            return $product ? ['product'=>$product,'quantity'=>(int)$quantity,'line_total'=>(float)$product->harga*(int)$quantity] : null;
        })->filter()->values();
    }
    public function add(Produk $produk, int $quantity): void
    {
        if (!$produk->isAvailable()) throw ValidationException::withMessages(['jumlah'=>'Produk sedang tidak tersedia.']);
        $cart=$this->raw(); $current=(int)($cart[$produk->id]??0); $target=$current+$quantity;
        if ($quantity<1 || $target>$produk->stok_jumlah) throw ValidationException::withMessages(['jumlah'=>'Jumlah melebihi stok yang tersedia.']);
        $cart[$produk->id]=$target; Session::put('cart',$cart);
    }
    public function update(array $quantities): void
    {
        $cart=$this->raw();
        foreach($quantities as $id=>$quantity) {
            $product=Produk::find((int)$id); if(!$product){unset($cart[$id]);continue;}
            $quantity=(int)$quantity;
            if($quantity<=0){unset($cart[$id]);continue;}
            if(!$product->isAvailable() || $quantity>$product->stok_jumlah) throw ValidationException::withMessages(["jumlah_cart.$id"=>"Jumlah {$product->nama_produk} melebihi stok."]);
            $cart[$product->id]=$quantity;
        }
        Session::put('cart',$cart);
    }
    public function remove(int $productId): void { $cart=$this->raw(); unset($cart[$productId]); Session::put('cart',$cart); }
    public function clear(): void { Session::put('cart',[]); }
    public function subtotal(): float { return (float)$this->items()->sum('line_total'); }
}

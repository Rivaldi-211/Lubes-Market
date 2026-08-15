<?php
namespace App\Http\Controllers;
use App\Models\Produk;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class CartController extends Controller
{
    public function index(CartService $cart): View { return view('cart.index',['items'=>$cart->items(),'subtotal'=>$cart->subtotal()]); }
    public function add(Request $request, Produk $produk, CartService $cart): RedirectResponse
    {
        $data = $request->validate(['jumlah' => ['required', 'integer', 'min:1']]);
        $cart->add($produk, (int)$data['jumlah']);

        if ($request->has('direct_checkout')) {
            return redirect()->route('checkout.create');
        }

        return redirect()->back(fallback: route('cart.index'))->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }
    public function update(Request $request, CartService $cart): RedirectResponse
    {
        $data=$request->validate(['jumlah_cart'=>['required','array'],'jumlah_cart.*'=>['required','integer','min:0']]);
        $cart->update($data['jumlah_cart']); return redirect()->route('cart.index')->with('success','Keranjang diperbarui.');
    }
    public function remove(Produk $produk, CartService $cart): RedirectResponse
    {
        $cart->remove($produk->id); return redirect()->route('cart.index')->with('success','Produk dihapus dari keranjang.');
    }
    public function clear(CartService $cart): RedirectResponse
    {
        $cart->clear(); return redirect()->route('cart.index')->with('success','Keranjang dikosongkan.');
    }
}

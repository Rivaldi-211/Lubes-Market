<?php

namespace App\Http\Controllers;

use App\Models\KelompokKeroyokan;
use App\Models\Produk;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cart): View
    {
        $keroyokanContext = $cart->keroyokanContext();
        $kelompok = null;
        if ($keroyokanContext && !empty($keroyokanContext['kelompok_keroyokan_id'])) {
            $kelompok = KelompokKeroyokan::with(['kategori'])->find($keroyokanContext['kelompok_keroyokan_id']);
        }

        $keroyokanItems = $cart->keroyokanItems();
        $regularItems = $cart->regularItems();

        return view('cart.index', [
            'items' => $cart->items(),
            'keroyokanItems' => $keroyokanItems,
            'regularItems' => $regularItems,
            'subtotal' => $cart->subtotal(),
            'keroyokanSubtotal' => $cart->keroyokanSubtotal(),
            'regularSubtotal' => $cart->regularSubtotal(),
            'isKeroyokan' => $cart->isKeroyokan(),
            'keroyokanContext' => $keroyokanContext,
            'kelompok' => $kelompok,
        ]);
    }

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
        $data = $request->validate(['jumlah_cart' => ['required', 'array'], 'jumlah_cart.*' => ['required', 'integer', 'min:0']]);
        $cart->update($data['jumlah_cart']);
        return redirect()->route('cart.index')->with('success', 'Keranjang diperbarui.');
    }

    public function remove(Produk $produk, CartService $cart): RedirectResponse
    {
        $cart->remove($produk->id);
        return redirect()->route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }

    public function removeKeroyokan(CartService $cart): RedirectResponse
    {
        $cart->removeKeroyokan();
        return redirect()->route('cart.index')->with('success', 'Paket Keroyokan dihapus dari keranjang.');
    }

    public function clear(CartService $cart): RedirectResponse
    {
        $cart->clear();
        return redirect()->route('cart.index')->with('success', 'Keranjang dikosongkan.');
    }
}


<?php
namespace App\Http\Controllers;
use App\Http\Requests\CheckoutRequest;
use App\Services\ActivityLogger;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class CheckoutController extends Controller
{
    public function create(Request $request, CartService $cart): View|RedirectResponse
    {
        $items=$cart->items();
        if($items->isEmpty()) return redirect()->route('catalogue')->with('error','Keranjang masih kosong.');
        return view('checkout.create',['items'=>$items,'subtotal'=>$cart->subtotal(),'user'=>$request->user()]);
    }
    public function store(CheckoutRequest $request, CheckoutService $checkout, ActivityLogger $logger): RedirectResponse
    {
        $orders=$checkout->checkout($request->user(),$request->validated());
        $ids=$orders->pluck('id')->implode(', ');
        $logger->log('Membuat pesanan #'.$ids,$request->user(),$request->ip());
        return redirect()->route('buyer.dashboard')->with('success','Pesanan berhasil dibuat. Pantau statusnya dari dashboard pembeli.');
    }
}

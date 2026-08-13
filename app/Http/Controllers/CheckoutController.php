<?php

namespace App\Http\Controllers;

use App\Exceptions\Payment\QrisPaymentConflictException;
use App\Exceptions\Payment\QrisPaymentProviderException;
use App\Exceptions\Payment\QrisPaymentValidationException;
use App\Http\Requests\CheckoutRequest;
use App\Services\ActivityLogger;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\QrisPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, CartService $cart): View|RedirectResponse
    {
        $items = $cart->items();
        if ($items->isEmpty()) {
            return redirect()->route('catalogue')->with('error', 'Keranjang masih kosong.');
        }
        return view('checkout.create', ['items' => $items, 'subtotal' => $cart->subtotal(), 'user' => $request->user()]);
    }

    public function store(
        CheckoutRequest $request,
        CheckoutService $checkout,
        QrisPaymentService $qrisPayment,
        ActivityLogger $logger
    ): RedirectResponse {
        $validated = $request->validated();
        $orders = $checkout->checkout($request->user(), $validated);

        $ids = $orders->pluck('id')->implode(', ');
        $logger->log('Membuat pesanan #' . $ids, $request->user(), $request->ip());

        if (($validated['metode_pembayaran'] ?? '') === 'QRIS') {
            try {
                $payment = $qrisPayment->initiateQrisPayment(
                    $request->user(),
                    $orders->pluck('id')->toArray()
                );

                return redirect()->route('payment.qris.show', ['reference' => $payment->reference_id]);
            } catch (QrisPaymentConflictException|QrisPaymentProviderException $e) {
                $ref = $e->getPaymentReferenceId();
                if ($ref) {
                    return redirect()->route('payment.qris.show', ['reference' => $ref])->with('info', $e->getMessage());
                }
                return redirect()->route('buyer.dashboard')->with('error', $e->getMessage());
            } catch (QrisPaymentValidationException $e) {
                return redirect()->route('buyer.dashboard')->with('error', $e->getMessage());
            }
        }

        return redirect()->route('buyer.dashboard')->with('success', 'Pesanan berhasil dibuat. Pantau statusnya dari dashboard pembeli.');
    }
}

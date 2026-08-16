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
        if ($cart->items()->isEmpty()) {
            return redirect()->route('catalogue')->with('error', 'Keranjang masih kosong.');
        }

        $selectedProducts = $request->input('selected_products');
        $hasSelectionInput = $request->has('selected_products') || $request->has('select_keroyokan');

        if ($hasSelectionInput) {
            $selectKeroyokan = (bool)$request->input('select_keroyokan', false);
            session([
                'checkout_selection' => [
                    'products' => is_array($selectedProducts) ? array_map('intval', $selectedProducts) : [],
                    'keroyokan' => $selectKeroyokan,
                ]
            ]);
        }

        $selection = session('checkout_selection');

        $isKeroyokan = $cart->isKeroyokan();
        if ($selection !== null) {
            $isKeroyokan = $isKeroyokan && !empty($selection['keroyokan']);
        }

        $keroyokanItems = $isKeroyokan ? $cart->keroyokanItems() : collect();
        $regularItems = $cart->regularItems();

        if ($selection !== null && isset($selection['products'])) {
            $regularItems = $regularItems->filter(function ($item) use ($selection) {
                return in_array((int)$item['product']->id, $selection['products'], true);
            })->values();
        }

        $items = $keroyokanItems->concat($regularItems);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Pilih minimal 1 produk untuk melanjutkan ke checkout.');
        }

        $rekeningBankList = \App\Models\RekeningBank::aktif()
            ->whereNull('umkm_id')
            ->orderBy('urutan')
            ->get();
        $zonaPengiriman = \App\Models\ZonaPengiriman::aktif()->orderBy('urutan')->get();
        $opsiPacking = \App\Models\OpsiPacking::aktif()->orderBy('urutan')->get();
        $keroyokanContext = $cart->keroyokanContext();
        $kelompok = null;
        if ($isKeroyokan && !empty($keroyokanContext['kelompok_keroyokan_id'])) {
            $kelompok = \App\Models\KelompokKeroyokan::with(['kategori'])->find($keroyokanContext['kelompok_keroyokan_id']);
        }
        $umkmList = $items->pluck('product.umkm')->filter()->unique('id')->values();
        $umkmCount = $umkmList->count();

        return view('checkout.create', [
            'items' => $items,
            'keroyokanItems' => $keroyokanItems,
            'regularItems' => $regularItems,
            'subtotal' => (float)$items->sum('line_total'),
            'keroyokanSubtotal' => (float)$keroyokanItems->sum('line_total'),
            'regularSubtotal' => (float)$regularItems->sum('line_total'),
            'user' => $request->user(),
            'rekeningBankList' => $rekeningBankList,
            'zonaPengiriman' => $zonaPengiriman,
            'opsiPacking' => $opsiPacking,
            'isKeroyokan' => $isKeroyokan,
            'keroyokanContext' => $keroyokanContext,
            'kelompok' => $kelompok,
            'umkmList' => $umkmList,
            'umkmCount' => $umkmCount,
        ]);
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

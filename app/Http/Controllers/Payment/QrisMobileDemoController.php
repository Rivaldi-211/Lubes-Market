<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\XenditWebhookService;
use Exception;
use Illuminate\Http\Request;

class QrisMobileDemoController extends Controller
{
    public function show(string $reference)
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $payment = Payment::with(['pesanan.produk', 'user'])
            ->where('reference_id', $reference)
            ->firstOrFail();

        return view('payment.mobile_demo', [
            'payment' => $payment,
        ]);
    }

    public function pay(Request $request, string $reference, XenditWebhookService $webhookService)
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $payment = Payment::where('reference_id', $reference)->firstOrFail();

        if ($payment->status === 'PENDING') {
            try {
                $webhookToken = config('services.xendit.webhook_token');

                $dummyWebhookRequest = Request::create(
                    '/webhooks/xendit/payment',
                    'POST',
                    [
                        'event' => 'payment.capture',
                        'data' => [
                            'payment_id' => 'py_mob_demo_' . uniqid(),
                            'payment_request_id' => $payment->xendit_payment_request_id ?? ('pr_mob_' . uniqid()),
                            'reference_id' => $payment->reference_id,
                            'request_amount' => $payment->amount,
                            'status' => 'SUCCEEDED',
                            'type' => 'PAY',
                            'country' => 'ID',
                            'currency' => 'IDR',
                            'capture_method' => 'AUTOMATIC',
                            'channel_code' => 'QRIS',
                        ],
                    ],
                    [],
                    [],
                    ['HTTP_X_CALLBACK_TOKEN' => $webhookToken]
                );

                $webhookService->handlePaymentWebhook($dummyWebhookRequest);
            } catch (Exception $e) {
                return back()->with('error', 'Gagal memproses pembayaran demo: ' . $e->getMessage());
            }
        }

        return redirect()->route('payment.qris.demo_mobile', $payment->reference_id)
            ->with('success', 'Pembayaran berhasil dikonfirmasi!');
    }
}

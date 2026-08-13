<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\XenditService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QrisPaymentSimulationController extends Controller
{
    public function store(Request $request, string $reference, XenditService $xenditService): RedirectResponse
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $payment = Payment::where('reference_id', $reference)->firstOrFail();

        if ($payment->user_id !== $request->user()->id) {
            abort(404);
        }

        if (
            $payment->status !== 'PENDING' ||
            $payment->payment_method !== 'QRIS' ||
            empty($payment->xendit_payment_request_id) ||
            ($payment->expires_at !== null && $payment->expires_at->isPast())
        ) {
            return redirect()
                ->route('payment.qris.show', $payment->reference_id)
                ->with('error', 'Pembayaran tidak memenuhi syarat untuk disimulasikan.');
        }

        try {
            $xenditService->simulateQrisPayment($payment);

            if (app()->environment('local') && !app()->environment('testing')) {
                /** @var \App\Services\XenditWebhookService $webhookService */
                $webhookService = app(\App\Services\XenditWebhookService::class);
                $webhookToken = config('services.xendit.webhook_token');

                $dummyWebhookRequest = Request::create(
                    '/webhooks/xendit/payment',
                    'POST',
                    [
                        'event' => 'payment.capture',
                        'data' => [
                            'payment_id' => 'py_sim_' . uniqid(),
                            'payment_request_id' => $payment->xendit_payment_request_id,
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
            }
        } catch (Exception $e) {
            return redirect()
                ->route('payment.qris.show', $payment->reference_id)
                ->with('error', 'Gagal mengirim simulasi pembayaran ke penyedia Xendit.');
        }

        $message = app()->environment('local') && !app()->environment('testing')
            ? 'Simulasi pembayaran berhasil! Pembayaran telah dikonfirmasi LUNAS.'
            : 'Simulasi pembayaran dikirim ke Xendit. Menunggu konfirmasi webhook Xendit.';

        return redirect()
            ->route('payment.qris.show', $payment->reference_id)
            ->with('success', $message);
    }
}

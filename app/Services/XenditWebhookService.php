<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XenditWebhookService
{
    public function __construct(private QrisPaymentService $qrisPaymentService) {}

    public function handlePaymentWebhook(Request $request): JsonResponse
    {
        $configuredToken = config('services.xendit.webhook_token');
        if ($configuredToken === null || trim((string) $configuredToken) === '') {
            Log::warning('Xendit webhook token is not configured.');
            return response()->json(['message' => 'Xendit webhook token is not configured.'], 503);
        }

        $headerToken = $request->header('x-callback-token');
        if (empty($headerToken) || !hash_equals((string) $configuredToken, (string) $headerToken)) {
            return response()->json(['message' => 'Invalid or missing callback token.'], 401);
        }

        $event = $request->input('event');
        if (!in_array($event, ['payment.capture', 'payment.failure', 'payment_request.expiry'], true)) {
            return response()->json(['message' => 'Event ignored.', 'event' => $event], 200);
        }

        $data = $request->input('data');
        if (!is_array($data)) {
            return response()->json(['message' => 'Invalid webhook data structure.'], 400);
        }

        $referenceId = $data['reference_id'] ?? null;
        $paymentRequestId = $data['payment_request_id'] ?? $data['id'] ?? null;
        $paymentId = $data['payment_id'] ?? null;
        $requestAmount = $data['request_amount'] ?? $data['amount'] ?? null;
        $status = $data['status'] ?? null;
        $type = $data['type'] ?? null;
        $country = $data['country'] ?? null;
        $currency = $data['currency'] ?? null;
        $captureMethod = $data['capture_method'] ?? null;
        $channelCode = $data['channel_code'] ?? null;

        if (empty($referenceId) || empty($paymentRequestId)) {
            return response()->json(['message' => 'Payload contract mismatch.'], 400);
        }

        if ($event === 'payment.capture' || $event === 'payment.failure') {
            if (
                empty($paymentId) ||
                $type !== 'PAY' ||
                $country !== 'ID' ||
                $currency !== 'IDR' ||
                $captureMethod !== 'AUTOMATIC' ||
                $channelCode !== 'QRIS'
            ) {
                return response()->json(['message' => 'Payload contract mismatch.'], 400);
            }
        }

        if ($event === 'payment.capture' && $status !== 'SUCCEEDED') {
            return response()->json(['message' => 'Payment capture status must be SUCCEEDED.'], 400);
        }

        if ($event === 'payment.failure' && $status !== 'FAILED') {
            return response()->json(['message' => 'Payment failure status must be FAILED.'], 400);
        }

        if ($event === 'payment_request.expiry' && !in_array($status, ['EXPIRED', 'CANCELED'], true)) {
            return response()->json(['message' => 'Payment request expiry status must be EXPIRED or CANCELED.'], 400);
        }

        $normalizedAmount = $this->parseExactIntegerAmount($requestAmount);
        if ($normalizedAmount === null) {
            return response()->json(['message' => 'Invalid request_amount format.'], 400);
        }

        return DB::transaction(function () use ($event, $data, $referenceId, $paymentRequestId, $paymentId, $normalizedAmount) {
            /** @var Payment|null $payment */
            $payment = Payment::where('xendit_payment_request_id', $paymentRequestId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                $payment = Payment::where('reference_id', $referenceId)
                    ->whereIn('status', ['CREATING', 'PENDING', 'CREATION_UNKNOWN', 'EXPIRED'])
                    ->lockForUpdate()
                    ->first();
            }

            if (!$payment) {
                return response()->json(['message' => 'Payment record not found.'], 400);
            }

            if ($payment->reference_id !== $referenceId) {
                return response()->json(['message' => 'Reference ID mismatch.'], 400);
            }

            if ($payment->xendit_payment_request_id !== null && $payment->xendit_payment_request_id !== $paymentRequestId) {
                return response()->json(['message' => 'Payment request ID mismatch.'], 400);
            }

            if ($payment->amount !== $normalizedAmount) {
                return response()->json(['message' => 'Payment amount mismatch.'], 400);
            }

            if ($paymentId !== null && $payment->xendit_payment_id !== null && $payment->xendit_payment_id !== $paymentId) {
                Log::warning("Payment ID mismatch for reference {$referenceId}: existing {$payment->xendit_payment_id} vs incoming {$paymentId}");
                return response()->json(['message' => 'Existing payment ID mismatch.'], 400);
            }

            if ($event === 'payment.capture') {
                if ($payment->status === 'PAID') {
                    return response()->json(['message' => 'Duplicate payment capture acknowledged.'], 200);
                }

                $payment->status = 'PAID';
                if ($payment->xendit_payment_request_id === null) {
                    $payment->xendit_payment_request_id = $paymentRequestId;
                }
                if ($payment->xendit_payment_id === null && $paymentId !== null) {
                    $payment->xendit_payment_id = $paymentId;
                }
                if ($payment->paid_at === null) {
                    $payment->paid_at = now();
                }
                $payment->save();

                foreach ($payment->pesanan as $pesanan) {
                    if ($pesanan->status === 'Menunggu') {
                        $pesanan->update(['status' => 'Diproses']);
                    }
                }

                Log::info("Payment {$payment->reference_id} marked as PAID. Pesanan status updated to Diproses.");

                return response()->json(['message' => 'Payment marked as PAID successfully.'], 200);
            }

            if ($event === 'payment.failure' || $event === 'payment_request.expiry') {
                if ($payment->status === 'PAID') {
                    Log::warning("Failure or expiry callback received for already PAID payment {$payment->id}");
                    return response()->json(['message' => 'Failure/expiry callback ignored for PAID payment.'], 200);
                }

                $targetStatus = ($event === 'payment_request.expiry') ? 'EXPIRED' : 'FAILED';

                if ($payment->xendit_payment_request_id === null) {
                    $payment->xendit_payment_request_id = $paymentRequestId;
                }
                if ($payment->xendit_payment_id === null && $paymentId !== null) {
                    $payment->xendit_payment_id = $paymentId;
                }
                $payment->save();

                $this->qrisPaymentService->expirePaymentRecord($payment, $targetStatus);

                return response()->json(['message' => "Payment processed as {$targetStatus} and stock restored."], 200);
            }

            return response()->json(['message' => 'Processed.'], 200);
        });
    }

    private function parseExactIntegerAmount(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $str = (string) $amount;
        if (str_contains(strtolower($str), 'e')) {
            return null; // Scientific notation rejected
        }

        if (!is_numeric($str)) {
            return null;
        }

        $parts = explode('.', $str);
        $whole = $parts[0];
        $fraction = $parts[1] ?? '00';

        if ($fraction !== '00' && $fraction !== '0' && rtrim($fraction, '0') !== '') {
            return null; // Fractional IDR rejected
        }

        $val = (int) $whole;
        if ($val < 1) {
            return null;
        }

        return $val;
    }
}

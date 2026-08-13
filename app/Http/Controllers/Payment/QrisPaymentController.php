<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrisPaymentController extends Controller
{
    public function show(Request $request, string $reference): View
    {
        $payment = Payment::with(['pesanan.produk', 'user'])
            ->where('reference_id', $reference)
            ->firstOrFail();

        if ($payment->user_id !== $request->user()->id) {
            abort(404);
        }

        $qrDataUri = null;
        if ($payment->status === 'PENDING' && !empty($payment->qr_string)) {
            $qrDataUri = (new QRCode())->render($payment->qr_string);
        }

        return view('payment.qris', [
            'payment' => $payment,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    public function status(Request $request, string $reference): JsonResponse
    {
        $payment = Payment::where('reference_id', $reference)->firstOrFail();

        if ($payment->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json([
            'reference_id' => $payment->reference_id,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at ? $payment->paid_at->toIso8601String() : null,
            'expires_at' => $payment->expires_at ? $payment->expires_at->toIso8601String() : null,
            'server_time' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}

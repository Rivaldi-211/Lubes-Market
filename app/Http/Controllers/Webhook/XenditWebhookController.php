<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\XenditWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XenditWebhookController extends Controller
{
    public function payment(Request $request, XenditWebhookService $webhookService): JsonResponse
    {
        return $webhookService->handlePaymentWebhook($request);
    }
}

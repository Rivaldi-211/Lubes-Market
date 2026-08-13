<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\QrisPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireStalePaymentsCommand extends Command
{
    protected $signature = 'payments:expire-stale';

    protected $description = 'Expire stale PENDING QRIS payments that passed their reservation expires_at deadline and restore product stock.';

    public function handle(QrisPaymentService $qrisPaymentService): int
    {
        $stalePaymentIds = Payment::query()
            ->where('payment_method', 'QRIS')
            ->where('status', 'PENDING')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->toDateTimeString())
            ->pluck('id');

        $expiredCount = 0;

        foreach ($stalePaymentIds as $id) {
            try {
                $processed = $qrisPaymentService->expirePaymentRecord($id, 'EXPIRED');
                if ($processed) {
                    $expiredCount++;
                }
            } catch (\Throwable $e) {
                Log::error("Failed to expire stale payment ID {$id}: " . $e->getMessage());
            }
        }

        $this->info("Processed {$expiredCount} stale QRIS payments.");

        return Command::SUCCESS;
    }
}

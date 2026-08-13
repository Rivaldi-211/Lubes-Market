<?php

namespace App\Exceptions\Payment;

use Exception;
use Throwable;

class QrisPaymentConflictException extends Exception
{
    public const REASON_ALREADY_PAID = 'ALREADY_PAID';
    public const REASON_PAYMENT_IN_PROGRESS = 'PAYMENT_IN_PROGRESS';
    public const REASON_PAYMENT_STATE_UNKNOWN = 'PAYMENT_STATE_UNKNOWN';
    public const REASON_ACTIVE_PAYMENT_OVERLAP = 'ACTIVE_PAYMENT_OVERLAP';
    public const REASON_STALE_PENDING = 'STALE_PENDING';

    public function __construct(
        string $message,
        private string $reason,
        private ?int $paymentId = null,
        private ?string $paymentReferenceId = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function getPaymentReferenceId(): ?string
    {
        return $this->paymentReferenceId;
    }
}

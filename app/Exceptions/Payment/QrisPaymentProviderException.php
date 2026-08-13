<?php

namespace App\Exceptions\Payment;

use Exception;
use Throwable;

class QrisPaymentProviderException extends Exception
{
    public const REASON_PROVIDER_REJECTED = 'PROVIDER_REJECTED';
    public const REASON_PROVIDER_OUTCOME_UNKNOWN = 'PROVIDER_OUTCOME_UNKNOWN';
    public const REASON_PROVIDER_CONFIGURATION_ERROR = 'PROVIDER_CONFIGURATION_ERROR';

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

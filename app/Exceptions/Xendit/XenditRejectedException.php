<?php

namespace App\Exceptions\Xendit;

use Exception;

class XenditRejectedException extends Exception
{
    public function __construct(string $message, private int $statusCode = 400, private ?string $errorCode = null)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}

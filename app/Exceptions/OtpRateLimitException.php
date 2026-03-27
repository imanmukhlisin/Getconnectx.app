<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class OtpRateLimitException extends TooManyRequestsHttpException
{
    public function __construct(string $message = 'Terlalu banyak permintaan OTP.', int $retryAfterSeconds = 600)
    {
        parent::__construct($retryAfterSeconds * 60, $message);
    }
}

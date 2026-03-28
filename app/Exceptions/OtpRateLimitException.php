<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class OtpRateLimitException extends TooManyRequestsHttpException
{
    public function __construct(?string $message = null, int $retryAfterSeconds = 600)
    {
        $message = $message ?? __('messages.too_many_requests');
        parent::__construct($retryAfterSeconds * 60, $message);
    }
}

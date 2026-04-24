<?php

namespace App\Exceptions;

use Exception;

class PremiumRequiredException extends Exception
{
    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => 'Premium subscription required to use advanced discovery filters',
            'error'   => [
                'code' => 'PREMIUM_REQUIRED',
            ],
        ], 403);
    }
}

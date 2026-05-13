<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProController extends Controller
{
    /**
     * Get available Premium Plans.
     * Based on CON-56.
     */
    public function plans(Request $request)
    {
        $region = $request->query('region', 'ID');
        
        $plans = [
            [ 'id' => 'connectx_pro_weekly', 'name' => 'Weekly', 'price' => 29000, 'price_display' => 'Rp 29.000', 'duration_days' => 7, 'popular' => false ],
            [ 'id' => 'connectx_pro_monthly', 'name' => 'Monthly', 'price' => 99000, 'price_display' => 'Rp 99.000', 'duration_days' => 30, 'popular' => true ],
            [ 'id' => 'connectx_pro_quarterly', 'name' => '3 Months', 'price' => 249000, 'price_display' => 'Rp 249.000', 'duration_days' => 90, 'popular' => false, 'save_percentage' => 16 ],
            [ 'id' => 'connectx_pro_annual', 'name' => 'Annual', 'price' => 799000, 'price_display' => 'Rp 799.000', 'duration_days' => 365, 'popular' => false, 'save_percentage' => 33 ],
            [ 'id' => 'connectx_pro_lifetime', 'name' => 'Lifetime', 'price' => 1499000, 'price_display' => 'Rp 1.499.000', 'duration_days' => null, 'popular' => false ]
        ];

        return response()->json([
            'detected_region' => $region,
            'currency' => 'IDR',
            'plans' => $plans,
            'features' => [
                'See who liked you',
                'Unlimited swipes',
                'Advanced filters',
                'Priority visibility',
                'Rewind swipes'
            ]
        ]);
    }

    /**
     * Get the current user's PRO status.
     * Based on CON-56.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user->is_pro) {
            return response()->json([
                'is_pro' => false,
                'plan' => null,
                'expires_at' => null
            ]);
        }

        // Mock data for an active subscription.
        // In a full implementation, this might fetch from a `subscriptions` table.
        return response()->json([
            'is_pro' => true,
            'plan' => [ 'id' => 'connectx_pro_monthly', 'name' => 'Monthly', 'price_display' => 'Rp 99.000' ],
            'started_at' => now()->subDays(2)->toIso8601String(),
            'expires_at' => now()->addDays(28)->toIso8601String(),
            'auto_renew' => true,
        ]);
    }
}

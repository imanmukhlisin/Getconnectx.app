<?php

namespace App\Http\Controllers\Staging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AlgorithmTestController extends Controller
{
    public function free(Request $request)
    {
        return view('staging.matchmaking', [
            'mode' => 'free',
            'title' => 'Standard Matchmaking Sandbox'
        ]);
    }

    public function pro(Request $request)
    {
        return view('staging.matchmaking', [
            'mode' => 'pro',
            'title' => 'Premium PRO Matchmaking Sandbox'
        ]);
    }

    public function calculate(Request $request)
    {
        $mode = $request->input('mode', 'free');
        $userA = $request->input('userA');
        $userB = $request->input('userB');

        // Parse tags from comma separated string
        $userA['tags'] = array_filter(array_map('trim', explode(',', $userA['tags'] ?? '')));
        $userB['tags'] = array_filter(array_map('trim', explode(',', $userB['tags'] ?? '')));

        if ($mode === 'pro') {
            return response()->json($this->calculatePro($userA, $userB));
        }

        return response()->json($this->calculateFree($userA, $userB));
    }

    private function calculateFree($userA, $userB)
    {
        $weights = ['interest' => 0.5, 'role' => 0.3, 'location' => 0.2, 'pro_bonus' => 25];
        
        $sharedTags = array_intersect($userA['tags'], $userB['tags']);
        $maxTags = max(count($userA['tags'] ?: [1]), count($userB['tags'] ?: [1]));
        $interestScore = ($sharedTags && $maxTags > 0) ? (count($sharedTags) / $maxTags) * 100 : 0;

        $roleScore = ($userA['role'] !== $userB['role']) ? 100 : 50;

        $distance = $this->haversine($userA['lat'] ?? 0, $userA['lng'] ?? 0, $userB['lat'] ?? 0, $userB['lng'] ?? 0);
        $locationScore = max(0, 100 - ($distance / 50 * 100));

        $total = ($interestScore * $weights['interest']) +
                 ($roleScore * $weights['role']) +
                 ($locationScore * $weights['location']);

        if ($userB['is_pro'] ?? false) $total += $weights['pro_bonus'];

        return [
            'score' => min(100, round($total, 2)),
            'breakdown' => [
                ['label' => 'Vision Overlap', 'value' => round($interestScore, 1) . '%', 'weight' => '50%'],
                ['label' => 'Role Complement', 'value' => $roleScore . '%', 'weight' => '30%'],
                ['label' => 'Location Proximity', 'value' => round($locationScore, 1) . '%', 'weight' => '20%'],
                ['label' => 'Pro Status Bonus', 'value' => ($userB['is_pro'] ?? false) ? '+25' : '0', 'weight' => 'Bonus']
            ],
            'distance' => round($distance, 2) . ' km'
        ];
    }

    private function calculatePro($userA, $userB)
    {
        $weights = ['vision' => 0.4, 'skill' => 0.3, 'commitment' => 0.2, 'active' => 0.1];
        
        $sharedTags = array_intersect($userA['tags'], $userB['tags']);
        $maxTags = max(count($userA['tags'] ?: [1]), count($userB['tags'] ?: [1]));
        $visionScore = ($sharedTags && $maxTags > 0) ? (count($sharedTags) / $maxTags) * 100 : 0;

        $skillScore = ($userA['role'] !== $userB['role']) ? 100 : 40;
        $commitmentScore = ($userA['commitment'] === $userB['commitment']) ? 100 : 50;
        $activeScore = ($userB['active_days'] ?? 30) <= 7 ? 100 : 20;

        $total = ($visionScore * $weights['vision']) +
                 ($skillScore * $weights['skill']) +
                 ($commitmentScore * $weights['commitment']) +
                 ($activeScore * $weights['active']);

        return [
            'score' => min(100, round($total, 2)),
            'breakdown' => [
                ['label' => 'Vision Overlap', 'value' => round($visionScore, 1) . '%', 'weight' => '40%'],
                ['label' => 'Role Complement', 'value' => $skillScore . '%', 'weight' => '30%'],
                ['label' => 'Commitment Sync', 'value' => $commitmentScore . '%', 'weight' => '20%'],
                ['label' => 'Velocity Bonus', 'value' => $activeScore . '%', 'weight' => '10%']
            ]
        ];
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

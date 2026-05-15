<?php

/**
 * Matchmaking Algorithm Sandbox & Simulator
 * Location: /staging/algoritmfree/MatchmakingSandbox.php
 * 
 * This script allows for testing the scoring logic without database side effects.
 * It simulates user profiles and calculates compatibility scores based on the 
 * weighted algorithm used in FeedService.
 */

class MatchmakingSandbox
{
    // Default Weights from FeedService
    public $weights = [
        'interest' => 0.5,
        'role'     => 0.3,
        'location' => 0.2,
        'pro_bonus' => 25
    ];

    /**
     * Calculate score between two simulated users
     */
    public function calculateScore($userA, $userB, $radius = 50.0)
    {
        // 1. Interest Score (Overlap)
        $sharedTags = array_intersect($userA['tags'], $userB['tags']);
        $maxTags = max(count($userA['tags']), count($userB['tags']));
        $interestScore = $maxTags > 0 ? (count($sharedTags) / $maxTags) * 100 : 0;

        // 2. Role Score
        $roleScore = 0;
        if (!empty($userA['role']) && !empty($userB['role'])) {
            $roleScore = ($userA['role'] !== $userB['role']) ? 100 : 50;
        }

        // 3. Location Score (Haversine simplified)
        $distance = $this->haversine(
            $userA['lat'] ?? 0, $userA['lng'] ?? 0,
            $userB['lat'] ?? 0, $userB['lng'] ?? 0
        );
        
        $locationScore = max(0, 100 - ($distance / $radius * 100));

        // 4. Weighted Total
        $total = ($interestScore * $this->weights['interest']) +
                 ($roleScore * $this->weights['role']) +
                 ($locationScore * $this->weights['location']);

        // 5. Pro Bonus
        if ($userB['is_pro'] ?? false) {
            $total += $this->weights['pro_bonus'];
        }

        // 6. Final Cap at 100%
        $finalScore = min(100, round($total, 2));

        return [
            'total_score'    => $finalScore,
            'breakdown' => [
                'interest' => round($interestScore, 2),
                'role'     => $roleScore,
                'location' => round($locationScore, 2),
                'distance' => round($distance, 2) . ' km',
                'is_pro'   => $userB['is_pro'] ?? false,
                'raw_score'=> round($total, 2)
            ]
        ];
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

// Example Simulation Data
$tester = [
    'name' => 'Rama (Founder)',
    'role' => 'Founder',
    'tags' => ['AI', 'Laravel', 'SaaS', 'Matchmaking'],
    'lat'  => -6.2088, // Jakarta
    'lng'  => 106.8456
];

$candidates = [
    [
        'name' => 'Alice (Builder)',
        'role' => 'Builder',
        'tags' => ['AI', 'Python', 'React'],
        'lat'  => -6.2146, // Near Jakarta
        'lng'  => 106.8451,
        'is_pro' => true
    ],
    [
        'name' => 'Bob (Founder)',
        'role' => 'Founder',
        'tags' => ['Sales', 'Marketing'],
        'lat'  => -7.2575, // Surabaya
        'lng'  => 112.7521,
        'is_pro' => false
    ]
];

$sandbox = new MatchmakingSandbox();

echo "=== Matchmaking Algorithm Simulation ===\n";
echo "Tester: {$tester['name']}\n\n";

foreach ($candidates as $target) {
    $result = $sandbox->calculateScore($tester, $target);
    echo "Target: {$target['name']}\n";
    echo "Score: {$result['total_score']}\n";
    echo "Breakdown: " . json_encode($result['breakdown'], JSON_PRETTY_PRINT) . "\n";
    echo "--------------------------------------\n";
}

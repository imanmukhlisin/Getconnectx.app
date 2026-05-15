<?php

/**
 * Premium Matchmaking Algorithm (PRO)
 * Location: /staging/algoritmpro/ProMatchmakingSandbox.php
 * 
 * Advanced version for Pro Users.
 * Includes Skill Complementary and Commitment Alignment.
 */

class ProMatchmakingSandbox
{
    // Pro Weights
    public $weights = [
        'vision'      => 0.4, // Tag overlap
        'skill_match' => 0.3, // Complementary skills/roles
        'commitment'  => 0.2, // Full-time vs Part-time alignment
        'active_score' => 0.1  // Recency bonus
    ];

    public function calculateScore($userA, $userB)
    {
        // 1. Vision/Interest Overlap (40%)
        $sharedTags = array_intersect($userA['tags'], $userB['tags']);
        $maxTags = max(count($userA['tags']), count($userB['tags']));
        $visionScore = $maxTags > 0 ? (count($sharedTags) / $maxTags) * 100 : 0;

        // 2. Skill & Role Complement (30%)
        // Prefer Founder + Builder pairs over same-role pairs
        $skillScore = 0;
        if ($userA['role'] !== $userB['role']) {
            $skillScore = 100; // Perfect complementary roles
        } else {
            $skillScore = 40;  // Same roles (can still collaborate but lower priority)
        }

        // 3. Commitment Alignment (20%)
        // Prefer matching users with similar commitment levels
        $commitmentScore = 0;
        if (($userA['commitment'] ?? '') === ($userB['commitment'] ?? '')) {
            $commitmentScore = 100;
        } else {
            $commitmentScore = 50; // Different commitment might still work
        }

        // 4. Activity Score (10%)
        $activeScore = ($userB['last_active_days'] ?? 30) <= 7 ? 100 : 20;

        // 5. Final Weighted Total
        $total = ($visionScore * $this->weights['vision']) +
                 ($skillScore * $this->weights['skill_match']) +
                 ($commitmentScore * $this->weights['commitment']) +
                 ($activeScore * $this->weights['active_score']);

        return [
            'total_score' => min(100, round($total, 2)),
            'breakdown' => [
                'vision_overlap' => round($visionScore, 2),
                'role_complement' => $skillScore,
                'commitment_match' => $commitmentScore,
                'activity_bonus' => $activeScore
            ]
        ];
    }
}

// Example Pro Simulation
$tester = [
    'name' => 'Premium Rama',
    'role' => 'Founder',
    'tags' => ['AI', 'Fintech', 'SaaS'],
    'commitment' => 'Full-time',
];

$candidates = [
    [
        'name' => 'Elite Developer (Pro Target)',
        'role' => 'Builder',
        'tags' => ['AI', 'Python'],
        'commitment' => 'Full-time',
        'last_active_days' => 1
    ],
    [
        'name' => 'Side Project Bob',
        'role' => 'Founder',
        'tags' => ['Fintech'],
        'commitment' => 'Weekends',
        'last_active_days' => 15
    ]
];

$proSandbox = new ProMatchmakingSandbox();

echo "=== PRO Matchmaking Simulation ===\n";
foreach ($candidates as $target) {
    $result = $proSandbox->calculateScore($tester, $target);
    echo "Target: {$target['name']}\n";
    echo "Premium Score: {$result['total_score']}%\n";
    echo "Breakdown: " . json_encode($result['breakdown'], JSON_PRETTY_PRINT) . "\n";
    echo "--------------------------------------\n";
}

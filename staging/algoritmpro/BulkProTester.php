<?php

/**
 * Bulk Match Tester PRO
 * Location: /staging/algoritmpro/BulkProTester.php
 */

require_once 'ProMatchmakingSandbox.php';

class BulkProTester {
    private $sandbox;
    private $commitments = ['Full-time', 'Part-time', 'Weekends', 'Evening Only'];
    private $roles = ['Founder', 'Builder', 'Investor'];
    private $tags = ['AI', 'Fintech', 'SaaS', 'Web3', 'Marketplace', 'Social'];

    public function __construct() {
        $this->sandbox = new ProMatchmakingSandbox();
    }

    public function run($count = 100) {
        $tester = [
            'name' => 'Premium User',
            'role' => 'Founder',
            'tags' => ['AI', 'SaaS'],
            'commitment' => 'Full-time'
        ];

        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $candidate = [
                'name' => "ProCandidate_$i",
                'role' => $this->roles[array_rand($this->roles)],
                'tags' => (array) array_rand(array_flip($this->tags), rand(1, 3)),
                'commitment' => $this->commitments[array_rand($this->commitments)],
                'last_active_days' => rand(1, 30)
            ];
            
            $scoreData = $this->sandbox->calculateScore($tester, $candidate);
            $results[] = [
                'name' => $candidate['name'],
                'score' => $scoreData['total_score'],
                'breakdown' => $scoreData['breakdown']
            ];
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        echo "=== PRO Bulk Simulation Results (Top 5) ===\n";
        print_r(array_slice($results, 0, 5));
    }
}

$tester = new BulkProTester();
$tester->run(100);

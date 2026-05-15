<?php

/**
 * Bulk Match Tester
 * Location: /staging/algoritmfree/BulkMatchTester.php
 * 
 * Simulates 1000 random users and identifies top matches for a test profile.
 */

require_once 'MatchmakingSandbox.php';

class BulkMatchTester {
    private $sandbox;
    private $industries = ['Fintech', 'Edutech', 'Healthtech', 'SaaS', 'E-commerce', 'AI', 'Blockchain'];
    private $roles = ['Founder', 'Builder', 'Investor', 'Marketing', 'Sales'];

    public function __construct() {
        $this->sandbox = new MatchmakingSandbox();
    }

    public function generateRandomUser($id) {
        return [
            'id' => $id,
            'name' => "User_$id",
            'role' => $this->roles[array_rand($this->roles)],
            'tags' => array_intersect($this->industries, (array) array_rand(array_flip($this->industries), rand(1, 4))),
            'lat'  => -6.2 + (mt_rand() / mt_getrandmax() * 0.5), // Randomized around Jakarta
            'lng'  => 106.8 + (mt_rand() / mt_getrandmax() * 0.5),
            'is_pro' => (rand(1, 10) > 8) // 20% pro
        ];
    }

    public function run($count = 1000) {
        $tester = [
            'name' => 'Main Tester',
            'role' => 'Founder',
            'tags' => ['AI', 'SaaS', 'Fintech'],
            'lat'  => -6.2088,
            'lng'  => 106.8456
        ];

        $results = [];
        $start = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            $candidate = $this->generateRandomUser($i);
            $scoreData = $this->sandbox->calculateScore($tester, $candidate);
            $results[] = [
                'user' => $candidate['name'],
                'role' => $candidate['role'],
                'score' => $scoreData['total_score'],
                'breakdown' => $scoreData['breakdown']
            ];
        }

        // Sort by score
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        $end = microtime(true);

        echo "Simulation of $count users completed in " . round($end - $start, 4) . "s\n";
        echo "Top 5 Matches:\n";
        print_r(array_slice($results, 0, 5));
    }
}

$tester = new BulkMatchTester();
$tester->run(1000);

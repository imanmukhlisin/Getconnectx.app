<?php

namespace App\Http\Controllers\Staging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Matchmaking Engine: SAW + Profile Matching Edition
 * Implementation of GAP Analysis and Simple Additive Weighting
 */
class AlgorithmTestController extends Controller
{
    // GAP Analysis Conversion Table
    private function getGapWeight($gap)
    {
        $map = [
            '0'  => 5.0, // Ideal
            '1'  => 4.5, // Over-competent +1
            '-1' => 4.0, // Under-competent -1
            '2'  => 3.5, // Over-competent +2
            '-2' => 3.0, // Under-competent -2
            '3'  => 2.5, // Over-competent +3
            '-3' => 2.0, // Under-competent -3
            '4'  => 1.5, // Over-competent +4
            '-4' => 1.0, // Under-competent -4
        ];
        
        return $map[$gap] ?? 1.0; // Default to lowest if GAP is extreme
    }

    private function getFullOptions()
    {
        return [
            'roles' => [
                'BUILDERS' => [
                    'Founder' => ['Founder'],
                    'Co-Founder' => ['Co-Founder'],
                    'Team' => [
                        'CEO', 'CTO', 'CPO', 'CMO', 
                        'Frontend Engineer', 'Backend Engineer', 'Full Stack Engineer', 'Mobile Engineer', 
                        'AI/ML Engineer', 'Data Engineer', 'DevOps',
                        'Product Manager', 'UI/UX Designer', 'Product Designer', 'UX Researcher',
                        'Growth Marketer', 'Digital Marketer', 'Sales Executive', 'Operations Manager'
                    ]
                ],
                'STARTUPS' => [
                    'Entity' => ['Stealth Startup', 'Idea Stage', 'MVP Stage', 'Pre-Seed Startup', 'Seed Stage', 'Series A+']
                ]
            ],
            'industries' => ['AI', 'SaaS', 'Fintech', 'Ecommerce', 'DeepTech', 'Web3', 'Healthcare', 'EdTech'],
            'experience' => ['Junior (0-2y)', 'Mid (3-5y)', 'Senior (5-8y)', 'Expert (8y+)'],
            'stages' => ['Idea', 'MVP', 'Seed', 'Series A+'],
            'commitments' => ['Full-time', 'Part-time', 'Weekends'],
            'leadership' => ['Democratic', 'Autocratic', 'Laissez-faire', 'Transformational'],
            'languages' => ['English', 'Indonesian', 'Mandarin', 'Mixed'],
            'education' => ['High School', 'Bachelor', 'Master', 'PhD']
        ];
    }

    public function free(Request $request)
    {
        return view('staging.matchmaking', array_merge($this->getFullOptions(), [
            'mode' => 'free',
            'title' => 'SAW + Profile Matching (FREE)'
        ]));
    }

    public function pro(Request $request)
    {
        return view('staging.matchmaking', array_merge($this->getFullOptions(), [
            'mode' => 'pro',
            'title' => 'SAW + Profile Matching (PRO)'
        ]));
    }

    public function calculate(Request $request)
    {
        $mode = $request->input('mode', 'free');
        $userA = $request->input('userA');
        $userB = $request->input('userB');

        if ($mode === 'pro') {
            return response()->json($this->calculateSAWPro($userA, $userB));
        }

        return response()->json($this->calculateSAWFree($userA, $userB));
    }

    private function calculateSAWFree($userA, $userB)
    {
        // FREE: 4 Variables (2 CF, 2 SF)
        
        // --- Core Factors (60%) ---
        // 1. modeFit (Intent)
        $gapMode = ($userA['commitment'] === $userB['commitment']) ? 0 : -1;
        $cf1 = $this->getGapWeight($gapMode);

        // 2. skillComp (Role)
        $gapSkill = ($userA['role'] !== $userB['role']) ? 0 : -2; // Founder vs Builder = 0 Gap (Ideal)
        $cf2 = $this->getGapWeight($gapSkill);

        $ncf = ($cf1 + $cf2) / 2;

        // --- Secondary Factors (40%) ---
        // 3. industryFit
        $shared = array_intersect((array)($userA['tags'] ?? []), (array)($userB['tags'] ?? []));
        $gapIndustry = (count($shared) >= 1) ? 0 : -2;
        $sf1 = $this->getGapWeight($gapIndustry);

        // 4. commitmentFit
        $sf2 = $cf1; // Simplified for Free

        $nsf = ($sf1 + $sf2) / 2;

        // Final Score (60% CF + 40% SF)
        $finalScoreRaw = (0.6 * $ncf) + (0.4 * $nsf);
        $scorePercent = ($finalScoreRaw / 5.0) * 100;

        return [
            'score' => round($scorePercent, 2),
            'ncf' => round($ncf, 2),
            'nsf' => round($nsf, 2),
            'breakdown' => [
                ['label' => 'Intent Alignment (CF)', 'value' => $cf1 . '/5.0', 'weight' => '60%'],
                ['label' => 'Role Synergy (CF)', 'value' => $cf2 . '/5.0', 'weight' => '60%'],
                ['label' => 'Sector Relevance (SF)', 'value' => $sf1 . '/5.0', 'weight' => '40%'],
                ['label' => 'Availability (SF)', 'value' => $sf2 . '/5.0', 'weight' => '40%'],
            ]
        ];
    }

    private function calculateSAWPro($userA, $userB)
    {
        // PRO: 10 Variables (Detailed Implementation)
        
        // --- Core Factors (60%) ---
        $cf_scores = [];
        // 1. modeFit & skillComp
        $cf_scores[] = $this->getGapWeight(($userA['commitment'] === $userB['commitment']) ? 0 : -1);
        $cf_scores[] = $this->getGapWeight(($userA['role'] !== $userB['role']) ? 0 : -2);
        // 2. industryFit
        $shared = array_intersect((array)($userA['tags'] ?? []), (array)($userB['tags'] ?? []));
        $cf_scores[] = $this->getGapWeight((count($shared) >= 2) ? 0 : (count($shared) == 1 ? -1 : -2));
        // 3. experienceFit
        $cf_scores[] = $this->getGapWeight(($userA['experience'] === $userB['experience']) ? 0 : -1);
        // 4. stageFit
        $cf_scores[] = $this->getGapWeight(($userA['stage'] === $userB['stage']) ? 0 : 1);

        $ncf = array_sum($cf_scores) / count($cf_scores);

        // --- Secondary Factors (40%) ---
        $sf_scores = [];
        $sf_scores[] = $this->getGapWeight(($userA['leadership'] === $userB['leadership']) ? 0 : -1);
        $sf_scores[] = $this->getGapWeight(($userA['language'] === $userB['language']) ? 0 : -1);
        $sf_scores[] = $this->getGapWeight(($userA['education'] === $userB['education']) ? 0 : -1);
        $sf_scores[] = $this->getGapWeight(0); // Location Score (Simulated Ideal)
        $sf_scores[] = $this->getGapWeight(0); // Readiness (Simulated Ideal)

        $nsf = array_sum($sf_scores) / count($sf_scores);

        $finalScoreRaw = (0.6 * $ncf) + (0.4 * $nsf);
        $scorePercent = ($finalScoreRaw / 5.0) * 100;

        return [
            'score' => round($scorePercent, 2),
            'ncf' => round($ncf, 2),
            'nsf' => round($nsf, 2),
            'breakdown' => [
                ['label' => 'Core Compatibility (NCF)', 'value' => round($ncf, 2) . '/5.0', 'weight' => '60%'],
                ['label' => 'Secondary Synergy (NSF)', 'value' => round($nsf, 2) . '/5.0', 'weight' => '40%'],
                ['label' => 'Leadership Fit', 'value' => $sf_scores[0] . '/5.0', 'weight' => 'SF'],
                ['label' => 'Language Match', 'value' => $sf_scores[1] . '/5.0', 'weight' => 'SF'],
            ]
        ];
    }
}

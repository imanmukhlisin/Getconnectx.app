<?php

namespace App\Http\Controllers\Staging;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AlgorithmTestController extends Controller
{
    // 100% Perfect GAP Conversion Table based on Specification
    private function getGapWeight($gap)
    {
        $map = [
            '0'  => 5.0, // Ideal
            '1'  => 4.5, // Over +1
            '-1' => 4.0, // Under -1
            '2'  => 3.5, // Over +2
            '-2' => 3.0, // Under -2
            '3'  => 2.5, // Over +3
            '-3' => 2.0, // Under -3
            '4'  => 1.5, // Over +4
            '-4' => 1.0, // Under -4
        ];
        return $map[(string)$gap] ?? 1.0;
    }

    private function getFullOptions()
    {
        return [
            'roles' => [
                'BUILDERS' => [
                    'Founder' => ['Founder'],
                    'Co-Founder' => ['Co-Founder'],
                    'Team' => ['CEO', 'CTO', 'CPO', 'CMO', 'Frontend Engineer', 'Backend Engineer', 'Full Stack Engineer', 'Mobile Engineer', 'AI/ML Engineer', 'Data Engineer', 'DevOps', 'Product Manager', 'UI/UX Designer', 'Growth Marketer', 'Operations Manager']
                ],
                'STARTUPS' => [
                    'Entity' => ['Stealth Startup', 'Idea Stage', 'MVP Stage', 'Pre-Seed Startup', 'Seed Stage', 'Series A+']
                ]
            ],
            'industries' => ['AI', 'SaaS', 'Fintech', 'Ecommerce', 'DeepTech', 'Web3', 'Healthcare', 'EdTech'],
            'experience' => ['Junior', 'Mid', 'Senior', 'Expert'], // Normalized for GAP
            'stages' => ['Stealth', 'Idea', 'MVP', 'Pre-Seed', 'Seed', 'Series A+'],
            'commitments' => ['Weekends', 'Part-time', 'Full-time'],
            'leadership' => ['Laissez-faire', 'Democratic', 'Transformational', 'Autocratic'],
            'languages' => ['Basic', 'Conversational', 'Fluent', 'Native'],
            'education' => ['High School', 'Bachelor', 'Master', 'PhD']
        ];
    }

    public function free(Request $request)
    {
        return view('staging.matchmaking', array_merge($this->getFullOptions(), ['mode' => 'free', 'title' => 'ConnectX Match Engine (Free)']));
    }

    public function pro(Request $request)
    {
        return view('staging.matchmaking', array_merge($this->getFullOptions(), ['mode' => 'pro', 'title' => 'ConnectX Match Engine (Pro)']));
    }

    public function calculate(Request $request)
    {
        $mode = $request->input('mode', 'free');
        $userA = $request->input('userA');
        $userB = $request->input('userB');

        if ($mode === 'pro') return response()->json($this->calculateSAWPro($userA, $userB));
        return response()->json($this->calculateSAWFree($userA, $userB));
    }

    private function calculateSAWFree($userA, $userB)
    {
        // Spec: 2 Core, 2 Secondary
        $cf = [];
        $cf[] = $this->getGapWeight(($userA['commitment'] === $userB['commitment']) ? 0 : -1); // modeFit
        $cf[] = $this->getGapWeight(($userA['role'] !== $userB['role']) ? 0 : -2); // skillComp
        $ncf = array_sum($cf) / count($cf);

        $sf = [];
        $shared = array_intersect((array)($userA['tags'] ?? []), (array)($userB['tags'] ?? []));
        $sf[] = $this->getGapWeight((count($shared) >= 1) ? 0 : -2); // industryFit
        $sf[] = $cf[0]; // commitmentFit
        $nsf = array_sum($sf) / count($sf);

        $final = (0.6 * $ncf) + (0.4 * $nsf);
        return [
            'score' => round(($final / 5.0) * 100, 1),
            'ncf' => round($ncf, 2),
            'nsf' => round($nsf, 2),
            'breakdown' => [
                ['label' => 'modeFit', 'value' => $cf[0], 'weight' => 'Core'],
                ['label' => 'skillComp', 'value' => $cf[1], 'weight' => 'Core'],
                ['label' => 'industryFit', 'value' => $sf[0], 'weight' => 'Secondary'],
                ['label' => 'commitmentFit', 'value' => $sf[1], 'weight' => 'Secondary']
            ]
        ];
    }

    private function calculateSAWPro($userA, $userB)
    {
        // Spec: 5 Core, 5 Secondary
        $cf = [];
        $cf[] = $this->getGapWeight(($userA['commitment'] === $userB['commitment']) ? 0 : -1); // modeFit
        $cf[] = $this->getGapWeight(($userA['role'] !== $userB['role']) ? 0 : -2); // skillComp
        $shared = array_intersect((array)($userA['tags'] ?? []), (array)($userB['tags'] ?? []));
        $cf[] = $this->getGapWeight((count($shared) >= 2) ? 0 : (count($shared) == 1 ? -1 : -2)); // industryFit
        $cf[] = $this->getGapWeight(0); // experienceFit (Ideal Simulation)
        $cf[] = $this->getGapWeight(0); // stageFit (Ideal Simulation)
        $ncf = array_sum($cf) / count($cf);

        $sf = [];
        $sf[] = $this->getGapWeight(($userA['commitment'] === $userB['commitment']) ? 0 : -1); // commitmentFit
        $sf[] = $this->getGapWeight(0); // locationScore
        $sf[] = $this->getGapWeight(0); // leadershipFit
        $sf[] = $this->getGapWeight(0); // languageFit
        $sf[] = $this->getGapWeight(0); // educationFit
        $nsf = array_sum($sf) / count($sf);

        $final = (0.6 * $ncf) + (0.4 * $nsf);
        return [
            'score' => round(($final / 5.0) * 100, 1),
            'ncf' => round($ncf, 2),
            'nsf' => round($nsf, 2),
            'breakdown' => [
                ['label' => 'Core (NCF)', 'value' => round($ncf, 2), 'weight' => '60%'],
                ['label' => 'Secondary (NSF)', 'value' => round($nsf, 2), 'weight' => '40%'],
                ['label' => 'Skill Synergry', 'value' => $cf[1], 'weight' => 'Core'],
                ['label' => 'Sector Match', 'value' => $cf[2], 'weight' => 'Core']
            ]
        ];
    }
}

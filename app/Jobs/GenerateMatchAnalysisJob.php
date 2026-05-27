<?php

namespace App\Jobs;

use App\Models\MatchAnalysis;
use App\Models\MatchScore;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * GenerateMatchAnalysisJob
 *
 * Implements SAW (Simple Additive Weighting) + Profile Matching (GAP Analysis)
 * Based on ConnectX Matchmaking Engine Specification v2.0
 *
 * FREE Tier  → 4 variables  (2 Core + 2 Secondary)
 * PRO Tier   → 10 variables (5 Core + 5 Secondary)
 *
 * Formula: TotalScore = (60% × NCF) + (40% × NSF)
 * Final    = (TotalScore / 5.0) × 100
 */
class GenerateMatchAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $matchId;
    protected string $userAId;
    protected string $userBId;

    public function __construct(string $matchId, string $userAId, string $userBId)
    {
        $this->matchId = $matchId;
        $this->userAId = $userAId;
        $this->userBId = $userBId;
    }

    public function handle(): void
    {
        $userA = User::with(['tags', 'preference', 'builder'])->find($this->userAId);
        $userB = User::with(['tags', 'preference', 'builder'])->find($this->userBId);

        if (!$userA || !$userB) return;

        $isPro = $userA->is_pro || $userB->is_pro;

        $result = $isPro
            ? $this->calculateProScore($userA, $userB)
            : $this->calculateFreeScore($userA, $userB);

        MatchScore::updateOrCreate(
            ['match_id' => $this->matchId],
            [
                'score'   => (int) round($result['finalScore']),
                'label'   => $result['gradeLabel'],
                'insight' => $result['insight'],
            ]
        );

        MatchAnalysis::updateOrCreate(
            ['match_id' => $this->matchId],
            [
                'analysis_json' => $result['analysis'],
                'generated_at'  => now(),
            ]
        );
    }

    // =========================================================================
    // GAP TABLE — Converts gap value to normalized weight (1.0 – 5.0)
    // =========================================================================
    private function gapToWeight(int $gap): float
    {
        $table = [
             0 => 5.0,
             1 => 4.5,  -1 => 4.0,
             2 => 3.5,  -2 => 3.0,
             3 => 2.5,  -3 => 2.0,
             4 => 1.5,  -4 => 1.0,
        ];
        $clamped = max(-4, min(4, $gap));
        return $table[$clamped] ?? 1.0;
    }

    // =========================================================================
    // SAW FORMULA
    // =========================================================================
    private function saw(array $coreWeights, array $secondaryWeights): array
    {
        $ncf = count($coreWeights) > 0
            ? array_sum($coreWeights) / count($coreWeights)
            : 0;

        $nsf = count($secondaryWeights) > 0
            ? array_sum($secondaryWeights) / count($secondaryWeights)
            : 0;

        $rawScore   = (0.60 * $ncf) + (0.40 * $nsf);
        $finalScore = min(100, round(($rawScore / 5.0) * 100, 1));

        return [
            'ncf'        => round($ncf, 2),
            'nsf'        => round($nsf, 2),
            'rawScore'   => round($rawScore, 2),
            'finalScore' => $finalScore,
        ];
    }

    // =========================================================================
    // FREE TIER — 4 Variables
    // Core (60%): modeFit, skillComp
    // Secondary (40%): industryFit, commitmentFit
    // =========================================================================
    private function calculateFreeScore(User $a, User $b): array
    {
        $modeFit       = $this->calcModeFit($a, $b);
        $skillComp     = $this->calcSkillComp($a, $b);
        $industryFit   = $this->calcIndustryFit($a, $b);
        $commitmentFit = $this->calcCommitmentFit($a, $b);

        $saw = $this->saw(
            [$modeFit['weight'], $skillComp['weight']],
            [$industryFit['weight'], $commitmentFit['weight']]
        );

        return $this->buildResult($saw, $modeFit, $skillComp, $industryFit, $commitmentFit, false);
    }

    // =========================================================================
    // PRO TIER — 10 Variables
    // Core (60%): modeFit, skillComp, industryFit, experienceFit, stageFit
    // Secondary (40%): commitmentFit, locationScore, leadershipFit, languageFit, educationFit
    // =========================================================================
    private function calculateProScore(User $a, User $b): array
    {
        // Core
        $modeFit       = $this->calcModeFit($a, $b);
        $skillComp     = $this->calcSkillComp($a, $b);
        $industryFit   = $this->calcIndustryFit($a, $b);
        $experienceFit = $this->calcExperienceFit($a, $b);
        $stageFit      = $this->calcStageFit($a, $b);

        // Secondary
        $commitmentFit = $this->calcCommitmentFit($a, $b);
        $locationScore = $this->calcLocationScore($a, $b);
        $leadershipFit = $this->calcLeadershipFit($a, $b);
        $languageFit   = $this->calcLanguageFit($a, $b);
        $educationFit  = $this->calcEducationFit($a, $b);

        $saw = $this->saw(
            [$modeFit['weight'], $skillComp['weight'], $industryFit['weight'], $experienceFit['weight'], $stageFit['weight']],
            [$commitmentFit['weight'], $locationScore['weight'], $leadershipFit['weight'], $languageFit['weight'], $educationFit['weight']]
        );

        return $this->buildResult(
            $saw, $modeFit, $skillComp, $industryFit, $commitmentFit, true,
            $experienceFit, $stageFit, $locationScore, $leadershipFit, $languageFit, $educationFit
        );
    }

    // =========================================================================
    // VARIABLE CALCULATORS
    // =========================================================================

    /** V1: modeFit — Intent alignment (Founder ↔ CoFounder/Team is ideal) */
    private function calcModeFit(User $a, User $b): array
    {
        $roleA = strtolower($a->role_category ?? '');
        $roleB = strtolower($b->role_category ?? '');

        $perfectPairs = [
            ['founder', 'cofounder'], ['cofounder', 'founder'],
            ['founder', 'team'],      ['team', 'founder'],
            ['startup', 'cofounder'], ['cofounder', 'startup'],
            ['startup', 'team'],      ['team', 'startup'],
        ];

        $isPerfect = in_array([$roleA, $roleB], $perfectPairs);
        $gap       = $isPerfect ? 0 : ($roleA === $roleB ? -1 : -2);
        $weight    = $this->gapToWeight($gap);

        return [
            'weight' => $weight,
            'gap'    => $gap,
            'detail' => "Intent: {$roleA} ↔ {$roleB}",
        ];
    }

    /** V2: skillComp — Skill complementarity (higher unique skills = better) */
    private function calcSkillComp(User $a, User $b): array
    {
        $skillsA   = $a->tags->where('type', 'skill')->pluck('name')->toArray();
        $skillsB   = $b->tags->where('type', 'skill')->pluck('name')->toArray();
        $youBring  = array_values(array_diff($skillsA, $skillsB));
        $theyBring = array_values(array_diff($skillsB, $skillsA));
        $shared    = array_values(array_intersect($skillsA, $skillsB));
        $total     = count(array_unique(array_merge($skillsA, $skillsB)));

        $complementaryRatio = $total > 0 ? (count($youBring) + count($theyBring)) / $total : 0;
        $levelA             = (int) round($complementaryRatio * 4) + 1; // 1-5
        $gap                = $levelA - 5; // Ideal = 5 (fully complementary)
        $weight             = $this->gapToWeight($gap);

        return [
            'weight'    => $weight,
            'gap'       => $gap,
            'youBring'  => $youBring,
            'theyBring' => $theyBring,
            'shared'    => $shared,
            'detail'    => (count($youBring) + count($theyBring)) . " complementary skills",
        ];
    }

    /** V3: industryFit — Industry/sector overlap */
    private function calcIndustryFit(User $a, User $b): array
    {
        $indA       = $a->tags->where('type', 'industry')->pluck('name')->toArray();
        $indB       = $b->tags->where('type', 'industry')->pluck('name')->toArray();
        $overlap    = array_values(array_intersect($indA, $indB));
        $maxPossible = min(count($indA), count($indB));

        $levelA = $maxPossible > 0
            ? (int) round((count($overlap) / $maxPossible) * 4) + 1
            : 1;
        $gap    = $levelA - 5;
        $weight = $this->gapToWeight($gap);

        return [
            'weight'      => $weight,
            'gap'         => $gap,
            'overlapping' => $overlap,
            'detail'      => count($overlap) . " shared industries",
        ];
    }

    /** V4: commitmentFit — Commitment level alignment */
    private function calcCommitmentFit(User $a, User $b): array
    {
        $map    = ['full_time' => 3, 'part_time' => 2, 'flexible' => 1];
        $levelA = $map[strtolower($a->commitment_level ?? '')] ?? 2;
        $levelB = $map[strtolower($b->commitment_level ?? '')] ?? 2;
        $gap    = $levelA - $levelB;
        $weight = $this->gapToWeight($gap);

        return [
            'weight'    => $weight,
            'gap'       => $gap,
            'userA'     => $a->commitment_level ?? 'unknown',
            'userB'     => $b->commitment_level ?? 'unknown',
            'isAligned' => $gap === 0,
            'detail'    => "Commitment: {$a->commitment_level} ↔ {$b->commitment_level}",
        ];
    }

    /** V5 (PRO): experienceFit — Years of experience alignment */
    private function calcExperienceFit(User $a, User $b): array
    {
        $expA = (int) ($a->builder?->years_experience ?? $a->years_experience ?? 0);
        $expB = (int) ($b->builder?->years_experience ?? $b->years_experience ?? 0);

        $toLevel = fn($y) => match(true) {
            $y >= 10 => 5,
            $y >= 7  => 4,
            $y >= 4  => 3,
            $y >= 2  => 2,
            default  => 1,
        };

        $gap    = $toLevel($expA) - $toLevel($expB);
        $weight = $this->gapToWeight($gap);

        return [
            'weight' => $weight,
            'gap'    => $gap,
            'userA'  => "{$expA} years",
            'userB'  => "{$expB} years",
            'detail' => "Experience gap: {$gap} levels",
        ];
    }

    /** V6 (PRO): stageFit — Startup stage alignment */
    private function calcStageFit(User $a, User $b): array
    {
        $map    = ['idea' => 1, 'mvp' => 2, 'live' => 3, 'scale' => 4, 'growth' => 5];
        $levelA = $map[strtolower($a->startup_stage ?? '')] ?? 3;
        $levelB = $map[strtolower($b->startup_stage ?? '')] ?? 3;
        $gap    = $levelA - $levelB;
        $weight = $this->gapToWeight($gap);

        return [
            'weight' => $weight,
            'gap'    => $gap,
            'userA'  => $a->startup_stage ?? 'unknown',
            'userB'  => $b->startup_stage ?? 'unknown',
            'detail' => "Stage gap: {$gap} levels",
        ];
    }

    /** V7 (PRO): locationScore — Geographic proximity + remote readiness */
    private function calcLocationScore(User $a, User $b): array
    {
        $remoteA = strtolower($a->remote_preference ?? '');
        $remoteB = strtolower($b->remote_preference ?? '');

        // Both remote-ready = ideal
        if (str_contains($remoteA, 'remote') && str_contains($remoteB, 'remote')) {
            return ['weight' => $this->gapToWeight(0), 'gap' => 0, 'detail' => 'Both remote-ready'];
        }

        $distance = $this->haversine(
            (float) ($a->latitude ?? 0),  (float) ($a->longitude ?? 0),
            (float) ($b->latitude ?? 0),  (float) ($b->longitude ?? 0)
        );

        $gap = match(true) {
            $distance < 50   => 0,
            $distance < 200  => -1,
            $distance < 500  => -2,
            $distance < 1000 => -3,
            default          => -4,
        };

        $weight = $this->gapToWeight($gap);
        return [
            'weight'   => $weight,
            'gap'      => $gap,
            'distance' => round($distance, 1) . ' km',
            'detail'   => round($distance, 1) . "km apart",
        ];
    }

    /** V8 (PRO): leadershipFit — Complementary leadership styles */
    private function calcLeadershipFit(User $a, User $b): array
    {
        $leaders = ['founder', 'startup'];
        $isLeaderA = in_array(strtolower($a->role_category ?? ''), $leaders);
        $isLeaderB = in_array(strtolower($b->role_category ?? ''), $leaders);

        // Best = one leads, one executes (complementary)
        $gap    = ($isLeaderA !== $isLeaderB) ? 0 : -2;
        $weight = $this->gapToWeight($gap);

        return [
            'weight' => $weight,
            'gap'    => $gap,
            'detail' => $gap === 0 ? 'Complementary leadership' : 'Similar leadership styles',
        ];
    }

    /** V9 (PRO): languageFit — Shared communication language */
    private function calcLanguageFit(User $a, User $b): array
    {
        $langA  = $a->tags->where('type', 'language')->pluck('name')->toArray();
        $langB  = $b->tags->where('type', 'language')->pluck('name')->toArray();
        $shared = array_values(array_intersect($langA, $langB));

        // No language tags → assume Indonesian/English (default OK)
        if (empty($langA) && empty($langB)) {
            return ['weight' => $this->gapToWeight(0), 'gap' => 0, 'shared' => [], 'detail' => 'Default shared language assumed'];
        }

        $gap    = count($shared) > 0 ? 0 : -4;
        $weight = $this->gapToWeight($gap);

        return [
            'weight' => $weight,
            'gap'    => $gap,
            'shared' => $shared,
            'detail' => count($shared) . " shared language(s)",
        ];
    }

    /** V10 (PRO): educationFit — Education level alignment */
    private function calcEducationFit(User $a, User $b): array
    {
        $map = ['sma' => 1, 'd3' => 2, 's1' => 3, 'bachelor' => 3, 's2' => 4, 'master' => 4, 's3' => 5, 'phd' => 5, 'doctorate' => 5];

        $getLevel = function (User $u) use ($map): int {
            $edu = strtolower($u->education_level ?? '');
            foreach ($map as $key => $level) {
                if (str_contains($edu, $key)) return $level;
            }
            return 3; // Default: S1/Bachelor
        };

        $gap    = $getLevel($a) - $getLevel($b);
        $weight = $this->gapToWeight($gap);

        return [
            'weight' => $weight,
            'gap'    => $gap,
            'userA'  => $a->education_level ?? 'Not specified',
            'userB'  => $b->education_level ?? 'Not specified',
            'detail' => "Education gap: {$gap} levels",
        ];
    }

    // =========================================================================
    // RESULT BUILDER
    // =========================================================================
    private function buildResult(
        array $saw,
        array $modeFit,
        array $skillComp,
        array $industryFit,
        array $commitmentFit,
        bool  $isPro,
        ?array $experienceFit = null,
        ?array $stageFit      = null,
        ?array $locationScore = null,
        ?array $leadershipFit = null,
        ?array $languageFit   = null,
        ?array $educationFit  = null,
    ): array {
        $score      = $saw['finalScore'];
        $gradeLabel = match(true) {
            $score >= 85 => 'Excellent Match',
            $score >= 70 => 'Strong Match',
            $score >= 55 => 'Good Fit',
            $score >= 40 => 'Potential Fit',
            default      => 'Low Compatibility',
        };

        // Build insight string
        $parts = [];
        if (!empty($skillComp['theyBring'])) {
            $parts[] = "They bring: " . implode(', ', array_slice($skillComp['theyBring'], 0, 3));
        }
        if (!empty($industryFit['overlapping'])) {
            $parts[] = "Shared interest in " . implode(', ', array_slice($industryFit['overlapping'], 0, 2));
        }
        if ($modeFit['gap'] === 0) {
            $parts[] = "Intent is perfectly aligned";
        }
        $insight = !empty($parts) ? implode('. ', $parts) . '.' : "Strong team potential.";

        $analysis = [
            'algorithm'          => 'SAW + Profile Matching v2.0',
            'tier'               => $isPro ? 'PRO' : 'FREE',
            'compatibilityScore' => $score,
            'gradeLabel'         => $gradeLabel,
            'ncf'                => $saw['ncf'],
            'nsf'                => $saw['nsf'],
            'rawScore'           => $saw['rawScore'],
            'breakdown'          => [
                'core' => array_filter([
                    'modeFit'       => $this->fmt($modeFit),
                    'skillComp'     => $this->fmt($skillComp),
                    'industryFit'   => $this->fmt($industryFit),
                    'experienceFit' => $isPro ? $this->fmt($experienceFit) : null,
                    'stageFit'      => $isPro ? $this->fmt($stageFit)      : null,
                ]),
                'secondary' => array_filter([
                    'commitmentFit' => $this->fmt($commitmentFit),
                    'locationScore' => $isPro ? $this->fmt($locationScore) : null,
                    'leadershipFit' => $isPro ? $this->fmt($leadershipFit) : null,
                    'languageFit'   => $isPro ? $this->fmt($languageFit)   : null,
                    'educationFit'  => $isPro ? $this->fmt($educationFit)  : null,
                ]),
            ],
            'skillComplementarity' => [
                'youBring'  => $skillComp['youBring']  ?? [],
                'theyBring' => $skillComp['theyBring'] ?? [],
                'shared'    => $skillComp['shared']    ?? [],
            ],
            'startupVisionAlignment' => [
                'overlappingInterests' => $industryFit['overlapping'] ?? [],
            ],
            'commitmentCompatibility' => [
                'userA'     => $commitmentFit['userA']     ?? null,
                'userB'     => $commitmentFit['userB']     ?? null,
                'isAligned' => $commitmentFit['isAligned'] ?? false,
            ],
            'workStyle' => [
                'sharedTraits' => $isPro ? ($locationScore['sharedTraits'] ?? []) : []
            ],
            'potentialRisks' => $isPro ? ($score < 60 ? ['Different commitment levels', 'Significant distance'] : []) : [],
            'suggestedRoles' => [
                'userA' => $modeFit['userARole'] ?? '',
                'userB' => $modeFit['userBRole'] ?? '',
            ],
            'suggestedTeamStructure' => $isPro ? "Standard co-founder dynamic based on {$modeFit['detail']}" : "Co-founder matching."
        ];

        return [
            'finalScore' => $score,
            'gradeLabel' => $gradeLabel,
            'insight'    => $insight,
            'analysis'   => $analysis,
        ];
    }

    private function fmt(?array $f): array
    {
        return ['weight' => $f['weight'] ?? 0, 'gap' => $f['gap'] ?? 0, 'detail' => $f['detail'] ?? ''];
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

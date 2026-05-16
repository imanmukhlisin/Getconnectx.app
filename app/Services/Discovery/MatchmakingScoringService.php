<?php

namespace App\Services\Discovery;

use App\Models\User;

/**
 * MatchmakingScoringService
 *
 * Calculates real-time matchmaking scores for the Discovery Feed.
 * Uses the SAME SAW + Profile Matching (GAP Analysis) algorithm as
 * GenerateMatchAnalysisJob to ensure 100% score consistency.
 *
 * FREE Tier → 4 variables  (Core: modeFit, skillComp | Secondary: industryFit, commitmentFit)
 * PRO Tier  → 10 variables (Core: +experienceFit, stageFit | Secondary: +locationScore, leadershipFit, languageFit, educationFit)
 *
 * Formula: TotalScore = (60% × NCF) + (40% × NSF) → mapped to 0–100
 */
class MatchmakingScoringService
{
    // =========================================================================
    // ENTRY POINT
    // =========================================================================

    public function computeScore(User $authUser, User $targetUser, string $mode = 'finding_cofounder'): array
    {
        $isPro = $authUser->is_pro;

        if ($isPro) {
            return $this->computeProScore($authUser, $targetUser, $mode);
        }

        return $this->computeFreeScore($authUser, $targetUser, $mode);
    }

    // =========================================================================
    // FREE TIER — 4 Variables
    // =========================================================================

    private function computeFreeScore(User $a, User $b, string $mode): array
    {
        $modeFit       = $this->calcModeFit($a, $b, $mode);
        $skillComp     = $this->calcSkillComp($a, $b);
        $industryFit   = $this->calcIndustryFit($a, $b);
        $commitmentFit = $this->calcCommitmentFit($a, $b);

        $ncf = ($modeFit + $skillComp) / 2;
        $nsf = ($industryFit + $commitmentFit) / 2;

        return $this->buildResult($ncf, $nsf, $a, $b);
    }

    // =========================================================================
    // PRO TIER — 10 Variables
    // =========================================================================

    private function computeProScore(User $a, User $b, string $mode): array
    {
        // Core (5)
        $modeFit       = $this->calcModeFit($a, $b, $mode);
        $skillComp     = $this->calcSkillComp($a, $b);
        $industryFit   = $this->calcIndustryFit($a, $b);
        $experienceFit = $this->calcExperienceFit($a, $b);
        $stageFit      = $this->calcStageFit($a, $b);

        // Secondary (5)
        $commitmentFit = $this->calcCommitmentFit($a, $b);
        $locationScore = $this->calcLocationScore($a, $b);
        $leadershipFit = $this->calcLeadershipFit($a, $b);
        $languageFit   = $this->calcLanguageFit($a, $b);
        $educationFit  = $this->calcEducationFit($a, $b);

        $ncf = ($modeFit + $skillComp + $industryFit + $experienceFit + $stageFit) / 5;
        $nsf = ($commitmentFit + $locationScore + $leadershipFit + $languageFit + $educationFit) / 5;

        return $this->buildResult($ncf, $nsf, $a, $b);
    }

    // =========================================================================
    // SAW RESULT BUILDER
    // =========================================================================

    private function buildResult(float $ncf, float $nsf, User $a, User $b): array
    {
        $rawScore   = ($ncf * 0.60) + ($nsf * 0.40);
        $finalScore = (int) min(100, max(0, round(($rawScore / 5.0) * 100)));

        return [
            'score'      => $finalScore,
            'label'      => $this->buildMatchLabel($finalScore),
            'highlights' => $this->buildHighlights($a, $b),
        ];
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
        return $table[max(-4, min(4, $gap))] ?? 1.0;
    }

    // =========================================================================
    // VARIABLE CALCULATORS (Returns weight 1.0 – 5.0)
    // =========================================================================

    /** V1: modeFit — Intent alignment */
    private function calcModeFit(User $a, User $b, string $mode): float
    {
        $roleA = strtolower($a->role_category ?? $a->builder?->role_category ?? '');
        $roleB = strtolower($b->role_category ?? $b->builder?->role_category ?? '');

        $perfectPairs = [
            ['founder', 'cofounder'], ['cofounder', 'founder'],
            ['founder', 'team'],      ['team', 'founder'],
            ['startup', 'cofounder'], ['cofounder', 'startup'],
            ['startup', 'team'],      ['team', 'startup'],
        ];

        $isPerfect = in_array([$roleA, $roleB], $perfectPairs);
        $gap       = $isPerfect ? 0 : ($roleA === $roleB ? -1 : -2);

        return $this->gapToWeight($gap);
    }

    /** V2: skillComp — Skill complementarity (higher unique = better) */
    private function calcSkillComp(User $a, User $b): float
    {
        $skillsA = $this->getTagsByType($a, 'skill');
        $skillsB = $this->getTagsByType($b, 'skill');

        // Fallback to role tag if skill tags empty
        if (empty($skillsA)) $skillsA = $this->getTagsByType($a, 'role');
        if (empty($skillsB)) $skillsB = $this->getTagsByType($b, 'role');

        if (empty($skillsA) && empty($skillsB)) return $this->gapToWeight(-2);

        $youBring  = array_diff($skillsA, $skillsB);
        $theyBring = array_diff($skillsB, $skillsA);
        $total     = count(array_unique(array_merge($skillsA, $skillsB)));

        $ratio  = $total > 0 ? (count($youBring) + count($theyBring)) / $total : 0;
        $levelA = (int) round($ratio * 4) + 1; // Maps 0.0-1.0 → 1-5
        $gap    = $levelA - 5;

        return $this->gapToWeight($gap);
    }

    /** V3: industryFit — Industry sector overlap */
    private function calcIndustryFit(User $a, User $b): float
    {
        $indA = $this->getTagsByType($a, 'industry');
        $indB = $this->getTagsByType($b, 'industry');

        // Fallback to column
        if (empty($indA) && $a->industry) $indA = [$a->industry];
        if (empty($indB) && $b->industry) $indB = [$b->industry];

        if (empty($indA) || empty($indB)) return $this->gapToWeight(-2);

        $overlap     = count(array_intersect($indA, $indB));
        $maxPossible = min(count($indA), count($indB));
        $levelA      = $maxPossible > 0 ? (int) round(($overlap / $maxPossible) * 4) + 1 : 1;
        $gap         = $levelA - 5;

        return $this->gapToWeight($gap);
    }

    /** V4: commitmentFit — Commitment level alignment */
    private function calcCommitmentFit(User $a, User $b): float
    {
        $map = ['full_time' => 3, 'part_time' => 2, 'flexible' => 1];

        $getLevel = function (User $u) use ($map): int {
            $val = strtolower(
                $u->commitment_level
                ?? $u->builder?->commitment_level
                ?? ''
            );
            return $map[$val] ?? 2;
        };

        $gap = $getLevel($a) - $getLevel($b);
        return $this->gapToWeight($gap);
    }

    /** V5 (PRO): experienceFit — Years of experience alignment */
    private function calcExperienceFit(User $a, User $b): float
    {
        $toLevel = fn($y) => match(true) {
            $y >= 10 => 5, $y >= 7 => 4, $y >= 4 => 3, $y >= 2 => 2, default => 1,
        };

        $expA = (int) ($a->builder?->years_experience ?? $a->years_experience ?? 0);
        $expB = (int) ($b->builder?->years_experience ?? $b->years_experience ?? 0);
        $gap  = $toLevel($expA) - $toLevel($expB);

        return $this->gapToWeight($gap);
    }

    /** V6 (PRO): stageFit — Startup stage alignment */
    private function calcStageFit(User $a, User $b): float
    {
        $map    = ['idea' => 1, 'mvp' => 2, 'live' => 3, 'scale' => 4, 'growth' => 5];
        $levelA = $map[strtolower($a->startup_stage ?? '')] ?? 3;
        $levelB = $map[strtolower($b->startup_stage ?? '')] ?? 3;
        $gap    = $levelA - $levelB;

        return $this->gapToWeight($gap);
    }

    /** V7 (PRO): locationScore — Geographic proximity + remote readiness */
    private function calcLocationScore(User $a, User $b): float
    {
        $remoteA = strtolower($a->remote_preference ?? '');
        $remoteB = strtolower($b->remote_preference ?? '');

        if (str_contains($remoteA, 'remote') && str_contains($remoteB, 'remote')) {
            return $this->gapToWeight(0); // Both remote-ready = ideal
        }

        // Check same city / country first (fast path)
        if ($a->city && $a->city === $b->city) return $this->gapToWeight(0);
        if ($a->country && $a->country === $b->country) return $this->gapToWeight(-1);

        // Full Haversine calculation
        $latA = (float) ($a->latitude ?? 0);
        $lngA = (float) ($a->longitude ?? 0);
        $latB = (float) ($b->latitude ?? 0);
        $lngB = (float) ($b->longitude ?? 0);

        if ($latA === 0.0 && $lngA === 0.0) return $this->gapToWeight(-1);

        $distance = $this->haversine($latA, $lngA, $latB, $lngB);
        $gap = match(true) {
            $distance < 50   => 0,
            $distance < 200  => -1,
            $distance < 500  => -2,
            $distance < 1000 => -3,
            default          => -4,
        };

        return $this->gapToWeight($gap);
    }

    /** V8 (PRO): leadershipFit — Complementary leadership roles */
    private function calcLeadershipFit(User $a, User $b): float
    {
        $leaders   = ['founder', 'startup'];
        $isLeaderA = in_array(strtolower($a->role_category ?? ''), $leaders);
        $isLeaderB = in_array(strtolower($b->role_category ?? ''), $leaders);

        $gap = ($isLeaderA !== $isLeaderB) ? 0 : -2; // Complementary = ideal
        return $this->gapToWeight($gap);
    }

    /** V9 (PRO): languageFit — Shared communication language */
    private function calcLanguageFit(User $a, User $b): float
    {
        $langA = is_array($a->languages) ? $a->languages : [];
        $langB = is_array($b->languages) ? $b->languages : [];

        // Augment with language tags
        $langA = array_merge($langA, $this->getTagsByType($a, 'language'));
        $langB = array_merge($langB, $this->getTagsByType($b, 'language'));

        // If neither has language data, assume shared default (Indonesian/English)
        if (empty($langA) && empty($langB)) return $this->gapToWeight(0);
        if (empty($langA) || empty($langB)) return $this->gapToWeight(-1);

        $shared = count(array_intersect($langA, $langB));
        $gap    = $shared > 0 ? 0 : -4;

        return $this->gapToWeight($gap);
    }

    /** V10 (PRO): educationFit — Education level alignment */
    private function calcEducationFit(User $a, User $b): float
    {
        $map = [
            'sma' => 1, 'd3' => 2,
            's1' => 3, 'bachelor' => 3,
            's2' => 4, 'master' => 4,
            's3' => 5, 'phd' => 5, 'doctorate' => 5,
        ];

        $getLevel = function (User $u) use ($map): int {
            $edu = strtolower($u->education_level ?? '');
            foreach ($map as $key => $level) {
                if (str_contains($edu, $key)) return $level;
            }
            return 3; // Default: S1
        };

        $gap = $getLevel($a) - $getLevel($b);
        return $this->gapToWeight($gap);
    }

    // =========================================================================
    // HIGHLIGHTS — Reasoning text for the match card
    // =========================================================================

    private function buildHighlights(User $a, User $b): array
    {
        $highlights = [];

        // Shared industries
        $indA    = $this->getTagsByType($a, 'industry');
        $indB    = $this->getTagsByType($b, 'industry');
        $shared  = array_intersect($indA, $indB);
        if (!empty($shared)) {
            $highlights[] = 'Shared interest in ' . implode(', ', array_slice($shared, 0, 2));
        }

        // Same city
        if ($a->city && $a->city === $b->city) {
            $highlights[] = 'Both based in ' . $a->city;
        }

        // Shared languages
        $langA   = is_array($a->languages) ? $a->languages : [];
        $langB   = is_array($b->languages) ? $b->languages : [];
        $sharedL = array_intersect(
            array_merge($langA, $this->getTagsByType($a, 'language')),
            array_merge($langB, $this->getTagsByType($b, 'language'))
        );
        if (!empty($sharedL)) {
            $highlights[] = 'Both speak ' . implode(', ', array_slice($sharedL, 0, 2));
        }

        // Complementary roles
        $roleA = strtolower($a->role_category ?? '');
        $roleB = strtolower($b->role_category ?? '');
        if ($roleA && $roleB && $roleA !== $roleB) {
            $highlights[] = ucfirst($roleA) . ' + ' . ucfirst($roleB) . ' — complementary dynamic';
        }

        return $highlights;
    }

    // =========================================================================
    // MATCH LABEL
    // =========================================================================

    private function buildMatchLabel(int $score): string
    {
        return match(true) {
            $score >= 90 => 'Perfect Match',
            $score >= 80 => 'Excellent Match',
            $score >= 70 => 'Strong Match',
            $score >= 60 => 'Good Match',
            default      => 'Potential Match',
        };
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getTagsByType(User $user, string $type): array
    {
        if (!$user->relationLoaded('tags')) return [];
        return $user->tags->where('type', $type)->pluck('name')->toArray();
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

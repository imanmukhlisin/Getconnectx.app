<?php

namespace App\Services\Discovery;

use App\Models\User;

class MatchmakingScoringService
{
    /**
     * Compute the matchmaking score using SAW (Simple Additive Weighting) + Profile Matching.
     * Returns an array with 'score' (0-100) and 'label' (e.g. "Perfect Match").
     */
    public function computeScore(User $authUser, User $targetUser, string $mode = 'finding_cofounder'): array
    {
        $isPro = $authUser->is_pro;

        // 1. Gather all individual scores (converted to 1.0 - 5.0 scale)
        $scores = [
            'modeFit'       => $this->computeModeFit($authUser, $targetUser, $mode) * 5,
            'skillComp'     => $this->computeSkillComplementarity($authUser, $targetUser) * 5,
            'industryFit'   => $this->computeIndustryFit($authUser, $targetUser) * 5,
            'commitmentFit' => $this->computeCommitmentFit($authUser, $targetUser) * 5,
        ];

        if ($isPro) {
            $scores['experienceFit'] = $this->computeExperienceFit($authUser, $targetUser) * 5;
            $scores['stageFit']      = $this->computeStageFit($authUser, $targetUser) * 5;
            $scores['locationScore'] = $this->computeLocationScore($authUser, $targetUser) * 5;
            $scores['leadershipFit'] = $this->computeLeadershipFit($authUser, $targetUser) * 5;
            $scores['languageFit']   = $this->computeLanguageFit($authUser, $targetUser) * 5;
            $scores['educationFit']  = $this->computeEducationFit($authUser, $targetUser) * 5;
        }

        // 2. Define Core Factors (CF) and Secondary Factors (SF)
        if ($isPro) {
            $cfKeys = ['modeFit', 'skillComp', 'industryFit', 'experienceFit', 'stageFit'];
            $sfKeys = ['commitmentFit', 'locationScore', 'leadershipFit', 'languageFit', 'educationFit'];
        } else {
            $cfKeys = ['modeFit', 'skillComp'];
            $sfKeys = ['industryFit', 'commitmentFit'];
        }

        // 3. Calculate NCF (Average Core Factor) and NSF (Average Secondary Factor)
        $ncf = 0;
        if (count($cfKeys) > 0) {
            $sum = 0;
            foreach ($cfKeys as $k) {
                $sum += ($scores[$k] ?? 0);
            }
            $ncf = $sum / count($cfKeys);
        }

        $nsf = 0;
        if (count($sfKeys) > 0) {
            $sum = 0;
            foreach ($sfKeys as $k) {
                $sum += ($scores[$k] ?? 0);
            }
            $nsf = $sum / count($sfKeys);
        }

        // 4. Calculate Final SAW Score (60% CF + 40% SF)
        $totalScoreValue = ($ncf * 0.6) + ($nsf * 0.4);

        // 5. Convert to 0-100 scale
        $finalScore = (int) round(($totalScoreValue / 5.0) * 100);
        $finalScore = min(100, max(0, $finalScore));

        return [
            'score'      => $finalScore,
            'label'      => $this->buildMatchLabel($finalScore),
            'highlights' => $this->buildMatchHighlights($authUser, $targetUser, $scores),
            'analysis'   => $this->buildMatchAnalysis($authUser, $targetUser, $scores, $finalScore),
        ];
    }


    /**
     * Build a list of highlights (reasons why they match).
     */
    private function buildMatchHighlights(User $a, User $b, array $scores): array
    {
        $highlights = [];

        // 1. Shared Industries
        $aInd = $this->getUserTagGroupIds($a, 'industry');
        $bInd = $this->getUserTagGroupIds($b, 'industry');
        $sharedInd = array_intersect($aInd, $bInd);
        if (!empty($sharedInd)) {
            $highlights[] = 'Shared interest in ' . implode(', ', $sharedInd);
        }

        // 2. Skill Complementarity
        if (($scores['skillComp'] ?? 0) >= 4.5) {
            $highlights[] = 'Complementary skill sets (Hacker/Hustler dynamic)';
        }

        // 3. Shared Location
        if ($a->city && $a->city === $b->city) {
            $highlights[] = 'Both based in ' . $a->city;
        }

        // 4. Shared Languages
        $aLangs = is_array($a->languages) ? $a->languages : [];
        $bLangs = is_array($b->languages) ? $b->languages : [];
        $sharedLangs = array_intersect($aLangs, $bLangs);
        if (!empty($sharedLangs)) {
            $highlights[] = 'Both speak ' . implode(', ', $sharedLangs);
        }

        return $highlights;
    }

    /**
     * Map a GAP (difference) to a weight value (1-5).
     * Used for more granular Profile Matching.
     */
    private function mapGapToWeight(float $gap): float
    {
        return match (true) {
            $gap == 0   => 5.0, // Ideal
            $gap == 1   => 4.5, // Surplus 1
            $gap == -1  => 4.0, // Deficit 1
            $gap == 2   => 3.5, // Surplus 2
            $gap == -2  => 3.0, // Deficit 2
            $gap == 3   => 2.5, // Surplus 3
            $gap == -3  => 2.0, // Deficit 3
            $gap == 4   => 1.5, // Surplus 4
            $gap >= -4  => 1.0, // Deficit 4 or more
            default     => 1.0,
        };
    }

    /**
     * Match Label mapping based on product spec.
     */
    private function buildMatchLabel(int $score): string
    {
        if ($score >= 90) return "Perfect Match";
        if ($score >= 80) return "Excellent Match";
        if ($score >= 70) return "Strong Match";
        if ($score >= 60) return "Good Match";
        return "Potential Match";
    }

    // ─── Core Computations (Normalized to 0.0 - 1.0) ──────────────────────────

    private function computeModeFit(User $a, User $b, string $mode): float
    {
        return 1.0; 
    }

    private function computeSkillComplementarity(User $a, User $b): float
    {
        // 1. Try from Tags first
        $aTags = $this->getUserTagGroupIds($a, 'role');
        $bTags = $this->getUserTagGroupIds($b, 'role');
        
        // 2. Fallback to builder model columns if tags empty
        if (empty($aTags)) {
            $builder = $a->builder;
            if ($builder) {
                $aTags = [$builder->role_category];
            } else {
                // Last fallback to legacy columns
                $aTags = is_array($a->cofounder_type) ? $a->cofounder_type : ($a->cofounder_type ? [$a->cofounder_type] : []);
            }
        }
        if (empty($bTags)) {
            $builder = $b->builder;
            if ($builder) {
                $bTags = [$builder->role_category];
            } else {
                $bTags = is_array($b->cofounder_type) ? $b->cofounder_type : ($b->cofounder_type ? [$b->cofounder_type] : []);
            }
        }

        if (empty($aTags) || empty($bTags)) return 0.3;
        
        $intersection = array_intersect($aTags, $bTags);
        // Hacker/Hustler dynamic: if they have DIFFERENT roles, it's a better match
        return count($intersection) === 0 ? 1.0 : 0.4;
    }

    private function computeIndustryFit(User $a, User $b): float
    {
        // 1. Try from Tags
        $aInd = $this->getUserTagGroupIds($a, 'industry');
        $bInd = $this->getUserTagGroupIds($b, 'industry');

        // 2. Fallback to primary industry column
        if (empty($aInd) && $a->industry) $aInd = [$a->industry];
        if (empty($bInd) && $b->industry) $bInd = [$b->industry];

        if (empty($aInd) || empty($bInd)) return 0.3;

        $intersection = count(array_intersect($aInd, $bInd));
        $union = count(array_unique(array_merge($aInd, $bInd)));
        
        return ($intersection / $union);
    }

    private function computeCommitmentFit(User $a, User $b): float
    {
        // 1. Try from Tags
        $aCom = $this->getUserTagGroupIds($a, 'commitment');
        $bCom = $this->getUserTagGroupIds($b, 'commitment');
        
        // 2. Fallback to builder model
        if (empty($aCom)) {
            $builder = $a->builder;
            if ($builder && $builder->commitment_level) {
                $aCom = [$builder->commitment_level];
            } elseif ($a->commitment_level) {
                $aCom = [$a->commitment_level];
            }
        }
        if (empty($bCom)) {
            $builder = $b->builder;
            if ($builder && $builder->commitment_level) {
                $bCom = [$builder->commitment_level];
            } elseif ($b->commitment_level) {
                $bCom = [$b->commitment_level];
            }
        }

        if (empty($aCom) || empty($bCom)) return 0.3;

        $intersect = count(array_intersect($aCom, $bCom));
        return $intersect > 0 ? 1.0 : 0.3;
    }

    private function computeStageFit(User $a, User $b): float
    {
        return 0.8; 
    }

    private function computeLocationScore(User $a, User $b): float
    {
        $lScore = ($a->city && $a->city === $b->city) ? 1.0 : (($a->country && $a->country === $b->country) ? 0.8 : 0.3);
        $aRemote = $a->remote_ready ?? false;
        $bRemote = $b->remote_ready ?? false;
        $rScore = ($aRemote && $bRemote) ? 1.0 : (($aRemote || $bRemote) ? 0.5 : 0.0);
        $relScore = 0.5;

        return (($lScore * 5) + ($rScore * 3) + ($relScore * 2)) / 10;
    }

    private function computeExperienceFit(User $a, User $b): float
    {
        return 0.7; 
    }

    private function computeLeadershipFit(User $a, User $b): float
    {
        return 0.75;
    }

    private function computeLanguageFit(User $a, User $b): float
    {
        $aLangs = is_array($a->languages) ? $a->languages : [];
        $bLangs = is_array($b->languages) ? $b->languages : [];
        
        if (empty($aLangs) || empty($bLangs)) return 0.3;
        
        $shared = count(array_intersect($aLangs, $bLangs));
        return $shared > 0 ? 1.0 : 0.3;
    }

    private function computeEducationFit(User $a, User $b): float
    {
        $aEdu = is_array($a->education) ? $a->education : [];
        $bEdu = is_array($b->education) ? $b->education : [];
        
        if (empty($aEdu) || empty($bEdu)) return 0.3;

        return (count(array_intersect($aEdu, $bEdu)) > 0) ? 1.0 : 0.7;
    }

    /**
     * Build full analysis object for Team Fit screen (CON-51)
     */
    private function buildMatchAnalysis(User $a, User $b, array $scores, int $finalScore): array
    {
        // 1. Skill Complementarity
        $aRoles = $this->getProfileRoles($a);
        $bRoles = $this->getProfileRoles($b);
        
        $skillSummary = "Your skill sets complement each other well.";
        if ($scores['skillComp'] >= 4.5) {
            $skillSummary = "Strong Hacker/Hustler dynamic detected. Your backgrounds cover both technical and business leadership.";
        }

        // 2. Vision/Interests
        $aInd = $this->getUserTagGroupNames($a, 'industry');
        if (empty($aInd) && $a->industry) $aInd = [$a->industry];
        
        $bInd = $this->getUserTagGroupNames($b, 'industry');
        if (empty($bInd) && $b->industry) $bInd = [$b->industry];
        
        $sharedInterests = array_values(array_intersect($aInd, $bInd));

        // 3. Commitment
        $aCommit = $a->commitment_level ?? 'Not specified';
        $bCommit = $b->commitment_level ?? 'Not specified';

        return [
            'compatibilityScore' => $finalScore,
            'label' => $this->buildMatchLabel($finalScore),
            'subtitle' => "You & " . $b->name,
            'skillComplementarity' => [
                'title' => 'Skill Complementarity',
                'youBring' => $aRoles,
                'theyBring' => $bRoles,
                'summary' => $skillSummary
            ],
            'startupVisionAlignment' => [
                'title' => 'Startup Vision Alignment',
                'sharedInterests' => $sharedInterests
            ],
            'commitmentCompatibility' => [
                'title' => 'Commitment Compatibility',
                'you' => ucwords(str_replace('_', ' ', $aCommit)),
                'them' => ucwords(str_replace('_', ' ', $bCommit))
            ],
            'suggestedRoles' => [
                'title' => 'Suggested Roles',
                'you' => $a->position ?? 'CEO/Founder',
                'them' => $b->position ?? 'CTO/Founder'
            ]
        ];
    }

    private function getProfileRoles(User $user): array
    {
        $tags = $this->getUserTagGroupIds($user, 'role');
        if (!empty($tags)) return $tags;

        $builder = $user->builder;
        if ($builder && $builder->role_category) return [$builder->role_category];

        return is_array($user->cofounder_type) ? $user->cofounder_type : ($user->cofounder_type ? [$user->cofounder_type] : ['Builder']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getUserTagGroupIds(User $user, string $type): array
    {
        if (!$user->relationLoaded('tags')) {
            return [];
        }
        return $user->tags->where('type', $type)->pluck('id')->toArray();
    }

    private function getUserTagGroupNames(User $user, string $type): array
    {
        if (!$user->relationLoaded('tags')) {
            return [];
        }
        return $user->tags->where('type', $type)->pluck('name')->toArray();
    }
}

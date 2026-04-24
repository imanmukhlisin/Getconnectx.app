<?php

namespace App\Services\Discovery;

use App\Models\User;

class MatchmakingScoringService
{
    /**
     * Compute the matchmaking score between two users.
     * Returns an array with 'score' (0-100) and 'label' (e.g. "Perfect Match").
     */
    public function computeScore(User $authUser, User $targetUser, string $mode = 'finding_cofounder'): array
    {
        // Compute base variables (0.0 to 1.0)
        $modeFit = $this->computeModeFit($authUser, $targetUser, $mode);
        $skillComp = $this->computeSkillComplementarity($authUser, $targetUser);
        $industryFit = $this->computeIndustryFit($authUser, $targetUser);
        $commitmentFit = $this->computeCommitmentFit($authUser, $targetUser);

        if ($authUser->is_pro) {
            // Premium Weighting
            $score = 
                ($modeFit * 21.7) +
                ($skillComp * 18.6) +
                ($industryFit * 12.4) +
                ($commitmentFit * 9.3) +
                ($this->computeStageFit($authUser, $targetUser) * 10) +
                ($this->computeLocationScore($authUser, $targetUser) * 8) +
                ($this->computeExperienceFit($authUser, $targetUser) * 5) +
                ($this->computeLeadershipFit($authUser, $targetUser) * 5) +
                ($this->computeLanguageFit($authUser, $targetUser) * 5) +
                ($this->computeEducationFit($authUser, $targetUser) * 5);
        } else {
            // Free Weighting
            $score = 
                ($modeFit * 35) +
                ($skillComp * 30) +
                ($industryFit * 20) +
                ($commitmentFit * 15);
        }

        // Ensure max theoretical limit is exactly 100
        $finalScore = min(100, max(0, (int) round($score)));

        return [
            'score' => $finalScore,
            'label' => $this->buildMatchLabel($finalScore),
        ];
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

    // ─── Free Core Computations ──────────────────────────────────────────────

    private function computeModeFit(User $a, User $b, string $mode): float
    {
        // Example logic: Founder seeking Co-Founder gives max points
        // In real life this depends on mode and their self-identified intentions.
        // For 'finding_cofounder', both being aligned gets 1.0.
        return 1.0; 
    }

    private function computeSkillComplementarity(User $a, User $b): float
    {
        // Hacker x Hustler complementarity
        $aTags = $this->getUserTagGroupIds($a, 'role');
        $bTags = $this->getUserTagGroupIds($b, 'role');
        
        // Simplified Logic: if they have different primary roles (Hacker vs Hustler), 1.0
        // If same (Hacker x Hacker), 0.3
        $intersection = array_intersect($aTags, $bTags);
        if (empty($aTags) || empty($bTags)) return 0.5; // neutral if unmapped
        
        return count($intersection) === 0 ? 1.0 : 0.4;
    }

    private function computeIndustryFit(User $a, User $b): float
    {
        // Jaccard Similarity on industry tags
        $aInd = $this->getUserTagGroupIds($a, 'industry');
        $bInd = $this->getUserTagGroupIds($b, 'industry');

        $intersection = count(array_intersect($aInd, $bInd));
        $union = count(array_unique(array_merge($aInd, $bInd)));
        
        return $union === 0 ? 0.5 : ($intersection / $union);
    }

    private function computeCommitmentFit(User $a, User $b): float
    {
        $aCom = $this->getUserTagGroupIds($a, 'commitment');
        $bCom = $this->getUserTagGroupIds($b, 'commitment');
        
        $intersect = count(array_intersect($aCom, $bCom));
        return $intersect > 0 ? 1.0 : 0.5; // Same level = 1.0, diff = 0.5
    }

    // ─── Premium Sub-scores ──────────────────────────────────────────────────

    private function computeStageFit(User $a, User $b): float
    {
        return 0.8; // Stubbed stage fit (Requires startup relationship to be checked)
    }

    private function computeLocationScore(User $a, User $b): float
    {
        // L (Local Fit)
        $lScore = 0.3; // Diff
        if ($a->city && $a->city === $b->city) $lScore = 1.0;
        elseif ($a->country && $a->country === $b->country) $lScore = 0.8;
        
        // R (Remote Fit)
        $aRemote = $a->remote_ready ?? false;
        $bRemote = $b->remote_ready ?? false;
        $rScore = ($aRemote && $bRemote) ? 1.0 : (($aRemote || $bRemote) ? 0.5 : 0.0);

        // Relocate (Rel)
        // For simplicity, let's assume rel willingness is part of work_arrangement
        $relScore = 0.5;

        // Formula: (Lx5) + (Rx3) + (Relx2) => max 10
        return (($lScore * 5) + ($rScore * 3) + ($relScore * 2)) / 10;
    }

    private function computeExperienceFit(User $a, User $b): float
    {
        // Proximity of experience. (Assuming experience is saved in years or similar level tag)
        return 0.7; 
    }

    private function computeLeadershipFit(User $a, User $b): float
    {
        // Complementary leadership styles
        return 0.75;
    }

    private function computeLanguageFit(User $a, User $b): float
    {
        // Shared languages
        $aLangs = is_array($a->languages) ? $a->languages : [];
        $bLangs = is_array($b->languages) ? $b->languages : [];
        
        if (empty($aLangs) && empty($bLangs)) return 1.0;
        
        $shared = count(array_intersect($aLangs, $bLangs));
        return $shared > 0 ? 1.0 : 0.0;
    }

    private function computeEducationFit(User $a, User $b): float
    {
        $aEdu = is_array($a->education) ? $a->education : [];
        $bEdu = is_array($b->education) ? $b->education : [];
        // Same education level match
        return (count(array_intersect($aEdu, $bEdu)) > 0) ? 1.0 : 0.7;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getUserTagGroupIds(User $user, string $type): array
    {
        if (!$user->relationLoaded('tags')) {
            // Safe fallback if not eager loaded
            return [];
        }
        return $user->tags->where('type', $type)->pluck('id')->toArray();
    }
}

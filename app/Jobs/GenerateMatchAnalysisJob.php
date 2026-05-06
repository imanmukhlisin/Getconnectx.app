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

class GenerateMatchAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $matchId;
    protected string $userAId;
    protected string $userBId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $matchId, string $userAId, string $userBId)
    {
        $this->matchId = $matchId;
        $this->userAId = $userAId;
        $this->userBId = $userBId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $userA = User::with(['tags', 'preference'])->find($this->userAId);
        $userB = User::with(['tags', 'preference'])->find($this->userBId);

        if (!$userA || !$userB) return;

        // 1. Skill Complementarity (40%)
        $skillsA = $userA->tags->where('type', 'skill')->pluck('name')->toArray();
        $skillsB = $userB->tags->where('type', 'skill')->pluck('name')->toArray();
        
        $youBring = array_diff($skillsA, $skillsB);
        $theyBring = array_diff($skillsB, $skillsA);
        
        $skillScore = 0;
        if (count($skillsA) > 0 || count($skillsB) > 0) {
            // Basic metric: higher complementarity gives better score
            $totalUniqueSkills = count(array_unique(array_merge($skillsA, $skillsB)));
            $complementaryCount = count($youBring) + count($theyBring);
            $skillScore = ($totalUniqueSkills > 0) ? ($complementaryCount / $totalUniqueSkills) * 100 : 0;
        }

        // 2. Interest Overlap (25%)
        $interestsA = $userA->tags->where('type', 'industry')->pluck('name')->toArray();
        $interestsB = $userB->tags->where('type', 'industry')->pluck('name')->toArray();
        
        $overlapInterests = array_intersect($interestsA, $interestsB);
        
        $interestScore = 0;
        if (count($interestsA) > 0 && count($interestsB) > 0) {
            $maxPossibleOverlap = min(count($interestsA), count($interestsB));
            $interestScore = ($maxPossibleOverlap > 0) ? (count($overlapInterests) / $maxPossibleOverlap) * 100 : 0;
        }

        // 3. Role Complementarity (20%)
        // Example: Founder matched with Builder is excellent (100)
        // Founder vs Founder / Builder vs Builder might be lower (e.g. 50)
        $roleScore = 50; 
        if ($userA->role_category !== $userB->role_category && $userA->role_category && $userB->role_category) {
            $roleScore = 100;
        }

        // 4. Commitment Compatibility (10%)
        $commitmentScore = 50;
        if ($userA->commitment_level === $userB->commitment_level && $userA->commitment_level) {
            $commitmentScore = 100;
        }

        // 5. Work Style Match (5%)
        $workStyleScore = 0;
        $wsA = $userA->preference?->work_style ?? [];
        $wsB = $userB->preference?->work_style ?? [];
        
        $wsIntersect = array_intersect_assoc($wsA, $wsB);
        if (count($wsA) > 0 && count($wsB) > 0) {
            $workStyleScore = (count($wsIntersect) / max(count($wsA), count($wsB))) * 100;
        } else {
            $workStyleScore = 50; // default medium if no data
        }

        // ============ CALCULATE TOTAL SCORE =============
        $totalScore = round(
            ($skillScore * 0.40) +
            ($interestScore * 0.25) +
            ($roleScore * 0.20) +
            ($commitmentScore * 0.10) +
            ($workStyleScore * 0.05)
        );

        $label = $totalScore >= 80 ? 'Excellent Fit' : ($totalScore >= 60 ? 'Good Fit' : 'Potential Fit');

        // ============ SAVE TO DB =============
        
        // Save Score
        MatchScore::create([
            'match_id' => $this->matchId,
            'score' => $totalScore,
            'label' => $label,
            'insight' => count($overlapInterests) > 0 ? "You both are interested in " . implode(', ', $overlapInterests) : "You have complementary skills."
        ]);

        // Build JSON UI Ready
        $analysisJson = [
            'compatibilityScore' => $totalScore,
            'skillComplementarity' => [
                'youBring' => array_values($youBring),
                'theyBring' => array_values($theyBring),
            ],
            'startupVisionAlignment' => [
                'overlappingInterests' => array_values($overlapInterests),
            ],
            'commitmentCompatibility' => [
                'userA' => $userA->commitment_level,
                'userB' => $userB->commitment_level,
                'isAligned' => $userA->commitment_level === $userB->commitment_level
            ],
            'workStyle' => [
                'sharedTraits' => $wsIntersect
            ],
            'potentialRisks' => [],
            'suggestedRoles' => [
                $userA->name => $userA->role_category,
                $userB->name => $userB->role_category,
            ],
            'suggestedTeamStructure' => "Standard co-founder dynamic."
        ];

        // Save Analysis
        MatchAnalysis::create([
            'match_id' => $this->matchId,
            'analysis_json' => $analysisJson,
            'generated_at' => now(),
        ]);
    }
}

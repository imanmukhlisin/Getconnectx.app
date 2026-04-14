<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\UserMatch;
use App\Services\MatchmakingService;
use Illuminate\Http\Request;

class MatchmakingController extends Controller
{
    protected MatchmakingService $matchmakingService;

    public function __construct(MatchmakingService $matchmakingService)
    {
        $this->matchmakingService = $matchmakingService;
    }

    /**
     * Get list of matches for authenticated user
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $matches = UserMatch::with(['scores'])
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('matched_user_id', $userId);
            })
            ->where('status', 'active')
            ->orderBy('matched_at', 'desc')
            ->paginate(15);

        // Map to standard output
        $matches->getCollection()->transform(function ($match) use ($userId) {
            $otherUserId = $match->user_id === $userId ? $match->matched_user_id : $match->user_id;

            return [
                'match_id' => $match->id,
                'conversation_id' => $match->conversation_id,
                'matched_at' => $match->matched_at,
                'partner_id' => $otherUserId,
                // Include eager loaded score (if available)
                'fitSummary' => $match->scores->first() ?? null,
            ];
        });

        // Also figure out who likes you
        $likesYou = Like::where('to_user_id', $userId)
                        ->where('is_mutual', false)
                        ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'matches' => $matches,
                'likesYouCount' => $likesYou
            ]
        ]);
    }

    /**
     * Send a swipe / like
     */
    public function like(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|uuid|exists:users,id',
        ]);

        try {
            $result = $this->matchmakingService->likeUser(
                $request->user()->id, 
                $request->input('to_user_id')
            );

            return response()->json([
                'status' => 'success',
                'message' => $result['is_mutual'] ? 'It\'s a Match!' : 'Like sent.',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get Match Analysis Detail
     */
    public function analysis(Request $request, $matchId)
    {
        $userId = $request->user()->id;

        $match = UserMatch::with('analysis')->findOrFail($matchId);

        // Security Validation Ownership
        if ($match->user_id !== $userId && $match->matched_user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized access to this match.'], 403);
        }

        if (!$match->analysis) {
            return response()->json(['message' => 'Analysis is still generating.'], 404);
        }

        // Return pure JSON structure as requested (UI-Ready)
        return response()->json([
            'status' => 'success',
            'data' => collect($match->analysis->analysis_json)->merge([
                'generated_at' => $match->analysis->generated_at
            ])
        ]);
    }
}

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
        $limit  = (int) $request->query('limit', 10);
        $page   = (int) $request->query('page', 1);

        $paginator = UserMatch::with(['scores', 'user', 'matchedUser'])
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('matched_user_id', $userId);
            })
            ->where('status', 'active')
            ->orderBy('matched_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        // Map to standard output
        $items = $paginator->getCollection()->map(function ($match) use ($userId) {
            $otherUser = $match->user_id === $userId ? $match->matchedUser : $match->user;
            
            // Calculate expiry dynamically
            $expiresAt = $match->expires_at ?? $match->matched_at->copy()->addDays(7);
            $expiresInDays = max(0, (int) now()->diffInDays($expiresAt, false));

            $fitScore = $match->scores->first();

            return [
                'matchId'        => $match->id,
                'status'         => $match->status,
                'matchedAt'      => $match->matched_at,
                'expiresAt'      => $expiresAt,
                'expiresInDays'  => $expiresInDays,
                'hasMessaged'    => false, // Mock
                'isOnline'       => false, // Mock
                'conversationId' => $match->conversation_id,
                'user' => [
                    'userId'   => $otherUser->id,
                    'name'     => $otherUser->name,
                    'photoUrl' => $otherUser->avatar_url,
                    'headline' => $otherUser->position,
                    'location' => 'Indonesia',
                ],
                'fitSummary' => $fitScore ? [
                    'score'   => $fitScore->overall_score ?? 0,
                    'label'   => $fitScore->grade_label ?? 'Good Match',
                    'insight' => $fitScore->summary_insight ?? 'Great team alignment.'
                ] : null,
                'actions' => [
                    'canChat'         => true,
                    'canViewAnalysis' => true
                ]
            ];
        });

        // Resolve "likesYou" data
        $totalNewLikes = Like::connects()->where('to_user_id', $userId)->where('is_mutual', false)->count();
        $topLikes      = Like::with('fromUser')
                            ->connects()
                            ->where('to_user_id', $userId)
                            ->where('is_mutual', false)
                            ->latest()
                            ->take(3)
                            ->get()
                            ->map(function ($like) {
                                return [
                                    'likeId'  => $like->id,
                                    'likedAt' => $like->created_at,
                                    'user'    => [
                                        'userId'   => $like->fromUser->id,
                                        'name'     => $like->fromUser->name,
                                        'photoUrl' => $like->fromUser->avatar_url,
                                        'headline' => $like->fromUser->position,
                                        'location' => 'Indonesia',
                                    ]
                                ];
                            });

        return response()->json([
            'success' => true,
            'message' => 'Matches fetched successfully',
            'data'    => [
                'likesYou' => [
                    'locked'   => true,
                    'items'    => $topLikes,
                    'totalNew' => $totalNewLikes,
                ],
                'items'   => $items,
                'total'   => $paginator->total(),
                'page'    => $paginator->currentPage(),
                'limit'   => $paginator->perPage(),
                'hasMore' => $paginator->hasMorePages()
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

        $match = UserMatch::with(['analysis', 'user', 'matchedUser'])->findOrFail($matchId);

        // Security Validation Ownership
        if ($match->user_id !== $userId && $match->matched_user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this match.'], 403);
        }

        if (!$match->analysis) {
            return response()->json(['success' => false, 'message' => 'Analysis is still generating.'], 404);
        }

        $otherUser = $match->user_id === $userId ? $match->matchedUser : $match->user;

        // Return pure JSON structure as requested (UI-Ready)
        return response()->json([
            'success' => true,
            'message' => 'Match analysis fetched successfully',
            'data'    => [
                'matchId'        => $match->id,
                'conversationId' => $match->conversation_id,
                'status'         => $match->status,
                'generatedAt'    => $match->analysis->generated_at ?? $match->analysis->created_at,
                'user'           => [
                    'userId'   => $otherUser->id,
                    'name'     => $otherUser->name,
                    'photoUrl' => $otherUser->avatar_url,
                    'headline' => $otherUser->position,
                    'location' => 'Indonesia',
                ],
                'analysis' => collect($match->analysis->analysis_json)->toArray()
            ]
        ]);
    }
}

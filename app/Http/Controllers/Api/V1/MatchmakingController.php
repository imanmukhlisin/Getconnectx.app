<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\UserMatch;
use App\Services\MatchmakingService;
use App\Services\ViewerContextService;
use Illuminate\Http\Request;

class MatchmakingController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService,
        protected ViewerContextService $viewerContextService
    ) {}

    /**
     * Get list of matches for authenticated user
     */
    public function index(Request $request)
    {
        $authUser = $request->user();
        $userId   = $authUser->id;
        $limit    = (int) $request->query('limit', 10);
        $page     = (int) $request->query('page', 1);

        // CON-71 + CON-72: Resolve and validate viewer_context
        $ctxResult = $this->viewerContextService->resolve($authUser, $request->query('viewer_context'));
        if ($ctxResult instanceof \Illuminate\Http\JsonResponse) {
            return $ctxResult; // 409 DISCOVERY_ONBOARDING_REQUIRED
        }
        $viewerContext = $ctxResult['context'];

        $paginator = UserMatch::with(['scores', 'user', 'matchedUser'])
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('matched_user_id', $userId);
            })
            ->where(function($q) use ($viewerContext) {
                if ($viewerContext === 'talent') {
                    $q->where('viewer_context', 'talent')
                      ->orWhereNull('viewer_context'); // Legacy fallback
                } else {
                    $q->where('viewer_context', 'startup');
                }
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
                    'score'   => $fitScore->score ?? 0,
                    'label'   => $fitScore->label ?? 'Good Match',
                    'insight' => $fitScore->insight ?? 'Great team alignment.'
                ] : null,
                'actions' => [
                    'canChat'         => true,
                    'canViewAnalysis' => true
                ]
            ];
        });

        // Resolve "likesYou" context mapping
        // Startup mode expects likes sent from Talent mode ('talent')
        // Talent mode expects likes sent from Startup mode ('startup') or co-founder swipes ('talent')
        $incomingContexts = $viewerContext === 'startup' ? ['talent'] : ['startup', 'talent'];

        $totalNewLikes = Like::connects()
            ->where('to_user_id', $userId)
            ->where(function($q) use ($incomingContexts) {
                $q->whereIn('viewer_context', $incomingContexts);
                if (in_array('talent', $incomingContexts)) {
                    $q->orWhereNull('viewer_context'); // Legacy fallback
                }
            })
            ->where('is_mutual', false)
            ->count();

        $topLikes      = Like::with('fromUser')
                            ->connects()
                            ->where('to_user_id', $userId)
                            ->where(function($q) use ($incomingContexts) {
                                $q->whereIn('viewer_context', $incomingContexts);
                                if (in_array('talent', $incomingContexts)) {
                                    $q->orWhereNull('viewer_context'); // Legacy fallback
                                }
                            })
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
                'viewer_context' => $viewerContext,
                'likesYou' => [
                    'locked'   => !$request->user()->is_pro,
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
     * Get paginated list of users who liked the authenticated user (See Who Likes You)
     */
    public function likesYouList(Request $request)
    {
        $authUser = $request->user();
        $userId   = $authUser->id;
        $limit    = (int) $request->query('limit', 10);
        $page     = (int) $request->query('page', 1);

        // Resolve viewer_context to separate incoming likes
        $ctxResult = $this->viewerContextService->resolve($authUser, $request->query('viewer_context'));
        if ($ctxResult instanceof \Illuminate\Http\JsonResponse) {
            return $ctxResult; // 409 DISCOVERY_ONBOARDING_REQUIRED
        }
        $viewerContext = $ctxResult['context'];
        $incomingContexts = $viewerContext === 'startup' ? ['talent'] : ['startup', 'talent'];

        $isLocked = !$authUser->is_pro;

        $paginator = Like::with('fromUser')
            ->connects()
            ->where('to_user_id', $userId)
            ->where(function($q) use ($incomingContexts) {
                $q->whereIn('viewer_context', $incomingContexts);
                if (in_array('talent', $incomingContexts)) {
                    $q->orWhereNull('viewer_context'); // Legacy fallback
                }
            })
            ->where('is_mutual', false)
            ->latest()
            ->paginate($limit, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($like) {
            $location = 'Location not set';
            if ($like->fromUser->city) {
                $location = trim($like->fromUser->city . ', ' . $like->fromUser->country, ', ');
            }

            return [
                'likeId'  => $like->id,
                'likedAt' => $like->created_at,
                'user'    => [
                    'userId'   => $like->fromUser->id,
                    'name'     => $like->fromUser->name,
                    'photoUrl' => $like->fromUser->avatar_url,
                    'headline' => $like->fromUser->position,
                    'location' => $location,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Likes fetched successfully',
            'data'    => [
                'locked'  => $isLocked,
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
        $authUser = $request->user();
        $userId   = $authUser->id;

        // CON-72: Resolve viewer_context
        $ctxResult = $this->viewerContextService->resolve($authUser, $request->query('viewer_context'));
        if ($ctxResult instanceof \Illuminate\Http\JsonResponse) {
            return $ctxResult;
        }
        $viewerContext = $ctxResult['context'];

        $match = UserMatch::with(['analysis', 'user', 'matchedUser'])->findOrFail($matchId);

        // Security Validation Ownership
        if ($match->user_id !== $userId && $match->matched_user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this match.'], 403);
        }

        // ── CON-72 Fallback: If analysis is missing (e.g., queue worker not running), generate it inline ──
        if (!$match->analysis) {
            try {
                $job = new \App\Jobs\GenerateMatchAnalysisJob($match->id, $match->user_id, $match->matched_user_id);
                $job->handle();
                $match->refresh(); // Load newly generated analysis
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to generate match analysis inline: " . $e->getMessage());
            }
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
                'viewer_context'  => $viewerContext,
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

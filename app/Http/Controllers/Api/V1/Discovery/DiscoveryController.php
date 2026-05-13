<?php

namespace App\Http\Controllers\Api\V1\Discovery;

use App\Exceptions\PremiumRequiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DiscoveryCardsRequest;
use App\Models\Like;
use App\Services\Discovery\CardTransformerService;
use App\Services\Discovery\DiscoveryCatalogService;
use App\Services\Discovery\FilterBuilderService;
use App\Services\Discovery\VertexAiService;
use App\Services\SwipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DiscoveryController extends Controller
{
    private const P2P_MODES = ['finding_cofounder', 'building_team'];
    private const P2B_MODES = ['explore_startups', 'joining_startups'];
    private const ALL_MODES = ['finding_cofounder', 'building_team', 'explore_startups', 'joining_startups'];
    private const DEFAULT_MODE = 'finding_cofounder';

    public function __construct(
        private DiscoveryCatalogService $catalogService,
        private FilterBuilderService $filterBuilder,
        private CardTransformerService $cardTransformer,
        private SwipeService $swipeService,
        private VertexAiService $vertexAiService,
    ) {}

    // ═══════════════════════════════════════════════════════════════════
    //  1. GET /api/v1/discovery/filter-options
    // ═══════════════════════════════════════════════════════════════════

    #[OA\Get(
        path: '/api/v1/discovery/filter-options',
        summary: 'Get filter options for discovery',
        description: 'Returns grouped catalogs for industries, skills, roles, and languages scoped by discovery mode.',
        security: [['sanctum' => []]],
        tags: ['Discovery'],
        parameters: [
            new OA\Parameter(name: 'mode', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['finding_cofounder', 'building_team', 'explore_startups', 'joining_startups'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Filter options fetched successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function filterOptions(Request $request): JsonResponse
    {
        $request->validate([
            'mode' => 'required|string|in:' . implode(',', self::ALL_MODES),
        ]);

        $mode = $request->query('mode');
        $data = $this->catalogService->getFilterOptions($mode);

        return response()->json([
            'success' => true,
            'message' => 'Discovery filter options fetched successfully',
            'data'    => array_merge(['mode' => $mode], $data),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  2. POST /api/v1/discovery/cards
    // ═══════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: '/api/v1/discovery/cards',
        summary: 'Fetch discovery card stack',
        description: 'Returns a stack of discovery cards based on provided filters and context mode. Supports polymorphic response: profile cards for person modes and startup cards for startup modes.',
        security: [['sanctum' => []]],
        tags: ['Discovery'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DiscoveryCardsRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Discovery cards fetched successfully'),
            new OA\Response(response: 403, description: 'Premium required'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function cards(DiscoveryCardsRequest $request): JsonResponse
    {
        $authUser = $request->user();
        $validated = $request->validated();

        $mode    = $validated['context']['mode'] ?? self::DEFAULT_MODE;
        $filters = $validated['filters'] ?? [];
        $limit   = $validated['pagination']['limit'] ?? 10;
        $cursor  = $validated['pagination']['cursor'] ?? null;

        // ── Premium validation ────────────────────────────────────────
        try {
            $this->filterBuilder->validatePremiumFilters($authUser, $filters, $mode);
        } catch (PremiumRequiredException $e) {
            return $e->render();
        }

        // ── Branch: P2P (user cards) or P2B (startup cards) ──────────
        $isP2P = in_array($mode, self::P2P_MODES);

        if ($isP2P) {
            $query  = $this->filterBuilder->buildProfileQuery($authUser, $filters, $mode);
            $query->with(['tags', 'credentials', 'builder']); // eager load for card transformation
            $result = $this->filterBuilder->applyCursorPagination($query, $cursor, $limit);

            $items = $result['items']->map(function ($user, $idx) use ($authUser) {
                return $this->cardTransformer->transformProfileCard($user, $idx, null, $authUser);
            })->values()->toArray();
        } else {
            $query  = $this->filterBuilder->buildStartupQuery($authUser, $filters, $mode);
            $query->with('owner'); // eager load founder
            $result = $this->filterBuilder->applyCursorPagination($query, $cursor, $limit);

            $items = $result['items']->map(function ($startup, $idx) use ($authUser) {
                return $this->cardTransformer->transformStartupCard($startup, $idx, null, $authUser);
            })->values()->toArray();
        }

        // Generate AI Insight
        $aiInsight = $this->vertexAiService->generateInsight($authUser, $filters, $mode);

        return response()->json([
            'success' => true,
            'message' => 'Discovery cards fetched successfully',
            'data'    => $items,
            'meta'    => [
                'aiInsight'  => $aiInsight,
                'nextCursor' => $result['nextCursor'],
                'hasMore'    => $result['hasMore'],
            ]
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  3. POST /api/v1/discovery/cards/:targetId/action
    // ═══════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: '/api/v1/discovery/cards/{targetId}/action',
        summary: 'Record a swipe action on a discovery card',
        description: 'Records a swipe action (like, pass, super_like) on a specific discovery target. Returns match status if applicable.',
        security: [['sanctum' => []]],
        tags: ['Discovery'],
        parameters: [
            new OA\Parameter(name: 'targetId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'action', type: 'string', enum: ['like', 'pass', 'super_like']),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Swipe action recorded'),
            new OA\Response(response: 400, description: 'Invalid action'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function swipeAction(Request $request, string $targetId): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:like,pass,super_like',
        ]);

        // Guard: targetId must be a valid UUID — not a card display ID like "card_xxxx"
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $targetId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid targetId. Use profileId (UUID) from the cards response, not the card display id.',
            ], 422);
        }

        $authUser = $request->user();
        $action   = $request->input('action');

        // Premium gate for super_like
        if ($action === 'super_like' && !$authUser->is_pro) {
            return response()->json([
                'success' => false,
                'message' => 'ConnectX Pro is required to use Super Like.',
                'error'   => [
                    'code'    => 'PREMIUM_REQUIRED'
                ]
            ], 403);
        }

        // Detect if targetId is a startup UUID by looking it up in the startups table.
        // FE passes the raw startupId UUID from the card response (no prefix needed).
        $startup = \App\Models\Startup::find($targetId);
        $isStartup = $startup !== null;
        $actualTargetId = $targetId;

        // Resolve: if startup, match is against the owner (founder)
        $resolvedUserId = $actualTargetId;
        if ($isStartup) {
            $resolvedUserId = $startup->owner_id;
        }

        try {
            if ($action === 'pass') {
                // Record skip
                $this->swipeService->skip($authUser->id, $resolvedUserId);

                return response()->json([
                    'success' => true,
                    'message' => 'Swipe action recorded successfully',
                    'data'    => [
                        'id'        => 'card_' . substr(md5($targetId), 0, 6),
                        'targetId'  => $targetId,
                        'profileId' => $isStartup ? null : $resolvedUserId,
                        'startupId' => $isStartup ? $actualTargetId : null,
                        'action'    => $action,
                        'isMatch'   => false,
                        'matchId'   => null,
                    ],
                ]);
            }

            // like or super_like → use connect logic
            $result = $this->swipeService->connect($authUser->id, $resolvedUserId);
            $isMatch = $result['isMatch'] ?? false;

            return response()->json([
                'success' => true,
                'message' => 'Swipe action recorded successfully',
                'data'    => [
                    'id'             => 'card_' . substr(md5($targetId), 0, 6),
                    'targetId'       => $targetId,
                    'profileId'      => $isStartup ? null : $resolvedUserId,
                    'startupId'      => $isStartup ? $actualTargetId : null,
                    'action'         => $action,
                    'isMatch'        => $isMatch,
                    'matchId'        => $result['matchId']        ?? null,
                    'conversationId' => $result['conversationId'] ?? null,
                ],
            ]);


        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  4. POST /api/v1/discovery/swipes/rewind
    // ═══════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: '/api/v1/discovery/swipes/rewind',
        summary: 'Rewind the last swipe action',
        description: 'Restores the most recent swipe for the current user. Premium feature.',
        security: [['sanctum' => []]],
        tags: ['Discovery'],
        responses: [
            new OA\Response(response: 200, description: 'Last swipe rewound'),
            new OA\Response(response: 403, description: 'Premium required'),
            new OA\Response(response: 409, description: 'No swipe available to rewind'),
        ]
    )]
    public function rewind(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $mode     = $request->query('mode'); // e.g. explore_startups

        // 1. Check premium entitlement
        if (!$authUser->is_pro) {
            return response()->json([
                'success' => false,
                'message' => 'ConnectX Pro is required to rewind your last swipe.',
                'error'   => [
                    'code'    => 'DISCOVERY_REWIND_PREMIUM_REQUIRED',
                    'details' => [
                        'requiredEntitlement' => 'connectx_pro'
                    ]
                ]
            ], 403);
        }

        try {
            $rewoundData = $this->swipeService->rewind($authUser->id);

            if (!$rewoundData) {
                return $this->rewindConflictResponse('EMPTY_HISTORY');
            }

            // Restore the card payload to return
            $targetUserId = $rewoundData['targetUserId'];
            $action = $rewoundData['action'];
            
            // Check if target exists
            $targetUser = \App\Models\User::with(['tags', 'startup'])->find($targetUserId);
            
            if (!$targetUser) {
                return $this->rewindConflictResponse('WINDOW_EXPIRED');
            }

            // Transform into card based on mode
            // If mode is startup-related and target has a startup, return startup card
            if (in_array($mode, ['explore_startups', 'joining_startups']) && $targetUser->startup) {
                $card = $this->cardTransformer->transformStartupCard($targetUser->startup, 0, null, $authUser);
                $profileId = null;
                $startupId = $targetUser->startup->id;
            } else {
                $card = $this->cardTransformer->transformProfileCard($targetUser, 0, null, $authUser);
                $profileId = $targetUserId;
                $startupId = null;
            }

            return response()->json([
                'success' => true,
                'message' => 'Last swipe rewound.',
                'data'    => [
                    'profileId'     => $profileId,
                    'startupId'     => $startupId,
                    'rewoundAction' => $action,
                    'card'          => $card
                ]
            ]);

        } catch (\Exception $e) {
            if ($e->getMessage() === 'ALREADY_MATCHED') {
                return $this->rewindConflictResponse('ALREADY_REWOUND'); // or ALREADY_MATCHED
            }

            return response()->json(['success' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    private function rewindConflictResponse(string $reason): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'No swipe is available to rewind right now.',
            'error'   => [
                'code'    => 'DISCOVERY_REWIND_NOT_AVAILABLE',
                'details' => [
                    'profileId'     => null,
                    'rewoundAction' => null,
                    'reason'        => $reason
                ]
            ]
        ], 409);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  5. GET /api/v1/discovery/who-liked-me
    // ═══════════════════════════════════════════════════════════════════

    #[OA\Get(
        path: '/api/v1/discovery/who-liked-me',
        summary: 'Get users who liked the current user',
        description: 'Returns a list of users who have swiped right on the current user. Premium feature.',
        security: [['sanctum' => []]],
        tags: ['Discovery'],
        responses: [
            new OA\Response(response: 200, description: 'List fetched successfully'),
            new OA\Response(response: 403, description: 'Premium required'),
        ]
    )]
    public function whoLikedMe(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->is_pro) {
            return response()->json([
                'success' => false,
                'message' => 'ConnectX Pro is required to see who liked you.',
                'error'   => [
                    'code'    => 'PREMIUM_REQUIRED'
                ]
            ], 403);
        }

        // 1. Get users who liked me
        $likedMeIds = Like::where('to_user_id', $authUser->id)
            ->where('type', 'like')
            ->pluck('from_user_id')
            ->toArray();

        // 2. Exclude users I have already swiped or matched with
        $alreadySwipedIds = Like::where('from_user_id', $authUser->id)
            ->pluck('to_user_id')
            ->toArray();

        $matchedIds = \App\Models\UserMatch::where(function ($q) use ($authUser) {
            $q->where('user_id', $authUser->id)->orWhere('matched_user_id', $authUser->id);
        })->where('status', 'active')
          ->get()
          ->flatMap(fn($m) => [$m->user_id, $m->matched_user_id])
          ->unique()
          ->toArray();

        $excludeIds = array_unique(array_merge([$authUser->id], $alreadySwipedIds, $matchedIds));
        $filteredIds = array_diff($likedMeIds, $excludeIds);

        // 3. Transform to cards
        $users = \App\Models\User::whereIn('id', $filteredIds)
            ->with(['tags', 'credentials'])
            ->get();

        $items = $users->map(function ($user, $idx) use ($authUser) {
            return $this->cardTransformer->transformProfileCard($user, $idx, null, $authUser);
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Who liked you fetched successfully',
            'data'    => [
                'items' => $items
            ]
        ]);
    }
}

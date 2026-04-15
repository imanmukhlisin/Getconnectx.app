<?php

namespace App\Http\Controllers\Api\V1\Discovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedRequest;
use App\Services\FeedService;
use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/feed',
    summary: 'Get discovery feed',
    description: 'Returns a paginated, compatibility-scored list of users for the authenticated user to discover. Excludes already-swiped, already-matched, and self entries. Results are cached for 5 minutes.',
    security: [['sanctum' => []]],
    tags: ['Discovery'],
    parameters: [
        new OA\Parameter(name: 'page',         in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
        new OA\Parameter(name: 'industry',     in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'fintech')),
        new OA\Parameter(name: 'role',         in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['founder', 'co-founder', 'builder', 'engineer', 'designer', 'marketer', 'investor', 'advisor', 'operator'])),
        new OA\Parameter(name: 'availability', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['full-time', 'part-time', 'weekends', 'flexible'])),
        new OA\Parameter(name: 'stage',        in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['idea', 'mvp', 'early-traction', 'growth', 'scaling'])),
        new OA\Parameter(name: 'radius',       in: 'query', required: false, description: 'Radius in km', schema: new OA\Schema(type: 'number', format: 'float', minimum: 1, maximum: 500, default: 50)),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Feed fetched successfully',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Feed fetched successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/FeedItem')),
                    new OA\Property(property: 'total', type: 'integer', example: 120),
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'limit', type: 'integer', example: 10),
                    new OA\Property(property: 'hasMore', type: 'boolean', example: true),
                ]),
            ])
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Registration not complete'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Schema(
    schema: 'FeedItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'id',                  type: 'string', format: 'uuid',  example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'name',                type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'username',            type: 'string', example: 'budisan',   nullable: true),
        new OA\Property(property: 'avatar_url',          type: 'string', example: 'https://cdn.example.com/avatars/budi.jpg', nullable: true),
        new OA\Property(property: 'position',            type: 'string', example: 'CTO at StealthAI', nullable: true),
        new OA\Property(property: 'role_category',       type: 'string', example: 'builder',    nullable: true),
        new OA\Property(property: 'startup_stage',       type: 'string', example: 'mvp',        nullable: true),
        new OA\Property(property: 'commitment_level',    type: 'string', example: 'full-time',  nullable: true),
        new OA\Property(property: 'distance_km',         type: 'number', format: 'float', example: 12.5, nullable: true),
        new OA\Property(property: 'compatibility_score', type: 'number', format: 'float', example: 78.5),
    ]
)]
class FeedController extends Controller
{
    public function __construct(private FeedService $feedService) {}

    public function index(FeedRequest $request)
    {
        $authUser = $request->user();
        $filters  = $request->validated();

        $paginator = $this->feedService->getDiscoveryFeed($authUser, $filters);

        $items = $paginator->getCollection()->map(fn($user) => [
            'id'                  => $user->id,
            'name'                => $user->name,
            'username'            => $user->username,
            'avatar_url'          => $user->avatar_url,
            'position'            => $user->position,
            'role_category'       => $user->role_category,
            'startup_stage'       => $user->startup_stage,
            'commitment_level'    => $user->commitment_level,
            'distance_km'         => $user->distance_km ? round((float) $user->distance_km, 1) : null,
            'compatibility_score' => (float) ($user->compatibility_score ?? 0),
        ])->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Feed fetched successfully',
            'data'    => [
                'items'   => $items,
                'total'   => $paginator->total(),
                'page'    => $paginator->currentPage(),
                'limit'   => $paginator->perPage(),
                'hasMore' => $paginator->hasMorePages()
            ]
        ]);
    }
}

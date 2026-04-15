<?php

namespace App\Http\Controllers\Api\V1\Discovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\SwipeRequest;
use App\Services\SwipeService;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SwipeConnectRequest',
    required: ['to_user_id'],
    properties: [
        new OA\Property(property: 'to_user_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000', description: 'UUID of the user to swipe on'),
    ]
)]
#[OA\Post(
    path: '/api/v1/swipe/connect',
    summary: 'Swipe right — connect with a user',
    description: 'Records a connect (swipe-right) action. If the target user has already swiped right on the authenticated user, a mutual match is created along with a conversation and an async match-analysis job is dispatched.',
    security: [['sanctum' => []]],
    tags: ['Discovery'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SwipeConnectRequest')
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Swipe recorded',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',  type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: "It's a Match!"),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'isMatch',        type: 'boolean', example: true),
                    new OA\Property(property: 'matchId',        type: 'string',  example: 'mtc_01HXYZ', nullable: true),
                    new OA\Property(property: 'conversationId', type: 'string',  format: 'uuid', nullable: true),
                ]),
            ])
        ),
        new OA\Response(response: 400, description: 'Business logic error (e.g. self-swipe)'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Registration not complete'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Post(
    path: '/api/v1/swipe/skip',
    summary: 'Swipe left — skip a user',
    description: 'Records a skip (swipe-left) action. The target user will no longer appear in the feed. This action is idempotent.',
    security: [['sanctum' => []]],
    tags: ['Discovery'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SwipeConnectRequest')
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Skip recorded',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status',  type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'User skipped.'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                ]),
            ])
        ),
        new OA\Response(response: 400, description: 'Business logic error'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Registration not complete'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
class SwipeController extends Controller
{
    public function __construct(private SwipeService $swipeService) {}

    public function connect(SwipeRequest $request)
    {
        try {
            $result  = $this->swipeService->connect(
                $request->user()->id,
                $request->validated('to_user_id')
            );

            return response()->json([
                'status'  => 'success',
                'message' => $result['isMatch'] ? "It's a Match! 🎉" : 'Connect sent.',
                'data'    => $result,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function skip(SwipeRequest $request)
    {
        try {
            $result = $this->swipeService->skip(
                $request->user()->id,
                $request->validated('to_user_id')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'User skipped.',
                'data'    => $result,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}

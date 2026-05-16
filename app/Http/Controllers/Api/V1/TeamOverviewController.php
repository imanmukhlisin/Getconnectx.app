<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Startup;
use App\Models\StartupMember;
use App\Models\StartupInvitation;
use App\Models\StartupApplication;
use App\Services\ViewerContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamOverviewController extends Controller
{
    public function __construct(private ViewerContextService $viewerContextService) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        // CON-71 + CON-72: Resolve and validate viewer_context
        $ctxResult = $this->viewerContextService->resolve($user, $request->query('viewer_context'));
        if ($ctxResult instanceof \Illuminate\Http\JsonResponse) {
            return $ctxResult; // 409 DISCOVERY_ONBOARDING_REQUIRED
        }
        $viewerContext = $ctxResult['context'];
        $startup       = $ctxResult['startup'];
        $membership    = $ctxResult['membership'];

        // ─── Talent/Joiner View ───────────────────────────────────────────────────
        if ($viewerContext === 'talent') {
            $invites = StartupInvitation::with('startup', 'sender')
                ->where('recipient_email', $user->email)
                ->where('status', 'pending')
                ->get();

            $applications = StartupApplication::with('startup')
                ->where('user_id', $user->id)
                ->get();

            // If the talent is also a member of a startup, include that context
            $joinedStartupData = null;
            if ($membership) {
                $joinedStartup     = $membership->startup;
                $joinedStartupData = [
                    'id'   => $joinedStartup->id,
                    'name' => $joinedStartup->name,
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'viewer_context' => $viewerContext,
                    'viewerContext'  => [
                        'kind'             => $membership ? 'startup_member' : 'person',
                        'hasActiveStartup' => (bool) $membership,
                        'startupId'        => $membership?->startup_id,
                        'membershipId'     => $membership?->id,
                    ],
                    'joinedStartup'  => $joinedStartupData,
                    'startup'        => null,
                    'teamRoster'     => [
                        'title'   => 'Your Team',
                        'members' => [],
                        'actions' => ['inviteViaLink' => false, 'addFromMatches' => false],
                    ],
                    'myApplications' => [
                        'title' => 'My Applications',
                        'stats' => [
                            'applied'    => $applications->where('status', 'applied')->count(),
                            'inReview'   => $applications->where('status', 'in_review')->count(),
                            'interviews' => $applications->where('status', 'interview')->count(),
                        ],
                        'items'   => $applications->map(fn ($app) => [
                            'id'          => $app->id,
                            'startupId'   => $app->startup_id,
                            'startupName' => $app->startup->name,
                            'role'        => ['id' => $app->role_id, 'label' => ucwords(str_replace('_', ' ', $app->role_id))],
                            'appliedAt'   => $app->created_at->toIso8601String(),
                            'status'      => $app->status,
                            'statusLabel' => ucwords(str_replace('_', ' ', $app->status)),
                        ])->values(),
                        'actions' => ['browseStartups' => true, 'discoverMoreStartups' => true],
                    ],
                    'teamInvites' => [
                        'title' => 'Team Invites',
                        'items' => $invites->map(fn ($inv) => [
                            'id'               => $inv->id,
                            'direction'        => 'received',
                            'startupId'        => $inv->startup_id,
                            'startupName'      => $inv->startup->name ?? 'Unknown Startup',
                            'role'             => ['id' => $inv->role_id, 'label' => ucwords(str_replace('_', ' ', $inv->role_id))],
                            'email'            => $inv->recipient_email,
                            'sentAt'           => $inv->created_at->toIso8601String(),
                            'status'           => $inv->status,
                            'statusLabel'      => ucfirst($inv->status),
                            'availableActions' => ['accept', 'decline'],
                        ])->values(),
                    ],
                    'teamCompleteness' => ['percent' => 0, 'filledRoles' => 0, 'targetRoles' => 0],
                    'requiredRoles'    => [],
                    'missingRoles'     => [],
                ]
            ]);
        }

        // ─── Startup/Founder View ─────────────────────────────────────────────────
        // Auto-create startup on the fly if user is a Founder but doesn't have a startup yet
        if (!$startup) {
            $builder = DB::table('builders')->where('user_id', $user->id)->first();
            if ($builder && strtolower(trim($builder->role_category)) === 'founder') {
                $startup = Startup::create([
                    'owner_id' => $user->id,
                    'name'     => ($user->name ?? 'Founder') . "'s Startup",
                    'industry' => 'technology',
                    'stage'    => 'idea',
                ]);
            }
        }

        // If still no startup after auto-create, fall back to talent view
        if (!$startup) {
            // Use query->set() to modify the Symfony ParameterBag directly
            // so that $request->query('viewer_context') returns 'talent' on recursive call
            $request->query->set('viewer_context', 'talent');
            return $this->index($request);
        }

        $members     = StartupMember::with('user')->where('startup_id', $startup->id)->get();
        $sentInvites = StartupInvitation::where('startup_id', $startup->id)->where('status', 'pending')->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'viewer_context' => $viewerContext,
                'viewerContext'  => [
                    'kind'             => 'startup_owner',
                    'hasActiveStartup' => true,
                    'startupId'        => $startup->id,
                    'membershipId'     => $membership?->id,
                ],
                'startup' => [
                    'id'          => $startup->id,
                    'name'        => $startup->name,
                    'description' => $startup->description,
                    'industry'    => ['id' => $startup->industry, 'label' => ucfirst((string) $startup->industry)],
                    'stage'       => ['id' => $startup->stage, 'label' => ucfirst((string) $startup->stage)],
                ],
                'teamRoster' => [
                    'title'   => 'Your Team',
                    'members' => $members->map(fn ($mem) => [
                        'id'               => $mem->id,
                        'userId'           => $mem->user_id,
                        'avatarUrl'        => $mem->user->avatar_url ?? null,
                        'name'             => $mem->user->name ?? 'Unknown',
                        'role'             => ['id' => $mem->role_id, 'label' => ucwords(str_replace('_', ' ', $mem->role_id))],
                        'equityPercent'    => $mem->equity_percent,
                        'commitment'       => $mem->commitment,
                        'status'           => $mem->status,
                        'statusLabel'      => ucfirst($mem->status),
                        'isCurrentUser'    => $mem->user_id === $user->id,
                        'availableActions' => ['edit_role', 'remove'],
                    ])->values(),
                    'actions' => ['inviteViaLink' => true, 'addFromMatches' => true],
                ],
                'myApplications' => [
                    'title'   => 'My Applications',
                    'stats'   => ['applied' => 0, 'inReview' => 0, 'interviews' => 0],
                    'items'   => [],
                    'actions' => ['browseStartups' => false, 'discoverMoreStartups' => false],
                ],
                'teamInvites' => [
                    'title' => 'Team Invites',
                    'items' => $sentInvites->map(fn ($inv) => [
                        'id'               => $inv->id,
                        'direction'        => 'sent',
                        'startupId'        => $inv->startup_id,
                        'startupName'      => $inv->startup->name ?? '',
                        'role'             => ['id' => $inv->role_id, 'label' => ucwords(str_replace('_', ' ', $inv->role_id))],
                        'email'            => $inv->recipient_email,
                        'sentAt'           => $inv->created_at->toIso8601String(),
                        'status'           => $inv->status,
                        'statusLabel'      => ucfirst($inv->status),
                        'availableActions' => ['revoke'],
                    ])->values(),
                ],
                'teamCompleteness' => [
                    'percent'     => 50,
                    'filledRoles' => $members->count(),
                    'targetRoles' => max($members->count() + 1, 4),
                ],
                'requiredRoles' => [],
                'missingRoles'  => [],
            ]
        ]);
    }
}

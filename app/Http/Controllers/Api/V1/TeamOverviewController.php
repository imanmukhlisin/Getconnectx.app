<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Startup;
use App\Models\StartupMember;
use App\Models\StartupInvitation;
use App\Models\StartupApplication;
use Illuminate\Support\Facades\Auth;

class TeamOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $startup = Startup::where('owner_id', $user->id)->first();
        $membership = StartupMember::where('user_id', $user->id)->first();

        // Auto-create startup on the fly if user is a Founder but doesn't have a startup yet
        if (!$startup && !$membership) {
            $builder = \Illuminate\Support\Facades\DB::table('builders')->where('user_id', $user->id)->first();
            // In GetConnect-X, role_category usually stores 'Founder'
            if ($builder && strtolower(trim($builder->role_category)) === 'founder') {
                $startup = Startup::create([
                    'owner_id' => $user->id,
                    'name' => ($user->name ?? 'Founder') . "'s Startup",
                    'industry' => 'technology',
                    'stage' => 'idea',
                ]);
            }
        }

        // Person / co-founder view if no active startup (and not a founder)
        if (!$startup && !$membership) {
            $invites = StartupInvitation::with('startup', 'sender')
                ->where('recipient_email', $user->email)
                ->where('status', 'pending')
                ->get();

            $applications = StartupApplication::with('startup')
                ->where('user_id', $user->id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'viewerContext' => [
                        'kind' => 'person',
                        'hasActiveStartup' => false,
                        'startupId' => null,
                        'membershipId' => null,
                    ],
                    'startup' => null,
                    'teamRoster' => [
                        'title' => 'Your Team',
                        'members' => [],
                        'actions' => [
                            'inviteViaLink' => false,
                            'addFromMatches' => false,
                        ],
                    ],
                    'myApplications' => [
                        'title' => 'My Applications',
                        'stats' => [
                            'applied' => $applications->where('status', 'applied')->count(),
                            'inReview' => $applications->where('status', 'in_review')->count(),
                            'interviews' => $applications->where('status', 'interview')->count(),
                        ],
                        'items' => $applications->map(function ($app) {
                            return [
                                'id' => $app->id,
                                'startupId' => $app->startup_id,
                                'startupName' => $app->startup->name,
                                'role' => ['id' => $app->role_id, 'label' => ucwords(str_replace('_', ' ', $app->role_id))],
                                'appliedAt' => $app->created_at->toIso8601String(),
                                'status' => $app->status,
                                'statusLabel' => ucwords(str_replace('_', ' ', $app->status)),
                            ];
                        })->values(),
                        'actions' => [
                            'browseStartups' => true,
                            'discoverMoreStartups' => true,
                        ],
                    ],
                    'teamInvites' => [
                        'title' => 'Team Invites',
                        'items' => $invites->map(function ($inv) {
                            return [
                                'id' => $inv->id,
                                'direction' => 'received',
                                'startupId' => $inv->startup_id,
                                'startupName' => $inv->startup->name ?? 'Unknown Startup',
                                'role' => ['id' => $inv->role_id, 'label' => ucwords(str_replace('_', ' ', $inv->role_id))],
                                'email' => $inv->recipient_email,
                                'sentAt' => $inv->created_at->toIso8601String(),
                                'status' => $inv->status,
                                'statusLabel' => ucfirst($inv->status),
                                'availableActions' => ['accept', 'decline'],
                            ];
                        })->values(),
                    ],
                    'teamCompleteness' => ['percent' => 0, 'filledRoles' => 0, 'targetRoles' => 0],
                    'requiredRoles' => [],
                    'missingRoles' => [],
                ]
            ]);
        }

        // Startup owner / member view
        $activeStartup = $startup ?? $membership->startup;
        $kind = $startup ? 'startup_owner' : 'startup_member';

        $members = StartupMember::with('user')->where('startup_id', $activeStartup->id)->get();
        $sentInvites = StartupInvitation::where('startup_id', $activeStartup->id)->where('status', 'pending')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'viewerContext' => [
                    'kind' => $kind,
                    'hasActiveStartup' => true,
                    'startupId' => $activeStartup->id,
                    'membershipId' => $membership ? $membership->id : null,
                ],
                'startup' => [
                    'id' => $activeStartup->id,
                    'name' => $activeStartup->name,
                    'description' => $activeStartup->description,
                    'industry' => ['id' => $activeStartup->industry, 'label' => ucfirst((string)$activeStartup->industry)],
                    'stage' => ['id' => $activeStartup->stage, 'label' => ucfirst((string)$activeStartup->stage)],
                ],
                'teamRoster' => [
                    'title' => 'Your Team',
                    'members' => $members->map(function ($mem) use ($user, $kind) {
                        return [
                            'id' => $mem->id,
                            'userId' => $mem->user_id,
                            'avatarUrl' => $mem->user->avatar_url ?? null,
                            'name' => $mem->user->name ?? 'Unknown',
                            'role' => ['id' => $mem->role_id, 'label' => ucwords(str_replace('_', ' ', $mem->role_id))],
                            'equityPercent' => $mem->equity_percent,
                            'commitment' => $mem->commitment,
                            'status' => $mem->status,
                            'statusLabel' => ucfirst($mem->status),
                            'isCurrentUser' => $mem->user_id === $user->id,
                            'availableActions' => $kind === 'startup_owner' ? ['edit_role', 'remove'] : [],
                        ];
                    })->values(),
                    'actions' => [
                        'inviteViaLink' => $kind === 'startup_owner',
                        'addFromMatches' => $kind === 'startup_owner',
                    ]
                ],
                'myApplications' => [
                    'title' => 'My Applications',
                    'stats' => ['applied' => 0, 'inReview' => 0, 'interviews' => 0],
                    'items' => [],
                    'actions' => ['browseStartups' => false, 'discoverMoreStartups' => false]
                ],
                'teamInvites' => [
                    'title' => 'Team Invites',
                    'items' => $sentInvites->map(function ($inv) use ($kind) {
                        return [
                            'id' => $inv->id,
                            'direction' => 'sent',
                            'startupId' => $inv->startup_id,
                            'startupName' => $inv->startup->name ?? '',
                            'role' => ['id' => $inv->role_id, 'label' => ucwords(str_replace('_', ' ', $inv->role_id))],
                            'email' => $inv->recipient_email,
                            'sentAt' => $inv->created_at->toIso8601String(),
                            'status' => $inv->status,
                            'statusLabel' => ucfirst($inv->status),
                            'availableActions' => $kind === 'startup_owner' ? ['revoke'] : [],
                        ];
                    })->values(),
                ],
                'teamCompleteness' => ['percent' => 50, 'filledRoles' => $members->count(), 'targetRoles' => max($members->count() + 1, 4)],
                'requiredRoles' => [],
                'missingRoles' => [],
            ]
        ]);
    }
}

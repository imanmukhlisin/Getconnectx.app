<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StartupInvitation;
use App\Models\StartupMember;
use App\Http\Requests\RespondStartupInvitationRequest;
use App\Jobs\SendTeamInviteAcceptedPush;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncomingInvitationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $invitations = StartupInvitation::with(['startup', 'sender'])
            ->where('recipient_email', $user->email)
            ->where('status', 'pending')
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'recipientEmail' => $inv->recipient_email,
                    'status' => $inv->status,
                    'sentAt' => $inv->created_at->toIso8601String(),
                    'expiresAt' => $inv->expires_at ? $inv->expires_at->toIso8601String() : null,
                    'startup' => [
                        'id' => $inv->startup->id,
                        'name' => $inv->startup->name,
                        'description' => $inv->startup->description,
                        'industry' => ['id' => $inv->startup->industry, 'label' => ucfirst((string)$inv->startup->industry)],
                        'stage' => ['id' => $inv->startup->stage, 'label' => ucfirst((string)$inv->startup->stage)],
                    ],
                    'inviter' => [
                        'userId' => $inv->sender->id ?? '',
                        'name' => $inv->sender->name ?? '',
                        'email' => $inv->sender->email ?? '',
                        'avatarUrl' => $inv->sender->avatar_url ?? null,
                        'roleLabel' => null, // If needed we could join with their own membership
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'invitations' => $invitations
            ]
        ]);
    }

    public function respond($invitationId, RespondStartupInvitationRequest $request)
    {
        $user = Auth::user();

        $invitation = StartupInvitation::where('id', $invitationId)
            ->where('recipient_email', $user->email)
            ->firstOrFail();

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Invitation is no longer pending'], 400);
        }

        $decision = $request->decision;
        $status = $decision === 'accept' ? 'accepted' : 'denied';

        DB::beginTransaction();
        try {
            $invitation->update([
                'status' => $status,
                'acted_at' => now(),
            ]);

            if ($status === 'accepted') {
                $member = StartupMember::create([
                    'startup_id' => $invitation->startup_id,
                    'user_id' => $user->id,
                    'role_id' => $invitation->role_id,
                    'equity_percent' => $invitation->equity_percent,
                    'commitment' => $invitation->commitment,
                    'status' => 'active',
                ]);

                // Notify founder
                SendTeamInviteAcceptedPush::dispatch($invitation, $invitation->sender, $user);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation ' . $status,
                'data' => [
                    'invitationId' => $invitation->id,
                    'status' => $status,
                    'startupId' => $invitation->startup_id,
                    'actedAt' => $invitation->acted_at->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to process response: ' . $e->getMessage()], 500);
        }
    }
}

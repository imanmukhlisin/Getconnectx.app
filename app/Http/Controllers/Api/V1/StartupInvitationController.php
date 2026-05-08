<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SendStartupInvitationRequest;
use App\Models\Startup;
use App\Models\StartupInvitation;
use App\Models\User;
use App\Jobs\SendTeamInviteReceivedPush;
use Illuminate\Support\Facades\Auth;

class StartupInvitationController extends Controller
{
    public function options(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'roleOptions' => [
                    ['id' => 'co_founder', 'label' => 'Co-Founder'],
                    ['id' => 'cto', 'label' => 'CTO'],
                    ['id' => 'engineer', 'label' => 'Engineer'],
                    ['id' => 'product_manager', 'label' => 'Product Manager'],
                    ['id' => 'designer', 'label' => 'Designer'],
                    ['id' => 'marketing', 'label' => 'Marketing'],
                    ['id' => 'operations', 'label' => 'Operations']
                ],
                'commitmentOptions' => [
                    ['id' => 'full_time', 'label' => 'Full-time'],
                    ['id' => 'part_time', 'label' => 'Part-time'],
                    ['id' => 'advisor', 'label' => 'Advisor']
                ],
                'equity' => [
                    'min' => 1,
                    'max' => 50,
                    'step' => 1,
                    'defaultValue' => 15
                ]
            ]
        ]);
    }

    public function store(SendStartupInvitationRequest $request)
    {
        $user = Auth::user();
        $startup = Startup::where('owner_id', $user->id)->first();

        // If not owner, check if they are an active member
        if (!$startup) {
            $membership = \App\Models\StartupMember::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();
            if ($membership) {
                $startup = Startup::find($membership->startup_id);
            }
        }

        if (!$startup) {
            return response()->json(['success' => false, 'message' => 'No active startup'], 403);
        }

        $invitation = StartupInvitation::create([
            'startup_id' => $startup->id,
            'sender_id' => $user->id,
            'recipient_email' => strtolower($request->email),
            'role_id' => $request->roleId,
            'equity_percent' => $request->equityPercent,
            'commitment' => $request->commitment,
            'status' => 'pending',
        ]);

        // If user with this email exists, send push notification
        $recipientUser = User::where('email', strtolower($request->email))->first();
        if ($recipientUser) {
            SendTeamInviteReceivedPush::dispatch($invitation, $recipientUser);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent',
            'data' => [
                'invitationId' => $invitation->id,
                'email' => $invitation->recipient_email,
                'status' => $invitation->status,
            ]
        ]);
    }

    public function destroy($invitationId)
    {
        $user = Auth::user();
        $startup = Startup::where('owner_id', $user->id)->first();

        // If not owner, check if they are an active member
        if (!$startup) {
            $membership = \App\Models\StartupMember::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();
            if ($membership) {
                $startup = Startup::find($membership->startup_id);
            }
        }

        if (!$startup) {
            return response()->json(['success' => false, 'message' => 'No active startup'], 403);
        }

        $invitation = StartupInvitation::where('id', $invitationId)
            ->where('startup_id', $startup->id)
            ->firstOrFail();

        $invitation->update(['status' => 'revoked']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation revoked',
            'data' => [
                'invitationId' => $invitation->id,
                'status' => 'revoked'
            ]
        ]);
    }
}

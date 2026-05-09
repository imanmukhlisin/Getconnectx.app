<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SendStartupInvitationRequest;
use App\Models\Startup;
use App\Models\StartupInvitation;
use App\Models\User;
use App\Jobs\SendTeamInviteReceivedPush;
use App\Mail\TeamInvitationMail;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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

        // Auto-create startup on the fly if user is a Founder but doesn't have a startup yet
        if (!$startup) {
            $builder = \Illuminate\Support\Facades\DB::table('builders')->where('user_id', $user->id)->first();
            if ($builder && strtolower(trim($builder->role_category)) === 'founder') {
                $startup = Startup::create([
                    'owner_id' => $user->id,
                    'name' => ($user->name ?? 'Founder') . "'s Startup",
                    'industry' => 'technology',
                    'stage' => 'idea',
                ]);
            }
        }

        if (!$startup) {
            return response()->json(['success' => false, 'message' => 'No active startup'], 403);
        }

        $email = $request->email;
        if ($request->has('user_id') && $request->user_id) {
            $targetUser = User::find($request->user_id);
            if ($targetUser) {
                $email = $targetUser->email;
            }
        }

        if (!$email) {
            return response()->json(['success' => false, 'message' => 'Email is required or user_id is invalid'], 400);
        }

        $invitation = StartupInvitation::create([
            'startup_id' => $startup->id,
            'sender_id' => $user->id,
            'recipient_email' => strtolower($email),
            'role_id' => $request->roleId ?? $request->role,
            'equity_percent' => $request->equityPercent,
            'commitment' => $request->commitment,
            'status' => 'pending',
        ]);

        // If user with this email exists, send push notification
        $recipientUser = User::where('email', strtolower($email))->first();
        if ($recipientUser) {
            SendTeamInviteReceivedPush::dispatch($invitation, $recipientUser);
        }

        // Send an actual email to the recipient asynchronously using Brevo HTTP API (bypassing SMTP IP blocks)
        try {
            $mailable = new TeamInvitationMail($invitation, $startup);
            $htmlContent = $mailable->render();
            $subject = "You have been invited to join {$startup->name} on ConnectX";
            
            $brevoService = app(BrevoService::class);
            $brevoService->sendHtmlEmail($subject, $htmlContent, strtolower($email), $recipientUser->name ?? $email);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send invitation email via Brevo API: ' . $e->getMessage());
            // We don't want to break the API response just because the email failed
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

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\StartupInvitation;
use App\Models\User;
use App\Services\PushNotificationService;

class SendTeamInviteReceivedPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invitation;
    public $recipient;

    public function __construct(StartupInvitation $invitation, User $recipient)
    {
        $this->invitation = $invitation;
        $this->recipient = $recipient;
    }

    public function handle(PushNotificationService $pushService): void
    {
        $senderName = $this->invitation->sender->name ?? 'Someone';
        $role = $this->invitation->role_id ?? 'a role';
        $title = "Team invitation";
        $body = "{$senderName} invited you to join as {$role}";

        $pushService->sendDirect($this->recipient, $title, $body, [
            'type' => 'team_invite_received',
            'invitation_id' => $this->invitation->id,
        ]);
    }
}

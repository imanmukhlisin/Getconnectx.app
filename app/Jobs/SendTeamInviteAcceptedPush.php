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

class SendTeamInviteAcceptedPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invitation;
    public $founder;
    public $talent;

    public function __construct(StartupInvitation $invitation, User $founder, User $talent)
    {
        $this->invitation = $invitation;
        $this->founder = $founder;
        $this->talent = $talent;
    }

    public function handle(PushNotificationService $pushService): void
    {
        $talentName = $this->talent->name ?? 'Someone';
        $title = "Invite accepted!";
        $body = "{$talentName} accepted your invite";

        $pushService->sendDirect($this->founder, $title, $body, [
            'type' => 'team_invite_accepted',
            'invitation_id' => $this->invitation->id,
        ]);
    }
}

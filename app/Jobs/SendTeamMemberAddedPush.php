<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\StartupMember;
use App\Models\User;
use App\Services\PushNotificationService;

class SendTeamMemberAddedPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $member;
    public $founder;

    public function __construct(StartupMember $member, User $founder)
    {
        $this->member = $member;
        $this->founder = $founder;
    }

    public function handle(PushNotificationService $pushService): void
    {
        $founderName = $this->founder->name ?? 'Someone';
        $role = $this->member->role_id ?? 'a role';
        $title = "You've been added to a team!";
        $body = "{$founderName} added you as {$role}";

        $pushService->sendDirect($this->member->user, $title, $body, [
            'type' => 'team_member_added',
            'member_id' => $this->member->id,
            'startup_id' => $this->member->startup_id,
        ]);
    }
}

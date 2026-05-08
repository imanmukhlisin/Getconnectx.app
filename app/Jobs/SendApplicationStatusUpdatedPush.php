<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\StartupApplication;
use App\Services\PushNotificationService;

class SendApplicationStatusUpdatedPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $application;

    public function __construct(StartupApplication $application)
    {
        $this->application = $application;
    }

    public function handle(PushNotificationService $pushService): void
    {
        $startupName = $this->application->startup->name ?? 'a startup';
        $status = ucfirst(str_replace('_', ' ', $this->application->status));
        $title = "Application update";
        $body = "Your application to {$startupName} is now {$status}";

        $pushService->sendDirect($this->application->user, $title, $body, [
            'type' => 'application_status_updated',
            'application_id' => $this->application->id,
            'startup_id' => $this->application->startup_id,
        ]);
    }
}

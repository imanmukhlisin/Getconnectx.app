<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    protected $targetUser;

    /**
     * @var string
     */
    protected $templateCode;

    /**
     * @var array
     */
    protected $replacements;

    /**
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(User $targetUser, string $templateCode, array $replacements = [], array $data = [])
    {
        $this->targetUser = $targetUser;
        $this->templateCode = $templateCode;
        $this->replacements = $replacements;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(PushNotificationService $pushService): void
    {
        try {
            $pushService->sendFromTemplate(
                $this->targetUser,
                $this->templateCode,
                $this->replacements,
                $this->data
            );
        } catch (\Exception $e) {
            Log::error('SendPushNotificationJob failed', [
                'user_id' => $this->targetUser->id,
                'template' => $this->templateCode,
                'error' => $e->getMessage()
            ]);
        }
    }
}

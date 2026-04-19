<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\LinkedInScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeLinkedInProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 menit batas waktu dari apify
    public $tries = 2;

    protected User $user;
    protected string $linkedinUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $linkedinUrl)
    {
        $this->user = $user;
        $this->linkedinUrl = $linkedinUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(LinkedInScraperService $scraperService): void
    {
        // Panggil endpoint Apify secara Sync (tapi dari sisi Queue laravel)
        $scrapedData = $scraperService->scrapeProfile($this->linkedinUrl);

        if ($scrapedData) {
            // Jika scraper berhasil mengumpulkan dataset
            $scraperService->syncToUserProfile($this->user, $scrapedData);
        }
    }
}

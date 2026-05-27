<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    /**
     * Run the Laravel queue worker on Vercel Serverless environment.
     * Accessible via secure webhook/cron token.
     */
    public function work(Request $request)
    {
        $token = $request->header('X-Queue-Token') ?? $request->query('token');
        $expectedToken = env('QUEUE_WEBHOOK_TOKEN', 'connectx_queue_secret_2026');

        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            // Run queue:work to process all outstanding jobs in a single execution.
            // Serverless friendly execution options.
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-jobs'        => 30, // Processes up to 30 jobs
                '--time-limit'      => 20, // Stop after 20 seconds to prevent Vercel timeout (default max 60s)
            ]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Queue processed successfully',
                'output'  => trim($output)
            ]);
        } catch (\Throwable $e) {
            Log::error('QueueController: Failed to process queue: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

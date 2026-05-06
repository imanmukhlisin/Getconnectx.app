<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller untuk sinkronisasi profil LinkedIn secara background.
 *
 * Alur:
 * 1. Terima request dari frontend (linkedin_url, fcm_token, device_id)
 * 2. Validasi input
 * 3. Update device_id dan fcm_token user ke database
 * 4. Panggil Apify API secara asynchronous (non-blocking)
 * 5. Langsung return response 200 tanpa menunggu scraping selesai
 *
 * PENTING: Controller ini DILARANG melakukan proses scraping/fetch API secara sinkronus.
 * Semua operasi berat harus didelegasikan ke background job.
 */
class LinkedInSyncController extends Controller
{
    /**
     * POST /api/v1/auth/linkedin-sync
     *
     * Terima linkedin_url dari frontend, lalu trigger Apify scraper API.
     * Webhook Apify akan dikonfigurasi untuk memanggil endpoint aplikasi kita
     * nanti apabila fetching (yg makan waktu menitan) sudah selesai.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        // ── Validasi Input ────────────────────────────────────────────
        $validated = $request->validate([
            // URL profil LinkedIn
            'linkedin_url' => 'required|string|url|max:255',

            // Token Firebase Cloud Messaging untuk push notification
            'fcm_token' => 'required|string|min:5',

            // Device ID untuk tracking perangkat (berguna untuk audit keamanan)
            'device_id' => 'required|string|min:3',
        ]);

        // ── Ambil User dari Token Sanctum ─────────────────────────────
        // User sudah terautentikasi via middleware auth:sanctum
        $user = $request->user();

        // Update fcm_token dan last_device_id agar tidak perlu menunggu background job
        $user->update([
            'fcm_token' => $validated['fcm_token'],
            'last_device_id' => $validated['device_id'],
        ]);

        // ── Trigger Apify Scraper (Async) ─────────────────────────────────
        // Memanggil Apify secara async. Apify akan memanggil endpoint webhook kita setelah selesai.
        app(\App\Services\LinkedInScraperService::class)->triggerScrapeAsync($user, $validated['linkedin_url']);

        // ── Return Langsung (< 100ms) ─────────────────────────────────
        // Frontend tidak perlu menunggu proses LinkedIn API & AI selesai.
        // Proses berjalan di background; frontend bisa polling profile endpoint
        // setelah beberapa detik untuk mendapatkan data yang sudah diupdate.
        return response()->json([
            'success' => true,
            'message' => 'LinkedIn sync initiated. Processing in the background.',
        ]);
    }
}

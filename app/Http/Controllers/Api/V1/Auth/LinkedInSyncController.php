<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLinkedInProfileJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller untuk sinkronisasi profil LinkedIn secara background.
 *
 * Alur:
 * 1. Terima request dari frontend (access_token, fcm_token, device_id)
 * 2. Validasi input
 * 3. Dispatch ProcessLinkedInProfileJob ke Redis Queue (async)
 * 4. Langsung return response 200 tanpa menunggu job selesai
 *
 * PENTING: Controller ini DILARANG melakukan proses scraping/fetch API secara sinkronus.
 * Semua operasi berat harus didelegasikan ke background job.
 */
class LinkedInSyncController extends Controller
{
    /**
     * POST /api/v1/auth/linkedin-sync
     *
     * Terima access_token LinkedIn dari frontend, lalu dispatch job
     * untuk memproses data profil secara asynchronous di background queue.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        // ── Validasi Input ────────────────────────────────────────────
        $validated = $request->validate([
            // Token OAuth LinkedIn yang dikirim dari Expo/React Native SDK
            'access_token' => 'required|string|min:10',

            // Token Firebase Cloud Messaging untuk push notification
            'fcm_token' => 'required|string|min:5',

            // Device ID untuk tracking perangkat (berguna untuk audit keamanan)
            'device_id' => 'required|string|min:3',
        ]);

        // ── Ambil User dari Token Sanctum ─────────────────────────────
        // User sudah terautentikasi via middleware auth:sanctum
        $user = $request->user();

        // ── Dispatch Job ke Queue (Async) ─────────────────────────────
        // Job ini akan dieksekusi oleh worker Redis di background,
        // TIDAK memblokir response HTTP ini.
        ProcessLinkedInProfileJob::dispatch(
            $user,
            $validated['access_token'],
            $validated['fcm_token'],
            $validated['device_id'],
        );

        // ── Return Langsung (< 100ms) ─────────────────────────────────
        // Frontend tidak perlu menunggu proses LinkedIn API & AI selesai.
        // Proses berjalan di background; frontend bisa polling profile endpoint
        // setelah beberapa detik untuk mendapatkan data yang sudah diupdate.
        return response()->json([
            'success' => true,
            'message' => 'LinkedIn sync is processing in the background.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/me/profile
     *
     * Berdasarkan API-PROFILE-LINKEDIN.md, endpoint ini mengembalikan profil user
     * dengan struktur ter-mapping (termasuk sections.about, personalityAndHobbies, dll).
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['tags', 'startup']);

        return response()->json([
            'success' => true,
            'message' => 'Profile fetched successfully',
            'data'    => new ProfileResource($user),
        ]);
    }

    /**
     * PATCH /api/v1/me/profile
     *
     * Update data profil secara parsial. Mendukung fields:
     * name, headline, location, about, personalityAndHobbyIds
     */
    public function updateMe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                   => 'nullable|string|max:255',
            'headline'               => 'nullable|string|max:255',
            'location'               => 'nullable|string|max:255',
            'about'                  => 'nullable|string|max:1000',
            'personalityAndHobbyIds' => 'nullable|array',
            'personalityAndHobbyIds.*' => 'integer|exists:tags,id',
        ]);

        $user = $request->user();
        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['headline'])) {
            $updateData['position'] = $validated['headline']; // Kita simpan headline di kolom position
        }
        if (isset($validated['location'])) {
            // FE mengirim string misal "Bandung, Indonesia", kita pisah untuk struktur database
            $locParts = explode(',', $validated['location']);
            $updateData['city'] = trim($locParts[0] ?? '');
            $updateData['country'] = trim($locParts[1] ?? '');
        }

        if (isset($validated['about'])) {
            $user->load('startup');
            if ($user->startup !== null) {
                // Di database kita simpan di kolom startup_idea untuk founder yg punya entitas startup
                $updateData['startup_idea'] = $validated['about'];
            } else {
                $updateData['bio'] = $validated['about'];
            }
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        if (isset($validated['personalityAndHobbyIds'])) {
            // Karena tag menggunakan sync, kita hanya menyinkronkan tag yg divalidasi
            // Ini akan menghapus tag lama dan menggantinya dengan yg baru. Pastikan FE mengirim semua P&H id.
            $user->tags()->sync($validated['personalityAndHobbyIds']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => new ProfileResource($user->fresh(['tags', 'startup'])),
        ]);
    }

    /**
     * GET /api/v1/profile-options
     *
     * Mengambil daftar master data seperti personalityAndHobbies agar Front End
     * dapat me-render opsi saat edit profile.
     */
    public function options(): JsonResponse
    {
        // Ambil Tag untuk personality, hobby
        $tags = Tag::whereIn('type', ['personality_hobbies', 'hobby', 'personality'])->get()
            ->map(function($tag) {
                return [
                    'id'   => $tag->id,
                    'name' => $tag->name,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Profile options fetched successfully',
            'data'    => [
                'personalityAndHobbies' => $tags
            ]
        ]);
    }

    /**
     * GET /api/v1/profiles/{id}
     *
     * Berdasarkan API-PROFILE-LINKEDIN.md, ini untuk fetch public profile.
     */
    public function show(string $id): JsonResponse
    {
        $user = User::with(['tags', 'startup'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Profile fetched successfully',
            'data'    => new ProfileResource($user),
        ]);
    }

    /**
     * PUT /api/v1/profile/fcm-token
     *
     * Masih dipertahankan karena Firebase Flutter masih memanggilnya.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM Token berhasil diperbarui.',
        ]);
    }
}

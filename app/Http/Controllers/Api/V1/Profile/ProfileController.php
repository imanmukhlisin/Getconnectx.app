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
        $user = $request->user()->load(['tags', 'startup', 'credentials']);

        // Calculate dynamic stats
        $user->matches_count = \App\Models\UserMatch::where(function($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('matched_user_id', $user->id);
        })->count();

        $user->teams_joined_count = \App\Models\StartupMember::where('user_id', $user->id)->count();

        // For now, connections can be treated as active matches or conversations
        $user->connections_count = $user->matches_count;

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
        ]);

        $user = $request->user();
        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['headline'])) {
            $updateData['position'] = $validated['headline'];
        }
        if (isset($validated['location'])) {
            $locParts = explode(',', $validated['location']);
            $updateData['city'] = trim($locParts[0] ?? '');
            $updateData['country'] = trim($locParts[1] ?? '');
        }

        if (isset($validated['about'])) {
            $user->load('startup');
            if ($user->startup !== null) {
                $updateData['startup_idea'] = $validated['about'];
            } else {
                $updateData['bio'] = $validated['about'];
            }
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        if ($request->has('personalityAndHobbyIds') || $request->has('personalityAndHobbies')) {
            $phIds = $request->input('personalityAndHobbyIds', []);
            if (empty($phIds) && $request->has('personalityAndHobbies.items')) {
                $phIds = collect($request->input('personalityAndHobbies.items'))->pluck('id')->all();
            }

            $tagIds = \App\Models\Tag::whereIn('code', $phIds)->pluck('id')->all();
            $otherTags = $user->tags()->where('type', '!=', 'personality_hobbies')->pluck('tags.id')->all();
            $user->tags()->sync(array_merge($otherTags, $tagIds));
        }

        if ($request->has('skillIds') || $request->has('skills')) {
            $skIds = $request->input('skillIds', []);
            if (empty($skIds) && $request->has('skills.items')) {
                $skIds = collect($request->input('skills.items'))->pluck('id')->all();
            }

            $tagIds = \App\Models\Tag::whereIn('code', $skIds)->pluck('id')->all();
            $otherTags = $user->tags()->where('type', '!=', 'skill')->pluck('tags.id')->all();
            $user->tags()->sync(array_merge($otherTags, $tagIds));
        }

        if ($request->has('interestIds') || $request->has('interests')) {
            $inIds = $request->input('interestIds', []);
            if (empty($inIds) && $request->has('interests.items')) {
                $inIds = collect($request->input('interests.items'))->pluck('id')->all();
            }

            $tagIds = \App\Models\Tag::whereIn('code', $inIds)->pluck('id')->all();
            $otherTags = $user->tags()->where('type', '!=', 'industry')->pluck('tags.id')->all();
            $user->tags()->sync(array_merge($otherTags, $tagIds));
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => new ProfileResource($user->fresh(['tags', 'startup', 'credentials'])),
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
        // Ambil Tag personality/hobby dan pakai kolom `code` sebagai ID
        // agar konsisten dengan kontrak API (ph_1, ph_2, ...)
        $tags = Tag::whereIn('type', ['personality_hobbies'])
            ->whereNotNull('code')
            ->orderBy('code')
            ->get()
            ->map(function ($tag) {
                return [
                    'id'   => $tag->code,  // ph_1, ph_2, ...
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
    /**
     * PUT /api/v1/profile/location
     *
     * Endpoint khusus untuk update titik koordinat (latitude, longitude) user.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $request->user()->update([
            'latitude'  => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location coordinates updated successfully.',
            'data'    => [
                'latitude'  => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]
        ]);
    }
}

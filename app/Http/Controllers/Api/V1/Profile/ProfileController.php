<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Profile\UpdateProfileStageARequest;
use App\Http\Requests\Profile\UpdateProfileStageBRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/profile
     *
     * Ambil data profil user saat ini beserta tag-nya.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('tags');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => $user->registrationSummary(),
                'tags' => $user->tags
            ],
        ]);
    }

    /**
     * GET /api/v1/profile/tags
     *
     * Ambil semua master tag (industry & skill) untuk opsi di frontend.
     */
    public function tags(): JsonResponse
    {
        $tags = Tag::all()->groupBy('type');

        return response()->json([
            'status' => 'success',
            'data'   => $tags,
        ]);
    }

    /**
     * PUT /api/v1/profile/stage-a-identity
     *
     * Simpan data Identitas Dasar (Stage A).
     */
    public function updateStageA(UpdateProfileStageARequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'name'          => $request->name,
            'username'      => $request->username,
            'position'      => $request->position,
            'role_category' => $request->role_category,
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => 'Identitas dasar berhasil diperbarui.',
            'next_step' => 'STAGE_B_TECHNICAL',
            'data'      => ['user' => $user->fresh()->registrationSummary()],
        ]);
    }

    /**
     * PUT /api/v1/profile/stage-b-technical
     *
     * Simpan data Profil Teknis (Stage B).
     */
    public function updateStageB(UpdateProfileStageBRequest $request): JsonResponse
    {
        $user = $request->user();

        // Gabungkan semua ID tag dari industri dan skill
        $allTagIds = array_merge(
            $request->industry_tag_ids,
            $request->skill_tag_ids
        );

        $user->update([
            'commitment_level' => $request->commitment_level,
            'startup_stage'    => $request->startup_stage,
        ]);

        // Sinkronisasi Many-to-Many
        $user->tags()->sync($allTagIds);

        return response()->json([
            'status'    => 'success',
            'message'   => 'Profil teknis berhasil diperbarui.',
            'next_step' => 'PROFILE_COMPLETE',
            'data'      => ['user' => $user->fresh()->load('tags')],
        ]);
    }
}

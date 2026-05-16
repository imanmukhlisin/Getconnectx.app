<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Startup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StartupProfileController extends Controller
{
    /**
     * PATCH /api/v1/me/startup
     *
     * Post-onboarding startup profile update.
     * Allows founders to update all enriched card fields:
     * tagline, stage, industry, traction, links, commitment, offering.
     */
    public function update(Request $request)
    {
        $request->validate([
            // Core profile
            'name'               => 'nullable|string|max:255',
            'tagline'            => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'stage'              => 'nullable|in:idea,mvp,pre_seed,seed,series_a',
            'industry'           => 'nullable|string',
            'secondary_industry' => 'nullable|string',
            'team_size'          => 'nullable|integer|min:1',
            'open_roles'         => 'nullable|array',

            // looking_for nested fields
            // Traction
            'user_count'         => 'nullable|string',
            'mau'                => 'nullable|string',
            'revenue'            => 'nullable|string',

            // Links
            'website'            => 'nullable|url',
            'prototype_url'      => 'nullable|url',
            'instagram'          => 'nullable|string',
            'twitter'            => 'nullable|string',
            'linkedin'           => 'nullable|string',
            'tiktok'             => 'nullable|string',

            // Offering & commitment
            'commitment'         => 'nullable|string',
            'equity'             => 'nullable|string',
            'paid'               => 'nullable|boolean',

            // Or send the whole looking_for blob
            'looking_for'        => 'nullable|array',
        ]);

        $user    = Auth::user();
        $startup = Startup::where('owner_id', $user->id)->first();

        if (!$startup) {
            return response()->json([
                'success' => false,
                'message' => 'No startup profile found. Please complete onboarding first.',
                'error'   => ['code' => 'NO_STARTUP_PROFILE'],
            ], 404);
        }

        // ── Merge looking_for JSON (non-destructive) ───────────────────────────────
        $existing = is_array($startup->looking_for) ? $startup->looking_for : [];

        $patch = array_filter([
            'user_count'    => $request->user_count,
            'mau'           => $request->mau,
            'revenue'       => $request->revenue,
            'website'       => $request->website,
            'prototype_url' => $request->prototype_url,
            'instagram'     => $request->instagram,
            'twitter'       => $request->twitter,
            'linkedin'      => $request->linkedin,
            'tiktok'        => $request->tiktok,
            'commitment'    => $request->commitment,
            'equity'        => $request->equity,
            'paid'          => $request->has('paid') ? (bool) $request->paid : null,
        ], fn ($v) => $v !== null);

        // If FE sends a full looking_for object, merge it on top
        if ($request->has('looking_for') && is_array($request->looking_for)) {
            $patch = array_merge($patch, $request->looking_for);
        }

        $merged = array_merge($existing, $patch);

        // ── Update startup record ──────────────────────────────────────────────────
        $startup->update(array_filter([
            'name'               => $request->name,
            'tagline'            => $request->tagline,
            'description'        => $request->description,
            'stage'              => $request->stage,
            'industry'           => $request->industry,
            'secondary_industry' => $request->secondary_industry,
            'team_size'          => $request->team_size,
            'open_roles'         => $request->open_roles,
            'looking_for'        => !empty($merged) ? $merged : null,
        ], fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Startup profile updated successfully.',
            'data'    => [
                'id'                 => $startup->id,
                'name'               => $startup->name,
                'tagline'            => $startup->tagline,
                'stage'              => $startup->stage,
                'industry'           => $startup->industry,
                'secondary_industry' => $startup->secondary_industry,
                'description'        => $startup->description,
                'team_size'          => $startup->team_size,
                'open_roles'         => $startup->open_roles,
                'looking_for'        => $startup->looking_for,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StartupApplication;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        // This is mainly handled in TeamOverviewController for "myApplications",
        // but if they hit this endpoint directly, we can return just the applications list.
        $user = Auth::user();

        $applications = StartupApplication::with('startup')
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'applied' => $applications->where('status', 'applied')->count(),
                    'in_review' => $applications->where('status', 'in_review')->count(),
                    'interviews' => $applications->where('status', 'interview')->count(),
                ],
                'applications' => $applications->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'startup' => [
                            'startup_id' => $app->startup->id,
                            'name' => $app->startup->name,
                            'logo_url' => $app->startup->logo_url ?? null,
                        ],
                        'role_applied' => ucwords(str_replace('_', ' ', $app->role_id)),
                        'status' => $app->status,
                        'applied_at' => $app->created_at->toIso8601String(),
                    ];
                })->values()
            ]
        ]);
    }
}

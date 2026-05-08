<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Startup;
use App\Models\StartupMember;
use App\Http\Requests\UpdateTeamMemberRequest;
use Illuminate\Support\Facades\Auth;

class TeamMemberController extends Controller
{
    public function update($startupId, $memberId, UpdateTeamMemberRequest $request)
    {
        $user = Auth::user();

        // Check if user is the startup owner
        $startup = Startup::where('id', $startupId)->where('owner_id', $user->id)->first();
        if (!$startup) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or startup not found'], 403);
        }

        $member = StartupMember::where('id', $memberId)->where('startup_id', $startupId)->firstOrFail();

        $member->update($request->only(['roleId', 'equityPercent', 'commitment', 'status']));

        // If the FE sends `roleId` instead of `role_id`, map it correctly
        if ($request->has('roleId')) {
            $member->update(['role_id' => $request->roleId]);
        }
        if ($request->has('equityPercent')) {
            $member->update(['equity_percent' => $request->equityPercent]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Member updated',
            'data' => $member
        ]);
    }

    public function destroy($startupId, $memberId)
    {
        $user = Auth::user();

        // Check if user is the startup owner
        $startup = Startup::where('id', $startupId)->where('owner_id', $user->id)->first();
        if (!$startup) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or startup not found'], 403);
        }

        $member = StartupMember::where('id', $memberId)->where('startup_id', $startupId)->firstOrFail();

        // Owner cannot remove themselves through this endpoint usually
        if ($member->user_id === $startup->owner_id) {
            return response()->json(['success' => false, 'message' => 'Cannot remove the startup owner'], 400);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully'
        ]);
    }
}

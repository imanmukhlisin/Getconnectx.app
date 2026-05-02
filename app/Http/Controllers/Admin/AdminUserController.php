<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    // ─────────────────────────────────────────────
    // STATISTICS
    // ─────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $today      = Carbon::today();
        $thisWeek   = Carbon::now()->startOfWeek();
        $thisMonth  = Carbon::now()->startOfMonth();

        $totalUsers        = User::count();
        $registeredToday   = User::whereDate('created_at', $today)->count();
        $registeredWeek    = User::where('created_at', '>=', $thisWeek)->count();
        $registeredMonth   = User::where('created_at', '>=', $thisMonth)->count();
        $onboardedUsers    = User::where('is_onboarded', true)->count();
        $blockedUsers      = User::where('is_blocked', true)->count();
        $activeUsers       = User::where('is_active', true)->where('is_blocked', false)->count();

        // Role breakdown
        $roleBreakdown = User::whereNotNull('role_category')
            ->groupBy('role_category')
            ->selectRaw('role_category, count(*) as total')
            ->pluck('total', 'role_category');

        // Daily registrations — last 14 days
        $dailyStats = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(fn($r) => $r->total);

        // Fill missing days with 0
        $labels = [];
        $data   = [];
        for ($i = 13; $i >= 0; $i--) {
            $date     = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::now()->subDays($i)->format('d M');
            $data[]   = $dailyStats[$date] ?? 0;
        }

        return response()->json([
            'total_users'       => $totalUsers,
            'registered_today'  => $registeredToday,
            'registered_week'   => $registeredWeek,
            'registered_month'  => $registeredMonth,
            'onboarded_users'   => $onboardedUsers,
            'onboarding_rate'   => $totalUsers > 0
                ? round(($onboardedUsers / $totalUsers) * 100, 1)
                : 0,
            'blocked_users'     => $blockedUsers,
            'active_users'      => $activeUsers,
            'role_breakdown'    => $roleBreakdown,
            'chart'             => ['labels' => $labels, 'data' => $data],
        ]);
    }

    // ─────────────────────────────────────────────
    // USER LIST
    // ─────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select([
                'id', 'name', 'email', 'whatsapp_number',
                'is_onboarded', 'is_active', 'is_blocked',
                'blocked_reason', 'blocked_at',
                'role_category', 'registration_step',
                'created_at',
            ]);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%");
            });
        }

        // Filter
        if ($request->has('is_blocked')) {
            $query->where('is_blocked', filter_var($request->is_blocked, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->has('is_onboarded')) {
            $query->where('is_onboarded', filter_var($request->is_onboarded, FILTER_VALIDATE_BOOLEAN));
        }
        if ($role = $request->get('role')) {
            $query->where('role_category', $role);
        }

        $users = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data'       => $users->map(fn($u) => $this->formatUser($u)),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // USER DETAIL
    // ─────────────────────────────────────────────

    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json($this->formatUserDetail($user));
    }

    // ─────────────────────────────────────────────
    // BLOCK / UNBLOCK
    // ─────────────────────────────────────────────

    public function block(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'is_blocked'     => true,
            'blocked_reason' => $request->reason,
            'blocked_at'     => now(),
            'is_active'      => false,
        ]);

        // Revoke all tokens so user is immediately logged out
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->email} telah diblokir.",
            'user'    => $this->formatUser($user->fresh()),
        ]);
    }

    public function unblock(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'is_blocked'     => false,
            'blocked_reason' => null,
            'blocked_at'     => null,
            'is_active'      => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->email} telah dibuka blokirnya.",
            'user'    => $this->formatUser($user->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────
    // NOTIFICATIONS
    // ─────────────────────────────────────────────

    public function notifications(): JsonResponse
    {
        $templates = NotificationTemplate::orderBy('type')->get();
        return response()->json($templates);
    }

    public function updateNotification(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'subject_en' => 'required|string',
            'subject_id' => 'required|string',
            'body_en'    => 'required|string',
            'body_id'    => 'required|string',
        ]);

        $template = NotificationTemplate::findOrFail($id);
        $template->update($request->only(['subject_en', 'subject_id', 'body_en', 'body_id']));

        return response()->json(['success' => true, 'template' => $template]);
    }

    // ─────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────

    private function formatUser(User $u): array
    {
        return [
            'id'                => $u->id,
            'name'              => $u->name ?? '—',
            'email'             => $u->email,
            'whatsapp'          => $u->whatsapp_number ?? '—',
            'role'              => $u->role_category ?? '—',
            'sudah_onboarding'  => $u->is_onboarded ? 'Iya' : 'Belum',
            'is_onboarded'      => (bool) $u->is_onboarded,
            'status'            => $u->is_blocked ? 'Diblokir' : ($u->is_active ? 'Aktif' : 'Tidak Aktif'),
            'is_blocked'        => (bool) $u->is_blocked,
            'blocked_reason'    => $u->blocked_reason,
            'blocked_at'        => $u->blocked_at?->format('d M Y H:i'),
            'daftar'            => $u->created_at->format('d M Y H:i'),
        ];
    }

    private function formatUserDetail(User $u): array
    {
        return array_merge($this->formatUser($u), [
            'gender'            => $u->gender ?? '—',
            'date_of_birth'     => $u->date_of_birth ?? '—',
            'city'              => $u->city ?? '—',
            'country'           => $u->country ?? '—',
            'linkedin_url'      => $u->linkedin_url ?? '—',
            'registration_step' => $u->registration_step,
            'primary_role'      => $u->primary_role ?? '—',
            'startup_name'      => $u->startup_name ?? '—',
            'is_pro'            => (bool) $u->is_pro,
        ]);
    }
}

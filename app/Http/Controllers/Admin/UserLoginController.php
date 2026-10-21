<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserLoginController extends Controller
{
    /**
     * Enforce that only Super Administrators can access this controller.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!$request->user() || !$request->user()->isSuperAdmin()) {
                abort(403, 'Unauthorized access. Only super administrators are permitted to view user login details.');
            }
            return $next($request);
        });
    }

    /**
     * Display the User Login Details directory window.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $cooperativeId = $request->input('cooperative_id');
        $role = $request->input('role');
        $status = $request->input('status');

        $query = User::with('cooperative');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($cooperativeId) {
            if ($cooperativeId === 'platform') {
                $query->whereNull('cooperative_id');
            } else {
                $query->where('cooperative_id', $cooperativeId);
            }
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status === 'active') {
            $query->where('is_active', true)->where(function ($q) {
                $q->where('membership_status', 'approved')->orWhereNull('membership_status');
            });
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'pending') {
            $query->where('membership_status', 'pending');
        }

        // Order by role priority (super_admin first, then admin, then others) and name
        $query->orderByRaw("CASE 
            WHEN role = 'super_admin' THEN 1 
            WHEN role = 'admin' THEN 2 
            WHEN role = 'chairman' THEN 3 
            WHEN role = 'secretary' THEN 4 
            WHEN role = 'treasurer' THEN 5 
            ELSE 6 END")
            ->orderBy('name', 'asc');

        $users = $query->paginate(20)->withQueryString();

        $cooperatives = Cooperative::orderBy('name')->get();

        $stats = [
            'total_users' => User::count(),
            'total_members' => User::where('role', 'member')->count(),
            'total_admins' => User::whereIn('role', ['admin', 'chairman', 'secretary', 'treasurer'])->count(),
            'total_super_admins' => User::where('role', 'super_admin')->count(),
        ];

        return view('admin.user_logins.index', compact(
            'users',
            'cooperatives',
            'stats',
            'search',
            'cooperativeId',
            'role',
            'status'
        ));
    }

    /**
     * Allow Super Admin to set or reset a user's password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $newPassword = $request->input('password');

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        return back()->with('success', "Password for user {$user->name} ({$user->email}) was successfully updated.");
    }
}

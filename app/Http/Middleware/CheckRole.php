<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        // If the route specifically requires super_admin, only super_admin can proceed
        if (in_array('super_admin', $roles, true)) {
            if (!$user->isSuperAdmin()) {
                abort(403, 'Unauthorized access. Only super administrators are permitted to manage cooperatives.');
            }
            return $next($request);
        }

        // Super admin has access to administrative and management routes
        if ($user->isSuperAdmin() && !in_array('member', $roles, true)) {
            return $next($request);
        }

        if (!in_array($user->role, $roles, true)) {
            // If an administrative/super_admin user tries to access member-only routes, redirect to admin dashboard
            if ($user->isManagementRole() && in_array('member', $roles, true)) {
                return redirect()->route('admin.dashboard');
            }
            abort(403, 'Unauthorized access.');
        }

        // Prevent pending or deactivated members from accessing member routes
        if (in_array('member', $roles, true) && ($user->isPendingMembership() || !$user->is_active)) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Your membership application is currently pending approval by the cooperative admin. You will be able to log in once your application has been accepted.',
            ]);
        }

        return $next($request);
    }
}
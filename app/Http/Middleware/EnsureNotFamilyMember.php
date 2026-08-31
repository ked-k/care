<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Keeps the staff application area off-limits to Family-role logins. Applied
 * to the main authenticated route group in routes/web.php; the family portal
 * routes live outside that group under their own 'role:Family' middleware.
 */
class EnsureNotFamilyMember
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->hasRole('Family')) {
            return redirect()->route('family.portal');
        }

        return $next($request);
    }
}

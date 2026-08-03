<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DemoMode
{
    /**
     * HTTP verbs that create / update / delete data.
     *
     * @var string[]
     */
    protected array $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Block data-mutating requests while the app runs in demo mode.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('demo.enabled') || ! in_array($request->method(), $this->writeMethods, true)) {
            return $next($request);
        }

        // Always allow authentication so visitors can sign in / out.
        if ($request->routeIs('login') || $request->is('login', 'logout')) {
            return $next($request);
        }

        $message = config('demo.message');

        // AJAX / API callers get a 423 Locked + message (surfaced as a toast).
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 423);
        }

        // Normal form posts bounce back with a warning toast.
        return back()->with('warning', $message);
    }
}

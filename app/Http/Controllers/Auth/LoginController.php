<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | Authenticates users for the application. Re-implemented without the
    | legacy AuthenticatesUsers trait, which was removed in Laravel 11.
    |
    */

    protected string $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('item_checkbox'))) {
            $request->session()->regenerate();

            // Batch 20: a login has no Eloquent "subject" model to attach
            // LogsActivity to, so this is the one place that still calls
            // Spatie's activity() helper directly rather than relying on
            // the trait — replaces the old AuditLogger::log('LOGIN_SUCCESS',
            // Auth::user()).
            activity()
                ->causedBy(Auth::user())
                ->useLog('Authentication')
                ->event('login')
                ->log('User logged in');

            // Family-role logins never land in the staff app — send them
            // straight to their own portal regardless of what was "intended".
            if (Auth::user()->hasRole('Family')) {
                return redirect()->route('family.portal');
            }

            return redirect()->intended($this->redirectTo);
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ConfirmPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Confirm Password Controller
    |--------------------------------------------------------------------------
    |
    | Handles password confirmations. Re-implemented without the legacy
    | ConfirmsPasswords trait, which was removed in Laravel 11.
    |
    */

    protected string $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showConfirmForm()
    {
        return view('auth.passwords.confirm');
    }

    public function confirm(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended($this->redirectTo);
    }
}

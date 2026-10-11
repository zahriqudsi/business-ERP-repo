<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, LoginOtp $otp): RedirectResponse
    {
        $credentials = [
            'username' => trim((string) $request->input('username')),
            'password' => trim((string) $request->input('password')),
        ];

        if ($credentials['username'] === '' || $credentials['password'] === '') {
            return back()->withErrors(['login' => 'Please enter username and password!'])->onlyInput('username');
        }

        if (! Auth::validate($credentials)) {
            return back()->withErrors(['login' => 'Invalid username or password!'])->onlyInput('username');
        }

        $user = User::where('username', $credentials['username'])->firstOrFail();

        // Checked only after a correct password, so it doesn't reveal which usernames exist
        if (! $user->is_active) {
            return back()->withErrors(['login' => 'This account has been switched off. Please contact the owner.'])->onlyInput('username');
        }

        // OTP disabled: log the user in directly.
        if (! config('sjpos.otp.enabled')) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        if (! $otp->issue($user)) {
            return back()->withErrors(['login' => 'Could not send verification code. Please try again.'])->onlyInput('username');
        }

        // Stash the pending login (not a full session yet) until the code is verified.
        $request->session()->put('pending_user_id', $user->id);
        $request->session()->put('otp_last_sent', now()->timestamp);

        return redirect()->route('otp.create');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

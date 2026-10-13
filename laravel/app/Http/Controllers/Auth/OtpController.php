<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp');
    }

    public function store(Request $request, LoginOtp $otp): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $error = $otp->verify($user, trim((string) $request->input('otp')));
        if ($error !== null) {
            return back()->withErrors(['otp' => $error]);
        }

        $request->session()->forget(['pending_user_id', 'otp_last_sent']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request, LoginOtp $otp): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $lastSent = (int) $request->session()->get('otp_last_sent', 0);
        if (now()->timestamp - $lastSent < config('sjpos.otp.resend_cooldown')) {
            return back()->withErrors(['otp' => 'Please wait before requesting another code.']);
        }

        if (! $otp->issue($user)) {
            return back()->withErrors(['otp' => 'Could not resend code. Please try again.']);
        }

        $request->session()->put('otp_last_sent', now()->timestamp);

        return back()->with('info', 'A new code has been sent.');
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('pending_user_id');

        return $id ? User::find($id) : null;
    }
}

<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Email one-time codes used as a second login step (when OTP is enabled).
 */
class LoginOtp
{
    /**
     * Replace any previous code for the user with a new one and email it.
     */
    public function issue(User $user): bool
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('user_id', $user->id)->delete();
        OtpCode::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(config('sjpos.otp.ttl_minutes')),
        ]);

        try {
            Mail::to(config('sjpos.otp.email'), $user->username)->send(new LoginOtpMail($user->username, $otp));

            return true;
        } catch (Throwable $e) {
            Log::error('Could not send login OTP email: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Check a code. Returns null on success, or the error message to show.
     */
    public function verify(User $user, string $code): ?string
    {
        $record = OtpCode::where('user_id', $user->id)->latest('id')->first();

        if (! $record) {
            return 'No active code found. Please request a new one.';
        }
        if ($record->expires_at->isPast()) {
            return 'Your code has expired. Please request a new one.';
        }
        if ($record->attempts >= config('sjpos.otp.max_attempts')) {
            return 'Too many failed attempts. Please request a new code.';
        }
        if (! Hash::check($code, $record->otp_hash)) {
            $record->increment('attempts');

            return 'Invalid code. Please try again.';
        }

        OtpCode::where('user_id', $user->id)->delete();

        return null;
    }
}

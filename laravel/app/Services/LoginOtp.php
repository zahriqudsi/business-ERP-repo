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
     * Where the user's codes go: their own email, else the business's mailbox from Settings.
     */
    public function recipientFor(User $user): ?string
    {
        return ($user->email ?: config('sjpos.otp.email')) ?: null;
    }

    /**
     * Replace any previous code for the user with a new one and email it.
     * Callers check recipientFor() first; with no address this returns false.
     */
    public function issue(User $user): bool
    {
        $recipient = $this->recipientFor($user);
        if ($recipient === null) {
            return false;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('user_id', $user->id)->delete();
        OtpCode::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(config('sjpos.otp.ttl_minutes')),
        ]);

        try {
            Mail::to($recipient, $user->display_name)->send(new LoginOtpMail($user->display_name, $otp));

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

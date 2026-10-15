<?php

namespace App\Services;

use App\Models\Landlord\PlatformAdmin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the people who run the app when something fails unattended (a background job, the nightly
 * backup, the billing check). Sent straight away, never queued: the queue may be what is failing.
 */
class Alerts
{
    public function send(string $subject, string $body): void
    {
        Log::error("Alert: {$subject}", ['details' => $body]);

        $recipients = $this->recipients();
        // The same problem is reported once per throttle period, not once per failing job
        if ($recipients === [] || ! Cache::add('alert:'.sha1($subject), true, now()->addMinutes(config('monitoring.alert_throttle_minutes')))) {
            return;
        }

        try {
            Mail::raw($body, fn ($message) => $message->to($recipients)->subject('['.config('sjpos.product_name').'] '.$subject));
        } catch (Throwable $e) {
            Log::error('Could not send an alert email: '.$e->getMessage());
        }
    }

    /**
     * @return list<string>
     */
    private function recipients(): array
    {
        $emails = config('monitoring.alert_emails');

        if (config('tenancy.enabled')) {
            try {
                $emails = array_merge($emails, PlatformAdmin::pluck('email')->all());
            } catch (Throwable) {
                // The platform database may be what's down; still alert the configured addresses
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }
}

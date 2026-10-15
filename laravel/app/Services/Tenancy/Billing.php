<?php

namespace App\Services\Tenancy;

use App\Models\Landlord\Plan;
use App\Models\Landlord\PlatformActivity;
use App\Models\Landlord\PlatformAdmin;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TenantPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Manual billing: staff record payments, which extend a business's paid time; a daily check
 * moves businesses whose time ran out to past due, and after a grace period pauses them.
 */
class Billing
{
    /**
     * Statuses the daily check looks at. Paused and closed businesses are left alone.
     */
    private const CHECKED_STATUSES = ['trial', 'active', 'past_due'];

    /**
     * Record a payment and extend the paid time, from today or from the end of time already paid for.
     *
     * @param  array{amount: float|string, currency?: string, months: int|string, method: string, reference?: ?string, note?: ?string, paid_on: string, plan_id?: ?int}  $data
     */
    public function recordPayment(Tenant $tenant, PlatformAdmin $admin, array $data): TenantPayment
    {
        return DB::connection('landlord')->transaction(function () use ($tenant, $admin, $data) {
            $tenant = Tenant::lockForUpdate()->findOrFail($tenant->id);
            $months = (int) $data['months'];
            $from = $tenant->paid_until?->isFuture() ? $tenant->paid_until : now();
            $coversUntil = $from->copy()->addMonthsNoOverflow($months);
            $planId = $data['plan_id'] ?? $tenant->plan_id;

            $payment = $tenant->payments()->create([
                'plan_id' => $planId,
                'platform_admin_id' => $admin->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? Plan::find($planId)?->currency ?? 'LKR',
                'months' => $months,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'paid_on' => $data['paid_on'],
                'covers_until' => $coversUntil,
                'created_at' => now(),
            ]);

            // Paying ends a trial or a billing pause; a pause staff set by hand stays until they resume it
            $resumes = in_array($tenant->status, ['trial', 'past_due'], true)
                || ($tenant->status === 'suspended' && $tenant->suspended_reason === 'billing');

            $tenant->update(['paid_until' => $coversUntil, 'plan_id' => $planId] + ($resumes ? [
                'status' => 'active', 'suspended_at' => null, 'suspended_reason' => null,
            ] : []));

            PlatformActivity::record('payment_recorded', "{$tenant->name} paid ".number_format((float) $data['amount'], 2)." for {$months} month(s), now paid until ".$coversUntil->format('j M Y'), $tenant, $admin);

            return $payment;
        });
    }

    /**
     * The daily check. Returns how many businesses moved to past due and to paused.
     *
     * @return array{past_due: int, suspended: int}
     */
    public function check(?Carbon $now = null): array
    {
        $now ??= now();
        $graceEnd = $now->copy()->subDays((int) config('tenancy.billing.grace_days'));
        $moved = ['past_due' => 0, 'suspended' => 0];

        Tenant::whereIn('status', self::CHECKED_STATUSES)->each(function (Tenant $tenant) use ($now, $graceEnd, &$moved) {
            $due = $tenant->billingDueAt();
            if ($due === null || $due->isAfter($now)) {
                return;
            }

            if ($due->isBefore($graceEnd)) {
                $tenant->update(['status' => 'suspended', 'suspended_at' => $now, 'suspended_reason' => 'billing']);
                PlatformActivity::record('suspended', "Paused {$tenant->name}: unpaid since ".$due->format('j M Y'), $tenant);
                $moved['suspended']++;
            } elseif ($tenant->status !== 'past_due') {
                $tenant->update(['status' => 'past_due']);
                PlatformActivity::record('past_due', "{$tenant->name} is past due since ".$due->format('j M Y'), $tenant);
                $moved['past_due']++;
            }
        });

        return $moved;
    }

    /**
     * Close a business's account. Its data is kept until the retention period ends.
     */
    public function close(Tenant $tenant, PlatformAdmin $admin): void
    {
        $keepUntil = now()->addDays((int) config('tenancy.billing.retention_days'));
        $tenant->update(['status' => 'cancelled', 'data_purge_after' => $keepUntil]);
        PlatformActivity::record('closed', "Closed {$tenant->name}; data kept until ".$keepUntil->format('j M Y'), $tenant, $admin);
    }
}

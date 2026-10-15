<?php

namespace App\Console\Commands;

use App\Services\Tenancy\Billing;
use Illuminate\Console\Command;

/**
 * Daily: businesses whose trial or paid time ran out become past due, and are paused after the grace period.
 */
class TenantsCheckBilling extends Command
{
    protected $signature = 'tenants:check-billing';

    protected $description = 'Move businesses whose trial or paid time ran out to past due, then pause them after the grace period';

    public function handle(Billing $billing): int
    {
        $moved = $billing->check();

        $this->info("Past due: {$moved['past_due']}. Paused: {$moved['suspended']}.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\Tenancy\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Run any artisan command as each business, e.g.
 * `php artisan tenants:run "sjpos:user admin --password=..." --tenant=acme`.
 */
class TenantsRun extends Command
{
    protected $signature = 'tenants:run {line : The command to run, with its arguments, in quotes}
        {--tenant=* : Only these slugs (default: every usable business)}';

    protected $description = 'Run an artisan command once per business';

    public function handle(TenantManager $tenancy): int
    {
        $tenants = Tenant::query()
            ->when(
                $this->option('tenant'),
                fn ($query, array $slugs) => $query->whereIn('slug', $slugs),
                fn ($query) => $query->whereIn('status', Tenant::USABLE_STATUSES),
            )
            ->orderBy('slug')
            ->get();

        $failed = 0;
        foreach ($tenants as $tenant) {
            $this->line("<info>{$tenant->slug}</info>");
            $exitCode = $tenancy->run($tenant, fn () => Artisan::call((string) $this->argument('line'), [], $this->output));
            $failed += $exitCode === self::SUCCESS ? 0 : 1;
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Console\Command;
use Throwable;

/**
 * Run on every deploy: brings each business database up to the current schema.
 */
class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate {--tenant=* : Only these slugs (default: every business)}';

    protected $description = 'Run the business-database migrations for every business (or the given ones)';

    public function handle(TenantProvisioner $provisioner): int
    {
        $tenants = Tenant::query()
            ->whereNotIn('status', ['provisioning', 'failed'])
            ->when($this->option('tenant'), fn ($query, array $slugs) => $query->whereIn('slug', $slugs))
            ->orderBy('slug')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No businesses to migrate.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($tenants as $tenant) {
            $this->line("<info>{$tenant->slug}</info> ({$tenant->database})");
            try {
                $this->line(trim($provisioner->migrate($tenant)));
            } catch (Throwable $e) {
                $failed++;
                $this->error("  Failed: {$e->getMessage()}");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}

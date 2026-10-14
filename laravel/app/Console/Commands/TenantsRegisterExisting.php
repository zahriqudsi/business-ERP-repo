<?php

namespace App\Console\Commands;

use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Brings an existing single-business database (SJ Technology's) onto the platform without copying it.
 */
class TenantsRegisterExisting extends Command
{
    protected $signature = 'tenants:register-existing {slug : Subdomain for the business}
        {--database= : The existing database}
        {--name= : Business name (defaults to the slug)}';

    protected $description = 'Register an existing database as a business, then run its pending migrations';

    public function handle(TenantProvisioner $provisioner): int
    {
        $database = (string) $this->option('database');
        if ($database === '') {
            $this->error('Give the existing database with --database=.');

            return self::FAILURE;
        }

        try {
            $tenant = $provisioner->registerExisting($this->option('name') ?: (string) $this->argument('slug'), (string) $this->argument('slug'), $database);
        } catch (ValidationException $e) {
            $this->error(implode(' ', $e->validator->errors()->all()));

            return self::FAILURE;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Database {$database} registered as '{$tenant->name}' ({$tenant->slug}.".config('tenancy.base_domain').').');

        return self::SUCCESS;
    }
}

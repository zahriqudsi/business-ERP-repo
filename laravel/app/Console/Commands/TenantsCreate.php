<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Tenancy\TenantManager;
use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Sign-up from the command line until the sign-up page exists (M4).
 */
class TenantsCreate extends Command
{
    protected $signature = 'tenants:create {slug : Subdomain, e.g. acme for acme.yourapp.com}
        {--name= : Business name (defaults to the slug)}
        {--owner-email= : Owner\'s email}
        {--owner= : Create an owner login with this username}
        {--password= : Owner password (prompted when omitted)}';

    protected $description = 'Create a business with its own database, optionally with an owner login';

    public function handle(TenantProvisioner $provisioner, TenantManager $tenancy): int
    {
        $slug = (string) $this->argument('slug');
        $owner = $this->option('owner');
        $password = $owner ? ($this->option('password') ?: $this->secret('Owner password')) : null;

        if ($owner && blank($password)) {
            $this->error('A password is required for the owner.');

            return self::FAILURE;
        }

        try {
            $tenant = $provisioner->create($this->option('name') ?: $slug, $slug, $this->option('owner-email'));
        } catch (ValidationException $e) {
            $this->error(implode(' ', $e->validator->errors()->all()));

            return self::FAILURE;
        }

        if ($owner) {
            $tenancy->run($tenant, fn () => User::create([
                'username' => $owner,
                'email' => $this->option('owner-email'),
                'password' => $password,
                'role' => 'owner',
            ]));
        }

        $this->info("Business '{$tenant->name}' created: {$tenant->slug}.".config('tenancy.base_domain')." (database {$tenant->database}).");

        return self::SUCCESS;
    }
}

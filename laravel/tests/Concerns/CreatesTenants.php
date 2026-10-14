<?php

namespace Tests\Concerns;

use App\Models\Landlord\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Real tenancy for a test: a fresh landlord database and real business databases,
 * all dropped again afterwards. Hosts are "{slug}.pos.test"; "pos.test" is the platform's own.
 */
trait CreatesTenants
{
    protected function setUpTenancy(): void
    {
        $landlord = config('database.connections.landlord.database');
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$landlord}`");
        $this->dropTenantDatabases();
        Artisan::call('migrate:fresh', ['--database' => 'landlord', '--path' => 'database/migrations/landlord', '--force' => true]);

        config([
            'tenancy.enabled' => true,
            'tenancy.base_domain' => 'pos.test',
            'tenancy.central_domains' => ['pos.test'],
        ]);

        $this->beforeApplicationDestroyed(function () {
            $this->app->make(TenantManager::class)->end();
            foreach (Tenant::withTrashed()->pluck('id') as $id) {
                File::deleteDirectory(storage_path("app/tenants/{$id}"));
            }
            $this->dropTenantDatabases();
        });
    }

    protected function createTenant(string $slug): Tenant
    {
        return $this->app->make(TenantProvisioner::class)->create(ucfirst($slug).' Ltd', $slug);
    }

    /**
     * Run $callback inside the business, e.g. to arrange its records.
     */
    protected function asTenant(Tenant $tenant, callable $callback): mixed
    {
        return $this->app->make(TenantManager::class)->run($tenant, $callback);
    }

    protected function createUser(Tenant $tenant, string $username, string $role = 'owner'): User
    {
        return $this->asTenant($tenant, fn () => User::create(['username' => $username, 'password' => 'secret-password', 'role' => $role]));
    }

    /**
     * Sign in through the login form on the business's own host, as a browser would.
     */
    protected function signInOn(Tenant $tenant, string $username): void
    {
        $this->post("http://{$tenant->slug}.pos.test/login", ['username' => $username, 'password' => 'secret-password'])
            ->assertRedirect("http://{$tenant->slug}.pos.test");
    }

    private function dropTenantDatabases(): void
    {
        $prefix = str_replace('_', '\_', (string) config('tenancy.database_prefix'));
        foreach (DB::select('SELECT SCHEMA_NAME AS name FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE ?', [$prefix.'%']) as $database) {
            DB::statement("DROP DATABASE `{$database->name}`");
        }
    }
}

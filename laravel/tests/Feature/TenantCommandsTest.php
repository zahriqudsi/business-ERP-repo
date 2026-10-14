<?php

namespace Tests\Feature;

use App\Models\Landlord\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class TenantCommandsTest extends TestCase
{
    use CreatesTenants;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenancy();
    }

    public function test_create_makes_a_business_database_with_the_schema_and_an_owner(): void
    {
        $this->artisan('tenants:create', ['slug' => 'acme', '--name' => 'Acme Traders', '--owner' => 'boss', '--password' => 'secret-password'])
            ->expectsOutputToContain("Business 'Acme Traders' created: acme.pos.test")
            ->assertSuccessful();

        $tenant = Tenant::where('slug', 'acme')->firstOrFail();
        $this->assertSame('trial', $tenant->status);
        $this->assertSame('sjpos_phpunit_tenant_'.$tenant->id, $tenant->database);
        $this->asTenant($tenant, function () {
            $this->assertTrue(Schema::hasTable('invoices'));
            $this->assertSame('owner', User::where('username', 'boss')->firstOrFail()->role);
        });
    }

    #[TestWith(['admin', 'That subdomain is reserved.'], 'reserved')]
    #[TestWith(['Bad_Slug', 'The subdomain may only use lowercase letters, numbers and hyphens'], 'invalid characters')]
    public function test_create_rejects_an_unusable_subdomain(string $slug, string $message): void
    {
        $this->artisan('tenants:create', ['slug' => $slug])
            ->expectsOutputToContain($message)
            ->assertFailed();

        $this->assertSame(0, Tenant::count());
    }

    public function test_create_rejects_a_subdomain_already_taken(): void
    {
        $this->createTenant('acme');

        $this->artisan('tenants:create', ['slug' => 'acme'])
            ->expectsOutputToContain('That subdomain is already taken.')
            ->assertFailed();

        $this->assertSame(1, Tenant::count());
    }

    public function test_register_existing_keeps_legacy_data_and_adds_the_missing_schema(): void
    {
        // A database shaped like the legacy app's: only the original users table, with a user in it
        $database = 'sjpos_phpunit_tenant_legacy';
        DB::statement("CREATE DATABASE `{$database}`");
        config(['database.connections.legacy' => ['database' => $database] + config('database.connections.mysql')]);
        Schema::connection('legacy')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 100)->unique();
            $table->string('password', 255);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
        DB::connection('legacy')->table('users')->insert(['username' => 'shane', 'password' => bcrypt('old-password')]);

        $this->artisan('tenants:register-existing', ['slug' => 'sjtechnology', '--database' => $database, '--name' => 'SJ Technology'])
            ->assertSuccessful();

        $tenant = Tenant::where('slug', 'sjtechnology')->firstOrFail();
        $this->assertSame('active', $tenant->status);
        $this->asTenant($tenant, function () {
            // The roles migration ran on the legacy table: existing accounts become owners
            $this->assertSame('owner', User::where('username', 'shane')->firstOrFail()->role);
            $this->assertTrue(Schema::hasTable('invoices'));
        });
    }

    public function test_register_existing_rejects_a_missing_database(): void
    {
        $this->artisan('tenants:register-existing', ['slug' => 'ghost', '--database' => 'sjpos_phpunit_tenant_missing'])
            ->expectsOutputToContain('Database `sjpos_phpunit_tenant_missing` does not exist.')
            ->assertFailed();

        $this->assertSame(0, Tenant::count());
    }

    public function test_run_executes_the_command_only_in_the_chosen_business(): void
    {
        $acme = $this->createTenant('acme');
        $beta = $this->createTenant('beta');

        $this->artisan('tenants:run', ['line' => 'sjpos:user boss --password=secret-password', '--tenant' => ['acme']])
            ->assertSuccessful();

        $this->assertTrue($this->asTenant($acme, fn () => User::where('username', 'boss')->exists()));
        $this->assertFalse($this->asTenant($beta, fn () => User::where('username', 'boss')->exists()));
    }
}

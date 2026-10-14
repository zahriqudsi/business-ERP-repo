<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Landlord\Domain;
use App\Models\Landlord\Tenant;
use App\Models\Setting;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

    public function test_import_files_copies_the_old_logo_backups_and_sign_in_settings_into_the_business(): void
    {
        $tenant = $this->createTenant('sjtechnology');
        File::ensureDirectoryExists(public_path('uploads'));
        File::copy(public_path('assets/img/default-logo.png'), public_path('uploads/logo-import-test.png'));
        File::ensureDirectoryExists(storage_path('app/backups'));
        File::put(storage_path('app/backups/backup-2000-01-01_000000.sql'), '-- old backup');
        $this->asTenant($tenant, fn () => $this->app->make(Settings::class)->save(['company.logo' => 'uploads/logo-import-test.png']));
        config(['sjpos.otp.enabled' => true, 'sjpos.otp.email' => 'office@sj.test']);

        try {
            $this->artisan('tenants:import-files', ['slug' => 'sjtechnology'])->assertSuccessful();

            $tenantFolder = storage_path("app/tenants/{$tenant->id}");
            $this->assertFileExists("{$tenantFolder}/branding/logo-import-test.png");
            $this->assertFileExists("{$tenantFolder}/backups/backup-2000-01-01_000000.sql");
            // Copied, not moved, and the logo also where a single-business install looks: a rollback keeps working
            $this->assertFileExists(public_path('uploads/logo-import-test.png'));
            $this->assertFileExists(storage_path('app/branding/logo-import-test.png'));
            $this->assertFileExists(storage_path('app/backups/backup-2000-01-01_000000.sql'));
            $this->asTenant($tenant, function () {
                $this->assertSame('branding/logo-import-test.png', config('sjpos.company.logo'));
                $this->assertTrue(Setting::findOrFail('otp.enabled')->value);
                $this->assertSame('office@sj.test', Setting::findOrFail('otp.email')->value);
            });
        } finally {
            File::delete([public_path('uploads/logo-import-test.png'), storage_path('app/branding/logo-import-test.png'), storage_path('app/backups/backup-2000-01-01_000000.sql')]);
        }
    }

    public function test_import_files_dry_run_copies_and_saves_nothing(): void
    {
        $tenant = $this->createTenant('sjtechnology');
        File::ensureDirectoryExists(storage_path('app/backups'));
        File::put(storage_path('app/backups/backup-2000-01-01_000000.sql'), '-- old backup');

        try {
            $this->artisan('tenants:import-files', ['slug' => 'sjtechnology', '--dry-run' => true])
                ->expectsOutputToContain('Dry run: nothing will be copied or saved.')
                ->assertSuccessful();

            $this->assertDirectoryDoesNotExist(storage_path("app/tenants/{$tenant->id}/backups"));
            $this->assertFalse($this->asTenant($tenant, fn () => Setting::whereKey('otp.enabled')->exists()));
        } finally {
            File::delete(storage_path('app/backups/backup-2000-01-01_000000.sql'));
        }
    }

    public function test_add_domain_opens_the_business_on_that_address(): void
    {
        $tenant = $this->createTenant('sjtechnology');

        $this->artisan('tenants:add-domain', ['slug' => 'sjtechnology', 'domain' => 'POS.SJTechnology.lk', '--primary' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('domains', ['tenant_id' => $tenant->id, 'domain' => 'pos.sjtechnology.lk', 'is_primary' => true], 'landlord');
    }

    #[TestWith(['other.pos.test', 'That address belongs to the platform itself.'], 'platform subdomain')]
    #[TestWith(['https://pos.example.com/', 'Give a domain name such as pos.example.com'], 'url instead of domain')]
    public function test_add_domain_rejects_an_unusable_address(string $domain, string $message): void
    {
        $this->createTenant('sjtechnology');

        $this->artisan('tenants:add-domain', ['slug' => 'sjtechnology', 'domain' => $domain])
            ->expectsOutputToContain($message)
            ->assertFailed();

        $this->assertSame(0, Domain::count());
    }

    public function test_verify_passes_a_set_up_business_that_matches_the_original(): void
    {
        $original = $this->createTenant('original');
        $copy = $this->createTenant('copy');
        foreach ([$original, $copy] as $tenant) {
            $this->asTenant($tenant, function () {
                $this->app->make(Settings::class)->save(['company.name' => 'SJ Technology']);
                User::create(['username' => 'boss', 'password' => 'secret-password', 'role' => 'owner']);
                Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01', 'subtotal' => 2500]);
            });
        }

        $this->artisan('tenants:verify', ['slug' => 'copy', '--compare' => $original->database])
            ->expectsOutputToContain('No failures.')
            ->assertSuccessful();
    }

    public function test_verify_fails_when_rows_or_totals_differ_from_the_original(): void
    {
        $original = $this->createTenant('original');
        $copy = $this->createTenant('copy');
        foreach ([$original, $copy] as $tenant) {
            $this->asTenant($tenant, function () {
                $this->app->make(Settings::class)->save(['company.name' => 'SJ Technology']);
                User::create(['username' => 'boss', 'password' => 'secret-password', 'role' => 'owner']);
            });
        }
        $this->asTenant($original, fn () => Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01', 'subtotal' => 2500]));

        $this->artisan('tenants:verify', ['slug' => 'copy', '--compare' => $original->database])
            ->expectsOutputToContain('1 before, 0 now')
            ->expectsOutputToContain('check(s) failed.')
            ->assertFailed();
    }

    public function test_verify_fails_a_business_without_details_or_an_owner(): void
    {
        $this->createTenant('empty');

        $this->artisan('tenants:verify', ['slug' => 'empty'])
            ->expectsOutputToContain('2 check(s) failed.')
            ->assertFailed();
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

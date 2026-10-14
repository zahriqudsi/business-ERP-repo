<?php

namespace App\Services\Tenancy;

use App\Models\Landlord\Tenant;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Creates businesses: the landlord row, the business database and its schema.
 */
class TenantProvisioner
{
    /**
     * A database name is built into SQL, so it may only hold these characters.
     */
    private const DATABASE_NAME_PATTERN = '/^[a-z0-9_]{1,64}$/';

    public function __construct(private TenantManager $tenancy) {}

    /**
     * A new business with a new, empty database. On any failure the database is dropped again.
     */
    public function create(string $name, string $slug, ?string $ownerEmail = null): Tenant
    {
        $this->validate($name, $slug);

        $tenant = new Tenant(['name' => $name, 'slug' => $slug, 'status' => 'provisioning', 'owner_email' => $ownerEmail]);
        $tenant->id = Str::lower((string) Str::ulid());
        $tenant->database = config('tenancy.database_prefix').$tenant->id;
        $tenant->save();

        try {
            $this->landlord()->statement("CREATE DATABASE `{$this->databaseName($tenant)}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->migrate($tenant);
        } catch (Throwable $e) {
            $this->dropDatabase($tenant);
            $tenant->update(['status' => 'failed']);

            throw $e;
        }

        $tenant->update(['status' => 'trial', 'trial_ends_at' => now()->addDays(14)]);

        return $tenant;
    }

    /**
     * An existing database (e.g. SJ Technology's) registered as a business, as it is.
     * Its migrations then run: the original ones are guarded with hasTable(), so only new ones change it.
     */
    public function registerExisting(string $name, string $slug, string $database): Tenant
    {
        $this->validate($name, $slug);
        $this->assertSafeName($database);

        $exists = $this->landlord()->selectOne('SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]);
        if (! $exists) {
            throw new InvalidArgumentException("Database `{$database}` does not exist.");
        }
        if (Tenant::withTrashed()->where('database', $database)->exists()) {
            throw new InvalidArgumentException("Database `{$database}` already belongs to a business.");
        }

        $tenant = Tenant::create(['name' => $name, 'slug' => $slug, 'database' => $database, 'status' => 'active']);
        $this->migrate($tenant);

        return $tenant;
    }

    /**
     * Run the business-database migrations against one business.
     */
    public function migrate(Tenant $tenant): string
    {
        return $this->tenancy->run($tenant, function () {
            Artisan::call('migrate', ['--database' => config('database.default'), '--force' => true]);

            return Artisan::output();
        });
    }

    /**
     * Permanently delete a business's database. Used to undo a failed sign-up and to clean up tests.
     */
    public function dropDatabase(Tenant $tenant): void
    {
        $this->landlord()->statement("DROP DATABASE IF EXISTS `{$this->databaseName($tenant)}`");
    }

    private function validate(string $name, string $slug): void
    {
        Validator::make(['name' => $name, 'slug' => $slug], [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:63', 'regex:'.Tenant::SLUG_PATTERN, 'not_in:'.implode(',', config('tenancy.reserved_slugs')), 'unique:landlord.tenants,slug'],
        ], [
            'slug.regex' => 'The subdomain may only use lowercase letters, numbers and hyphens (not at the start or end).',
            'slug.not_in' => 'That subdomain is reserved.',
            'slug.unique' => 'That subdomain is already taken.',
        ])->validate();
    }

    private function databaseName(Tenant $tenant): string
    {
        return $this->assertSafeName($tenant->database);
    }

    private function assertSafeName(string $database): string
    {
        if (! preg_match(self::DATABASE_NAME_PATTERN, $database)) {
            throw new InvalidArgumentException("Unsafe database name `{$database}`.");
        }

        return $database;
    }

    private function landlord(): Connection
    {
        return DB::connection(config('tenancy.landlord_connection'));
    }
}

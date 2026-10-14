<?php

namespace App\Services\Tenancy;

use App\Models\Landlord\Tenant;
use App\Services\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Switches the app to one business: its database, its saved settings and its timezone.
 *
 * The default connection ("mysql") is the business connection, so every model and
 * DB::table() query follows the switch without knowing about tenancy.
 */
class TenantManager
{
    private ?Tenant $current = null;

    private string $connection;

    /**
     * @var array{database: ?string, timezone: string, local_disk_root: string}
     */
    private array $original;

    public function __construct(private Settings $settings)
    {
        $this->connection = (string) config('database.default');
        $this->original = [
            'database' => config("database.connections.{$this->connection}.database"),
            'timezone' => (string) config('app.timezone'),
            'local_disk_root' => (string) config('filesystems.disks.local.root'),
        ];
    }

    /**
     * Where this business keeps its files (logo, backups...): storage/app/tenants/{id}/...,
     * or plain storage/app/... for a single-business install.
     */
    public function storagePath(string $path = ''): string
    {
        $base = $this->current ? 'app/tenants/'.$this->current->getKey() : 'app';

        return storage_path($base.($path === '' ? '' : '/'.ltrim($path, '/')));
    }

    public function current(): ?Tenant
    {
        return $this->current;
    }

    public function initialize(Tenant $tenant): void
    {
        $this->useDatabase($tenant->database);
        $this->useTimezone($tenant->timezone ?: $this->original['timezone']);
        $this->current = $tenant;
        // Storage::put() and friends (e.g. the quotation PDF archive) write into the business's own folder
        $this->useLocalDiskRoot($this->storagePath('private'));

        $this->settings->useScope($tenant->getKey());
        $this->settings->reset();
        $this->settings->apply();
    }

    /**
     * Back to the app's own database and settings (no business selected).
     */
    public function end(): void
    {
        $this->useDatabase($this->original['database']);
        $this->useTimezone($this->original['timezone']);
        $this->current = null;
        $this->useLocalDiskRoot($this->original['local_disk_root']);

        $this->settings->useScope(null);
        $this->settings->reset();
    }

    /**
     * Run $callback as $tenant, then return to whichever business (or none) was selected before.
     *
     * @template TResult
     *
     * @param  callable(Tenant): TResult  $callback
     * @return TResult
     */
    public function run(Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->current;
        $this->initialize($tenant);

        try {
            return $callback($tenant);
        } finally {
            $previous ? $this->initialize($previous) : $this->end();
        }
    }

    private function useDatabase(?string $database): void
    {
        config(["database.connections.{$this->connection}.database" => $database]);
        DB::purge($this->connection);
    }

    private function useLocalDiskRoot(string $root): void
    {
        config(['filesystems.disks.local.root' => $root]);
        Storage::forgetDisk('local');
    }

    private function useTimezone(string $timezone): void
    {
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }
}

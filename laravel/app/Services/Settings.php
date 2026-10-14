<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Business settings saved on the Settings page. Each key is a path under config "sjpos"
 * (e.g. "company.name"), so code keeps reading config('sjpos.company.name') and gets the saved value.
 */
class Settings
{
    private const CACHE_KEY = 'app-settings';

    /**
     * The business whose settings these are (with tenancy on); null for a single-business install.
     */
    private ?string $scope = null;

    /**
     * config('sjpos') as shipped, before any saved values were loaded over it.
     *
     * @var array<string, mixed>
     */
    private array $defaults;

    public function __construct()
    {
        $this->defaults = (array) config('sjpos');
    }

    /**
     * Read and cache the settings of this business from now on.
     */
    public function useScope(?string $tenantId): void
    {
        $this->scope = $tenantId;
    }

    /**
     * Put config('sjpos') back to the shipped defaults, dropping another business's values.
     */
    public function reset(): void
    {
        config(['sjpos' => $this->defaults]);
    }

    /**
     * Saved values, keyed by path. Empty when the settings table doesn't exist yet (before migrating).
     *
     * @return array<string, mixed>
     */
    public function saved(): array
    {
        try {
            return Cache::rememberForever($this->cacheKey(), fn () => Setting::pluck('value', 'key')->all());
        } catch (QueryException) {
            return [];
        }
    }

    /**
     * Load the saved values over the config defaults.
     */
    public function apply(?array $values = null): void
    {
        foreach ($values ?? $this->saved() as $key => $value) {
            config(['sjpos.'.$key => $value]);
        }

        // The address is edited as lines; documents use either the lines or a one-line version
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) config('sjpos.company.address')))));
        config([
            'sjpos.company.address_lines' => $lines,
            'sjpos.company.address' => implode(', ', array_map(fn (string $line) => rtrim($line, ', '), $lines)),
            'sjpos.company.address_multiline' => implode("\n", $lines),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values  path => value
     */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }

        Cache::forget($this->cacheKey());
        $this->apply();
    }

    /**
     * Each business caches its settings under its own key, so one never sees another's.
     */
    private function cacheKey(): string
    {
        return $this->scope === null ? self::CACHE_KEY : "tenant:{$this->scope}:".self::CACHE_KEY;
    }

    /**
     * Whether the owner has entered the business details yet (a fresh install hasn't).
     */
    public function isConfigured(): bool
    {
        return array_key_exists('company.name', $this->saved());
    }
}

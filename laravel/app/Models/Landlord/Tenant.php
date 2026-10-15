<?php

namespace App\Models\Landlord;

use Database\Factories\Landlord\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A business using the platform. Its records live in its own database ($database).
 */
#[Connection('landlord')]
#[Table('tenants')]
#[Fillable(['name', 'slug', 'database', 'status', 'plan_id', 'trial_ends_at', 'owner_email', 'timezone', 'suspended_at', 'data_purge_after'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUSES = ['provisioning', 'trial', 'active', 'past_due', 'suspended', 'cancelled', 'failed'];

    /**
     * Statuses whose users can sign in and work. Past-due businesses keep working until suspended.
     */
    public const USABLE_STATUSES = ['trial', 'active', 'past_due'];

    /**
     * Subdomain rules: letters, digits and inner hyphens, as DNS allows.
     */
    public const SLUG_PATTERN = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/';

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'data_purge_after' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function isUsable(): bool
    {
        return in_array($this->status, self::USABLE_STATUSES, true);
    }

    /**
     * The business's address: its primary custom domain, else {slug}.{base_domain}.
     * Scheme and port follow APP_URL, so local addresses like http://acme.localhost:8000 work too.
     */
    public function url(string $path = '/'): string
    {
        $host = $this->domains()->where('is_primary', true)->value('domain') ?? $this->slug.'.'.config('tenancy.base_domain');
        $appUrl = parse_url((string) config('app.url'));
        $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';

        return ($appUrl['scheme'] ?? 'https').'://'.$host.$port.'/'.ltrim($path, '/');
    }
}

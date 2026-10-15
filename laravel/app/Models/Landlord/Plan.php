<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A subscription plan. Limits are enforced from M5; null limits mean unlimited.
 */
#[Connection('landlord')]
#[Table('plans')]
#[Fillable(['code', 'name', 'price_monthly', 'price_yearly', 'currency', 'limits', 'is_public', 'is_active'])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'price_monthly' => 'float',
            'price_yearly' => 'float',
            'limits' => 'array',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * What each limit is called, for messages and the admin form.
     */
    public const LIMITS = [
        'users' => 'active users',
        'invoices_per_month' => 'invoices a month',
    ];

    /**
     * The plan's cap for $key, or null when it is unlimited.
     */
    public function limit(string $key): ?int
    {
        $value = $this->limits[$key] ?? null;

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}

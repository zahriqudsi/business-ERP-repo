<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The platform's own log: sign-ups, suspensions, plan changes and support sign-ins.
 */
#[Connection('landlord')]
#[Table('platform_activity', timestamps: false)]
#[Fillable(['platform_admin_id', 'tenant_id', 'action', 'description', 'data', 'created_at'])]
class PlatformActivity extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'platform_admin_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function record(string $action, string $description, ?Tenant $tenant = null, ?PlatformAdmin $admin = null, ?array $data = null): void
    {
        static::create([
            'platform_admin_id' => $admin?->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'description' => mb_substr($description, 0, 255),
            'data' => $data,
            'created_at' => now(),
        ]);
    }
}

<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A one-time support sign-in link into a business. Only its SHA-256 hash is stored.
 */
#[Connection('landlord')]
#[Table('impersonation_tokens', timestamps: false)]
#[Fillable(['platform_admin_id', 'tenant_id', 'token_hash', 'expires_at', 'used_at', 'created_at'])]
class ImpersonationToken extends Model
{
    /**
     * Long enough to follow the redirect, short enough that a leaked link is useless.
     */
    public const LIFETIME_SECONDS = 60;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'platform_admin_id');
    }

    /**
     * Make a token for $admin to enter $tenant, returning the plain token (shown once, in the link).
     */
    public static function issue(PlatformAdmin $admin, Tenant $tenant): string
    {
        $plain = Str::random(64);
        static::create([
            'platform_admin_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addSeconds(self::LIFETIME_SECONDS),
            'created_at' => now(),
        ]);

        return $plain;
    }

    /**
     * Use up a token for $tenant: the admin it was made for, or null if unknown, used, expired or for another business.
     */
    public static function redeem(string $plain, Tenant $tenant): ?PlatformAdmin
    {
        $token = static::with('admin')->where('token_hash', hash('sha256', $plain))->where('tenant_id', $tenant->id)->first();
        if (! $token || $token->used_at !== null || $token->expires_at->isPast()) {
            return null;
        }

        // Only the first of two simultaneous requests marks it used
        $claimed = static::whereKey($token->id)->whereNull('used_at')->update(['used_at' => now()]);

        return $claimed === 1 ? $token->admin : null;
    }
}

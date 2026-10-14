<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A custom domain (e.g. pos.sjtechnology.lk) that opens a business, besides its subdomain.
 */
#[Connection('landlord')]
#[Table('domains')]
#[Fillable(['tenant_id', 'domain', 'is_primary', 'verified_at'])]
class Domain extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

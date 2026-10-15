<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment a business made for its plan, recorded by platform staff.
 */
#[Connection('landlord')]
#[Table('tenant_payments', timestamps: false)]
#[Fillable(['tenant_id', 'plan_id', 'platform_admin_id', 'amount', 'currency', 'months', 'method', 'reference', 'note', 'paid_on', 'covers_until', 'created_at'])]
class TenantPayment extends Model
{
    public const METHODS = [
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'card' => 'Card',
        'other' => 'Other',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'months' => 'integer',
            'paid_on' => 'date',
            'covers_until' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'platform_admin_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\LinksParty;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksPayments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('purchases', timestamps: false)]
#[Fillable(['supplier_name', 'purchase_no', 'date', 'subtotal', 'status', 'amount_paid', 'notes'])]
class Purchase extends Model
{
    use LinksParty, LogsActivity, TracksPayments;

    public function activityType(): string
    {
        return 'purchase';
    }

    public function activityLabel(): string
    {
        return "Purchase {$this->display_no} ({$this->supplier_name})";
    }

    /**
     * Same as the column defaults, so new purchases know their status before being reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'confirmed',
        'amount_paid' => 0,
    ];

    protected static function partyLink(): array
    {
        return [Supplier::class, 'supplier_name', 'supplier_id'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public const STATUSES = ['draft', 'confirmed', 'paid', 'cancelled'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'subtotal' => 'float',
            'amount_paid' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentMade::class)->orderByDesc('payment_date');
    }

    public function getDisplayNoAttribute(): string
    {
        return $this->purchase_no ?: 'PO-'.$this->id;
    }
}

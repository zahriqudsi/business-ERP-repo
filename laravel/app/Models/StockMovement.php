<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('stock_movements', timestamps: false)]
#[Fillable(['product_id', 'quantity', 'type', 'source_type', 'source_id', 'note', 'user_id'])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Link to the invoice or purchase that caused this movement, if any.
     */
    public function getSourceUrlAttribute(): ?string
    {
        return match ($this->source_type) {
            'invoice' => route('invoices.show', $this->source_id),
            'purchase' => route('purchases.show', $this->source_id),
            default => null,
        };
    }
}

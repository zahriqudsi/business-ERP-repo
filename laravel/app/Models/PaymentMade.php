<?php

namespace App\Models;

use App\Models\Concerns\LinksParty;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('payments_made', timestamps: false)]
#[Fillable(['purchase_id', 'supplier_name', 'amount', 'payment_date', 'method', 'reference', 'notes'])]
class PaymentMade extends Model
{
    use LinksParty, LogsActivity;

    public function activityType(): string
    {
        return 'payment_made';
    }

    public function activityLabel(): string
    {
        return 'Payment of '.rs($this->amount).' to '.$this->supplier_name;
    }

    protected static function partyLink(): array
    {
        return [Supplier::class, 'supplier_name', 'supplier_id'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'payment_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}

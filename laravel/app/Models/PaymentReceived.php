<?php

namespace App\Models;

use App\Models\Concerns\LinksParty;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('payments_received', timestamps: false)]
#[Fillable(['invoice_id', 'customer_name', 'amount', 'payment_date', 'method', 'reference', 'notes'])]
class PaymentReceived extends Model
{
    use LinksParty, LogsActivity;

    public function activityType(): string
    {
        return 'payment_received';
    }

    public function activityLabel(): string
    {
        return 'Payment of '.rs($this->amount).' from '.$this->customer_name;
    }

    public const METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'cheque' => 'Cheque',
        'online' => 'Online',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'payment_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    protected static function partyLink(): array
    {
        return [Customer::class, 'customer_name', 'customer_id'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

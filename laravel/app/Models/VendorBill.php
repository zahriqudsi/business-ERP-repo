<?php

namespace App\Models;

use App\Models\Concerns\LinksParty;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Table('vendor_bills', timestamps: false)]
#[Fillable(['supplier_name', 'bill_no', 'bill_date', 'due_date', 'amount', 'amount_paid', 'status', 'notes'])]
class VendorBill extends Model
{
    use LinksParty, LogsActivity;

    public function activityType(): string
    {
        return 'bill';
    }

    public function activityLabel(): string
    {
        return "Bill {$this->display_no} ({$this->supplier_name})";
    }

    protected static function partyLink(): array
    {
        return [Supplier::class, 'supplier_name', 'supplier_id'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public const STATUS_BADGES = [
        'unpaid' => 'danger',
        'overdue' => 'warning text-dark',
        'partially_paid' => 'info',
        'paid' => 'success',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'amount' => 'float',
            'amount_paid' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public static function statusFor(float $amount, float $paid, ?string $dueDate): string
    {
        return match (true) {
            $paid >= $amount => 'paid',
            $paid > 0 => 'partially_paid',
            // Due today is not overdue yet (matches the auto-mark on the bills list)
            $dueDate !== null && Carbon::parse($dueDate)->lt(today()) => 'overdue',
            default => 'unpaid',
        };
    }

    public function getBalanceAttribute(): float
    {
        return $this->amount - $this->amount_paid;
    }

    public function getDisplayNoAttribute(): string
    {
        return $this->bill_no ?: 'BILL-'.$this->id;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null && $this->due_date->lt(today()) && $this->status !== 'paid';
    }
}

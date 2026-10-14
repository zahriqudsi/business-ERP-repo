<?php

namespace App\Models;

use App\Models\Concerns\HasTax;
use App\Models\Concerns\LinksParty;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksPayments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[Table('invoices', timestamps: false)]
#[Fillable(['customer_name', 'date', 'invoice_no', 'subtotal', 'net_amount', 'tax_rate', 'tax_amount', 'terms_conditions', 'status', 'amount_paid', 'discount'])]
class Invoice extends Model
{
    use HasTax, LinksParty, LogsActivity, TracksPayments;

    public function activityType(): string
    {
        return 'invoice';
    }

    public function activityLabel(): string
    {
        return "Invoice #{$this->invoice_no} ({$this->customer_name})";
    }

    /**
     * Same as the column defaults, so new invoices know their status before being reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'confirmed',
        'amount_paid' => 0,
    ];

    protected static function partyLink(): array
    {
        return [Customer::class, 'customer_name', 'customer_id'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'net_amount' => 'float',
            'tax_rate' => 'float',
            'tax_amount' => 'float',
            'subtotal' => 'float',
            'amount_paid' => 'float',
            'discount' => 'float',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    public function bankDetail(): HasOne
    {
        return $this->hasOne(BankDetail::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(InvoiceNote::class, 'invoice_no', 'invoice_no');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentReceived::class);
    }

    /**
     * invoice_no is a varchar, so compare numerically rather than as text.
     * With $lock (inside a transaction), concurrent callers wait so they can't take the same number.
     */
    public static function nextNumber(bool $lock = false): int
    {
        $query = static::query();
        if ($lock) {
            $query->lockForUpdate();
        }

        $last = (int) $query->max(DB::raw('CAST(invoice_no AS UNSIGNED)'));

        return max($last + 1, (int) config('sjpos.invoice.first_number'));
    }
}

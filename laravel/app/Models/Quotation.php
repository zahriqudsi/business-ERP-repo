<?php

namespace App\Models;

use App\Models\Concerns\HasTax;
use App\Models\Concerns\LinksParty;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('quotations', timestamps: false)]
#[Fillable(['quotation_no', 'prepared_for', 'prepared_by', 'date', 'validity_date', 'subtotal', 'net_amount', 'tax_rate', 'tax_amount', 'terms_conditions'])]
class Quotation extends Model
{
    use HasTax, LinksParty, LogsActivity;

    public function activityType(): string
    {
        return 'quotation';
    }

    public function activityLabel(): string
    {
        return "Quotation #{$this->quotation_no} ({$this->prepared_for})";
    }

    protected static function partyLink(): array
    {
        return [Customer::class, 'prepared_for', 'customer_id'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function casts(): array
    {
        return [
            'quotation_no' => 'integer',
            'date' => 'date',
            'validity_date' => 'date',
            'subtotal' => 'float',
            'net_amount' => 'float',
            'tax_rate' => 'float',
            'tax_amount' => 'float',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('item_no')->orderBy('id');
    }

    public static function nextNumber(): int
    {
        $last = (int) static::query()->max('quotation_no');

        return $last ? $last + 1 : (int) config('sjpos.quotation.first_number');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\IsParty;
use App\Models\Concerns\LogsActivity;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('customers')]
#[Fillable(['name', 'contact_person', 'phone', 'email', 'address', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, IsParty, LogsActivity;

    public function activityType(): string
    {
        return 'customer';
    }

    public function activityLabel(): string
    {
        return 'Customer '.$this->name;
    }

    /**
     * Document relation => name column printed on that document (kept in step on rename).
     */
    public const DOCUMENTS = [
        'invoices' => 'customer_name',
        'quotations' => 'prepared_for',
        'jobNotes' => 'customer_name',
        'payments' => 'customer_name',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function jobNotes(): HasMany
    {
        return $this->hasMany(JobNote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentReceived::class);
    }
}

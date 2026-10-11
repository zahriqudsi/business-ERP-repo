<?php

namespace App\Models;

use App\Models\Concerns\IsParty;
use App\Models\Concerns\LogsActivity;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('suppliers')]
#[Fillable(['name', 'contact_person', 'phone', 'email', 'address', 'notes'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, IsParty, LogsActivity;

    public function activityType(): string
    {
        return 'supplier';
    }

    public function activityLabel(): string
    {
        return 'Supplier '.$this->name;
    }

    /**
     * Document relation => name column printed on that document (kept in step on rename).
     */
    public const DOCUMENTS = [
        'purchases' => 'supplier_name',
        'bills' => 'supplier_name',
        'payments' => 'supplier_name',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentMade::class);
    }
}

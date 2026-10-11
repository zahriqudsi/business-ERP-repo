<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('activity_logs', timestamps: false)]
#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'description', 'changes', 'ip_address'])]
class ActivityLog extends Model
{
    /**
     * Subject type => [label, route name of its page (or null)].
     */
    public const SUBJECTS = [
        'invoice' => ['Invoice', 'invoices.show'],
        'quotation' => ['Quotation', 'quotations.show'],
        'job' => ['Job note', 'jobs.show'],
        'payment_received' => ['Payment received', null],
        'payment_made' => ['Payment made', null],
        'purchase' => ['Purchase', 'purchases.show'],
        'bill' => ['Vendor bill', 'bills.edit'],
        'product' => ['Product', 'products.show'],
        'customer' => ['Customer', 'customers.show'],
        'supplier' => ['Supplier', 'suppliers.show'],
        'user' => ['User', 'users.edit'],
        'settings' => ['Settings', 'settings.edit'],
        'backup' => ['Backup', 'backups.index'],
    ];

    public const ACTIONS = [
        'created' => ['Created', 'success'],
        'updated' => ['Changed', 'primary'],
        'deleted' => ['Deleted', 'danger'],
        'signed_in' => ['Signed in', 'secondary'],
        'stock_adjusted' => ['Stock adjusted', 'warning'],
        'emailed' => ['Emailed', 'info'],
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Write one entry for the signed-in user (if any).
     *
     * @param  array<string, array{0: mixed, 1: mixed}>|null  $changes
     */
    public static function record(string $action, string $description, ?string $subjectType = null, ?int $subjectId = null, ?array $changes = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => mb_substr($description, 0, 255),
            'changes' => $changes ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Link to the record this entry is about, when it still exists and has a page.
     */
    public function getSubjectUrlAttribute(): ?string
    {
        $route = self::SUBJECTS[$this->subject_type][1] ?? null;
        if (! $route || ($this->action === 'deleted' && $this->subject_id)) {
            return null;
        }

        return in_array($this->subject_type, ['settings', 'backup'], true) ? route($route) : ($this->subject_id ? route($route, $this->subject_id) : null);
    }
}

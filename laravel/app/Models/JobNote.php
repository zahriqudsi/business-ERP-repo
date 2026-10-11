<?php

namespace App\Models;

use App\Models\Concerns\LinksParty;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('job_notes', timestamps: false)]
#[Fillable([
    'job_no', 'customer_name', 'contact_person', 'contact_no', 'email', 'item', 'serial_no', 'fault',
    'accessories_received', 'device_condition', 'remarks', 'collected_date', 'collected_by',
    'terms_conditions', 'customer_signature', 'signed_name', 'signed_at',
])]
class JobNote extends Model
{
    use LinksParty, LogsActivity;

    public function activityType(): string
    {
        return 'job';
    }

    public function activityLabel(): string
    {
        return "Job note {$this->formatted_no} ({$this->customer_name}, {$this->item})";
    }

    protected static function partyLink(): array
    {
        return [Customer::class, 'customer_name', 'customer_id'];
    }

    protected function partyDetails(): array
    {
        return ['contact_person' => $this->contact_person, 'phone' => $this->contact_no, 'email' => $this->email];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function casts(): array
    {
        return [
            'job_no' => 'integer',
            'collected_date' => 'date',
            'signed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public static function formatNumber(int|string $jobNo): string
    {
        return str_pad((string) (int) $jobNo, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Pass $lock inside a transaction so two tablets can't take the same number.
     */
    public static function nextNumber(bool $lock = false): int
    {
        $query = static::query();
        if ($lock) {
            $query->lockForUpdate();
        }

        return max((int) $query->max('job_no') + 1, (int) config('sjpos.job.first_number'));
    }

    /**
     * Validate a signature data URL from the signature pad.
     * Returns the normalised data URL, or null if invalid.
     */
    public static function normaliseSignature(?string $dataUrl): ?string
    {
        $prefix = 'data:image/png;base64,';
        if (! is_string($dataUrl) || ! str_starts_with($dataUrl, $prefix)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strlen($prefix)), true);
        if ($binary === false || strlen($binary) > config('sjpos.job.signature_max_bytes')) {
            return null;
        }

        // PNG magic bytes
        if (! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        return $prefix.base64_encode($binary);
    }

    public function getFormattedNoAttribute(): string
    {
        return static::formatNumber($this->job_no);
    }

    public function getIsSignedAttribute(): bool
    {
        return ! empty($this->customer_signature);
    }
}

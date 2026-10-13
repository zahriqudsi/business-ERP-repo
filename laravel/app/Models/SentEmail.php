<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('sent_emails', timestamps: false)]
#[Fillable(['document_type', 'document_id', 'recipient', 'cc', 'subject', 'user_id'])]
class SentEmail extends Model
{
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Emails sent for one document, newest first.
     *
     * @param  Builder<SentEmail>  $query
     */
    public function scopeFor(Builder $query, string $type, int $id): void
    {
        $query->where('document_type', $type)->where('document_id', $id)->orderByDesc('sent_at')->orderByDesc('id');
    }
}

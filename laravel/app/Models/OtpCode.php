<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('otp_codes', timestamps: false)]
#[Fillable(['user_id', 'otp_hash', 'attempts', 'expires_at'])]
class OtpCode extends Model
{
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}

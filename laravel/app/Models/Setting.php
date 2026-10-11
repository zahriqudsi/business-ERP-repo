<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('settings', key: 'key', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['key', 'value', 'updated_at'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'json',
            'updated_at' => 'datetime',
        ];
    }
}

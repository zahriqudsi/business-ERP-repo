<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Table('users', timestamps: false)]
#[Fillable(['username', 'name', 'email', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    use LogsActivity;

    public function activityType(): string
    {
        return 'user';
    }

    public function activityLabel(): string
    {
        return 'User '.$this->username;
    }

    public const ROLES = [
        'owner' => 'Owner',
        'manager' => 'Manager',
        'staff' => 'Staff',
    ];

    /**
     * What each role can do, shown on the Users page.
     */
    public const ROLE_DESCRIPTIONS = [
        'owner' => 'Everything, including settings, users, backups and the activity log.',
        'manager' => 'Day-to-day work plus reports and deleting records.',
        'staff' => 'Create and edit invoices, quotations, job notes, payments and stock. Cannot delete or see reports.',
    ];

    /**
     * The legacy users table has no remember_token column.
     */
    protected $rememberTokenName = '';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'staff',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->username;
    }
}

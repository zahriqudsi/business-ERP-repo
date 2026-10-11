<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Replaces the legacy create.php / create_user.php / hash.php scripts.
 */
class CreateUser extends Command
{
    protected $signature = 'sjpos:user {username : Login name} {--password= : Password (prompted when omitted)} {--role=owner : owner, manager or staff}';

    protected $description = 'Create a login user, or reset the password of an existing one';

    public function handle(): int
    {
        $username = $this->argument('username');
        $password = $this->option('password') ?: $this->secret('Password');

        if (blank($password)) {
            $this->error('A password is required.');

            return self::FAILURE;
        }

        $role = (string) $this->option('role');
        if (! array_key_exists($role, User::ROLES)) {
            $this->error('The role must be one of: '.implode(', ', array_keys(User::ROLES)).'.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['username' => $username]);
        $existed = $user->exists;
        $user->password = $password; // hashed by the model cast
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        $this->info($existed ? "Password updated for '{$username}'." : "User '{$username}' created.");

        return self::SUCCESS;
    }
}

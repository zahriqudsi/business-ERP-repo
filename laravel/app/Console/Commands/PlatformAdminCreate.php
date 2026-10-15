<?php

namespace App\Console\Commands;

use App\Models\Landlord\PlatformAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Platform staff logins for the admin area; there is no sign-up for them.
 */
class PlatformAdminCreate extends Command
{
    protected $signature = 'platform:admin {email : Sign-in email}
        {--name= : Display name (defaults to the email)}
        {--password= : Password (prompted when omitted)}';

    protected $description = 'Create a platform admin login, or reset the password of an existing one';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = $this->option('password') ?: $this->secret('Password');

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ]);
        if ($validator->fails()) {
            $this->error(implode(' ', $validator->errors()->all()));

            return self::FAILURE;
        }

        $admin = PlatformAdmin::firstOrNew(['email' => $email]);
        $existed = $admin->exists;
        $admin->name = $this->option('name') ?: ($admin->name ?: $email);
        $admin->password = $password;
        $admin->save();

        $this->info($existed ? "Password updated for {$email}." : "Platform admin {$email} created.");

        return self::SUCCESS;
    }
}

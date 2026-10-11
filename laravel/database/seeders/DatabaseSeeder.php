<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Local development only: a throwaway login for trying the app.
     * Real users are created with `php artisan sjpos:user <username>`.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->warn('Skipped: the dev login is only seeded in the local environment.');

            return;
        }

        User::updateOrCreate(['username' => 'devtest'], ['password' => 'vCJj4DRxaeg4', 'role' => 'owner', 'is_active' => true]);
    }
}

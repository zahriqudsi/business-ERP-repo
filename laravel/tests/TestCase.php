<?php

namespace Tests;

use App\Models\User;
use App\Services\Settings;
use Database\Seeders\SjTechnologySettings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Realistic business details, as an existing install has them
        $this->app->make(Settings::class)->apply(SjTechnologySettings::VALUES);
    }

    protected function signIn(string $role = 'owner'): User
    {
        $user = User::create(['username' => 'tester', 'password' => 'secret-password', 'role' => $role]);
        $this->actingAs($user);

        return $user;
    }
}

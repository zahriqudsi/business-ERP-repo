<?php

namespace Tests\Concerns;

use Illuminate\Support\Env;

/**
 * The platform and admin routes are only registered when tenancy is on as the app boots, so these
 * tests switch it on through the environment before the application is created, and back afterwards.
 * Hosts: pos.test (platform; www.pos.test redirects to it), admin.pos.test, and {slug}.pos.test.
 */
trait BootsWithTenancy
{
    use CreatesTenants;

    /**
     * @var array<string, string>
     */
    private array $tenancyEnvironment = [
        'TENANCY_ENABLED' => 'true',
        'TENANCY_BASE_DOMAIN' => 'pos.test',
        'TENANCY_CENTRAL_DOMAINS' => 'pos.test,www.pos.test',
        'TENANCY_ADMIN_DOMAIN' => 'admin.pos.test',
        'APP_URL' => 'http://pos.test',
    ];

    /**
     * @var array<string, string|false>
     */
    private array $environmentBefore = [];

    protected function setUp(): void
    {
        foreach ($this->tenancyEnvironment as $key => $value) {
            $this->environmentBefore[$key] = getenv($key);
            $this->setEnvironment($key, $value);
        }
        // The env reader is shared by every test and overwrites variables it once loaded from .env
        // (e.g. a developer's APP_URL); a fresh reader treats the values above as set from outside.
        Env::enablePutenv();

        parent::setUp();
        $this->setUpTenancy();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->environmentBefore as $key => $value) {
            $this->setEnvironment($key, $value);
        }
        Env::enablePutenv();
    }

    private function setEnvironment(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            return;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

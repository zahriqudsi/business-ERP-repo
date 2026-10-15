<?php

namespace Tests\Feature;

use App\Models\Landlord\PlatformAdmin;
use App\Services\Alerts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Tests\Concerns\BootsWithTenancy;
use Tests\TestCase;

/**
 * Failure alerts to the people who run the app, and the /up health check.
 */
class MonitoringTest extends TestCase
{
    use BootsWithTenancy;

    /**
     * Emails the "array" mailer (used in tests) has sent so far.
     *
     * @return list<SentMessage>
     */
    private function sentEmails(): array
    {
        return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all();
    }

    public function test_a_failed_background_job_emails_the_platform_admins(): void
    {
        PlatformAdmin::create(['name' => 'Zahri', 'email' => 'zahri@platform.test', 'password' => 'admin-password-123']);
        config(['monitoring.alert_emails' => ['ops@platform.test']]);
        dispatch(fn () => throw new RuntimeException('Mail server unreachable'))->onConnection('database');

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 1]);

        $emails = $this->sentEmails();
        $this->assertCount(1, $emails);
        $message = $emails[0]->getOriginalMessage();
        $this->assertSame('[Billora] A background job failed', $message->getSubject());
        $this->assertEqualsCanonicalizing(['ops@platform.test', 'zahri@platform.test'], array_map(fn ($address) => $address->getAddress(), $message->getTo()));
        $this->assertStringContainsString('Mail server unreachable', $message->getTextBody());
    }

    public function test_the_same_alert_is_sent_once_per_throttle_period(): void
    {
        config(['monitoring.alert_emails' => ['ops@platform.test']]);
        $alerts = $this->app->make(Alerts::class);

        $alerts->send('The nightly backup failed', 'first');
        $alerts->send('The nightly backup failed', 'second');

        $this->assertCount(1, $this->sentEmails());
    }

    public function test_tenants_run_carries_on_when_one_business_fails(): void
    {
        $broken = $this->createTenant('broken');
        $working = $this->createTenant('working');
        DB::statement("DROP DATABASE `{$broken->database}`");

        $this->artisan('tenants:run', ['line' => 'sjpos:backup'])->assertFailed();

        $this->assertCount(1, File::files(storage_path("app/tenants/{$working->id}/backups")));
    }

    public function test_the_health_check_passes_when_the_databases_are_reachable(): void
    {
        $this->get('http://pos.test/up')->assertOk();
    }

    public function test_the_health_check_fails_when_the_platform_database_is_unreachable(): void
    {
        $landlord = config('database.connections.landlord.database');
        config(['database.connections.landlord.database' => 'sjpos_phpunit_missing_landlord']);
        DB::purge('landlord');

        try {
            $this->get('http://pos.test/up')->assertServerError();
        } finally {
            config(['database.connections.landlord.database' => $landlord]);
            DB::purge('landlord');
        }
    }
}

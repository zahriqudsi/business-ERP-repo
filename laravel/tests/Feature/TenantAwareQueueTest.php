<?php

namespace Tests\Feature;

use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BootsWithTenancy;
use Tests\TestCase;

/**
 * Background jobs run as the business they were queued in, whatever the worker handled before.
 */
class TenantAwareQueueTest extends TestCase
{
    use BootsWithTenancy;

    public function test_a_queued_job_runs_as_its_business_and_the_worker_goes_back_afterwards(): void
    {
        $alpha = $this->createTenant('alpha');
        $beta = $this->createTenant('beta');
        $tenancy = $this->app->make(TenantManager::class);
        // A statement, so the pending dispatch is sent while still inside Alpha
        $tenancy->run($alpha, function () {
            dispatch(fn () => Cache::put('job.database', DB::connection()->getDatabaseName()))->onConnection('database');
        });
        $tenancy->initialize($beta);

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

        $this->assertSame($alpha->database, Cache::get('job.database'));
        $this->assertSame($beta->id, $tenancy->current()?->id);
    }

    public function test_a_job_run_straight_away_in_its_own_business_leaves_the_open_transaction_alone(): void
    {
        $alpha = $this->createTenant('alpha');
        $tenancy = $this->app->make(TenantManager::class);
        $tenancy->initialize($alpha);

        DB::transaction(function () {
            dispatch_sync(fn () => Cache::put('job.ran', true));

            $this->assertSame(1, DB::transactionLevel());
        });

        $this->assertTrue(Cache::get('job.ran'));
        $this->assertSame($alpha->id, $tenancy->current()?->id);
    }
}

<?php

namespace App\Services\Tenancy;

use App\Models\Landlord\Tenant;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * Jobs remember the business they were queued in and run as that business, whichever business
 * (or none) the worker handled before. A job that runs straight away (sync) in its own business
 * changes nothing, so it can't disturb the request or an open transaction.
 */
class TenantAwareQueue
{
    /**
     * For each running job: the business to go back to afterwards, or false when nothing was switched.
     *
     * @var list<Tenant|null|false>
     */
    private array $previous = [];

    public function __construct(private TenantManager $tenancy) {}

    public function register(): void
    {
        Queue::createPayloadUsing(fn () => ($tenant = $this->tenancy->current()) ? ['tenant_id' => $tenant->getKey()] : []);

        Event::listen(JobProcessing::class, fn (JobProcessing $event) => $this->enter($event->job->payload()['tenant_id'] ?? null));
        Event::listen(JobProcessed::class, fn () => $this->leave());
        Event::listen(JobExceptionOccurred::class, fn () => $this->leave());
    }

    private function enter(?string $tenantId): void
    {
        $current = $this->tenancy->current();
        if ($tenantId === $current?->getKey()) {
            $this->previous[] = false;

            return;
        }

        $this->previous[] = $current;
        $tenant = $tenantId ? Tenant::find($tenantId) : null;
        $tenant ? $this->tenancy->initialize($tenant) : $this->tenancy->end();
    }

    private function leave(): void
    {
        $previous = array_pop($this->previous);
        if ($previous === false) {
            return;
        }

        $previous ? $this->tenancy->initialize($previous) : $this->tenancy->end();
    }
}

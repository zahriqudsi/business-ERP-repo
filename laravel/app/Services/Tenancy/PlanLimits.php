<?php

namespace App\Services\Tenancy;

use App\Models\Invoice;
use App\Models\Landlord\Plan;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The current business's plan limits. A business without a plan (or a single-business install)
 * is unlimited. Reaching a limit blocks adding more; what already exists keeps working.
 */
class PlanLimits
{
    public function __construct(private TenantManager $tenancy) {}

    public function limit(string $key): ?int
    {
        return $this->tenancy->current()?->plan?->limit($key);
    }

    public function usage(string $key): int
    {
        return match ($key) {
            'users' => User::where('is_active', true)->count(),
            // Invoices have no creation time, so the month is the invoice date's month
            'invoices_per_month' => Invoice::whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->count(),
        };
    }

    /**
     * Whether one more can be added.
     */
    public function allows(string $key): bool
    {
        $limit = $this->limit($key);

        return $limit === null || $this->usage($key) < $limit;
    }

    /**
     * Refuse adding one more, with a message the user can act on, when the plan's limit is reached.
     */
    public function ensureAllows(string $key, string $field): void
    {
        if (! $this->allows($key)) {
            throw ValidationException::withMessages([
                $field => "Your plan allows {$this->limit($key)} ".Plan::LIMITS[$key].'. Ask the owner to upgrade the plan to add more.',
            ]);
        }
    }

    /**
     * Usage against every limit, for the Plan & billing page.
     *
     * @return array<string, array{label: string, used: int, limit: ?int}>
     */
    public function summary(): array
    {
        return collect(Plan::LIMITS)
            ->map(fn (string $label, string $key) => ['label' => $label, 'used' => $this->usage($key), 'limit' => $this->limit($key)])
            ->all();
    }
}

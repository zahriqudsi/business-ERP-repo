<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Landlord\Plan;
use App\Models\Landlord\Tenant;
use App\Models\User;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * Reaching a plan limit blocks adding more; what already exists keeps working.
 */
class PlanLimitsTest extends TestCase
{
    use CreatesTenants;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenancy();
    }

    /**
     * @param  array<string, int|null>  $limits
     */
    private function tenantOnPlan(array $limits): Tenant
    {
        $tenant = $this->createTenant('alpha');
        $plan = Plan::create(['code' => 'starter', 'name' => 'Starter', 'limits' => $limits]);
        $tenant->update(['plan_id' => $plan->id]);
        $this->createUser($tenant, 'alice');
        $this->signInOn($tenant, 'alice');

        return $tenant;
    }

    /**
     * @return array<string, string>
     */
    private function newUser(string $username): array
    {
        return ['username' => $username, 'role' => 'staff', 'password' => 'secret-password', 'password_confirmation' => 'secret-password'];
    }

    public function test_the_user_limit_blocks_adding_one_more_user(): void
    {
        $tenant = $this->tenantOnPlan(['users' => 2]);
        $this->post('http://alpha.pos.test/users', $this->newUser('bob'))->assertSessionHasNoErrors();

        $this->post('http://alpha.pos.test/users', $this->newUser('carol'))
            ->assertSessionHasErrors(['username' => 'Your plan allows 2 active users. Ask the owner to upgrade the plan to add more.']);

        $this->assertSame(2, $this->asTenant($tenant, fn () => User::count()));
    }

    public function test_switching_a_user_back_on_counts_against_the_limit(): void
    {
        $tenant = $this->tenantOnPlan(['users' => 1]);
        $bob = $this->asTenant($tenant, fn () => User::create(['username' => 'bob', 'password' => 'secret-password', 'role' => 'staff', 'is_active' => false]));

        $this->put("http://alpha.pos.test/users/{$bob->id}", ['username' => 'bob', 'role' => 'staff', 'is_active' => '1'])
            ->assertSessionHasErrors(['is_active' => 'Your plan allows 1 active users. Ask the owner to upgrade the plan to add more.']);

        $this->assertFalse($this->asTenant($tenant, fn () => User::where('username', 'bob')->value('is_active')));
    }

    public function test_the_monthly_invoice_limit_blocks_the_next_invoice(): void
    {
        $this->travelTo('2026-10-15 10:00:00');
        $tenant = $this->tenantOnPlan(['invoices_per_month' => 1]);
        $this->asTenant($tenant, fn () => Invoice::create(['invoice_no' => '1', 'customer_name' => 'Acme', 'date' => '2026-10-02', 'subtotal' => 100]));

        $this->postJson('http://alpha.pos.test/invoices', [
            'invoice_no' => '2', 'customer_name' => 'Acme', 'date' => '2026-10-15',
            'description' => ['Repair'], 'quantity' => [1], 'unit_price' => [500],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'invoice_no' => 'Your plan allows 1 invoices a month. Ask the owner to upgrade the plan to add more.',
        ]);

        $this->assertSame(1, $this->asTenant($tenant, fn () => Invoice::count()));
    }

    public function test_invoices_from_earlier_months_do_not_count(): void
    {
        $this->travelTo('2026-10-15 10:00:00');
        $tenant = $this->tenantOnPlan(['invoices_per_month' => 1]);
        $this->asTenant($tenant, fn () => Invoice::create(['invoice_no' => '1', 'customer_name' => 'Acme', 'date' => '2026-09-30', 'subtotal' => 100]));

        $this->postJson('http://alpha.pos.test/invoices', [
            'invoice_no' => '2', 'customer_name' => 'Acme', 'date' => '2026-10-15',
            'description' => ['Repair'], 'quantity' => [1], 'unit_price' => [500],
        ])->assertOk();

        $this->assertSame(2, $this->asTenant($tenant, fn () => Invoice::count()));
    }

    public function test_a_business_without_a_plan_has_no_limits(): void
    {
        $tenant = $this->createTenant('alpha');
        $this->createUser($tenant, 'alice');
        $this->signInOn($tenant, 'alice');

        $this->post('http://alpha.pos.test/users', $this->newUser('bob'))->assertSessionHasNoErrors();
        $this->post('http://alpha.pos.test/users', $this->newUser('carol'))->assertSessionHasNoErrors();

        $this->assertSame(3, $this->asTenant($tenant, fn () => User::count()));
    }
}

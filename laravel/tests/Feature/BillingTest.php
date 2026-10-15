<?php

namespace Tests\Feature;

use App\Models\Landlord\Plan;
use App\Models\Landlord\PlatformActivity;
use App\Models\Landlord\PlatformAdmin;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TenantPayment;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Concerns\BootsWithTenancy;
use Tests\TestCase;

/**
 * Manual billing: payments staff record, the daily check, closing accounts, and the owner's billing page.
 */
class BillingTest extends TestCase
{
    use BootsWithTenancy;

    private function admin(): PlatformAdmin
    {
        return PlatformAdmin::create(['name' => 'Zahri', 'email' => 'zahri@platform.test', 'password' => 'admin-password-123']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(array $overrides = []): array
    {
        return $overrides + ['amount' => 7500, 'months' => 3, 'method' => 'bank_transfer', 'reference' => 'TRX-1', 'paid_on' => '2026-10-15'];
    }

    public function test_recording_a_payment_ends_the_trial_and_extends_paid_time_from_today(): void
    {
        $this->travelTo('2026-10-15 09:00:00');
        $tenant = Tenant::factory()->create(['status' => 'trial', 'trial_ends_at' => '2026-10-20']);

        $this->actingAs($this->admin(), 'platform')
            ->post("http://admin.pos.test/businesses/{$tenant->id}/payments", $this->payment())
            ->assertSessionHas('success', "Payment recorded. {$tenant->name} is paid until 15 Jan 2027.");

        $tenant->refresh();
        $this->assertSame(['active', '2027-01-15'], [$tenant->status, $tenant->paid_until->toDateString()]);
        $this->assertDatabaseHas('tenant_payments', ['tenant_id' => $tenant->id, 'amount' => 7500, 'months' => 3, 'reference' => 'TRX-1'], 'landlord');
        $this->assertTrue(PlatformActivity::where('tenant_id', $tenant->id)->where('action', 'payment_recorded')->exists());
    }

    public function test_a_payment_extends_from_the_end_of_time_already_paid_for(): void
    {
        $this->travelTo('2026-10-15 09:00:00');
        $tenant = Tenant::factory()->create(['paid_until' => '2026-12-01 00:00:00']);

        $this->actingAs($this->admin(), 'platform')
            ->post("http://admin.pos.test/businesses/{$tenant->id}/payments", $this->payment(['months' => 1]));

        $this->assertSame('2027-01-01', $tenant->refresh()->paid_until->toDateString());
    }

    #[TestWith(['billing', 'active'], 'paused for not paying')]
    #[TestWith(['manual', 'suspended'], 'paused by staff')]
    public function test_a_payment_only_resumes_a_business_paused_for_not_paying(string $reason, string $statusAfter): void
    {
        $tenant = Tenant::factory()->suspended()->create(['suspended_reason' => $reason]);

        $this->actingAs($this->admin(), 'platform')
            ->post("http://admin.pos.test/businesses/{$tenant->id}/payments", $this->payment());

        $this->assertSame($statusAfter, $tenant->refresh()->status);
    }

    public function test_the_daily_check_makes_an_expired_trial_past_due_then_pauses_it_after_the_grace_period(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'trial', 'trial_ends_at' => '2026-10-10 00:00:00']);

        $this->travelTo('2026-10-11 01:00:00');
        $this->artisan('tenants:check-billing')->expectsOutputToContain('Past due: 1. Paused: 0.')->assertSuccessful();
        $this->assertSame('past_due', $tenant->refresh()->status);

        $this->travelTo('2026-10-18 01:00:00');
        $this->artisan('tenants:check-billing')->expectsOutputToContain('Past due: 0. Paused: 1.')->assertSuccessful();
        $tenant->refresh();
        $this->assertSame(['suspended', 'billing'], [$tenant->status, $tenant->suspended_reason]);
    }

    public function test_the_daily_check_leaves_paid_and_never_billed_businesses_alone(): void
    {
        $this->travelTo('2026-10-15 01:00:00');
        $paid = Tenant::factory()->create(['paid_until' => '2026-11-01 00:00:00']);
        $neverBilled = Tenant::factory()->create(['trial_ends_at' => null, 'paid_until' => null]);

        $this->artisan('tenants:check-billing')->assertSuccessful();

        $this->assertSame(['active', 'active'], [$paid->refresh()->status, $neverBilled->refresh()->status]);
    }

    public function test_closing_an_account_keeps_its_data_until_the_retention_date(): void
    {
        $this->travelTo('2026-10-15 09:00:00');
        $tenant = Tenant::factory()->create(['slug' => 'alpha', 'name' => 'Alpha Ltd']);

        $this->actingAs($this->admin(), 'platform')->post("http://admin.pos.test/businesses/{$tenant->id}/close");

        $tenant->refresh();
        $this->assertSame(['cancelled', '2027-01-13'], [$tenant->status, $tenant->data_purge_after->toDateString()]);
        $this->get('http://alpha.pos.test/login')->assertForbidden()->assertSee('This account is closed')->assertSee('13 January 2027');
    }

    public function test_an_admin_downloads_a_backup_of_a_business(): void
    {
        $tenant = $this->createTenant('alpha');

        $response = $this->actingAs($this->admin(), 'platform')->post("http://admin.pos.test/businesses/{$tenant->id}/backup");

        $response->assertOk();
        $this->assertStringContainsString('filename=alpha-backup-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString("-- {$tenant->database} backup", gzdecode(file_get_contents($response->getFile()->getPathname())));
    }

    public function test_the_admin_business_page_shows_its_plan_paid_time_and_payments(): void
    {
        $plan = Plan::create(['code' => 'starter', 'name' => 'Starter', 'price_monthly' => 2500]);
        $tenant = Tenant::factory()->create(['name' => 'Alpha Ltd', 'plan_id' => $plan->id, 'paid_until' => '2027-01-01 00:00:00']);
        TenantPayment::create(['tenant_id' => $tenant->id, 'amount' => 7500, 'months' => 3, 'method' => 'cash', 'reference' => 'RCPT-9', 'paid_on' => '2026-10-01', 'covers_until' => '2027-01-01']);

        $this->actingAs($this->admin(), 'platform')
            ->get("http://admin.pos.test/businesses/{$tenant->id}")
            ->assertOk()
            ->assertSee('Alpha Ltd')
            ->assertSee('Starter')
            ->assertSee('1 Jan 2027')
            ->assertSee('LKR 7,500.00')
            ->assertSee('RCPT-9');
    }

    public function test_an_admin_creates_a_plan_with_limits(): void
    {
        $this->actingAs($this->admin(), 'platform')->post('http://admin.pos.test/plans', [
            'code' => 'starter', 'name' => 'Starter', 'price_monthly' => 2500, 'price_yearly' => 25000, 'currency' => 'lkr',
            'limits' => ['users' => 3, 'invoices_per_month' => ''], 'is_active' => '1', 'is_public' => '1',
        ])->assertRedirect('http://admin.pos.test/plans');

        $plan = Plan::where('code', 'starter')->firstOrFail();
        $this->assertSame(['LKR', 3, null], [$plan->currency, $plan->limit('users'), $plan->limit('invoices_per_month')]);
    }

    public function test_the_owner_sees_the_plan_its_usage_and_payments(): void
    {
        $tenant = $this->createTenant('alpha');
        $plan = Plan::create(['code' => 'starter', 'name' => 'Starter', 'price_monthly' => 2500, 'limits' => ['users' => 3]]);
        $tenant->update(['plan_id' => $plan->id]);
        TenantPayment::create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'amount' => 7500, 'months' => 3, 'method' => 'bank_transfer', 'paid_on' => '2026-10-01', 'covers_until' => '2027-01-01']);
        config(['tenancy.billing.payment_instructions' => 'Pay to HNB 12345']);
        $this->createUser($tenant, 'alice');
        $this->signInOn($tenant, 'alice');

        $this->get('http://alpha.pos.test/billing')
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('1 / 3')
            ->assertSee('LKR 7,500.00')
            ->assertSee('Pay to HNB 12345');
    }

    public function test_staff_cannot_open_the_billing_page(): void
    {
        $tenant = $this->createTenant('alpha');
        $this->createUser($tenant, 'sam', 'staff');
        $this->signInOn($tenant, 'sam');

        $this->get('http://alpha.pos.test/billing')->assertForbidden();
    }

    public function test_the_owner_is_reminded_when_the_trial_ends(): void
    {
        $this->travelTo('2026-10-15 09:00:00');
        $tenant = $this->createTenant('alpha');
        $tenant->update(['trial_ends_at' => '2026-10-20 09:00:00']);
        $this->createUser($tenant, 'alice');
        $this->signInOn($tenant, 'alice');

        $this->get('http://alpha.pos.test/')->assertOk()->assertSee('Your free trial ends on')->assertSee('20 Oct 2026')->assertSee('(5 days left)');
    }

    public function test_new_sign_ups_start_on_the_configured_plan(): void
    {
        $plan = Plan::create(['code' => 'starter', 'name' => 'Starter', 'limits' => ['users' => 3]]);
        config(['tenancy.billing.signup_plan' => 'starter']);

        $this->post('http://pos.test/signup', [
            'business_name' => 'Acme Traders', 'slug' => 'acme', 'name' => 'Ann', 'username' => 'ann',
            'email' => 'ann@acme.lk', 'password' => 'secret-password', 'password_confirmation' => 'secret-password',
        ])->assertRedirect('http://acme.pos.test/login');

        $this->assertSame($plan->id, Tenant::where('slug', 'acme')->value('plan_id'));
    }
}

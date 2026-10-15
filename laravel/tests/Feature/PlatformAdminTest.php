<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Landlord\ImpersonationToken;
use App\Models\Landlord\Plan;
use App\Models\Landlord\PlatformActivity;
use App\Models\Landlord\PlatformAdmin;
use App\Models\Landlord\Tenant;
use App\Models\User;
use Tests\Concerns\BootsWithTenancy;
use Tests\TestCase;

/**
 * The platform admin area on admin.pos.test, and support sign-ins into businesses.
 */
class PlatformAdminTest extends TestCase
{
    use BootsWithTenancy;

    private function admin(): PlatformAdmin
    {
        return PlatformAdmin::create(['name' => 'Zahri', 'email' => 'zahri@platform.test', 'password' => 'admin-password-123']);
    }

    public function test_a_guest_is_sent_to_the_admin_sign_in(): void
    {
        $this->get('http://admin.pos.test/')->assertRedirect('http://admin.pos.test/login');
    }

    public function test_an_admin_signs_in_and_sees_every_business(): void
    {
        $this->admin();
        Tenant::factory()->create(['name' => 'Alpha Ltd']);
        Tenant::factory()->create(['name' => 'Beta Ltd']);

        $this->post('http://admin.pos.test/login', ['email' => 'zahri@platform.test', 'password' => 'admin-password-123'])
            ->assertRedirect('http://admin.pos.test');

        $this->get('http://admin.pos.test/')->assertOk()->assertSee('Alpha Ltd')->assertSee('Beta Ltd');
    }

    public function test_a_business_login_does_not_open_the_admin_area(): void
    {
        $tenant = $this->createTenant('alpha');
        $this->asTenant($tenant, fn () => User::create(['username' => 'ann', 'email' => 'ann@alpha.lk', 'password' => 'secret-password', 'role' => 'owner']));

        $this->post('http://admin.pos.test/login', ['email' => 'ann@alpha.lk', 'password' => 'secret-password'])
            ->assertSessionHasErrors(['email' => 'Wrong email or password.']);

        $this->assertGuest('platform');
    }

    public function test_pausing_a_business_stops_it_opening_and_resuming_brings_it_back(): void
    {
        $admin = $this->admin();
        $tenant = Tenant::factory()->create(['slug' => 'alpha', 'name' => 'Alpha Ltd']);

        $this->actingAs($admin, 'platform')->post("http://admin.pos.test/businesses/{$tenant->id}/suspend")->assertRedirect();

        $this->assertSame('suspended', $tenant->refresh()->status);
        $this->get('http://alpha.pos.test/login')->assertForbidden()->assertSee('This account is paused');
        $this->assertTrue(PlatformActivity::where('tenant_id', $tenant->id)->where('action', 'suspended')->where('platform_admin_id', $admin->id)->exists());

        $this->post("http://admin.pos.test/businesses/{$tenant->id}/resume")->assertRedirect();

        $this->assertSame('active', $tenant->refresh()->status);
        $this->assertNull($tenant->suspended_at);
    }

    public function test_an_admin_changes_a_business_plan(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::create(['code' => 'starter', 'name' => 'Starter', 'price_monthly' => 2500]);

        $this->actingAs($this->admin(), 'platform')
            ->put("http://admin.pos.test/businesses/{$tenant->id}/plan", ['plan_id' => $plan->id])
            ->assertSessionHas('info', "{$tenant->name} is now on Starter.");

        $this->assertSame($plan->id, $tenant->refresh()->plan_id);
    }

    public function test_sign_in_as_owner_opens_the_business_once_and_is_logged_on_both_sides(): void
    {
        $admin = $this->admin();
        $tenant = $this->createTenant('alpha');
        $owner = $this->createUser($tenant, 'ann');

        $link = $this->actingAs($admin, 'platform')
            ->post("http://admin.pos.test/businesses/{$tenant->id}/sign-in")
            ->assertRedirectContains('http://alpha.pos.test/support/')
            ->headers->get('Location');

        $this->get($link)->assertRedirect('http://alpha.pos.test');

        $this->assertAuthenticatedAs($owner);
        $this->assertTrue($this->asTenant($tenant, fn () => ActivityLog::where('description', 'Support (Zahri) signed in as ann')->exists()));
        $this->assertTrue(PlatformActivity::where('tenant_id', $tenant->id)->where('action', 'support_sign_in')->exists());

        $this->post('http://alpha.pos.test/logout');
        $this->get($link)->assertNotFound();
        $this->assertGuest();
    }

    public function test_an_expired_sign_in_link_is_refused(): void
    {
        $tenant = $this->createTenant('alpha');
        $this->createUser($tenant, 'ann');
        $token = ImpersonationToken::issue($this->admin(), $tenant);

        $this->travel(ImpersonationToken::LIFETIME_SECONDS + 1)->seconds();

        $this->get("http://alpha.pos.test/support/{$token}")->assertNotFound();
        $this->assertGuest();
    }

    public function test_a_sign_in_link_only_works_for_its_own_business(): void
    {
        $alpha = $this->createTenant('alpha');
        $beta = $this->createTenant('beta');
        $this->createUser($beta, 'bob');
        $token = ImpersonationToken::issue($this->admin(), $alpha);

        $this->get("http://beta.pos.test/support/{$token}")->assertNotFound();
        $this->assertGuest();
    }

    public function test_the_command_creates_an_admin_and_rejects_a_short_password(): void
    {
        $this->artisan('platform:admin', ['email' => 'staff@platform.test', '--name' => 'Staff', '--password' => 'short'])
            ->expectsOutputToContain('must be at least 12 characters')
            ->assertFailed();

        $this->artisan('platform:admin', ['email' => 'staff@platform.test', '--name' => 'Staff', '--password' => 'long-enough-password'])
            ->assertSuccessful();

        $this->assertSame('Staff', PlatformAdmin::where('email', 'staff@platform.test')->value('name'));
    }
}

<?php

namespace Tests\Feature;

use App\Mail\FindWorkspaceMail;
use App\Mail\WelcomeMail;
use App\Models\Landlord\PlatformActivity;
use App\Models\Landlord\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BootsWithTenancy;
use Tests\TestCase;

/**
 * The platform's own pages on its main domain: landing, sign-up and "find my business".
 */
class SignupTest extends TestCase
{
    use BootsWithTenancy;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'business_name' => 'Acme Traders',
            'slug' => 'acme',
            'name' => 'Ann Perera',
            'username' => 'ann',
            'email' => 'ann@acme.lk',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'accept_terms' => '1',
        ];
    }

    public function test_landing_page_shows_on_the_platform_domain(): void
    {
        $this->get('http://pos.test/')->assertOk()->assertSee('Start your free trial');
    }

    public function test_other_platform_hosts_redirect_to_the_main_one(): void
    {
        $this->get('http://www.pos.test/signup')->assertRedirect('http://pos.test/signup')->assertStatus(301);
    }

    public function test_business_pages_still_open_on_business_hosts(): void
    {
        $this->createTenant('alpha');

        $this->get('http://alpha.pos.test/')->assertRedirect('http://alpha.pos.test/login');
    }

    public function test_signup_creates_a_business_its_owner_can_sign_in_to(): void
    {
        Mail::fake();

        $this->post('http://pos.test/signup', $this->payload())->assertRedirect('http://acme.pos.test/login');

        $tenant = Tenant::where('slug', 'acme')->firstOrFail();
        $this->assertSame('trial', $tenant->status);
        $this->assertSame('ann@acme.lk', $tenant->owner_email);
        $this->asTenant($tenant, function () {
            $owner = User::where('username', 'ann')->firstOrFail();
            $this->assertSame(['owner', 'ann@acme.lk', 'Ann Perera'], [$owner->role, $owner->email, $owner->name]);
            $this->assertSame('Acme Traders', config('sjpos.company.name'));
        });
        Mail::assertQueued(WelcomeMail::class, fn (WelcomeMail $mail) => $mail->hasTo('ann@acme.lk') && $mail->loginUrl === 'http://acme.pos.test/login');
        $this->assertTrue(PlatformActivity::where('tenant_id', $tenant->id)->where('action', 'signed_up')->exists());

        $this->signInOn($tenant, 'ann');
    }

    public function test_signup_requires_accepting_the_terms(): void
    {
        Mail::fake();
        $payload = $this->payload();
        unset($payload['accept_terms']);

        $this->post('http://pos.test/signup', $payload)
            ->assertSessionHasErrors(['accept_terms' => 'Please accept the Terms of Service and Privacy Policy to sign up.']);

        $this->assertSame(0, Tenant::count());
        Mail::assertNothingOutgoing();
    }

    public function test_signup_records_when_the_terms_were_accepted(): void
    {
        Mail::fake();
        $this->freezeTime();

        $this->post('http://pos.test/signup', $this->payload());

        $this->assertSame(now()->toIso8601String(), PlatformActivity::where('action', 'signed_up')->firstOrFail()->data['terms_accepted_at']);
    }

    public function test_terms_and_privacy_pages_name_the_operator(): void
    {
        config(['tenancy.operator.name' => 'SJ Technology (Pvt) Ltd']);

        $this->get('http://pos.test/terms')->assertOk()->assertSee('Terms of Service')->assertSee('SJ Technology (Pvt) Ltd');
        $this->get('http://pos.test/privacy')->assertOk()->assertSee('Privacy Policy')->assertSee('SJ Technology (Pvt) Ltd');
    }

    public function test_signup_rejects_a_reserved_subdomain(): void
    {
        Mail::fake();

        $this->post('http://pos.test/signup', $this->payload(['slug' => 'admin']))
            ->assertSessionHasErrors(['slug' => 'That subdomain is reserved.']);

        $this->assertSame(0, Tenant::count());
        Mail::assertNothingOutgoing();
    }

    public function test_signup_rejects_a_subdomain_already_taken(): void
    {
        $this->createTenant('acme');

        $this->post('http://pos.test/signup', $this->payload())
            ->assertSessionHasErrors(['slug' => 'That subdomain is already taken.']);

        $this->assertSame(1, Tenant::count());
    }

    public function test_find_emails_the_owner_the_addresses_of_their_businesses(): void
    {
        Mail::fake();
        $this->createTenant('acme')->update(['owner_email' => 'ann@acme.lk']);

        $this->post('http://pos.test/find', ['email' => 'ann@acme.lk'])
            ->assertSessionHas('success', 'If that email address owns any businesses, we have sent their addresses to it.');

        Mail::assertQueued(FindWorkspaceMail::class, fn (FindWorkspaceMail $mail) => $mail->hasTo('ann@acme.lk')
            && $mail->workspaces === [['name' => 'Acme Ltd', 'url' => 'http://acme.pos.test/login']]);
    }

    public function test_find_with_an_unknown_email_sends_nothing_and_reveals_nothing(): void
    {
        Mail::fake();
        $this->createTenant('acme')->update(['owner_email' => 'ann@acme.lk']);

        $this->post('http://pos.test/find', ['email' => 'someone@else.lk'])
            ->assertSessionHas('success', 'If that email address owns any businesses, we have sent their addresses to it.');

        Mail::assertNothingOutgoing();
    }

    public function test_a_paused_business_shows_a_notice_instead_of_its_pages(): void
    {
        Tenant::factory()->suspended()->create(['slug' => 'gone', 'name' => 'Gone Ltd']);

        $this->get('http://gone.pos.test/login')
            ->assertForbidden()
            ->assertSee('This account is paused')
            ->assertSee('Gone Ltd');
    }
}

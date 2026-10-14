<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Landlord\Domain;
use App\Models\Landlord\Tenant;
use App\Services\Settings;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * One business must never see or reach another's data. Each business here has a real database.
 */
class TenancyTest extends TestCase
{
    use CreatesTenants;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenancy();
    }

    private function invoice(Tenant $tenant, string $customerName): Invoice
    {
        return $this->asTenant($tenant, fn () => Invoice::create([
            'invoice_no' => '1500', 'customer_name' => $customerName, 'date' => '2026-10-01',
            'subtotal' => 10000, 'status' => 'confirmed', 'amount_paid' => 0,
        ]));
    }

    public function test_the_same_invoice_id_opens_each_business_own_invoice(): void
    {
        $alpha = $this->createTenant('alpha');
        $beta = $this->createTenant('beta');
        $this->invoice($alpha, 'Alpha Customer');
        $this->invoice($beta, 'Beta Customer');
        $this->createUser($alpha, 'alice');
        $this->signInOn($alpha, 'alice');

        $this->get('http://alpha.pos.test/invoices/1')
            ->assertOk()
            ->assertSee('Alpha Customer')
            ->assertDontSee('Beta Customer');
    }

    public function test_an_invoice_only_another_business_has_returns_404(): void
    {
        $alpha = $this->createTenant('alpha');
        $beta = $this->createTenant('beta');
        $this->invoice($alpha, 'Alpha Customer');
        $this->invoice($beta, 'Beta Customer');
        $this->asTenant($beta, fn () => Invoice::create(['invoice_no' => '1501', 'customer_name' => 'Beta Second', 'date' => '2026-10-02', 'subtotal' => 1]));
        $this->createUser($alpha, 'alice');
        $this->signInOn($alpha, 'alice');

        $this->get('http://alpha.pos.test/invoices/2')->assertNotFound();
    }

    public function test_reports_built_from_raw_queries_only_include_the_business_own_data(): void
    {
        $alpha = $this->createTenant('alpha');
        $beta = $this->createTenant('beta');
        $this->invoice($alpha, 'Alpha Customer');
        $this->invoice($beta, 'Beta Customer');
        $this->createUser($alpha, 'alice');
        $this->signInOn($alpha, 'alice');

        $this->get('http://alpha.pos.test/outstanding-ar')
            ->assertOk()
            ->assertSee('Alpha Customer')
            ->assertDontSee('Beta Customer');
    }

    public function test_a_session_from_one_business_is_not_signed_in_on_another(): void
    {
        $alpha = $this->createTenant('alpha');
        $beta = $this->createTenant('beta');
        // Both businesses have a user #1, which is what a copied session cookie would point at
        $this->createUser($alpha, 'alice');
        $this->createUser($beta, 'bob');
        $this->signInOn($alpha, 'alice');

        $this->get('http://beta.pos.test/')->assertRedirect('http://beta.pos.test/login');

        $this->assertGuest();
    }

    public function test_each_business_shows_its_own_saved_settings(): void
    {
        $alpha = $this->createTenant('alpha');
        $beta = $this->createTenant('beta');
        $this->asTenant($alpha, fn () => $this->app->make(Settings::class)->save(['company.name' => 'Alpha Trading']));
        $this->asTenant($beta, fn () => $this->app->make(Settings::class)->save(['company.name' => 'Beta Trading']));

        $this->get('http://alpha.pos.test/login')->assertOk()->assertSee('Alpha Trading')->assertDontSee('Beta Trading');
        $this->get('http://beta.pos.test/login')->assertOk()->assertSee('Beta Trading')->assertDontSee('Alpha Trading');
    }

    public function test_a_custom_domain_opens_its_business(): void
    {
        $alpha = $this->createTenant('alpha');
        Domain::create(['tenant_id' => $alpha->id, 'domain' => 'pos.alpha.lk', 'is_primary' => true]);
        $this->createUser($alpha, 'alice');

        $this->post('http://pos.alpha.lk/login', ['username' => 'alice', 'password' => 'secret-password'])
            ->assertRedirect('http://pos.alpha.lk');

        $this->assertAuthenticated();
    }

    #[TestWith(['nosuch.pos.test'], 'unknown subdomain')]
    #[TestWith(['pos.test'], 'platform domain')]
    #[TestWith(['bad_slug.pos.test'], 'invalid subdomain')]
    public function test_hosts_without_a_business_return_404(string $host): void
    {
        $this->get("http://{$host}/login")->assertNotFound();
    }

    public function test_a_suspended_business_returns_403(): void
    {
        Tenant::factory()->suspended()->create(['slug' => 'gone']);

        $this->get('http://gone.pos.test/login')->assertForbidden();
    }
}

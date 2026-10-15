<?php

namespace Tests\Feature;

use App\Models\Landlord\Plan;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Concerns\BootsWithTenancy;
use Tests\TestCase;

/**
 * The public website on the platform domain: home, features, solutions, pricing and contact.
 */
class MarketingPagesTest extends TestCase
{
    use BootsWithTenancy;

    #[TestWith(['/', 'Quotes, invoices and repair jobs'], 'home')]
    #[TestWith(['/features', 'Everything you need.'], 'features')]
    #[TestWith(['/solutions/repair-shops', 'Run your repair shop without the paperwork'], 'repair shops')]
    #[TestWith(['/solutions/retail-and-trading', 'Know your stock, your sales and who owes you'], 'shops and traders')]
    #[TestWith(['/solutions/service-businesses', 'Quote with confidence, invoice on time, get paid'], 'service businesses')]
    #[TestWith(['/pricing', 'Every feature included.'], 'pricing')]
    #[TestWith(['/contact', 'We\'re happy to help'], 'contact')]
    public function test_each_page_opens_with_its_headline_under_the_product_name(string $path, string $headline): void
    {
        $this->get("http://pos.test{$path}")
            ->assertOk()
            ->assertSee($headline, false)
            ->assertSee('<title>', false)
            ->assertSee('Billora');
    }

    public function test_an_unknown_solution_returns_404(): void
    {
        $this->get('http://pos.test/solutions/bakeries')->assertNotFound();
    }

    public function test_pricing_lists_only_plans_that_are_on_sale_cheapest_first(): void
    {
        Plan::create(['code' => 'business', 'name' => 'Business', 'price_monthly' => 6500, 'limits' => ['users' => 10]]);
        Plan::create(['code' => 'starter', 'name' => 'Starter', 'price_monthly' => 2500, 'price_yearly' => 25000, 'limits' => ['users' => 3, 'invoices_per_month' => 200]]);
        Plan::create(['code' => 'old', 'name' => 'Retired Plan', 'price_monthly' => 1000, 'is_active' => false]);
        Plan::create(['code' => 'internal', 'name' => 'Internal Plan', 'price_monthly' => 0, 'is_public' => false]);

        $this->get('http://pos.test/pricing')
            ->assertOk()
            ->assertSeeInOrder(['Starter', 'Rs. 2,500', 'Up to 3 active users', 'Up to 200 invoices a month', 'Business', 'Rs. 6,500', 'Unlimited invoices a month'])
            ->assertSee('Save Rs. 5,000 a year')
            ->assertDontSee('Retired Plan')
            ->assertDontSee('Internal Plan');
    }

    public function test_pricing_without_plans_invites_a_trial(): void
    {
        $this->get('http://pos.test/pricing')->assertOk()->assertSee('Pricing is on its way');
    }

    public function test_home_shows_the_cheapest_paid_plan(): void
    {
        Plan::create(['code' => 'starter', 'name' => 'Starter', 'price_monthly' => 2500]);
        Plan::create(['code' => 'business', 'name' => 'Business', 'price_monthly' => 6500]);

        $this->get('http://pos.test/')->assertOk()->assertSee('Plans from')->assertSee('Rs. 2,500');
    }

    public function test_contact_shows_whatsapp_and_email_when_configured(): void
    {
        config(['tenancy.operator.phone' => '+94 77 123 4567', 'tenancy.operator.email' => 'hello@billora.test']);

        $this->get('http://pos.test/contact')
            ->assertOk()
            ->assertSee('https://wa.me/94771234567', false)
            ->assertSee('mailto:hello@billora.test', false);
    }
}

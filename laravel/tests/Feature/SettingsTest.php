<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A complete, valid settings form.
     */
    private function form(array $overrides = []): array
    {
        return array_replace_recursive([
            'company' => ['name' => 'Acme Repairs', 'tagline' => 'Phones & Laptops', 'address' => "12 Main Street\nColombo 03", 'phone' => '011 222 3333', 'email' => 'hello@acme.lk'],
            'bank' => ['account_name' => 'Acme Repairs', 'bank_name' => 'HNB', 'account_number' => '123456', 'branch' => 'Colombo'],
            'currency_symbol' => 'LKR',
            'credit_days' => 45,
            'tax' => ['enabled' => '0', 'name' => 'VAT', 'rate' => '', 'registration_no' => ''],
            'invoice' => ['first_number' => 100, 'default_terms' => 'Invoice terms', 'default_note' => 'Pay by cheque'],
            'quotation' => ['first_number' => 50, 'default_terms' => 'Quote terms'],
            'job' => ['first_number' => 1, 'default_terms' => 'Job terms'],
            'backup' => ['keep' => 7],
        ], $overrides);
    }

    public function test_owner_saves_settings_and_they_appear_across_the_app(): void
    {
        $this->signIn();

        $this->put('/settings', $this->form())->assertRedirect(route('settings.edit'))->assertSessionHas('success');

        $this->assertSame('Acme Repairs', config('sjpos.company.name'));
        $this->assertSame('12 Main Street, Colombo 03', config('sjpos.company.address'));
        $this->assertSame('LKR 1,250.00', rs(1250));

        // Saved values survive a fresh load from the database
        app(Settings::class)->apply(Setting::pluck('value', 'key')->all());
        $this->assertSame(45, config('sjpos.credit_days'));

        $invoice = Invoice::create(['invoice_no' => '100', 'customer_name' => 'Bob', 'date' => '2026-10-01', 'subtotal' => 10]);
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('Acme Repairs')->assertSee('12 Main Street, Colombo 03');
        $this->get('/')->assertOk()->assertSee('Acme Repairs')->assertSee('over 45 days');
        $this->get('/settings')->assertOk()->assertSee("12 Main Street\nColombo 03", false);
    }

    public function test_saving_unchanged_settings_logs_nothing(): void
    {
        $this->signIn();
        $form = $this->form(['company' => ['address' => "12 Main Street\r\nColombo 03"]]);

        $this->put('/settings', $form);
        $this->put('/settings', $form);

        $this->assertSame("12 Main Street\nColombo 03", Setting::find('company.address')->value);
        $this->assertSame(1, ActivityLog::where('subject_type', 'settings')->count());
    }

    public function test_turning_tax_off_keeps_the_saved_rate(): void
    {
        $this->signIn();
        $this->put('/settings', $this->form(['tax' => ['enabled' => '1', 'name' => 'VAT', 'rate' => '18']]))->assertSessionHasNoErrors();

        // Older pages disabled the tax boxes, so the browser sent no name or rate at all
        $form = $this->form();
        unset($form['tax']['name'], $form['tax']['rate']);
        $this->put('/settings', $form)->assertRedirect(route('settings.edit'))->assertSessionHasNoErrors();

        $this->assertFalse(config('sjpos.tax.enabled'));
        $this->assertEquals(18, config('sjpos.tax.rate'));
        $this->assertSame('VAT', config('sjpos.tax.name'));
    }

    public function test_validation(): void
    {
        $this->signIn();

        $this->put('/settings', $this->form(['company' => ['name' => '']]))->assertSessionHasErrors(['company.name' => 'Enter your business name.']);
        $this->put('/settings', $this->form(['tax' => ['enabled' => '1', 'rate' => '']]))->assertSessionHasErrors('tax.rate');
        $this->put('/settings', $this->form(['company' => ['email' => 'nope']]))->assertSessionHasErrors('company.email');
    }

    public function test_logo_upload_and_removal(): void
    {
        $this->signIn();

        $this->put('/settings', $this->form() + ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)])->assertSessionHasNoErrors();
        $logo = config('sjpos.company.logo');
        $this->assertStringStartsWith('branding/logo-', $logo);
        $this->assertFileExists(storage_path('app/'.$logo));
        $this->assertFileDoesNotExist(public_path($logo));

        try {
            $this->get('/logo')->assertOk()->assertHeader('content-type', 'image/png');

            $this->put('/settings', $this->form() + ['remove_logo' => '1'])->assertSessionHasNoErrors();
            $this->assertSame('assets/img/default-logo.png', config('sjpos.company.logo'));
            $this->assertFileDoesNotExist(storage_path('app/'.$logo));
        } finally {
            File::delete(storage_path('app/'.$logo));
        }
    }

    public function test_replacing_a_logo_uploaded_before_tenancy_deletes_the_old_public_file(): void
    {
        $this->signIn();
        File::ensureDirectoryExists(public_path('uploads'));
        File::copy(public_path('assets/img/default-logo.png'), public_path('uploads/logo-legacy.png'));
        $this->app->make(Settings::class)->save(['company.logo' => 'uploads/logo-legacy.png']);

        try {
            $this->put('/settings', $this->form() + ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)])->assertSessionHasNoErrors();

            $this->assertFileDoesNotExist(public_path('uploads/logo-legacy.png'));
            $this->assertStringStartsWith('branding/logo-', config('sjpos.company.logo'));
        } finally {
            File::delete([public_path('uploads/logo-legacy.png'), storage_path('app/'.config('sjpos.company.logo'))]);
        }
    }

    public function test_owner_turns_on_sign_in_codes_with_a_fallback_mailbox(): void
    {
        $this->signIn();

        $this->put('/settings', $this->form(['otp' => ['enabled' => '1', 'email' => 'office@acme.lk']]))->assertSessionHasNoErrors();

        $this->assertTrue(config('sjpos.otp.enabled'));
        $this->assertSame('office@acme.lk', config('sjpos.otp.email'));
        $this->assertTrue(Setting::findOrFail('otp.enabled')->value);
    }

    public function test_only_owners_can_open_settings(): void
    {
        $this->signIn('manager');

        $this->get('/settings')->assertForbidden();
        $this->put('/settings', $this->form())->assertForbidden();
    }

    public function test_fresh_install_shows_setup_banner(): void
    {
        $this->signIn();
        $this->get('/')->assertSee('Set up my business');

        $this->put('/settings', $this->form());
        $this->get('/')->assertDontSee('Set up my business');
    }
}

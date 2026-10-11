<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sjpos.tax' => ['enabled' => true, 'name' => 'VAT', 'rate' => 18, 'registration_no' => 'VAT-123']]);
        $this->signIn();
    }

    private function invoicePayload(array $overrides = []): array
    {
        return $overrides + [
            'invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01',
            'description' => ['Laptop repair'], 'brand' => ['Dell'], 'quantity' => [2], 'unit_price' => [5000], 'apply_tax' => '1',
        ];
    }

    public function test_invoice_adds_tax_on_top_and_balance_uses_the_total(): void
    {
        $this->postJson('/invoices', $this->invoicePayload())->assertOk();
        $invoice = Invoice::firstOrFail();

        $this->assertSame(10000.0, $invoice->net_amount);
        $this->assertSame(18.0, $invoice->tax_rate);
        $this->assertSame(1800.0, $invoice->tax_amount);
        $this->assertSame(11800.0, $invoice->subtotal);
        $this->assertSame(11800.0, $invoice->balance);

        $this->get('/invoices/'.$invoice->id)->assertOk()
            ->assertSeeInOrder(['Sub Total', 'Rs. 10,000.00', 'VAT (18%)', 'Rs. 1,800.00', 'Total', 'Rs. 11,800.00'])
            ->assertSee('VAT Reg. No: VAT-123');
        $this->get('/invoices/'.$invoice->id.'/pdf')->assertOk();
    }

    public function test_tax_can_be_left_off_or_disabled(): void
    {
        $this->postJson('/invoices', $this->invoicePayload(['apply_tax' => '0']))->assertOk();
        $this->assertSame(0.0, Invoice::firstOrFail()->tax_amount);

        config(['sjpos.tax.enabled' => false]);
        $this->postJson('/invoices', $this->invoicePayload(['invoice_no' => '1501']))->assertOk();
        $this->assertSame(10000.0, Invoice::where('invoice_no', '1501')->value('subtotal'));
        $this->get('/invoices/create')->assertDontSee('apply_tax');
    }

    public function test_editing_keeps_the_rate_the_invoice_was_issued_with(): void
    {
        $this->postJson('/invoices', $this->invoicePayload());
        $invoice = Invoice::firstOrFail();

        config(['sjpos.tax.rate' => 20]);
        $this->putJson('/invoices/'.$invoice->id, $this->invoicePayload(['quantity' => [1]]))->assertOk();

        $invoice->refresh();
        $this->assertSame(18.0, $invoice->tax_rate);
        $this->assertSame(5900.0, $invoice->subtotal);
    }

    public function test_quotation_tax_carries_into_the_converted_invoice(): void
    {
        $this->postJson('/quotations', [
            'quotation_no' => 1180, 'prepared_for' => 'Acme', 'prepared_by' => 'Shane', 'date' => '2026-10-01', 'validity_date' => '2026-10-08',
            'item_no' => ['01'], 'description' => ['Printer'], 'brand' => ['HP'], 'quantity' => [1], 'unit_price' => [1000], 'apply_tax' => '1',
        ])->assertOk();
        $quotation = Quotation::firstOrFail();
        $this->assertSame(1180.0, $quotation->subtotal);

        $this->post("/quotations/{$quotation->id}/convert")->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame(180.0, $invoice->tax_amount);
        $this->assertSame(1180.0, $invoice->subtotal);
    }

    public function test_reports_show_tax_and_profit_excludes_it(): void
    {
        $this->travelTo('2026-10-15');
        $this->postJson('/invoices', $this->invoicePayload());

        $this->get('/reports/sales?period=this_month')->assertOk()
            ->assertSeeInOrder(['Total sales', 'Rs. 11,800.00', 'VAT collected', 'Rs. 1,800.00']);
        $this->get('/reports/profit?period=this_month')->assertOk()
            ->assertSeeInOrder(['Sales', 'Rs. 10,000.00'])
            ->assertSee('Sales are shown before tax');
    }

    public function test_untaxed_old_invoices_still_show_a_single_total(): void
    {
        $invoice = Invoice::create(['invoice_no' => '900', 'customer_name' => 'Old', 'date' => '2025-01-01', 'subtotal' => 500]);
        $invoice->items()->create(['item_no' => '01', 'description' => 'Thing', 'brand' => '', 'quantity' => 1, 'unit_price' => 500, 'total' => 500]);

        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('Rs. 500.00')->assertDontSee('VAT (');
    }
}

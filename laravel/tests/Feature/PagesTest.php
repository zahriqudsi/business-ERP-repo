<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    public function test_dashboard_renders_for_every_period(): void
    {
        Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => today(), 'subtotal' => 1500000, 'status' => 'confirmed']);

        foreach (['today', 'week', 'month', 'quarter', 'year', 'bogus'] as $period) {
            $this->get('/?period='.$period)->assertOk()->assertSee('Rs. 1.50M')->assertSee('Acme');
        }
    }

    public function test_dashboard_overdue_only_counts_invoices_past_credit_period(): void
    {
        Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Old', 'date' => today()->subDays(31), 'subtotal' => 200000, 'status' => 'confirmed']);
        Invoice::create(['invoice_no' => '1501', 'customer_name' => 'New', 'date' => today()->subDays(5), 'subtotal' => 50000, 'status' => 'confirmed']);

        $this->get('/')->assertOk()->assertSee('Rs. 250.0K')->assertSee('Rs. 200.0K over 30 days');
    }

    public function test_bill_due_today_is_not_overdue(): void
    {
        $this->post('/bills', [
            'supplier_name' => 'Dialog', 'bill_date' => today()->toDateString(), 'due_date' => today()->toDateString(), 'amount' => 5000,
        ])->assertRedirect(route('bills.index'));

        $this->assertSame('unpaid', VendorBill::firstOrFail()->status);
    }

    public function test_vendor_bills_crud_and_status(): void
    {
        $this->get('/bills')->assertOk()->assertSee('No bills yet');
        $this->get('/bills/create')->assertOk();

        $this->post('/bills', [
            'supplier_name' => 'Dialog', 'bill_no' => 'B-1', 'bill_date' => '2026-09-01', 'due_date' => '2026-09-15',
            'amount' => 5000, 'amount_paid' => 0,
        ])->assertRedirect(route('bills.index'));

        $bill = VendorBill::firstOrFail();
        $this->assertSame('overdue', $bill->status);

        $this->get("/bills/{$bill->id}/edit")->assertOk()->assertSee('Dialog');
        $this->put("/bills/{$bill->id}", [
            'supplier_name' => 'Dialog', 'bill_date' => '2026-09-01', 'amount' => 5000, 'amount_paid' => 2000,
        ])->assertRedirect();
        $this->assertSame('partially_paid', $bill->refresh()->status);

        $this->get('/bills')->assertOk()->assertSee('Partially paid');
        $this->delete("/bills/{$bill->id}")->assertRedirect();
        $this->assertDatabaseCount('vendor_bills', 0);
    }

    public function test_purchase_pages(): void
    {
        $this->get('/purchases')->assertOk();
        $this->get('/purchases/create')->assertOk();
    }

    public function test_legacy_urls_redirect(): void
    {
        $this->get('/login.php')->assertRedirect('/login');
        $this->get('/invoices/list_invoices.php')->assertRedirect('/invoices');
        $this->get('/invoices/view_invoice.php?id=5')->assertRedirect('/invoices/5');
        $this->get('/generate_pdf.php?id=5')->assertRedirect('/invoices/5/pdf');
        $this->get('/payments/receive_payment.php?invoice_id=9')->assertRedirect('/payments/receive?invoice_id=9');
    }
}

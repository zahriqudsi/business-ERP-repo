<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMade;
use App\Models\PaymentReceived;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\VendorBill;
use App\Services\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-15 10:00:00');
        $this->signIn();
    }

    private function invoice(string $no, string $customer, string $date, float $total, float $paid = 0, string $status = 'confirmed'): Invoice
    {
        return Invoice::create([
            'invoice_no' => $no, 'customer_name' => $customer, 'date' => $date, 'subtotal' => $total, 'amount_paid' => $paid, 'status' => $status,
        ]);
    }

    public function test_index_lists_reports(): void
    {
        $this->get('/reports')->assertOk()->assertSee('Profit &amp; Loss', false)->assertSee('Customer Statement');
        $this->get('/reports/bogus')->assertNotFound();
    }

    public function test_sales_report_counts_confirmed_and_paid_in_period(): void
    {
        $this->invoice('1500', 'Acme', '2026-10-02', 1000, 400);
        $this->invoice('1501', 'Beta', '2026-10-05', 3000, 3000, 'paid');
        $this->invoice('1502', 'Acme', '2026-10-06', 9999, 0, 'draft');
        $this->invoice('1503', 'Acme', '2026-09-30', 7777);

        $this->get('/reports/sales')->assertOk()
            ->assertSee('1 Oct 2026 – 31 Oct 2026', false)
            ->assertSeeInOrder(['Invoices', '2', 'Total sales', 'Rs. 4,000.00', 'Collected', 'Rs. 3,400.00', 'Still owed', 'Rs. 600.00'])
            ->assertSeeInOrder(['By customer', 'Beta', 'Rs. 3,000.00', 'Acme', 'Rs. 1,000.00'])
            ->assertDontSee('1502')
            ->assertDontSee('1503');

        $this->get('/reports/sales?period=custom&from=2026-09-01&to=2026-09-30')->assertOk()->assertSee('1503')->assertDontSee('1500');
    }

    public function test_csv_export_keeps_numbers_raw(): void
    {
        $this->invoice('1500', 'Acme, Ltd', '2026-10-02', 1234.5);

        $response = $this->get('/reports/sales?format=csv')->assertOk()->assertDownload('sales_report_2026-10-01_to_2026-10-31.csv');
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"Total sales",1234.5', $csv);
        $this->assertStringContainsString('2026-10-02,1500,"Acme, Ltd",Confirmed,1234.5,0,1234.5', $csv);
    }

    public function test_pdf_export(): void
    {
        $this->invoice('1500', 'Acme', '2026-10-02', 1000);

        $this->get('/reports/sales?format=pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_invalid_custom_range_is_rejected(): void
    {
        $this->get('/reports/sales?period=custom&from=2026-10-10&to=2026-10-01')->assertSessionHasErrors('to');
    }

    public function test_purchases_report_combines_purchases_and_bills(): void
    {
        Purchase::create(['supplier_name' => 'Tech', 'date' => '2026-10-03', 'subtotal' => 5000, 'amount_paid' => 1000, 'status' => 'confirmed']);
        Purchase::create(['supplier_name' => 'Tech', 'date' => '2026-10-03', 'subtotal' => 8888, 'status' => 'draft']);
        VendorBill::create(['supplier_name' => 'Dialog', 'bill_date' => '2026-10-04', 'amount' => 2000, 'amount_paid' => 0, 'status' => 'unpaid']);

        $this->get('/reports/purchases')->assertOk()
            ->assertSeeInOrder(['Purchase orders', 'Rs. 5,000.00', 'Vendor bills', 'Rs. 2,000.00', 'Total spending', 'Rs. 7,000.00', 'Paid', 'Rs. 1,000.00'])
            ->assertDontSee('8,888');
    }

    public function test_profit_uses_product_costs(): void
    {
        $product = Product::factory()->create(['name' => 'SSD', 'brand' => 'Samsung', 'cost_price' => 600, 'unit_price' => 1000]);
        $this->postJson('/invoices', [
            'invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-02',
            'description' => ['SSD', 'Labour'], 'brand' => ['Samsung', ''], 'quantity' => [2, 1], 'unit_price' => [1000, 500],
            'product_id' => [$product->id, null],
        ])->assertOk();
        Purchase::create(['supplier_name' => 'Tech', 'date' => '2026-10-03', 'subtotal' => 700, 'status' => 'confirmed']);

        // Sales 2,500; cost 2 x 600 = 1,200; gross 1,300 (52%); minus 700 spending = 1,800
        $this->get('/reports/profit')->assertOk()
            ->assertSeeInOrder(['Sales', 'Rs. 2,500.00', 'Cost of products sold', 'Rs. 1,200.00', 'Gross profit', 'Rs. 1,300.00', 'Gross margin', '52.0%'])
            ->assertSeeInOrder(['Sales minus spending', 'Rs. 1,800.00'])
            ->assertSeeInOrder(['Product margins', 'SSD (Samsung)', '2', 'Rs. 2,000.00', 'Rs. 1,200.00', 'Rs. 800.00', '40.0%']);
    }

    public function test_payments_report_by_method(): void
    {
        PaymentReceived::create(['customer_name' => 'Acme', 'amount' => 1500, 'payment_date' => '2026-10-02', 'method' => 'cash']);
        PaymentReceived::create(['customer_name' => 'Beta', 'amount' => 500, 'payment_date' => '2026-10-03', 'method' => 'cheque']);
        PaymentMade::create(['supplier_name' => 'Tech', 'amount' => 300, 'payment_date' => '2026-10-04', 'method' => 'cash']);

        $this->get('/reports/payments')->assertOk()
            ->assertSeeInOrder(['Received', 'Rs. 2,000.00', 'Paid out', 'Rs. 300.00', 'Net cash in', 'Rs. 1,700.00'])
            ->assertSeeInOrder(['By method', 'Cash', 'Rs. 1,500.00', 'Rs. 300.00', 'Cheque', 'Rs. 500.00']);
    }

    public function test_receivables_aging_buckets(): void
    {
        $this->invoice('1500', 'Acme', '2026-10-10', 1000, 200); // 5 days
        $this->invoice('1501', 'Acme', '2026-08-31', 3000);       // 45 days
        $this->invoice('1502', 'Beta', '2026-06-01', 500);        // 136 days
        $this->invoice('1503', 'Gamma', '2026-06-01', 999, 999, 'paid');

        $this->get('/reports/receivables')->assertOk()
            ->assertSee('As of 15 Oct 2026')
            ->assertSeeInOrder(['Total outstanding', 'Rs. 4,300.00', '0–30 days', 'Rs. 800.00', '31–60 days', 'Rs. 3,000.00', '61–90 days', '-', '90+ days', 'Rs. 500.00'], false)
            ->assertSeeInOrder(['By customer', 'Acme', 'Rs. 800.00', 'Rs. 3,000.00', 'Rs. 3,800.00', 'Beta'])
            ->assertDontSee('Gamma');
    }

    public function test_payables_age_bills_from_due_date(): void
    {
        VendorBill::create(['supplier_name' => 'Dialog', 'bill_date' => '2026-07-01', 'due_date' => '2026-11-01', 'amount' => 400, 'status' => 'unpaid']);
        Purchase::create(['supplier_name' => 'Tech', 'date' => '2026-07-01', 'subtotal' => 900, 'status' => 'confirmed']);

        $this->get('/reports/payables')->assertOk()
            ->assertSeeInOrder(['0–30 days', 'Rs. 400.00', '31–60 days', '-', '61–90 days', '-', '90+ days', 'Rs. 900.00'], false);
    }

    public function test_stock_valuation(): void
    {
        $product = Product::factory()->create(['name' => 'RAM', 'cost_price' => 100, 'unit_price' => 150, 'reorder_level' => 2]);
        app(StockLedger::class)->adjust($product, 4, null);
        Product::factory()->service()->create(['name' => 'Repair']);

        $this->get('/reports/stock')->assertOk()
            ->assertSeeInOrder(['Units in stock', '4', 'Value at cost', 'Rs. 400.00', 'Value at selling price', 'Rs. 600.00'])
            ->assertSee('RAM')
            ->assertDontSee('Repair');
    }

    public function test_customer_statement_with_running_balance(): void
    {
        $this->invoice('1400', 'Acme', '2026-09-20', 1000);                        // before the period
        PaymentReceived::create(['customer_name' => 'Acme', 'amount' => 400, 'payment_date' => '2026-09-25', 'method' => 'cash']);
        $this->invoice('1500', 'Acme', '2026-10-02', 2000);
        PaymentReceived::create(['customer_name' => 'Acme', 'amount' => 1500, 'payment_date' => '2026-10-05', 'method' => 'cheque', 'reference' => 'CHQ-9']);
        $this->invoice('1501', 'Other', '2026-10-02', 5000);
        $customer = Customer::where('name', 'Acme')->firstOrFail();

        $this->get('/reports/statement')->assertOk()->assertSee('Choose a customer to see their statement.');

        $this->get("/reports/statement?customer_id={$customer->id}&period=this_month")->assertOk()
            ->assertSee('Statement – Acme')
            ->assertSeeInOrder(['Opening balance', 'Rs. 600.00', 'Invoiced', 'Rs. 2,000.00', 'Paid', 'Rs. 1,500.00', 'Closing balance', 'Rs. 1,100.00'])
            ->assertSeeInOrder(['Invoice #1500', 'Rs. 2,600.00', 'Payment – Cheque (CHQ-9)', 'Rs. 1,100.00'])
            ->assertDontSee('#1501');

        $this->get('/reports/statement?format=csv')->assertSessionHasErrors('customer_id');
    }
}

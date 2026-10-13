<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'invoice_no' => '1500',
            'customer_name' => 'Acme Ltd',
            'date' => '2026-10-01',
            'terms_conditions' => 'Some terms',
            'details_type' => 'bank',
            'item_no' => ['01', '02'],
            'description' => ['Laptop repair', 'SSD 512GB'],
            'brand' => ['Dell', 'Samsung'],
            'quantity' => [1, 2],
            'unit_price' => [5000, 12500.5],
            'total' => [999, 999], // ignored: recomputed server-side
        ];
    }

    public function test_next_invoice_number_is_numeric_not_alphabetic(): void
    {
        Invoice::create(['invoice_no' => '999', 'customer_name' => 'A', 'date' => '2026-01-01', 'subtotal' => 1]);
        Invoice::create(['invoice_no' => '1416', 'customer_name' => 'B', 'date' => '2026-01-01', 'subtotal' => 1]);

        $this->assertSame(1417, Invoice::nextNumber());
    }

    public function test_store_creates_invoice_items_and_bank_details(): void
    {
        $this->postJson('/invoices', $this->payload())
            ->assertOk()
            ->assertJson(['status' => 'success', 'redirect' => route('invoices.index')]);

        $invoice = Invoice::where('invoice_no', '1500')->firstOrFail();
        $this->assertSame(30001.0, $invoice->subtotal);
        $this->assertSame('confirmed', $invoice->status);
        $this->assertCount(2, $invoice->items);
        $this->assertSame(25001.0, $invoice->items[1]->total);
        $this->assertSame('8016893890', $invoice->bankDetail->account_number);
    }

    public function test_store_rejects_duplicate_number(): void
    {
        $this->postJson('/invoices', $this->payload())->assertOk();

        $this->postJson('/invoices', $this->payload())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Invoice number already exists.');
    }

    public function test_update_replaces_items_and_switches_to_notes(): void
    {
        $this->postJson('/invoices', $this->payload());
        $invoice = Invoice::firstOrFail();

        $this->putJson("/invoices/{$invoice->id}", $this->payload([
            'customer_name' => 'Acme Holdings',
            'details_type' => 'notes',
            'notes' => 'Cheques payable to SJ Technology',
            'item_no' => ['01'],
            'description' => ['Only item'],
            'brand' => ['HP'],
            'quantity' => [3],
            'unit_price' => [100],
        ]))->assertOk()->assertJson(['status' => 'success']);

        $invoice->refresh()->load(['items', 'bankDetail', 'notes']);
        $this->assertSame('Acme Holdings', $invoice->customer_name);
        $this->assertSame(300.0, $invoice->subtotal);
        $this->assertCount(1, $invoice->items);
        $this->assertNull($invoice->bankDetail);
        $this->assertSame('Cheques payable to SJ Technology', $invoice->notes->first()->notes);
    }

    public function test_raising_total_of_paid_invoice_reopens_it(): void
    {
        $this->postJson('/invoices', $this->payload(['quantity' => [1, 1], 'unit_price' => [500, 500]]));
        $invoice = Invoice::firstOrFail();
        $invoice->applyPayment(1000);
        $this->assertSame('paid', $invoice->status);

        $this->putJson("/invoices/{$invoice->id}", $this->payload(['quantity' => [1, 1], 'unit_price' => [1000, 500]]))->assertOk();

        $invoice->refresh();
        $this->assertSame('confirmed', $invoice->status);
        $this->assertSame(500.0, $invoice->balance);
        $this->get('/outstanding-ar')->assertSee('Acme Ltd');
    }

    public function test_total_below_amount_paid_is_rejected(): void
    {
        $this->postJson('/invoices', $this->payload(['quantity' => [1, 1], 'unit_price' => [500, 500]]));
        $invoice = Invoice::firstOrFail();
        $invoice->applyPayment(800);

        $this->putJson("/invoices/{$invoice->id}", $this->payload(['quantity' => [1, 1], 'unit_price' => [300, 300]]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The new total is less than the Rs. 800.00 already paid on this invoice.');

        $this->assertSame(1000.0, $invoice->refresh()->subtotal);
    }

    public function test_mismatched_item_rows_are_rejected(): void
    {
        $this->postJson('/invoices', $this->payload(['quantity' => [1]]))->assertJsonValidationErrors('quantity');
    }

    public function test_pages_render(): void
    {
        $this->postJson('/invoices', $this->payload());
        $invoice = Invoice::firstOrFail();

        $this->get('/invoices')->assertOk()->assertSee('Acme Ltd');
        $this->get('/invoices/create')->assertOk()->assertSee('1501');
        $this->get("/invoices/{$invoice->id}")->assertOk()->assertSee('Laptop repair')->assertSee('Commercial Bank');
        $this->get("/invoices/{$invoice->id}?ajax=1")->assertOk()->assertDontSee('<nav', false);
        $this->get("/invoices/{$invoice->id}/edit")->assertOk()->assertSee('SSD 512GB');
        $this->get("/invoices/{$invoice->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_destroy_removes_invoice_and_children(): void
    {
        $this->postJson('/invoices', $this->payload(['details_type' => 'notes']));
        $invoice = Invoice::firstOrFail();

        $this->deleteJson("/invoices/{$invoice->id}")->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_outstanding_ar_lists_and_drills_down(): void
    {
        $this->postJson('/invoices', $this->payload());
        $this->postJson('/invoices', $this->payload(['invoice_no' => '1501', 'customer_name' => 'Beta Co']));

        $this->get('/outstanding-ar')->assertOk()->assertSee('Acme Ltd')->assertSee('Beta Co')->assertSee('2 customers owing');
        $this->get('/outstanding-ar?search=Beta')->assertOk()->assertSee('Beta Co')->assertDontSee('Acme Ltd');
        $this->get('/outstanding-ar?customer=Acme+Ltd')->assertOk()->assertSee('Invoice #1500')->assertSee('SSD 512GB');
        $this->get('/outstanding-ar?customer=Acme+Ltd&inv_search=Samsung')->assertOk()->assertSee('Invoice #1500');
        $this->get('/outstanding-ar?customer=Acme+Ltd&inv_search=nothing')->assertOk()->assertSee('No outstanding invoices match');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotationTest extends TestCase
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
            'quotation_no' => 1180,
            'prepared_for' => 'H&R Colombo',
            'prepared_by' => 'Shane',
            'date' => '2026-10-01',
            'validity_date' => '2026-10-08',
            'terms_conditions' => "1. Delivery: Ex-stock\n2. Payments: 100% advance",
            'item_no' => ['01', '02'],
            'description' => ['Printer', 'Toner'],
            'brand' => ['HP', 'HP'],
            'quantity' => [2, 5],
            'unit_price' => [45000, 3500],
        ];
    }

    public function test_store_update_and_render(): void
    {
        $this->postJson('/quotations', $this->payload())->assertOk()->assertJson(['status' => 'success']);
        $quotation = Quotation::firstOrFail();

        // Stored raw (the old system saved "H&amp;R")
        $this->assertSame('H&R Colombo', $quotation->prepared_for);
        $this->assertSame(107500.0, $quotation->subtotal);

        $this->putJson("/quotations/{$quotation->id}", $this->payload([
            'quantity' => [1, 1],
        ]))->assertOk();
        $this->assertSame(48500.0, $quotation->refresh()->subtotal);
        $this->assertCount(2, $quotation->items);

        $this->get('/quotations')->assertOk()->assertSee('H&amp;R Colombo', false);
        $this->get('/quotations/create')->assertOk()->assertSee('1181');
        $this->get("/quotations/{$quotation->id}?ajax=1")->assertOk()->assertSee('Convert to Invoice');
        $this->get("/quotations/{$quotation->id}/edit")->assertOk()->assertSee('Toner');
    }

    public function test_duplicate_number_is_rejected_but_own_number_is_kept_on_update(): void
    {
        $this->postJson('/quotations', $this->payload())->assertOk();
        $quotation = Quotation::firstOrFail();

        $this->postJson('/quotations', $this->payload())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Quotation number already exists.');
        $this->putJson("/quotations/{$quotation->id}", $this->payload())->assertOk();
    }

    public function test_zero_total_is_rejected(): void
    {
        $this->postJson('/quotations', $this->payload(['unit_price' => [0, 0]]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Subtotal must be a positive number.');
    }

    public function test_pdf_is_streamed_and_archived(): void
    {
        Storage::fake();
        $this->postJson('/quotations', $this->payload());
        $quotation = Quotation::firstOrFail();

        $response = $this->get("/quotations/{$quotation->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        Storage::assertExists('quotations/h_r_colombo_1180.pdf');
        $this->assertSame(Storage::get('quotations/h_r_colombo_1180.pdf'), $response->getContent());
    }

    public function test_convert_creates_invoice_with_items(): void
    {
        Invoice::create(['invoice_no' => '1416', 'customer_name' => 'X', 'date' => '2026-01-01', 'subtotal' => 1]);
        $this->postJson('/quotations', $this->payload());
        $quotation = Quotation::firstOrFail();

        $this->post("/quotations/{$quotation->id}/convert")
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $invoice = Invoice::where('invoice_no', '1417')->firstOrFail();
        $this->assertSame('H&R Colombo', $invoice->customer_name);
        $this->assertSame(107500.0, $invoice->subtotal);
        $this->assertCount(2, $invoice->items);
        $this->assertSame(today()->toDateString(), $invoice->date->toDateString());
    }

    public function test_destroy(): void
    {
        $this->postJson('/quotations', $this->payload());
        $quotation = Quotation::firstOrFail();

        $this->deleteJson("/quotations/{$quotation->id}")->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseCount('quotations', 0);
        $this->assertDatabaseCount('quotation_items', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quotation;
use App\Models\StockMovement;
use App\Services\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    private function stocked(int $quantity, array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);
        if ($quantity !== 0) {
            app(StockLedger::class)->adjust($product, $quantity, 'Opening stock');
        }

        return $product->refresh();
    }

    /**
     * Invoice form payload with one product line.
     */
    private function invoicePayload(Product $product, int $quantity, array $overrides = []): array
    {
        return $overrides + [
            'invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01',
            'item_no' => ['01'], 'description' => [$product->name], 'brand' => ['X'],
            'quantity' => [$quantity], 'unit_price' => [100], 'product_id' => [$product->id],
        ];
    }

    private function purchasePayload(Product $product, int $quantity, string $status = 'confirmed'): array
    {
        return [
            'supplier_name' => 'Tech Supplies', 'date' => '2026-10-01', 'status' => $status,
            'description' => [$product->name], 'quantity' => [$quantity], 'unit_price' => [50], 'product_id' => [$product->id],
        ];
    }

    public function test_create_with_opening_stock(): void
    {
        $this->post('/products', [
            'name' => 'SSD 512GB', 'sku' => 'SSD-512', 'unit_price' => 12500, 'cost_price' => 9000,
            'track_stock' => '1', 'is_active' => '1', 'opening_stock' => 5, 'reorder_level' => 2,
        ])->assertRedirect();

        $product = Product::firstOrFail();
        $this->assertSame(5, $product->stock_qty);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => 5, 'type' => 'adjustment']);
        $this->get("/products/{$product->id}")->assertOk()->assertSee('SSD 512GB')->assertSee('Opening stock')->assertSee('28.0% margin');
    }

    public function test_services_ignore_opening_stock_and_adjustments(): void
    {
        $this->post('/products', [
            'name' => 'Laptop repair', 'unit_price' => 5000, 'track_stock' => '0', 'is_active' => '1', 'opening_stock' => 5,
        ])->assertRedirect();
        $service = Product::firstOrFail();

        $this->assertSame(0, StockMovement::count());
        $this->post("/products/{$service->id}/adjust", ['mode' => 'add', 'quantity' => 3])->assertSessionHasErrors('quantity');
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        Product::factory()->create(['sku' => 'ABC']);

        $this->post('/products', ['name' => 'Other', 'sku' => 'ABC', 'unit_price' => 1])
            ->assertSessionHasErrors(['sku' => 'Another product already uses this SKU.']);
    }

    public function test_invoice_sells_stock_and_edits_do_not_double_count(): void
    {
        $product = $this->stocked(10);

        $this->postJson('/invoices', $this->invoicePayload($product, 3))->assertOk();
        $this->assertSame(7, $product->refresh()->stock_qty);
        $invoice = Invoice::firstOrFail();
        $this->assertSame($product->id, $invoice->items->first()->product_id);

        $this->putJson("/invoices/{$invoice->id}", $this->invoicePayload($product, 4))->assertOk();
        $this->assertSame(6, $product->refresh()->stock_qty);

        $this->deleteJson("/invoices/{$invoice->id}")->assertOk();
        $this->assertSame(10, $product->refresh()->stock_qty);
    }

    public function test_removing_the_product_from_an_invoice_line_returns_its_stock(): void
    {
        $product = $this->stocked(10);
        $this->postJson('/invoices', $this->invoicePayload($product, 3));
        $invoice = Invoice::firstOrFail();

        $this->putJson("/invoices/{$invoice->id}", $this->invoicePayload($product, 3, ['product_id' => [null]]))->assertOk();

        $this->assertSame(10, $product->refresh()->stock_qty);
    }

    public function test_service_lines_do_not_move_stock(): void
    {
        $service = Product::factory()->service()->create();

        $this->postJson('/invoices', $this->invoicePayload($service, 2))->assertOk();

        $this->assertSame(0, StockMovement::count());
    }

    public function test_purchases_add_stock_only_when_confirmed(): void
    {
        $product = $this->stocked(0);

        $this->post('/purchases', $this->purchasePayload($product, 5, 'draft'))->assertRedirect();
        $this->assertSame(0, $product->refresh()->stock_qty);
        $purchase = Purchase::firstOrFail();

        $this->put("/purchases/{$purchase->id}", $this->purchasePayload($product, 5))->assertRedirect();
        $this->assertSame(5, $product->refresh()->stock_qty);

        $this->put("/purchases/{$purchase->id}", $this->purchasePayload($product, 5, 'cancelled'))->assertRedirect();
        $this->assertSame(0, $product->refresh()->stock_qty);

        $this->put("/purchases/{$purchase->id}", $this->purchasePayload($product, 5))->assertRedirect();
        $this->delete("/purchases/{$purchase->id}")->assertRedirect();
        $this->assertSame(0, $product->refresh()->stock_qty);
    }

    public function test_quotations_do_not_move_stock_until_converted(): void
    {
        $product = $this->stocked(10);
        $this->postJson('/quotations', [
            'quotation_no' => 1180, 'prepared_for' => 'Acme', 'prepared_by' => 'Shane', 'date' => '2026-10-01',
            'validity_date' => '2026-10-08', 'item_no' => ['01'], 'description' => ['SSD'], 'brand' => ['Samsung'],
            'quantity' => [2], 'unit_price' => [100], 'product_id' => [$product->id],
        ])->assertOk();
        $this->assertSame(10, $product->refresh()->stock_qty);

        $this->post('/quotations/'.Quotation::firstOrFail()->id.'/convert')->assertRedirect();

        $this->assertSame(8, $product->refresh()->stock_qty);
        $this->assertSame($product->id, Invoice::firstOrFail()->items->first()->product_id);
    }

    public function test_manual_adjustments(): void
    {
        $product = $this->stocked(10);

        $this->post("/products/{$product->id}/adjust", ['mode' => 'remove', 'quantity' => 3, 'note' => 'Damaged'])->assertRedirect();
        $this->assertSame(7, $product->refresh()->stock_qty);

        $this->post("/products/{$product->id}/adjust", ['mode' => 'set', 'quantity' => 12])->assertRedirect();
        $this->assertSame(12, $product->refresh()->stock_qty);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => 5, 'note' => 'Stock count']);
    }

    public function test_used_products_cannot_be_deleted(): void
    {
        $used = $this->stocked(10);
        $unused = Product::factory()->create();
        $this->postJson('/invoices', $this->invoicePayload($used, 1));

        $this->delete("/products/{$used->id}")->assertSessionHasErrors('product');
        $this->delete("/products/{$unused->id}")->assertRedirect(route('products.index'));

        $this->assertSame([$used->id], Product::pluck('id')->all());
    }

    public function test_low_stock_filter_and_dashboard(): void
    {
        $this->stocked(1, ['name' => 'Almost Gone', 'reorder_level' => 2]);
        $this->stocked(50, ['name' => 'Plenty', 'reorder_level' => 2]);

        $this->get('/products?filter=low')->assertOk()->assertSee('Almost Gone')->assertDontSee('Plenty');
        $this->get('/')->assertOk()->assertSee('Almost Gone')->assertSee('1 left');
    }

    public function test_forms_offer_active_products_only(): void
    {
        $this->stocked(4, ['name' => 'Kingston RAM', 'sku' => 'RAM-16', 'brand' => 'Kingston']);
        Product::factory()->create(['name' => 'Retired Thing', 'is_active' => false]);

        foreach (['/invoices/create', '/quotations/create', '/purchases/create'] as $page) {
            $this->get($page)->assertOk()->assertSee('RAM-16 · Kingston RAM (Kingston)')->assertDontSee('Retired Thing');
        }
    }

    public function test_unknown_product_is_rejected(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/invoices', ['product_id' => [999]] + $this->invoicePayload($product, 1))
            ->assertJsonValidationErrors('product_id.0');
    }
}

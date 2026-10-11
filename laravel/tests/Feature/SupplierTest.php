<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    public function test_purchases_and_bills_link_and_total_up(): void
    {
        $purchase = Purchase::create([
            'supplier_name' => 'Tech Supplies', 'date' => '2026-10-01', 'subtotal' => 10000, 'status' => 'confirmed', 'amount_paid' => 0,
        ]);
        $purchase->applyPayment(4000);
        $bill = VendorBill::create([
            'supplier_name' => 'tech supplies', 'bill_date' => '2026-10-01', 'amount' => 3000, 'amount_paid' => 1000, 'status' => 'partially_paid',
        ]);

        $supplier = Supplier::firstOrFail();
        $this->assertSame(1, Supplier::count());
        $this->assertSame($supplier->id, $bill->supplier_id);

        // Billed 10,000 + 3,000; paid 4,000 + 1,000; owed 6,000 + 2,000
        $this->get('/suppliers')->assertOk()->assertSeeInOrder(['Tech Supplies', 'Rs. 13,000.00', 'Rs. 5,000.00', 'Rs. 8,000.00']);
        $this->get("/suppliers/{$supplier->id}")->assertOk()
            ->assertSee('PO-'.$purchase->id)
            ->assertSee('BILL-'.$bill->id)
            ->assertSee('Rs. 8,000.00');
    }

    public function test_rename_and_merge(): void
    {
        $first = VendorBill::create(['supplier_name' => 'Dialog', 'bill_date' => '2026-10-01', 'amount' => 100, 'status' => 'unpaid']);
        $second = VendorBill::create(['supplier_name' => 'Dialog Axiata', 'bill_date' => '2026-10-01', 'amount' => 100, 'status' => 'unpaid']);

        $this->put("/suppliers/{$second->supplier_id}", ['name' => 'Dialog Axiata PLC'])->assertRedirect();
        $this->assertSame('Dialog Axiata PLC', $second->refresh()->supplier_name);

        $this->post("/suppliers/{$first->supplier_id}/merge", ['target_id' => $second->supplier_id])->assertRedirect();
        $this->assertSame(1, Supplier::count());
        $this->assertSame('Dialog Axiata PLC', $first->refresh()->supplier_name);
    }

    public function test_supplier_forms_offer_existing_names(): void
    {
        Supplier::factory()->create(['name' => 'Known Supplier']);

        $this->get('/purchases/create')->assertOk()->assertSee('<option value="Known Supplier">', false);
        $this->get('/bills/create?supplier_name=Known+Supplier')->assertOk()->assertSee('value="Known Supplier"', false);
        $this->get('/payments/make')->assertOk()->assertSee('<option value="Known Supplier">', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PaymentMade;
use App\Models\PaymentReceived;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    private function invoice(float $subtotal = 10000): Invoice
    {
        return Invoice::create([
            'invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01',
            'subtotal' => $subtotal, 'status' => 'confirmed', 'amount_paid' => 0,
        ]);
    }

    private function receive(Invoice $invoice, float $amount): void
    {
        $this->post('/payments/receive', [
            'invoice_id' => $invoice->id, 'customer_name' => 'Acme', 'amount' => $amount,
            'payment_date' => '2026-10-05', 'method' => 'cash',
        ])->assertRedirect(route('payments.index', ['type' => 'received']));
    }

    public function test_partial_payment_keeps_invoice_confirmed(): void
    {
        // The legacy SQL marked this invoice "paid" after half the money arrived
        $invoice = $this->invoice();
        $this->receive($invoice, 5000);

        $invoice->refresh();
        $this->assertSame(5000.0, $invoice->amount_paid);
        $this->assertSame('confirmed', $invoice->status);
    }

    public function test_full_payment_marks_invoice_paid(): void
    {
        $invoice = $this->invoice();
        $this->receive($invoice, 6000);
        $this->receive($invoice, 4000);

        $invoice->refresh();
        $this->assertSame(10000.0, $invoice->amount_paid);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_payment_above_balance_is_rejected(): void
    {
        // Capping it silently used to lose money: deleting a payment later reversed more than was applied
        $invoice = $this->invoice();
        $this->receive($invoice, 6000);

        $this->post('/payments/receive', [
            'invoice_id' => $invoice->id, 'customer_name' => 'Acme', 'amount' => 6000,
            'payment_date' => '2026-10-05', 'method' => 'cash',
        ])->assertSessionHasErrors(['amount' => 'The amount is more than the balance due (Rs. 4,000.00).']);

        $this->assertSame(6000.0, $invoice->refresh()->amount_paid);
        $this->assertDatabaseCount('payments_received', 1);
    }

    public function test_payment_to_a_paid_invoice_is_rejected(): void
    {
        $invoice = $this->invoice();
        $this->receive($invoice, 10000);

        $this->post('/payments/receive', [
            'invoice_id' => $invoice->id, 'customer_name' => 'Acme', 'amount' => 1,
            'payment_date' => '2026-10-05', 'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments_received', 1);
    }

    public function test_editing_payment_above_balance_is_rejected(): void
    {
        $invoice = $this->invoice();
        $this->receive($invoice, 4000);
        $this->receive($invoice, 5000);
        $payment = PaymentReceived::firstOrFail();

        // 4000 of its own + 1000 still owed = 5000 at most
        $this->put("/payments/received/{$payment->id}", [
            'party_name' => 'Acme', 'amount' => 5001, 'payment_date' => '2026-10-06', 'method' => 'cash',
        ])->assertSessionHasErrors('amount');
        $this->assertSame(9000.0, $invoice->refresh()->amount_paid);

        $this->put("/payments/received/{$payment->id}", [
            'party_name' => 'Acme', 'amount' => 5000, 'payment_date' => '2026-10-06', 'method' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertSame(10000.0, $invoice->refresh()->amount_paid);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_editing_payment_swaps_amount_on_invoice(): void
    {
        $invoice = $this->invoice();
        $this->receive($invoice, 4000);
        $payment = PaymentReceived::firstOrFail();

        $this->put("/payments/received/{$payment->id}", [
            'party_name' => 'Acme Ltd', 'amount' => 10000, 'payment_date' => '2026-10-06', 'method' => 'cheque',
            'reference' => 'CHQ-1',
        ])->assertRedirect(route('payments.index', ['type' => 'received']));

        $this->assertSame('Acme Ltd', $payment->refresh()->customer_name);
        $this->assertSame(10000.0, $invoice->refresh()->amount_paid);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_deleting_payment_reverses_invoice_balance(): void
    {
        $invoice = $this->invoice();
        $this->receive($invoice, 10000);
        $payment = PaymentReceived::firstOrFail();

        $this->delete("/payments/received/{$payment->id}")->assertRedirect();

        $this->assertSame(0.0, $invoice->refresh()->amount_paid);
        $this->assertSame('confirmed', $invoice->status);
        $this->assertDatabaseCount('payments_received', 0);
    }

    public function test_payment_made_against_purchase(): void
    {
        $this->post('/purchases', [
            'supplier_name' => 'Tech Supplies', 'purchase_no' => 'PO-7', 'date' => '2026-10-01', 'status' => 'confirmed',
            'description' => ['RAM 16GB', ''], 'brand' => ['Kingston', ''], 'quantity' => [4, 1], 'unit_price' => [8000, 0],
        ])->assertRedirect(route('purchases.index'));
        $purchase = Purchase::firstOrFail();
        $this->assertSame(32000.0, $purchase->subtotal);
        $this->assertCount(1, $purchase->items);

        $this->get('/payments/make?purchase_id='.$purchase->id)->assertOk()->assertSee('PO-7');
        $this->post('/payments/make', [
            'purchase_id' => $purchase->id, 'supplier_name' => 'Tech Supplies', 'amount' => 32000,
            'payment_date' => '2026-10-02', 'method' => 'bank_transfer',
        ])->assertRedirect(route('payments.index', ['type' => 'made']));

        $this->assertSame('paid', $purchase->refresh()->status);
        $this->get('/payments?type=made')->assertOk()->assertSee('Tech Supplies');
        $this->get("/purchases/{$purchase->id}")->assertOk()->assertSee('100% paid');

        $this->delete('/payments/made/'.PaymentMade::firstOrFail()->id);
        $this->assertSame(0.0, $purchase->refresh()->amount_paid);
    }

    public function test_purchase_paid_status_follows_payments(): void
    {
        $purchase = Purchase::create([
            'supplier_name' => 'Tech Supplies', 'date' => '2026-10-01', 'subtotal' => 1000, 'status' => 'paid', 'amount_paid' => 1000,
        ]);
        $form = [
            'supplier_name' => 'Tech Supplies', 'date' => '2026-10-01', 'status' => 'paid',
            'description' => ['RAM'], 'quantity' => [3], 'unit_price' => [500],
        ];

        $this->put("/purchases/{$purchase->id}", $form)->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $purchase->refresh()->status);

        $this->put("/purchases/{$purchase->id}", ['unit_price' => [100]] + $form)->assertSessionHasErrors('subtotal');
        $this->assertSame(1500.0, $purchase->refresh()->subtotal);
    }

    public function test_pages_render(): void
    {
        $invoice = $this->invoice();
        $this->receive($invoice, 2500);

        $this->get('/payments')->assertOk()->assertSee('#1500');
        $this->get('/payments/receive?invoice_id='.$invoice->id)->assertOk()->assertSee('7500.00');
        $this->get('/payments/make')->assertOk();
    }
}

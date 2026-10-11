<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobNote;
use App\Models\PaymentReceived;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    private function invoice(string $customer, float $subtotal = 1000, string $no = '1500'): Invoice
    {
        return Invoice::create([
            'invoice_no' => $no, 'customer_name' => $customer, 'date' => '2026-10-01', 'subtotal' => $subtotal, 'status' => 'confirmed',
        ]);
    }

    public function test_documents_link_to_one_customer_per_name_ignoring_case(): void
    {
        $invoice = $this->invoice('Acme Ltd');
        $quotation = Quotation::create([
            'quotation_no' => 1180, 'prepared_for' => ' acme ltd ', 'prepared_by' => 'Shane', 'date' => '2026-10-01',
            'validity_date' => '2026-10-08', 'subtotal' => 100, 'terms_conditions' => '',
        ]);
        $payment = PaymentReceived::create(['customer_name' => 'ACME LTD', 'amount' => 10, 'payment_date' => '2026-10-02', 'method' => 'cash']);

        $this->assertSame(1, Customer::count());
        $customer = Customer::firstOrFail();
        $this->assertSame('Acme Ltd', $customer->name);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame($customer->id, $quotation->customer_id);
        $this->assertSame($customer->id, $payment->customer_id);
    }

    public function test_changing_the_name_on_a_document_relinks_it(): void
    {
        $invoice = $this->invoice('Acme Ltd');
        $invoice->update(['customer_name' => 'Beta Co']);

        $this->assertSame(Customer::where('name', 'Beta Co')->value('id'), $invoice->customer_id);
    }

    public function test_new_customer_from_job_note_keeps_contact_details(): void
    {
        JobNote::create([
            'job_no' => 10, 'customer_name' => 'Gamma Pvt', 'contact_person' => 'Nimal', 'contact_no' => '0771234567',
            'email' => 'nimal@gamma.lk', 'item' => 'Laptop', 'fault' => 'No power', 'collected_date' => '2026-10-01',
            'collected_by' => 'Shane', 'terms_conditions' => '',
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Gamma Pvt', 'contact_person' => 'Nimal', 'phone' => '0771234567', 'email' => 'nimal@gamma.lk',
        ]);
    }

    public function test_index_shows_balances_and_searches(): void
    {
        $this->invoice('Acme Ltd', 5000)->applyPayment(2000);
        $this->invoice('Beta Co', 700, '1501');

        $this->get('/customers')->assertOk()
            ->assertSeeInOrder(['Acme Ltd', 'Rs. 5,000.00', 'Rs. 2,000.00', 'Rs. 3,000.00', 'Beta Co']);
        $this->get('/customers?search=beta')->assertOk()->assertSee('Beta Co')->assertDontSee('Acme Ltd');

        // Quotations count as documents too
        Quotation::create([
            'quotation_no' => 1180, 'prepared_for' => 'Quote Only', 'prepared_by' => 'Shane', 'date' => '2026-10-01',
            'validity_date' => '2026-10-08', 'subtotal' => 100, 'terms_conditions' => '',
        ]);
        $this->get('/customers?search=quote')->assertOk()->assertSeeInOrder(['Quote Only', '<span class="badge bg-light text-dark border">1</span>'], false);
        $this->get('/customers?sort=balance')->assertOk()->assertSeeInOrder(['Acme Ltd', 'Beta Co']);
    }

    public function test_show_page_lists_documents(): void
    {
        $invoice = $this->invoice('Acme Ltd', 5000);
        $customer = $invoice->customer;

        $this->get("/customers/{$customer->id}")->assertOk()
            ->assertSee('#1500')
            ->assertSee('Rs. 5,000.00')
            ->assertSee(route('payments.receive', ['invoice_id' => $invoice->id]), false);
    }

    public function test_create_and_validation(): void
    {
        $this->post('/customers', ['name' => 'Delta', 'email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->post('/customers', ['name' => ' Delta ', 'phone' => '011 222 3333'])->assertRedirect();
        $this->post('/customers', ['name' => 'Delta'])->assertSessionHasErrors(['name' => 'A customer with this name already exists.']);

        $this->assertDatabaseHas('customers', ['name' => 'Delta', 'phone' => '011 222 3333']);
    }

    public function test_rename_updates_names_on_documents(): void
    {
        $invoice = $this->invoice('Acme');
        $customer = $invoice->customer;

        $this->put("/customers/{$customer->id}", ['name' => 'Acme Holdings'])->assertRedirect(route('customers.show', $customer));

        $this->assertSame('Acme Holdings', $invoice->refresh()->customer_name);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_merge_moves_documents_and_keeps_missing_details(): void
    {
        $old = $this->invoice('ACME', 100, '1500');
        $kept = $this->invoice('Acme Ltd', 200, '1501');
        $old->customer->update(['phone' => '0112223333']);

        $this->post("/customers/{$old->customer_id}/merge", ['target_id' => $kept->customer_id])
            ->assertRedirect(route('customers.show', $kept->customer_id));

        $this->assertSame(1, Customer::count());
        $this->assertSame($kept->customer_id, $old->refresh()->customer_id);
        $this->assertSame('Acme Ltd', $old->customer_name);
        $this->assertSame('0112223333', Customer::firstOrFail()->phone);
    }

    public function test_cannot_merge_into_itself(): void
    {
        $customer = $this->invoice('Acme')->customer;

        $this->post("/customers/{$customer->id}/merge", ['target_id' => $customer->id])->assertSessionHasErrors('target_id');
        $this->assertSame(1, Customer::count());
    }

    public function test_delete_only_without_documents(): void
    {
        $withInvoice = $this->invoice('Acme')->customer;
        $empty = Customer::factory()->create();

        $this->delete("/customers/{$withInvoice->id}")->assertSessionHasErrors('party');
        $this->delete("/customers/{$empty->id}")->assertRedirect(route('customers.index'));

        $this->assertSame([$withInvoice->id], Customer::pluck('id')->all());
    }

    public function test_document_forms_offer_existing_names(): void
    {
        Customer::factory()->create(['name' => 'Known Customer']);

        $this->get('/invoices/create')->assertOk()->assertSee('<option value="Known Customer">', false);
        $this->get('/jobs/create?customer_name=Known+Customer')->assertOk()->assertSee('value="Known Customer"', false);
    }
}

<?php

namespace Tests\Feature;

use App\Mail\DocumentMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobNote;
use App\Models\Quotation;
use App\Models\SentEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->signIn();
    }

    private function invoice(): Invoice
    {
        $invoice = Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01', 'subtotal' => 2500]);
        $invoice->items()->create(['item_no' => '01', 'description' => 'SSD', 'brand' => 'Samsung', 'quantity' => 1, 'unit_price' => 2500, 'total' => 2500]);

        return $invoice;
    }

    private function message(array $overrides = []): array
    {
        return $overrides + ['to' => 'accounts@acme.lk', 'subject' => 'Your invoice', 'message' => 'Please see attached.'];
    }

    public function test_invoice_form_is_prefilled_from_the_customer(): void
    {
        $invoice = $this->invoice();
        $invoice->customer->update(['email' => 'billing@acme.lk']);

        $this->get("/documents/invoice/{$invoice->id}/email")->assertOk()
            ->assertSee('value="billing@acme.lk"', false)
            ->assertSee('Invoice #1500 from SJ Technology')
            ->assertSee('for Rs. 2,500.00')
            ->assertSee('Not emailed yet.');
    }

    public function test_sending_an_invoice_attaches_the_pdf_and_logs_it(): void
    {
        $invoice = $this->invoice();
        config(['mail.default' => 'smtp']);

        $this->post("/documents/invoice/{$invoice->id}/email", $this->message(['cc' => 'boss@acme.lk, owner@acme.lk']))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHas('success', 'Invoice #1500 emailed to accounts@acme.lk.');

        Mail::assertSent(DocumentMail::class, function (DocumentMail $mail) {
            return $mail->hasTo('accounts@acme.lk')
                && $mail->hasCc('boss@acme.lk') && $mail->hasCc('owner@acme.lk')
                && $mail->subjectLine === 'Your invoice'
                && $mail->fileName === 'Acme_1500.pdf'
                && str_starts_with($mail->pdfContent, '%PDF');
        });
        $this->assertDatabaseHas('sent_emails', [
            'document_type' => 'invoice', 'document_id' => $invoice->id, 'recipient' => 'accounts@acme.lk', 'cc' => 'boss@acme.lk, owner@acme.lk',
        ]);
        $this->get("/documents/invoice/{$invoice->id}/email")->assertSee('accounts@acme.lk')->assertDontSee('Not emailed yet.');
    }

    public function test_address_is_saved_on_customer_only_when_asked(): void
    {
        $invoice = $this->invoice();

        $this->post("/documents/invoice/{$invoice->id}/email", $this->message());
        $this->assertNull($invoice->customer->refresh()->email);

        $this->post("/documents/invoice/{$invoice->id}/email", $this->message(['save_email' => '1']));
        $this->assertSame('accounts@acme.lk', $invoice->customer->refresh()->email);
    }

    public function test_without_a_mail_server_the_user_is_told_it_was_not_sent(): void
    {
        $invoice = $this->invoice();

        $this->get("/documents/invoice/{$invoice->id}/email")->assertSee("Email delivery isn't set up yet", false);
        $this->post("/documents/invoice/{$invoice->id}/email", $this->message())
            ->assertSessionHas('warning', "Invoice #1500 was written to the mail log, not sent: email delivery isn't set up yet.");
    }

    public function test_invalid_addresses_are_rejected(): void
    {
        $invoice = $this->invoice();

        $this->post("/documents/invoice/{$invoice->id}/email", $this->message(['to' => 'nope']))->assertSessionHasErrors('to');
        $this->post("/documents/invoice/{$invoice->id}/email", $this->message(['cc' => 'ok@acme.lk, broken']))
            ->assertSessionHasErrors(['cc.1' => '“broken” in CC is not a valid email address.']);

        Mail::assertNothingSent();
        $this->assertSame(0, SentEmail::count());
    }

    public function test_quotation_and_job_note_emails(): void
    {
        $quotation = Quotation::create([
            'quotation_no' => 1180, 'prepared_for' => 'Acme', 'prepared_by' => 'Shane', 'date' => '2026-10-01',
            'validity_date' => '2026-10-08', 'subtotal' => 100, 'terms_conditions' => 'Terms',
        ]);
        $job = JobNote::create([
            'job_no' => 10, 'customer_name' => 'Acme', 'contact_person' => 'Nimal', 'email' => 'nimal@acme.lk', 'item' => 'MacBook',
            'fault' => 'No power', 'collected_date' => '2026-10-01', 'collected_by' => 'Shane', 'terms_conditions' => 'Terms',
        ]);

        $this->get("/documents/quotation/{$quotation->id}/email")->assertOk()->assertSee('valid until 08 Oct 2026');
        $this->post("/documents/quotation/{$quotation->id}/email", $this->message())->assertRedirect(route('quotations.show', $quotation));

        $this->get("/documents/job/{$job->id}/email")->assertOk()->assertSee('value="nimal@acme.lk"', false)->assertSee('Dear Nimal');
        $this->post("/documents/job/{$job->id}/email", $this->message())->assertRedirect(route('jobs.show', $job));

        Mail::assertSent(DocumentMail::class, 2);
    }

    public function test_statement_email_keeps_the_period(): void
    {
        $customer = Customer::factory()->create(['name' => 'Acme', 'email' => 'acme@example.com']);

        $this->get("/documents/statement/{$customer->id}/email?period_from=2026-01-01&period_to=2026-06-30")->assertOk()
            ->assertSee('Account statement 01 Jan 2026 – 30 Jun 2026')
            ->assertSee('name="period_from" value="2026-01-01"', false);

        $this->post("/documents/statement/{$customer->id}/email", $this->message(['period_from' => '2026-01-01', 'period_to' => '2026-06-30']))
            ->assertRedirect();

        Mail::assertSent(DocumentMail::class, fn (DocumentMail $mail) => $mail->fileName === 'statement_acme_2026-01-01_to_2026-06-30.pdf');
    }

    public function test_unknown_document_types_and_ids_are_not_found(): void
    {
        $this->get('/documents/receipt/1/email')->assertNotFound();
        $this->get('/documents/invoice/999/email')->assertNotFound();
    }
}

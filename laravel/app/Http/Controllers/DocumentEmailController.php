<?php

namespace App\Http\Controllers;

use App\Mail\DocumentMail;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobNote;
use App\Models\Quotation;
use App\Models\SentEmail;
use App\Services\DocumentPdf;
use Barryvdh\DomPDF\PDF as RenderedPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

/**
 * Email an invoice, quotation, job note or customer statement as a PDF attachment.
 */
class DocumentEmailController extends Controller
{
    public const TYPES = ['invoice', 'quotation', 'job', 'statement'];

    public function __construct(private DocumentPdf $pdfs) {}

    public function create(Request $request, string $type, int $id): View
    {
        $document = $this->document($request, $type, $id);

        return view('emails.create', [
            'type' => $type,
            'id' => $id,
            'document' => $document,
            'history' => SentEmail::for($type, $id)->with('user:id,username')->limit(10)->get(),
            'mailIsLive' => ! in_array(config('mail.default'), ['log', 'array'], true),
        ]);
    }

    public function send(Request $request, string $type, int $id): RedirectResponse
    {
        $document = $this->document($request, $type, $id);

        $data = $request->validate([
            'to' => ['required', 'email', 'max:255'],
            'cc' => ['nullable', 'string', 'max:500'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'save_email' => ['nullable', 'boolean'],
        ]);

        $cc = collect(preg_split('/[\s,;]+/', (string) ($data['cc'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))->unique()->values();
        Validator::make(['cc' => $cc->all()], ['cc.*' => ['email']], ['cc.*.email' => '“:input” in CC is not a valid email address.'])->validate();

        /** @var RenderedPdf $pdf */
        [$fileName, $pdf] = ($document['pdf'])();

        try {
            Mail::to($data['to'], $document['name'])
                ->cc($cc->all())
                ->send(new DocumentMail($data['subject'], $data['message'], $fileName, $pdf->output()));
        } catch (Throwable $e) {
            Log::error("Could not email {$type} {$id}: ".$e->getMessage());

            return back()->withInput()->withErrors(['to' => 'The email could not be sent. Check the mail settings and try again.']);
        }

        SentEmail::create([
            'document_type' => $type,
            'document_id' => $id,
            'recipient' => $data['to'],
            'cc' => $cc->implode(', ') ?: null,
            'subject' => $data['subject'],
            'user_id' => $request->user()->id,
        ]);

        ActivityLog::record('emailed', "{$document['label']} to {$data['to']}", $type === 'statement' ? 'customer' : $type, $id);

        $customer = $document['customer'];
        if ($customer && blank($customer->email) && ($data['save_email'] ?? false)) {
            $customer->update(['email' => $data['to']]);
        }

        $mailIsLive = ! in_array(config('mail.default'), ['log', 'array'], true);

        return redirect($document['back'])->with(
            $mailIsLive ? 'success' : 'warning',
            $mailIsLive
                // With a queue running, the email goes out in the background a moment later
                ? (config('queue.default') === 'sync' ? "{$document['label']} emailed to {$data['to']}." : "{$document['label']} is being emailed to {$data['to']}.")
                : "{$document['label']} was written to the mail log, not sent: email delivery isn't set up yet.",
        );
    }

    /**
     * Everything the form and the sender need to know about one document.
     *
     * @return array{label: string, name: string, email: ?string, customer: ?Customer, subject: string, message: string, back: string, pdf: callable(): array{0: string, 1: RenderedPdf}, from?: Carbon, to?: Carbon}
     */
    private function document(Request $request, string $type, int $id): array
    {
        $company = config('sjpos.company');
        $signOff = "\n\nThank you,\n{$company['name']}\n{$company['phone']}";

        return match ($type) {
            'invoice' => (function () use ($id, $company, $signOff) {
                $invoice = Invoice::with('customer')->findOrFail($id);
                $balanceLine = $invoice->status === 'confirmed' && $invoice->balance > 0 && $invoice->amount_paid > 0
                    ? ' The balance due is '.rs($invoice->balance).'.'
                    : '';

                return [
                    'label' => "Invoice #{$invoice->invoice_no}",
                    'name' => $invoice->customer_name,
                    'email' => $invoice->customer?->email,
                    'customer' => $invoice->customer,
                    'subject' => "Invoice #{$invoice->invoice_no} from {$company['name']}",
                    'message' => "Dear {$invoice->customer_name},\n\nPlease find attached invoice #{$invoice->invoice_no} dated "
                        .$invoice->date?->format('d M Y').' for '.rs($invoice->subtotal).'.'.$balanceLine.$signOff,
                    'back' => route('invoices.show', $invoice),
                    'pdf' => fn () => $this->pdfs->invoice($invoice),
                ];
            })(),
            'quotation' => (function () use ($id, $company, $signOff) {
                $quotation = Quotation::with('customer')->findOrFail($id);

                return [
                    'label' => "Quotation #{$quotation->quotation_no}",
                    'name' => $quotation->prepared_for,
                    'email' => $quotation->customer?->email,
                    'customer' => $quotation->customer,
                    'subject' => "Quotation #{$quotation->quotation_no} from {$company['name']}",
                    'message' => "Dear {$quotation->prepared_for},\n\nThank you for your enquiry. Please find attached quotation #{$quotation->quotation_no} for "
                        .rs($quotation->subtotal).', valid until '.$quotation->validity_date?->format('d M Y').".\n\nPlease let us know if you have any questions."
                        .$signOff,
                    'back' => route('quotations.show', $quotation),
                    'pdf' => fn () => $this->pdfs->quotation($quotation),
                ];
            })(),
            'job' => (function () use ($id, $signOff) {
                $job = JobNote::with('customer')->findOrFail($id);
                $name = $job->contact_person ?: $job->customer_name;

                return [
                    'label' => "Job note {$job->formatted_no}",
                    'name' => $name,
                    'email' => $job->email ?: $job->customer?->email,
                    'customer' => $job->customer,
                    'subject' => "Job note {$job->formatted_no} – {$job->item}",
                    'message' => "Dear {$name},\n\nWe have received your {$job->item} for repair. Your job note {$job->formatted_no} is attached for your records; "
                        .'please quote this number when contacting us about the repair.'.$signOff,
                    'back' => route('jobs.show', $job),
                    'pdf' => fn () => $this->pdfs->jobNote($job),
                ];
            })(),
            'statement' => (function () use ($request, $id, $signOff) {
                $customer = Customer::findOrFail($id);
                // Named period_* so they can't clash with the "to" address field
                $period = $request->validate([
                    'period_from' => ['nullable', 'date_format:Y-m-d'],
                    'period_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:period_from'],
                ]);
                $from = Carbon::parse($period['period_from'] ?? now()->startOfYear())->startOfDay();
                $to = Carbon::parse($period['period_to'] ?? now()->endOfYear())->endOfDay();

                return [
                    'label' => "Statement for {$customer->name}",
                    'name' => $customer->contact_person ?: $customer->name,
                    'email' => $customer->email,
                    'customer' => $customer,
                    'subject' => 'Account statement '.$from->format('d M Y').' – '.$to->format('d M Y'),
                    'message' => 'Dear '.($customer->contact_person ?: $customer->name).",\n\nPlease find attached your account statement for "
                        .$from->format('d M Y').' to '.$to->format('d M Y').'.'.$signOff,
                    'back' => route('reports.show', ['report' => 'statement', 'customer_id' => $customer->id, 'period' => 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString()]),
                    'pdf' => fn () => $this->pdfs->statement($customer, $from, $to),
                    'from' => $from,
                    'to' => $to,
                ];
            })(),
        };
    }
}

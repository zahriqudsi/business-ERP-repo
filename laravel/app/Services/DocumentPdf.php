<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JobNote;
use App\Models\Quotation;
use App\Services\Reports\ReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as RenderedPdf;
use Illuminate\Support\Carbon;

/**
 * PDFs of documents, shared by the download links and email attachments.
 */
class DocumentPdf
{
    public function __construct(private ReportBuilder $reports) {}

    /**
     * @return array{0: string, 1: RenderedPdf} file name and PDF
     */
    public function invoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['items', 'bankDetail', 'notes']);

        return [
            str_replace(' ', '_', (string) $invoice->customer_name).'_'.$invoice->invoice_no.'.pdf',
            Pdf::loadView('pdf.invoice', compact('invoice'))->setPaper('a4'),
        ];
    }

    /**
     * @return array{0: string, 1: RenderedPdf}
     */
    public function quotation(Quotation $quotation): array
    {
        $quotation->loadMissing('items');

        return [
            $this->slug($quotation->prepared_for).'_'.$this->slug((string) $quotation->quotation_no).'.pdf',
            Pdf::loadView('pdf.quotation', compact('quotation'))->setPaper('a4'),
        ];
    }

    /**
     * @return array{0: string, 1: RenderedPdf}
     */
    public function jobNote(JobNote $job): array
    {
        return [
            'job_note_'.$job->formatted_no.'_'.$this->slug($job->customer_name).'.pdf',
            Pdf::loadView('pdf.job-note', ['job' => $job, 'logoSrc' => logo_data_uri()])->setPaper('a4'),
        ];
    }

    /**
     * @return array{0: string, 1: RenderedPdf}
     */
    public function statement(Customer $customer, Carbon $from, Carbon $to): array
    {
        return [
            'statement_'.$this->slug($customer->name).'_'.$from->format('Y-m-d').'_to_'.$to->format('Y-m-d').'.pdf',
            Pdf::loadView('reports.pdf', ['report' => $this->reports->statement($customer, $from, $to)])->setPaper('a4'),
        ];
    }

    private function slug(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($value));
    }
}

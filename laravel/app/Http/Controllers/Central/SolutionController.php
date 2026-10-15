<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * "Who it's for" pages: the same product, described for one kind of business.
 */
class SolutionController extends Controller
{
    /**
     * @var array<string, array{title: string, eyebrow: string, icon: string, headline: string, lead: string, pains: list<string>, points: list<array{0: string, 1: string, 2: string}>}>
     */
    public const SOLUTIONS = [
        'repair-shops' => [
            'title' => 'Repair shops',
            'eyebrow' => 'For repair shops',
            'icon' => 'tools',
            'headline' => 'Run your repair shop without the paperwork',
            'lead' => 'Phone, computer, electronics or CCTV repairs: take devices in with the customer\'s signature, quote the work, invoice the job and the parts, and keep your spare parts in stock.',
            'pains' => ['Paper job cards that get lost or are hard to read', 'Arguments about what the device looked like when it came in', 'Not knowing which jobs and invoices are still unpaid'],
            'points' => [
                ['pen', 'Signed job notes', 'Fault, accessories, condition and the customer\'s on-screen signature, with your terms.'],
                ['file-earmark-text', 'Quote, then invoice', 'Send a quotation for the repair, then convert it into an invoice in one click.'],
                ['box-seam', 'Spare parts stock', 'Parts used on invoices leave stock automatically, with low-stock warnings.'],
                ['hourglass-split', 'Who still owes you', 'Outstanding balances by customer and age, and statements to send.'],
            ],
        ],
        'retail-and-trading' => [
            'title' => 'Shops & traders',
            'eyebrow' => 'For shops and traders',
            'icon' => 'shop',
            'headline' => 'Know your stock, your sales and who owes you',
            'lead' => 'Hardware, stationery, IT equipment or spare parts: invoice customers, buy from suppliers, and let stock and balances keep themselves up to date.',
            'pains' => ['Stock counts that never match the books', 'Credit customers who are hard to keep track of', 'Supplier bills that slip past their due date'],
            'points' => [
                ['receipt', 'Fast invoices', 'Pick products to fill in prices, add tax when needed and email the PDF.'],
                ['cart', 'Purchases and bills', 'Record what you buy and what you owe suppliers, with due dates.'],
                ['box-seam', 'Stock that updates itself', 'Every invoice and purchase moves stock, with a history for each product.'],
                ['bar-chart-line', 'Profit and stock value', 'See sales against costs, and the value of what is on your shelves.'],
            ],
        ],
        'service-businesses' => [
            'title' => 'Service businesses',
            'eyebrow' => 'For service businesses',
            'icon' => 'briefcase',
            'headline' => 'Quote with confidence, invoice on time, get paid',
            'lead' => 'Installers, agencies, contractors and consultants: send clear quotations, invoice when the work is done, record part payments and follow up what is owed.',
            'pains' => ['Quotes and invoices typed again and again in Word or Excel', 'Part payments that make balances confusing', 'No clear view of how the month is going'],
            'points' => [
                ['file-earmark-text', 'Professional quotations', 'Your logo, terms and validity date, emailed straight to the client.'],
                ['arrow-repeat', 'Quote to invoice', 'The accepted quotation becomes the invoice, with nothing to retype.'],
                ['cash-coin', 'Part payments', 'Record deposits and instalments; the balance is always right.'],
                ['graph-up-arrow', 'Monthly reports', 'Sales, payments and profit, ready to print or download.'],
            ],
        ],
    ];

    public function __invoke(string $solution): View
    {
        return view('central.solution', ['solution' => self::SOLUTIONS[$solution], 'slug' => $solution]);
    }
}

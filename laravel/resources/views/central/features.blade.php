@extends('layouts.marketing')

@section('title', 'Features')
@section('description', 'Everything in '.config('sjpos.product_name').': quotations, invoices, payments, outstanding balances, repair job notes, purchases, stock, reports, users and backups.')

@section('content')
@php $product = config('sjpos.product_name'); @endphp
<section class="bl-hero pb-5">
    <div class="container text-center" style="max-width: 820px">
        <span class="bl-eyebrow">Features</span>
        <h1 class="mt-3 mb-4" style="font-size: clamp(2.2rem, 4.6vw, 3.4rem)">Everything you need. <span class="bl-highlight">Nothing you don't.</span></h1>
        <p class="bl-lead">{{ $product }} covers the whole path from the first quote to the money in your bank, and the reports that tell you how the business is doing.</p>
    </div>
</section>

@foreach ([
    ['Selling', 'Win the job and send the bill', [
        ['file-earmark-text', '', 'Quotations', 'Prepare price offers with your terms, validity date and optional tax. Email or print them as PDFs.'],
        ['arrow-repeat', '', 'One-click conversion', 'Turn an accepted quotation into an invoice with the same customer, items and tax. Each quotation converts only once.'],
        ['receipt', '', 'Invoices', 'Professional invoices with your logo, address, bank details or a payment note, and terms that you choose.'],
        ['percent', '', 'Tax', 'Charge VAT or another tax at your rate, with your registration number printed on documents.'],
        ['envelope', '', 'Email from the app', 'Send invoices, quotations, job notes and statements as PDF attachments, under your business name.'],
        ['123', '', 'Safe numbering', 'Invoice numbers never repeat, even when two people save at the same moment.'],
    ]],
    ['Getting paid', 'Know exactly what is owed', [
        ['cash-coin', 'green', 'Payments received', 'Record cash, bank transfer, cheque and online payments against invoices, in full or in parts.'],
        ['hourglass-split', 'green', 'Outstanding balances', 'See every customer who owes you, the oldest unpaid date, and amounts grouped from current to 90+ days.'],
        ['person-lines-fill', 'green', 'Customer statements', 'Invoices, payments and the running balance for one customer, for any period, by email or PDF.'],
        ['shield-check', 'green', 'Balances you can trust', 'A payment can never be more than what is due, and an invoice with payments can\'t be deleted by accident.'],
    ]],
    ['Service & repairs', 'Take in work professionally', [
        ['tools', 'amber', 'Job notes', 'Customer and contact details, the item and serial number, the reported fault, accessories and condition.'],
        ['pen', 'amber', 'On-screen signatures', 'The customer signs with a finger or mouse when the device is handed over, or later when it suits.'],
        ['printer', 'amber', 'Print or email', 'Give the customer a numbered job note with your terms and conditions.'],
    ]],
    ['Buying & stock', 'Keep track of what comes in and goes out', [
        ['cart', 'sky', 'Purchases', 'Record what you buy from suppliers item by item, from draft to confirmed and paid.'],
        ['file-earmark-ruled', 'sky', 'Vendor bills', 'Rent, utilities and services, with due dates and automatic overdue status.'],
        ['box-seam', 'sky', 'Products & stock', 'Prices, costs and stock levels that update from invoices and purchases, with low-stock warnings.'],
        ['clipboard-check', 'sky', 'Stock counts', 'Add, remove or set the count after a check, always with a reason and a history.'],
    ]],
    ['Running the business', 'Stay in control', [
        ['bar-chart-line', 'violet', 'Eight ready-made reports', 'Sales, purchases & bills, profit & loss, payments, receivables and payables aging, stock value and statements.'],
        ['download', 'violet', 'Export anywhere', 'Download any report as a spreadsheet (CSV) or PDF, or print it.'],
        ['people', 'pink', 'Users and roles', 'Owner, manager and staff roles decide who can delete records, see reports or change settings.'],
        ['clock-history', 'pink', 'Activity log', 'Who created, changed, deleted, emailed or signed in, and when.'],
        ['shield-lock', 'pink', 'Sign-in codes', 'Optionally ask for a code sent by email after the password.'],
        ['database-down', 'pink', 'Backups', 'Automatic nightly backups, and a download button for owners.'],
    ]],
] as $index => [$group, $heading, $items])
    <section class="bl-section-sm {{ $index % 2 ? 'bl-band' : '' }}">
        <div class="container">
            <div class="mb-4">
                <span class="bl-eyebrow">{{ $group }}</span>
                <h2 class="bl-title mt-3" style="font-size: clamp(1.6rem, 3vw, 2.2rem)">{{ $heading }}</h2>
            </div>
            <div class="row g-4">
                @foreach ($items as [$icon, $tone, $title, $text])
                    <div class="col-md-6 col-lg-4">
                        <div class="bl-card">
                            <div class="bl-icon {{ $tone }}"><i class="bi bi-{{ $icon }}"></i></div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endforeach
@endsection

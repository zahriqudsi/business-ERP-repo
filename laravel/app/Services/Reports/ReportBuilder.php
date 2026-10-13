<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\PaymentReceived;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds every report from the database. Sales and purchases count confirmed and paid documents
 * (drafts and cancelled ones are left out).
 */
class ReportBuilder
{
    /**
     * key => [title, description, icon, uses a date range]
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: bool}>
     */
    public const REPORTS = [
        'sales' => ['Sales', 'Invoiced sales by month, customer and invoice.', 'graph-up-arrow', true],
        'purchases' => ['Purchases & Bills', 'Spending with suppliers, from purchase orders and vendor bills.', 'cart', true],
        'profit' => ['Profit & Loss', 'Sales against product costs and supplier spending.', 'calculator', true],
        'payments' => ['Payments', 'Money received and paid out, by method.', 'cash-stack', true],
        'receivables' => ['Receivables Aging', 'Who owes you, grouped by how long the invoices have been open.', 'hourglass-split', false],
        'payables' => ['Payables Aging', 'What you owe suppliers, grouped by age.', 'hourglass-bottom', false],
        'stock' => ['Stock Valuation', 'Current stock levels and their value at cost and selling price.', 'box-seam', false],
        'statement' => ['Customer Statement', 'Invoices, payments and running balance for one customer.', 'person-lines-fill', true],
    ];

    private const COUNTED = ['confirmed', 'paid'];

    private const AGING_BUCKETS = ['0_30' => '0–30 days', '31_60' => '31–60 days', '61_90' => '61–90 days', 'over_90' => '90+ days'];

    /**
     * @param  array<string, mixed>  $options
     */
    public function build(string $key, Carbon $from, Carbon $to, array $options = []): Report
    {
        return match ($key) {
            'sales' => $this->sales($from, $to),
            'purchases' => $this->purchases($from, $to),
            'profit' => $this->profit($from, $to),
            'payments' => $this->payments($from, $to),
            'receivables' => $this->receivables(),
            'payables' => $this->payables(),
            'stock' => $this->stock(),
            'statement' => $this->statement(Customer::findOrFail($options['customer_id'] ?? 0), $from, $to),
        };
    }

    public function sales(Carbon $from, Carbon $to): Report
    {
        $invoices = DB::table('invoices')
            ->whereIn('status', self::COUNTED)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')->orderBy('id')
            ->get(['date', 'invoice_no', 'customer_name', 'status', 'subtotal', 'tax_amount', 'amount_paid']);

        $total = (float) $invoices->sum('subtotal');
        $paid = (float) $invoices->sum('amount_paid');
        $tax = (float) $invoices->sum('tax_amount');

        // Tax figures appear only when the period has taxed invoices
        $taxName = config('sjpos.tax.name') ?: 'Tax';
        $summary = ['Invoices' => [$invoices->count(), 'int'], 'Total sales' => [$total, 'money']]
            + ($tax > 0 ? ["{$taxName} collected" => [$tax, 'money']] : [])
            + [
                'Collected' => [$paid, 'money'],
                'Still owed' => [$total - $paid, 'money'],
                'Average invoice' => [$invoices->isEmpty() ? 0 : $total / $invoices->count(), 'money'],
            ];
        $invoiceColumns = [
            'date' => ['Date', 'date'], 'invoice_no' => ['Invoice #', 'text'], 'customer_name' => ['Customer', 'text'], 'status' => ['Status', 'text'],
        ] + ($tax > 0 ? ['tax_amount' => [$taxName, 'money']] : []) + [
            'subtotal' => ['Total', 'money'], 'amount_paid' => ['Paid', 'money'], 'balance' => ['Balance', 'money'],
        ];

        return new Report('Sales Report', $from, $to, $summary, [
            $this->byPeriodTable($invoices, $from, $to, 'date', ['subtotal' => 'Sales'], 'Invoices'),
            [
                'title' => 'By customer',
                'columns' => [
                    'customer' => ['Customer', 'text'], 'count' => ['Invoices', 'int'],
                    'total' => ['Sales', 'money'], 'paid' => ['Paid', 'money'], 'balance' => ['Balance', 'money'],
                ],
                'rows' => $invoices->groupBy(fn ($row) => $row->customer_name ?: 'Unknown')
                    ->map(fn (Collection $rows, string $customer) => [
                        'customer' => $customer,
                        'count' => $rows->count(),
                        'total' => $rows->sum('subtotal'),
                        'paid' => $rows->sum('amount_paid'),
                        'balance' => $rows->sum('subtotal') - $rows->sum('amount_paid'),
                    ])->sortByDesc('total')->values()->all(),
            ],
            [
                'title' => 'Invoices',
                'columns' => $invoiceColumns,
                'rows' => $invoices->map(fn ($row) => (array) $row + ['balance' => $row->subtotal - $row->amount_paid])
                    ->map(fn (array $row) => ['status' => ucfirst($row['status'])] + $row)->all(),
                'totals' => ['tax_amount' => $tax, 'subtotal' => $total, 'amount_paid' => $paid, 'balance' => $total - $paid],
                'empty' => 'No invoices in this period.',
            ],
        ]);
    }

    public function purchases(Carbon $from, Carbon $to): Report
    {
        $between = [$from->toDateString(), $to->toDateString()];
        $purchases = DB::table('purchases')->whereIn('status', self::COUNTED)->whereBetween('date', $between)
            ->orderBy('date')->orderBy('id')
            ->get(['id', 'date', 'purchase_no', 'supplier_name', 'subtotal', 'amount_paid'])
            ->map(fn ($row) => (object) [
                'date' => $row->date, 'kind' => 'Purchase', 'number' => $row->purchase_no ?: 'PO-'.$row->id,
                'supplier' => $row->supplier_name, 'amount' => (float) $row->subtotal, 'paid' => (float) $row->amount_paid,
            ]);
        $bills = DB::table('vendor_bills')->whereBetween('bill_date', $between)
            ->orderBy('bill_date')->orderBy('id')
            ->get(['id', 'bill_date', 'bill_no', 'supplier_name', 'amount', 'amount_paid'])
            ->map(fn ($row) => (object) [
                'date' => $row->bill_date, 'kind' => 'Bill', 'number' => $row->bill_no ?: 'BILL-'.$row->id,
                'supplier' => $row->supplier_name, 'amount' => (float) $row->amount, 'paid' => (float) $row->amount_paid,
            ]);
        $all = $purchases->concat($bills)->sortBy('date')->values();
        $total = (float) $all->sum('amount');
        $paid = (float) $all->sum('paid');

        return new Report('Purchases & Bills Report', $from, $to, [
            'Purchase orders' => [(float) $purchases->sum('amount'), 'money'],
            'Vendor bills' => [(float) $bills->sum('amount'), 'money'],
            'Total spending' => [$total, 'money'],
            'Paid' => [$paid, 'money'],
            'Still owed' => [$total - $paid, 'money'],
        ], [
            $this->byPeriodTable($all, $from, $to, 'date', ['amount' => 'Spending'], 'Documents'),
            [
                'title' => 'By supplier',
                'columns' => [
                    'supplier' => ['Supplier', 'text'], 'purchases' => ['Purchases', 'money'], 'bills' => ['Bills', 'money'],
                    'total' => ['Total', 'money'], 'paid' => ['Paid', 'money'], 'balance' => ['Balance', 'money'],
                ],
                'rows' => $all->groupBy(fn ($row) => $row->supplier ?: 'Unknown')
                    ->map(fn (Collection $rows, string $supplier) => [
                        'supplier' => $supplier,
                        'purchases' => $rows->where('kind', 'Purchase')->sum('amount'),
                        'bills' => $rows->where('kind', 'Bill')->sum('amount'),
                        'total' => $rows->sum('amount'),
                        'paid' => $rows->sum('paid'),
                        'balance' => $rows->sum('amount') - $rows->sum('paid'),
                    ])->sortByDesc('total')->values()->all(),
            ],
            [
                'title' => 'Documents',
                'columns' => [
                    'date' => ['Date', 'date'], 'kind' => ['Type', 'text'], 'number' => ['Number', 'text'], 'supplier' => ['Supplier', 'text'],
                    'amount' => ['Amount', 'money'], 'paid' => ['Paid', 'money'], 'balance' => ['Balance', 'money'],
                ],
                'rows' => $all->map(fn ($row) => (array) $row + ['balance' => $row->amount - $row->paid])->all(),
                'totals' => ['amount' => $total, 'paid' => $paid, 'balance' => $total - $paid],
                'empty' => 'No purchases or bills in this period.',
            ],
        ]);
    }

    public function profit(Carbon $from, Carbon $to): Report
    {
        $between = [$from->toDateString(), $to->toDateString()];

        $sales = (float) DB::table('invoices')->whereIn('status', self::COUNTED)->whereBetween('date', $between)->sum(DB::raw('COALESCE(net_amount, subtotal)'));
        $purchases = (float) DB::table('purchases')->whereIn('status', self::COUNTED)->whereBetween('date', $between)->sum('subtotal');
        $bills = (float) DB::table('vendor_bills')->whereBetween('bill_date', $between)->sum('amount');

        $products = DB::table('invoice_items as it')
            ->join('invoices as i', 'i.id', '=', 'it.invoice_id')
            ->join('products as p', 'p.id', '=', 'it.product_id')
            ->whereIn('i.status', self::COUNTED)->whereBetween('i.date', $between)
            ->groupBy('p.id', 'p.name', 'p.brand', 'p.cost_price')
            ->selectRaw('p.name, p.brand, SUM(it.quantity) AS quantity, SUM(it.total) AS revenue, SUM(it.quantity) * p.cost_price AS cost')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'product' => trim($row->name.($row->brand ? ' ('.$row->brand.')' : '')),
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
                'cost' => (float) $row->cost,
                'profit' => (float) $row->revenue - (float) $row->cost,
                'margin' => (float) $row->revenue > 0 ? ((float) $row->revenue - (float) $row->cost) / (float) $row->revenue * 100 : null,
            ]);

        $costOfGoods = (float) $products->sum('cost');
        $grossProfit = $sales - $costOfGoods;

        $monthly = $this->months($from, $to)->map(function (Carbon $month) {
            $range = [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()];
            $sales = (float) DB::table('invoices')->whereIn('status', self::COUNTED)->whereBetween('date', $range)->sum(DB::raw('COALESCE(net_amount, subtotal)'));
            $spending = (float) DB::table('purchases')->whereIn('status', self::COUNTED)->whereBetween('date', $range)->sum('subtotal')
                + (float) DB::table('vendor_bills')->whereBetween('bill_date', $range)->sum('amount');

            return ['month' => $month->format('M Y'), 'sales' => $sales, 'spending' => $spending, 'net' => $sales - $spending];
        });

        return new Report('Profit & Loss', $from, $to, [
            'Sales' => [$sales, 'money'],
            'Cost of products sold' => [$costOfGoods, 'money'],
            'Gross profit' => [$grossProfit, 'money'],
            'Gross margin' => [$sales > 0 ? $grossProfit / $sales * 100 : 0, 'percent'],
            'Purchases + bills' => [$purchases + $bills, 'money'],
            'Sales minus spending' => [$sales - $purchases - $bills, 'money'],
        ], [
            [
                'title' => 'By month',
                'columns' => ['month' => ['Month', 'text'], 'sales' => ['Sales', 'money'], 'spending' => ['Purchases + bills', 'money'], 'net' => ['Net', 'money']],
                'rows' => $monthly->all(),
                'totals' => ['sales' => $monthly->sum('sales'), 'spending' => $monthly->sum('spending'), 'net' => $monthly->sum('net')],
            ],
            [
                'title' => 'Product margins',
                'columns' => [
                    'product' => ['Product', 'text'], 'quantity' => ['Qty sold', 'int'], 'revenue' => ['Revenue', 'money'],
                    'cost' => ['Cost', 'money'], 'profit' => ['Profit', 'money'], 'margin' => ['Margin', 'percent'],
                ],
                'rows' => $products->all(),
                'totals' => ['quantity' => $products->sum('quantity'), 'revenue' => $products->sum('revenue'), 'cost' => $costOfGoods, 'profit' => $products->sum('profit')],
                'empty' => 'No invoice lines are linked to products in this period.',
            ],
        ], 'Sales are shown before tax. Product costs use each product’s current cost price; invoice lines not linked to a product (such as repairs) have no cost here.');
    }

    public function payments(Carbon $from, Carbon $to): Report
    {
        $between = [$from->toDateString(), $to->toDateString()];
        $received = DB::table('payments_received as p')->leftJoin('invoices as i', 'i.id', '=', 'p.invoice_id')
            ->whereBetween('p.payment_date', $between)->orderBy('p.payment_date')->orderBy('p.id')
            ->get(['p.payment_date', 'p.customer_name as party', 'i.invoice_no as document', 'p.method', 'p.reference', 'p.amount']);
        $made = DB::table('payments_made as p')->leftJoin('purchases as pu', 'pu.id', '=', 'p.purchase_id')
            ->whereBetween('p.payment_date', $between)->orderBy('p.payment_date')->orderBy('p.id')
            ->get(['p.payment_date', 'p.supplier_name as party', DB::raw("COALESCE(pu.purchase_no, CONCAT('PO-', pu.id)) AS document"), 'p.method', 'p.reference', 'p.amount']);

        $methodName = fn (?string $method) => PaymentReceived::METHODS[$method] ?? ($method ? ucfirst($method) : 'Unspecified');
        $columns = [
            'payment_date' => ['Date', 'date'], 'party' => ['Name', 'text'], 'document' => ['Document', 'text'],
            'method' => ['Method', 'text'], 'reference' => ['Reference', 'text'], 'amount' => ['Amount', 'money'],
        ];
        $rows = fn (Collection $payments) => $payments->map(fn ($row) => ['method' => $methodName($row->method)] + (array) $row)->all();

        return new Report('Payments Report', $from, $to, [
            'Received' => [(float) $received->sum('amount'), 'money'],
            'Paid out' => [(float) $made->sum('amount'), 'money'],
            'Net cash in' => [(float) $received->sum('amount') - (float) $made->sum('amount'), 'money'],
        ], [
            [
                'title' => 'By method',
                'columns' => ['method' => ['Method', 'text'], 'received' => ['Received', 'money'], 'made' => ['Paid out', 'money']],
                'rows' => $received->pluck('method')->concat($made->pluck('method'))->unique()->values()
                    ->map(fn ($method) => [
                        'method' => $methodName($method),
                        'received' => $received->where('method', $method)->sum('amount'),
                        'made' => $made->where('method', $method)->sum('amount'),
                    ])->all(),
            ],
            ['title' => 'Received from customers', 'columns' => $columns, 'rows' => $rows($received),
                'totals' => ['amount' => $received->sum('amount')], 'empty' => 'No payments received in this period.'],
            ['title' => 'Paid to suppliers', 'columns' => $columns, 'rows' => $rows($made),
                'totals' => ['amount' => $made->sum('amount')], 'empty' => 'No payments made in this period.'],
        ]);
    }

    public function receivables(): Report
    {
        $today = today();
        $invoices = DB::table('invoices')->where('status', 'confirmed')->whereColumn('subtotal', '>', 'amount_paid')
            ->orderBy('date')->get(['date', 'invoice_no', 'customer_name', 'subtotal', 'amount_paid'])
            ->map(fn ($row) => [
                'date' => $row->date,
                'invoice_no' => $row->invoice_no,
                'customer' => $row->customer_name ?: 'Unknown',
                'total' => (float) $row->subtotal,
                'balance' => (float) $row->subtotal - (float) $row->amount_paid,
                'days' => max(0, (int) Carbon::parse($row->date)->diffInDays($today)),
            ]);

        return $this->agingReport('Receivables Aging', 'customer', 'Customer', $invoices, [
            'date' => ['Invoice date', 'date'], 'invoice_no' => ['Invoice #', 'text'], 'customer' => ['Customer', 'text'],
            'days' => ['Days open', 'int'], 'total' => ['Total', 'money'], 'balance' => ['Balance', 'money'],
        ]);
    }

    public function payables(): Report
    {
        $today = today();
        $purchases = DB::table('purchases')->where('status', 'confirmed')->whereColumn('subtotal', '>', 'amount_paid')
            ->get(['id', 'date', 'purchase_no', 'supplier_name', 'subtotal', 'amount_paid'])
            ->map(fn ($row) => [
                'date' => $row->date, 'kind' => 'Purchase', 'number' => $row->purchase_no ?: 'PO-'.$row->id,
                'supplier' => $row->supplier_name ?: 'Unknown', 'total' => (float) $row->subtotal,
                'balance' => (float) $row->subtotal - (float) $row->amount_paid,
                'days' => max(0, (int) Carbon::parse($row->date)->diffInDays($today)),
            ]);
        // Bills age from their due date when they have one
        $bills = DB::table('vendor_bills')->whereColumn('amount', '>', 'amount_paid')
            ->get(['id', 'bill_date', 'due_date', 'bill_no', 'supplier_name', 'amount', 'amount_paid'])
            ->map(fn ($row) => [
                'date' => $row->bill_date, 'kind' => 'Bill', 'number' => $row->bill_no ?: 'BILL-'.$row->id,
                'supplier' => $row->supplier_name ?: 'Unknown', 'total' => (float) $row->amount,
                'balance' => (float) $row->amount - (float) $row->amount_paid,
                'days' => max(0, (int) Carbon::parse($row->due_date ?? $row->bill_date)->diffInDays($today, false)),
            ]);

        return $this->agingReport('Payables Aging', 'supplier', 'Supplier', $purchases->concat($bills)->sortBy('date')->values(), [
            'date' => ['Date', 'date'], 'kind' => ['Type', 'text'], 'number' => ['Number', 'text'], 'supplier' => ['Supplier', 'text'],
            'days' => ['Days open', 'int'], 'total' => ['Total', 'money'], 'balance' => ['Balance', 'money'],
        ], 'Bills are aged from their due date when one is set; purchases from their order date.');
    }

    public function stock(): Report
    {
        $products = DB::table('products')->where('is_active', true)->where('track_stock', true)->orderBy('name')
            ->get(['sku', 'name', 'brand', 'category', 'stock_qty', 'reorder_level', 'cost_price', 'unit_price'])
            ->map(fn ($row) => (array) $row + [
                'cost_value' => max(0, $row->stock_qty) * (float) $row->cost_price,
                'retail_value' => max(0, $row->stock_qty) * (float) $row->unit_price,
                'status' => match (true) {
                    $row->stock_qty <= 0 => 'Out of stock',
                    $row->stock_qty <= (int) $row->reorder_level => 'Low',
                    default => 'OK',
                },
            ]);

        return new Report('Stock Valuation', null, today(), [
            'Products' => [$products->count(), 'int'],
            'Units in stock' => [$products->sum(fn ($row) => max(0, $row['stock_qty'])), 'int'],
            'Value at cost' => [$products->sum('cost_value'), 'money'],
            'Value at selling price' => [$products->sum('retail_value'), 'money'],
            'Low / out of stock' => [$products->whereIn('status', ['Low', 'Out of stock'])->count(), 'int'],
        ], [
            [
                'title' => 'Stock on hand',
                'columns' => [
                    'sku' => ['SKU', 'text'], 'name' => ['Product', 'text'], 'brand' => ['Brand', 'text'], 'category' => ['Category', 'text'],
                    'stock_qty' => ['Qty', 'int'], 'status' => ['Status', 'text'], 'cost_price' => ['Cost', 'money'],
                    'cost_value' => ['Value (cost)', 'money'], 'retail_value' => ['Value (selling)', 'money'],
                ],
                'rows' => $products->all(),
                'totals' => ['cost_value' => $products->sum('cost_value'), 'retail_value' => $products->sum('retail_value')],
                'empty' => 'No stocked products yet.',
            ],
        ]);
    }

    /**
     * Opening balance, every invoice and payment in the period, and a running balance.
     */
    public function statement(Customer $customer, Carbon $from, Carbon $to): Report
    {
        $invoices = DB::table('invoices')->where('customer_id', $customer->id)->whereIn('status', self::COUNTED);
        $payments = DB::table('payments_received')->where('customer_id', $customer->id);

        $opening = (float) (clone $invoices)->where('date', '<', $from->toDateString())->sum('subtotal')
            - (float) (clone $payments)->where('payment_date', '<', $from->toDateString())->sum('amount');

        $between = [$from->toDateString(), $to->toDateString()];
        $lines = (clone $invoices)->whereBetween('date', $between)->get(['date', 'invoice_no', 'subtotal'])
            ->map(fn ($row) => ['date' => $row->date, 'description' => 'Invoice #'.$row->invoice_no, 'debit' => (float) $row->subtotal, 'credit' => null])
            ->concat((clone $payments)->whereBetween('payment_date', $between)->get(['payment_date', 'method', 'reference', 'amount'])
                ->map(fn ($row) => [
                    'date' => $row->payment_date,
                    'description' => 'Payment – '.(PaymentReceived::METHODS[$row->method] ?? 'Other').($row->reference ? ' ('.$row->reference.')' : ''),
                    'debit' => null,
                    'credit' => (float) $row->amount,
                ]))
            ->sortBy([['date', 'asc'], ['credit', 'asc']])
            ->values();

        $balance = $opening;
        $rows = [['date' => $from->toDateString(), 'description' => 'Opening balance', 'debit' => null, 'credit' => null, 'balance' => $opening]];
        foreach ($lines as $line) {
            $balance += ($line['debit'] ?? 0) - ($line['credit'] ?? 0);
            $rows[] = $line + ['balance' => $balance];
        }

        return new Report('Statement – '.$customer->name, $from, $to, [
            'Opening balance' => [$opening, 'money'],
            'Invoiced' => [(float) $lines->sum('debit'), 'money'],
            'Paid' => [(float) $lines->sum('credit'), 'money'],
            'Closing balance' => [$balance, 'money'],
        ], [
            [
                'title' => 'Account activity',
                'columns' => [
                    'date' => ['Date', 'date'], 'description' => ['Description', 'text'],
                    'debit' => ['Invoiced', 'money'], 'credit' => ['Paid', 'money'], 'balance' => ['Balance', 'money'],
                ],
                'rows' => $rows,
                'totals' => ['debit' => $lines->sum('debit'), 'credit' => $lines->sum('credit'), 'balance' => $balance],
            ],
        ]);
    }

    /**
     * Shared layout of the receivables/payables aging reports.
     *
     * @param  Collection<int, array<string, mixed>>  $documents  each with 'balance', 'days' and $partyKey
     * @param  array<string, array{0: string, 1: string}>  $columns
     */
    private function agingReport(string $title, string $partyKey, string $partyLabel, Collection $documents, array $columns, ?string $note = null): Report
    {
        $bucket = fn (int $days) => match (true) {
            $days <= 30 => '0_30',
            $days <= 60 => '31_60',
            $days <= 90 => '61_90',
            default => 'over_90',
        };
        $documents = $documents->map(fn (array $row) => $row + ['bucket' => $bucket($row['days'])]);

        $summary = ['Total outstanding' => [(float) $documents->sum('balance'), 'money']];
        foreach (self::AGING_BUCKETS as $key => $label) {
            $summary[$label] = [(float) $documents->where('bucket', $key)->sum('balance'), 'money'];
        }

        $bucketColumns = collect(self::AGING_BUCKETS)->map(fn (string $label) => [$label, 'money'])->all();

        return new Report($title, null, today(), $summary, [
            [
                'title' => 'By '.strtolower($partyLabel),
                'columns' => [$partyKey => [$partyLabel, 'text']] + $bucketColumns + ['total' => ['Total', 'money']],
                'rows' => $documents->groupBy($partyKey)
                    ->map(fn (Collection $rows, string $party) => [$partyKey => $party]
                        + collect(self::AGING_BUCKETS)->map(fn ($label, string $key) => $rows->where('bucket', $key)->sum('balance'))->all()
                        + ['total' => $rows->sum('balance')])
                    ->sortByDesc('total')->values()->all(),
                'totals' => collect(self::AGING_BUCKETS)->map(fn ($label, string $key) => $documents->where('bucket', $key)->sum('balance'))->all()
                    + ['total' => $documents->sum('balance')],
                'empty' => 'Nothing outstanding.',
            ],
            [
                'title' => 'Open documents',
                'columns' => $columns,
                'rows' => $documents->all(),
                'totals' => ['balance' => $documents->sum('balance')],
                'empty' => 'Nothing outstanding.',
            ],
        ], $note);
    }

    /**
     * Totals per day (ranges up to 31 days) or per month.
     *
     * @param  Collection<int, object>  $rows
     * @param  array<string, string>  $sums  field => heading
     * @return array{title: string, columns: array<string, array{0: string, 1: string}>, rows: list<array<string, mixed>>, totals: array<string, mixed>}
     */
    private function byPeriodTable(Collection $rows, Carbon $from, Carbon $to, string $dateField, array $sums, string $countLabel): array
    {
        $daily = $from->diffInDays($to) <= 31;
        $format = $daily ? 'Y-m-d' : 'Y-m';
        $grouped = $rows->groupBy(fn ($row) => Carbon::parse($row->{$dateField})->format($format));

        $periods = $daily
            ? collect(range(0, (int) $from->diffInDays($to)))->map(fn (int $offset) => $from->copy()->addDays($offset))
            : $this->months($from, $to);

        $tableRows = $periods->map(function (Carbon $period) use ($grouped, $format, $daily, $sums) {
            $group = $grouped->get($period->format($format), collect());
            $row = ['period' => $daily ? $period->format('D d M') : $period->format('M Y'), 'count' => $group->count()];
            foreach (array_keys($sums) as $field) {
                $row[$field] = (float) $group->sum($field);
            }

            return $row;
        });

        $columns = ['period' => [$daily ? 'Day' : 'Month', 'text'], 'count' => [$countLabel, 'int']];
        foreach ($sums as $field => $heading) {
            $columns[$field] = [$heading, 'money'];
        }

        return [
            'title' => $daily ? 'By day' : 'By month',
            'columns' => $columns,
            'rows' => $tableRows->all(),
            'totals' => ['count' => $tableRows->sum('count')] + collect($sums)->map(fn ($heading, string $field) => $tableRows->sum($field))->all(),
        ];
    }

    /**
     * @return Collection<int, Carbon> first day of each month in the range
     */
    private function months(Carbon $from, Carbon $to): Collection
    {
        $months = collect();
        for ($month = $from->copy()->startOfMonth(); $month->lte($to); $month->addMonth()) {
            $months->push($month->copy());
        }

        return $months;
    }
}

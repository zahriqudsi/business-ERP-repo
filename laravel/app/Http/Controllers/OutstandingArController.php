<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Accounts receivable: customers with unpaid/partially paid invoices,
 * drilling down into one customer's outstanding invoices and their items.
 */
class OutstandingArController extends Controller
{
    private const PER_PAGE = 5;

    public function __invoke(Request $request): View
    {
        $customer = trim((string) $request->query('customer', ''));

        return $customer === ''
            ? $this->customers(trim((string) $request->query('search', '')))
            : $this->customer($request, $customer);
    }

    private function customers(string $search): View
    {
        $invoices = $this->outstanding()
            ->when($search !== '', fn (Builder $q) => $q->where('i.customer_name', 'like', "%{$search}%"))
            ->get();

        $customers = $invoices
            ->groupBy(fn ($inv) => ($inv->customer_name ?? '') !== '' ? $inv->customer_name : 'Unknown Customer')
            ->map(fn (Collection $rows, string $name) => (object) [
                'name' => $name,
                'invoice_count' => $rows->count(),
                'total_invoiced' => $rows->sum('subtotal'),
                'total_paid' => $rows->sum('amount_paid'),
                'total_balance' => $rows->sum('balance'),
                'max_days' => (int) $rows->max('days_outstanding'),
            ])
            ->sortByDesc('total_balance')
            ->values();

        $aging = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        foreach ($invoices as $inv) {
            $days = (int) $inv->days_outstanding;
            $bucket = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => '1_30',
                $days <= 60 => '31_60',
                $days <= 90 => '61_90',
                default => 'over_90',
            };
            $aging[$bucket] += (float) $inv->balance;
        }

        return view('outstanding-ar.index', [
            'search' => $search,
            'customers' => $customers,
            'aging' => $aging,
            'totalOutstanding' => (float) $invoices->sum('balance'),
            'totalInvoiced' => (float) $invoices->sum('subtotal'),
            'totalPaid' => (float) $invoices->sum('amount_paid'),
            'invoiceCount' => $invoices->count(),
        ]);
    }

    private function customer(Request $request, string $customer): View
    {
        $search = trim((string) $request->query('inv_search', ''));

        // Newest first. A search term matches the invoice number or any item description/brand.
        $all = $this->outstanding()
            ->addSelect('i.invoice_no', 'i.date', 'i.status')
            ->where('i.customer_name', $customer)
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = "%{$search}%";
                $q->where(fn (Builder $w) => $w
                    ->where('i.invoice_no', 'like', $like)
                    ->orWhereExists(fn (Builder $e) => $e->from('invoice_items as it2')
                        ->whereColumn('it2.invoice_id', 'i.id')
                        ->where(fn (Builder $m) => $m->where('it2.description', 'like', $like)->orWhere('it2.brand', 'like', $like))));
            })
            ->orderByDesc('i.date')->orderByDesc('i.id')
            ->get();

        $page = max(1, (int) $request->query('page', 1));
        $page = min($page, max(1, (int) ceil($all->count() / self::PER_PAGE)));
        $pageInvoices = $all->forPage($page, self::PER_PAGE)->values();

        // Fetch items only for the invoices on this page
        $items = InvoiceItem::whereIn('invoice_id', $pageInvoices->pluck('id'))
            ->orderBy('invoice_id')->orderBy('item_no')->get()->groupBy('invoice_id');
        $pageInvoices->each(fn ($inv) => $inv->items = $items->get($inv->id, collect()));

        $paginator = new LengthAwarePaginator($pageInvoices, $all->count(), self::PER_PAGE, $page, [
            'path' => route('outstanding-ar'),
            'query' => array_filter(['customer' => $customer, 'inv_search' => $search]),
        ]);

        return view('outstanding-ar.customer', [
            'customer' => $customer,
            'search' => $search,
            'invoices' => $paginator,
            'invoiceCount' => $all->count(),
            'totalBalance' => (float) $all->sum('balance'),
            'totalInvoiced' => (float) $all->sum('subtotal'),
            'totalPaid' => (float) $all->sum('amount_paid'),
        ]);
    }

    /**
     * Invoices that still have a balance to collect.
     */
    private function outstanding(): Builder
    {
        return DB::table('invoices as i')
            ->select('i.id', 'i.customer_name', 'i.subtotal', 'i.amount_paid')
            ->selectRaw('(i.subtotal - i.amount_paid) AS balance')
            ->selectRaw('DATEDIFF(CURDATE(), i.date) AS days_outstanding')
            ->whereIn('i.status', ['confirmed', 'draft'])
            ->whereColumn('i.subtotal', '>', 'i.amount_paid');
    }
}

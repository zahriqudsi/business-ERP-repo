<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\JobNote;
use App\Models\Product;
use App\Models\VendorBill;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public const PERIODS = ['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Year'];

    public function __invoke(Request $request): View
    {
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : 'month';
        [$from, $to, $label] = $this->range($period);
        $between = [$from->toDateString(), $to->toDateString()];

        $sales = DB::table('invoices')
            ->whereIn('status', ['confirmed', 'paid'])->whereBetween('date', $between)
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(subtotal), 0) AS total')->first();

        $purchases = DB::table('purchases')
            ->whereIn('status', ['confirmed', 'paid'])->whereBetween('date', $between)
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(subtotal), 0) AS total')->first();

        $received = DB::table('payments_received')->whereBetween('payment_date', $between)
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total')->first();

        $made = DB::table('payments_made')->whereBetween('payment_date', $between)
            ->selectRaw('COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total')->first();

        // Invoices and purchases have no due date, so "overdue" means older than the credit period
        $overdueBefore = today()->subDays((int) config('sjpos.credit_days'))->toDateString();
        $outstanding = fn (string $table) => DB::table($table)->where('status', 'confirmed')
            ->selectRaw('COALESCE(SUM(subtotal - amount_paid), 0) AS total,
                COALESCE(SUM(CASE WHEN date < ? THEN subtotal - amount_paid ELSE 0 END), 0) AS overdue', [$overdueBefore])
            ->first();

        // Payables include unpaid vendor bills; a bill is overdue once its due date has passed
        $ap = $outstanding('purchases');
        $bills = DB::table('vendor_bills')->whereColumn('amount', '>', 'amount_paid')
            ->selectRaw('COALESCE(SUM(amount - amount_paid), 0) AS total,
                COALESCE(SUM(CASE WHEN due_date < ? THEN amount - amount_paid ELSE 0 END), 0) AS overdue', [today()->toDateString()])
            ->first();
        $ap->total += $bills->total;
        $ap->overdue += $bills->overdue;

        $topCustomers = DB::table('invoices')
            ->whereIn('status', ['confirmed', 'paid'])->whereBetween('date', $between)
            ->groupBy('customer_id', 'customer_name')
            ->selectRaw('customer_id, customer_name, COUNT(*) AS orders, COALESCE(SUM(subtotal), 0) AS revenue')
            ->orderByDesc('revenue')->limit(5)->get();

        return view('dashboard', [
            'period' => $period,
            'label' => $label,
            'sales' => $sales,
            'purchases' => $purchases,
            'received' => $received,
            'made' => $made,
            'ar' => $outstanding('invoices'),
            'ap' => $ap,
            'trend' => $this->trend(),
            'topCustomers' => $topCustomers,
            'recentInvoices' => Invoice::orderByDesc('date')->orderByDesc('id')->limit(8)->get(),
            'pendingBills' => VendorBill::whereIn('status', ['unpaid', 'overdue', 'partially_paid'])
                ->orderBy('due_date')->limit(5)->get(),
            'recentJobs' => JobNote::select('id', 'job_no', 'customer_name', 'item', 'collected_date')
                ->selectRaw('customer_signature IS NOT NULL AS signed')
                ->orderByDesc('job_no')->limit(6)->get(),
            'jobsPendingSignature' => JobNote::whereNull('customer_signature')->count(),
            'lowStock' => Product::lowStock()->orderBy('stock_qty')->orderBy('name')->limit(5)->get(),
        ]);
    }

    /**
     * Sales and money collected for each of the last six months.
     *
     * @return list<array{label: string, sales: float, collected: float}>
     */
    private function trend(): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $sales = DB::table('invoices')->whereIn('status', ['confirmed', 'paid'])->where('date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') AS month, SUM(subtotal) AS total")->groupBy('month')->pluck('total', 'month');
        $collected = DB::table('payments_received')->where('payment_date', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total")->groupBy('month')->pluck('total', 'month');

        return collect(range(0, 5))->map(function (int $offset) use ($start, $sales, $collected) {
            $month = $start->copy()->addMonths($offset);

            return [
                'label' => $month->format('M'),
                'sales' => (float) ($sales[$month->format('Y-m')] ?? 0),
                'collected' => (float) ($collected[$month->format('Y-m')] ?? 0),
            ];
        })->all();
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function range(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy(), $now->copy(), 'Today ('.$now->format('d M Y').')'],
            'week' => [
                $from = $now->copy()->startOfWeek(Carbon::MONDAY),
                $to = $now->copy()->endOfWeek(Carbon::SUNDAY),
                'This Week ('.$from->format('d M').' – '.$to->format('d M Y').')',
            ],
            'quarter' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter(), 'This Quarter'],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'This Year ('.$now->year.')'],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'This Month ('.$now->format('F Y').')'],
        };
    }
}

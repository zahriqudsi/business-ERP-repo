<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * @extends PartyController<Customer>
 */
class CustomerController extends PartyController
{
    protected function model(): string
    {
        return Customer::class;
    }

    protected function kind(): string
    {
        return 'customer';
    }

    protected function withTotals(Builder $query): Builder
    {
        $invoices = fn (string $expression, array $statuses = ['confirmed', 'paid']) => DB::table('invoices')
            ->selectRaw("COALESCE({$expression}, 0)")
            ->whereColumn('invoices.customer_id', 'customers.id')
            ->whereIn('status', $statuses);

        return $query->select('customers.*')
            ->selectRaw('(SELECT COUNT(*) FROM invoices WHERE invoices.customer_id = customers.id)
                + (SELECT COUNT(*) FROM quotations WHERE quotations.customer_id = customers.id)
                + (SELECT COUNT(*) FROM job_notes WHERE job_notes.customer_id = customers.id) AS document_count')
            ->selectSub($invoices('SUM(subtotal)'), 'billed')
            ->selectSub($invoices('SUM(amount_paid)'), 'paid')
            ->selectSub($invoices('SUM(subtotal - amount_paid)', ['confirmed']), 'balance');
    }

    public function show(Customer $customer): View
    {
        $invoices = $customer->invoices()->orderByDesc('date')->orderByDesc('id')->get();
        $billable = $invoices->whereIn('status', ['confirmed', 'paid']);

        return view('customers.show', [
            'customer' => $customer,
            'invoices' => $invoices,
            'quotations' => $customer->quotations()->orderByDesc('date')->orderByDesc('id')->get(),
            'jobs' => $customer->jobNotes()->orderByDesc('job_no')->get(),
            'payments' => $customer->payments()->with('invoice:id,invoice_no')->orderByDesc('payment_date')->orderByDesc('id')->get(),
            'billed' => (float) $billable->sum('subtotal'),
            'paid' => (float) $billable->sum('amount_paid'),
            'balance' => (float) $invoices->where('status', 'confirmed')->sum(fn ($invoice) => $invoice->balance),
            'mergeTargets' => Customer::whereKeyNot($customer->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return $this->editParty($customer);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        return $this->updateParty($request, $customer);
    }

    public function merge(Request $request, Customer $customer): RedirectResponse
    {
        return $this->mergeParty($request, $customer);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        return $this->destroyParty($customer);
    }
}

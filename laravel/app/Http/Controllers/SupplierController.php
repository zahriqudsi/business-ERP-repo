<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends PartyController<Supplier>
 */
class SupplierController extends PartyController
{
    protected function model(): string
    {
        return Supplier::class;
    }

    protected function kind(): string
    {
        return 'supplier';
    }

    /**
     * Supplier totals combine purchase orders and vendor bills.
     */
    protected function withTotals(Builder $query): Builder
    {
        $purchases = fn (string $expression, array $statuses = ['confirmed', 'paid']) => "(SELECT COALESCE({$expression}, 0) FROM purchases
            WHERE purchases.supplier_id = suppliers.id AND purchases.status IN ('".implode("','", $statuses)."'))";
        $bills = fn (string $expression) => "(SELECT COALESCE({$expression}, 0) FROM vendor_bills WHERE vendor_bills.supplier_id = suppliers.id)";

        return $query->select('suppliers.*')
            ->selectRaw($purchases('COUNT(*)', ['draft', 'confirmed', 'paid', 'cancelled']).' + '.$bills('COUNT(*)').' AS document_count')
            ->selectRaw($purchases('SUM(subtotal)').' + '.$bills('SUM(amount)').' AS billed')
            ->selectRaw($purchases('SUM(amount_paid)').' + '.$bills('SUM(amount_paid)').' AS paid')
            ->selectRaw($purchases('SUM(subtotal - amount_paid)', ['confirmed']).' + '.$bills('SUM(amount - amount_paid)').' AS balance');
    }

    public function show(Supplier $supplier): View
    {
        $purchases = $supplier->purchases()->orderByDesc('date')->orderByDesc('id')->get();
        $bills = $supplier->bills()->orderByDesc('bill_date')->orderByDesc('id')->get();
        $billablePurchases = $purchases->whereIn('status', ['confirmed', 'paid']);

        return view('suppliers.show', [
            'supplier' => $supplier,
            'purchases' => $purchases,
            'bills' => $bills,
            'payments' => $supplier->payments()->with('purchase:id,purchase_no')->orderByDesc('payment_date')->orderByDesc('id')->get(),
            'billed' => (float) $billablePurchases->sum('subtotal') + (float) $bills->sum('amount'),
            'paid' => (float) $billablePurchases->sum('amount_paid') + (float) $bills->sum('amount_paid'),
            'balance' => (float) $purchases->where('status', 'confirmed')->sum(fn ($purchase) => $purchase->balance)
                + (float) $bills->sum(fn ($bill) => $bill->balance),
            'mergeTargets' => Supplier::whereKeyNot($supplier->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        return $this->editParty($supplier);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        return $this->updateParty($request, $supplier);
    }

    public function merge(Request $request, Supplier $supplier): RedirectResponse
    {
        return $this->mergeParty($request, $supplier);
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        return $this->destroyParty($supplier);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        return view('purchases.index', [
            'purchases' => Purchase::orderByDesc('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('purchases.form', [
            'purchase' => new Purchase([
                'supplier_name' => $request->query('supplier_name', ''),
                'date' => now()->toDateString(),
                'status' => 'confirmed',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new Purchase(['amount_paid' => 0]));

        return redirect()->route('purchases.index')->with('success', 'Purchase saved successfully!');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['items', 'payments']);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View
    {
        $purchase->load('items.product');

        return view('purchases.form', compact('purchase'));
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->save($request, $purchase);

        return redirect()->route('purchases.index')->with('success', 'Purchase saved successfully!');
    }

    public function destroy(Purchase $purchase, StockLedger $stock): RedirectResponse
    {
        DB::transaction(function () use ($purchase, $stock) {
            $stock->forget($purchase);
            $purchase->delete(); // items cascade; linked payments keep their record with purchase_id set to NULL
        });

        return redirect()->route('purchases.index')->with('warning', 'Purchase deleted.');
    }

    private function save(Request $request, Purchase $purchase): void
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:100'],
            'purchase_no' => ['nullable', 'string', 'max:50'],
            'date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(Purchase::STATUSES)],
            'notes' => ['nullable', 'string'],
            'description' => ['array'],
            'description.*' => ['nullable', 'string'],
            'brand' => ['array'],
            'brand.*' => ['nullable', 'string', 'max:50'],
            'quantity' => ['array'],
            'quantity.*' => ['nullable', 'integer', 'min:0'],
            'unit_price' => ['array'],
            'unit_price.*' => ['nullable', 'numeric', 'min:0'],
            'product_id' => ['array'],
            'product_id.*' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        // Rows without a description are ignored
        $items = [];
        foreach ($data['description'] ?? [] as $i => $description) {
            if (trim((string) $description) === '') {
                continue;
            }
            $quantity = (int) ($data['quantity'][$i] ?? 0);
            $unitPrice = round((float) ($data['unit_price'][$i] ?? 0), 2);
            $items[] = [
                'product_id' => isset($data['product_id'][$i]) ? (int) $data['product_id'][$i] : null,
                'description' => trim($description),
                'brand' => $data['brand'][$i] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }

        $subtotal = array_sum(array_column($items, 'total'));
        if ($subtotal < (float) $purchase->amount_paid) {
            throw ValidationException::withMessages([
                'subtotal' => 'The new total is less than the '.rs($purchase->amount_paid).' already paid on this purchase.',
            ]);
        }

        DB::transaction(function () use ($purchase, $data, $items, $subtotal) {
            $purchase->fill([
                'supplier_name' => trim($data['supplier_name']),
                'purchase_no' => trim((string) ($data['purchase_no'] ?? '')) ?: null,
                'date' => $data['date'],
                'status' => $data['status'],
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
                'subtotal' => $subtotal,
            ]);
            // "Paid" follows the recorded payments rather than the dropdown
            $purchase->syncPaymentStatus();
            $purchase->save();

            $purchase->items()->delete();
            $purchase->items()->createMany($items);
            app(StockLedger::class)->record($purchase);
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\DocumentPdf;
use App\Services\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        return view('invoices.index', [
            'invoices' => Invoice::withSum('items', 'total')->orderByDesc('id')->get(),
            'grandTotal' => InvoiceItem::sum('total'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('invoices.form', [
            'invoice' => new Invoice([
                'invoice_no' => (string) Invoice::nextNumber(),
                'customer_name' => $request->query('customer_name', ''),
                'date' => $request->query('date', now()->toDateString()),
                'terms_conditions' => config('sjpos.invoice.default_terms'),
            ]),
            'detailsType' => '',
        ]);
    }

    /**
     * Saved over fetch() from the create form; responds with JSON.
     */
    public function store(Request $request, StockLedger $stock): JsonResponse
    {
        $data = $request->validate($this->rules() + [
            'invoice_no' => ['required', 'string', 'max:50', Rule::unique('invoices', 'invoice_no')],
        ], [
            'invoice_no.unique' => 'Invoice number already exists.',
        ]);

        DB::transaction(function () use ($request, $data, $stock) {
            $items = $this->lineItems($data);
            $rate = (new Invoice)->rateFor($request->boolean('apply_tax') && config('sjpos.tax.enabled'));

            $invoice = Invoice::create([
                'invoice_no' => $data['invoice_no'],
                'customer_name' => $data['customer_name'],
                'date' => $data['date'],
                'terms_conditions' => $data['terms_conditions'] ?? '',
            ] + Invoice::taxTotals(array_sum(array_column($items, 'total')), $rate));
            $invoice->items()->createMany($items);
            $stock->record($invoice);

            $this->saveExtraInfo($invoice, $data['details_type'] ?? null, $data['notes'] ?? null);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice saved successfully.',
            'redirect' => route('invoices.index'),
        ]);
    }

    /**
     * Full page, or just the document when loaded into the list page's modal (?ajax=1).
     */
    public function show(Request $request, Invoice $invoice): View
    {
        $invoice->load(['items', 'bankDetail', 'notes']);

        return view($request->boolean('ajax') ? 'invoices._document' : 'invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load(['items.product', 'bankDetail', 'notes']);

        return view('invoices.form', [
            'invoice' => $invoice,
            'detailsType' => match (true) {
                $invoice->bankDetail !== null => 'bank',
                $invoice->notes->isNotEmpty() => 'notes',
                default => '',
            },
        ]);
    }

    /**
     * Saved over fetch() from the edit form; responds with JSON.
     */
    public function update(Request $request, Invoice $invoice, StockLedger $stock): JsonResponse
    {
        $data = $request->validate($this->rules());
        $items = $this->lineItems($data);
        $totals = Invoice::taxTotals(array_sum(array_column($items, 'total')), $invoice->rateFor($request->boolean('apply_tax') && $invoice->offersTax()));

        if ($totals['subtotal'] < $invoice->amount_paid) {
            throw ValidationException::withMessages([
                'subtotal' => 'The new total is less than the '.rs($invoice->amount_paid).' already paid on this invoice.',
            ]);
        }

        DB::transaction(function () use ($invoice, $data, $items, $totals, $stock) {
            $invoice->fill([
                'customer_name' => $data['customer_name'],
                'date' => $data['date'],
                'terms_conditions' => $data['terms_conditions'] ?? '',
            ] + $totals);
            $invoice->syncPaymentStatus();
            $invoice->save();

            $invoice->items()->delete();
            $invoice->items()->createMany($items);
            $stock->record($invoice);

            $invoice->bankDetail()->delete();
            $invoice->notes()->delete();
            $this->saveExtraInfo($invoice, $data['details_type'] ?? null, $data['notes'] ?? null);
        });

        session()->flash('success', "Invoice #{$invoice->invoice_no} updated.");

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice updated successfully.',
            'redirect' => route('invoices.index'),
        ]);
    }

    public function destroy(Invoice $invoice, StockLedger $stock): JsonResponse
    {
        DB::transaction(function () use ($invoice, $stock) {
            $stock->forget($invoice);
            $invoice->bankDetail()->delete();
            $invoice->notes()->delete();
            $invoice->items()->delete();
            $invoice->delete();
        });

        return response()->json(['status' => 'success', 'message' => 'Invoice deleted successfully.']);
    }

    public function pdf(Invoice $invoice, DocumentPdf $pdfs): Response
    {
        [$fileName, $pdf] = $pdfs->invoice($invoice);

        return $pdf->stream($fileName);
    }

    private function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date_format:Y-m-d'],
            'terms_conditions' => ['nullable', 'string'],
            'details_type' => ['nullable', Rule::in(['bank', 'notes'])],
            'notes' => ['nullable', 'string'],
            'item_no' => ['array'],
            'item_no.*' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'array', 'min:1'],
            'description.*' => ['required', 'string'],
            'brand' => ['array'],
            'brand.*' => ['nullable', 'string', 'max:50'],
            // One quantity and price per description row
            'quantity' => ['required', 'array', 'size:'.count((array) request('description'))],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'array', 'size:'.count((array) request('description'))],
            'unit_price.*' => ['required', 'numeric', 'gt:0'],
            'product_id' => ['array'],
            'product_id.*' => ['nullable', 'integer', 'exists:products,id'],
            'apply_tax' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Build item rows from the posted arrays. Totals are recomputed server-side.
     *
     * @return list<array<string, mixed>>
     */
    private function lineItems(array $data): array
    {
        $items = [];
        foreach ($data['description'] as $i => $description) {
            $quantity = (int) $data['quantity'][$i];
            $unitPrice = round((float) $data['unit_price'][$i], 2);

            $items[] = [
                'product_id' => isset($data['product_id'][$i]) ? (int) $data['product_id'][$i] : null,
                'item_no' => ($data['item_no'][$i] ?? '') !== '' ? $data['item_no'][$i] : str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'description' => trim($description),
                'brand' => trim((string) ($data['brand'][$i] ?? '')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $items;
    }

    /**
     * The optional "Display on invoice" block: the company bank account, or a payment note.
     */
    private function saveExtraInfo(Invoice $invoice, ?string $type, ?string $note): void
    {
        if ($type === 'bank') {
            $invoice->bankDetail()->create(config('sjpos.bank'));
        } elseif ($type === 'notes') {
            $invoice->notes()->create(['notes' => trim((string) $note) ?: config('sjpos.invoice.default_note')]);
        }
    }
}

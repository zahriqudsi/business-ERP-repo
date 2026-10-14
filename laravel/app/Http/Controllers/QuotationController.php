<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\DocumentPdf;
use App\Services\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(): View
    {
        return view('quotations.index', [
            'quotations' => Quotation::withSum('items', 'total')->orderByDesc('id')->get(),
            'grandTotal' => DB::table('quotations')
                ->join('quotation_items', 'quotations.id', '=', 'quotation_items.quotation_id')
                ->sum('quotation_items.total'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('quotations.form', [
            'quotation' => new Quotation([
                'prepared_for' => $request->query('prepared_for', ''),
                'quotation_no' => Quotation::nextNumber(),
                'date' => now()->toDateString(),
                'terms_conditions' => config('sjpos.quotation.default_terms'),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, new Quotation);
    }

    public function show(Request $request, Quotation $quotation): View
    {
        $quotation->load(['items', 'invoice:id,invoice_no']);

        return view($request->boolean('ajax') ? 'quotations._document' : 'quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation): View
    {
        $quotation->load('items.product');

        return view('quotations.form', compact('quotation'));
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        return $this->save($request, $quotation);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        DB::transaction(function () use ($quotation) {
            $quotation->items()->delete();
            $quotation->delete();
        });

        return response()->json(['status' => 'success', 'message' => 'Quotation and its items deleted successfully.']);
    }

    public function pdf(Quotation $quotation, DocumentPdf $pdfs): Response
    {
        [$fileName, $pdf] = $pdfs->quotation($quotation);

        // Render once: a second dompdf output() re-subsets the already-subset fonts and garbles the text.
        $contents = $pdf->output();

        // Keep an archive copy of every generated quotation, as the legacy system did.
        Storage::put('quotations/'.$fileName, $contents);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Create a new invoice from the quotation's customer and items.
     */
    public function convert(Quotation $quotation, StockLedger $stock): RedirectResponse
    {
        $invoice = DB::transaction(function () use ($quotation, $stock) {
            // Locked so a double click can't convert the same quotation twice
            $quotation = Quotation::with('invoice:id,invoice_no')->lockForUpdate()->findOrFail($quotation->id);
            if ($quotation->invoice) {
                throw ValidationException::withMessages([
                    'quotation' => "Quotation #{$quotation->quotation_no} was already converted to invoice #{$quotation->invoice->invoice_no}.",
                ]);
            }

            $items = $quotation->items()->get(['product_id', 'item_no', 'description', 'brand', 'quantity', 'unit_price', 'total']);

            $invoice = Invoice::create([
                'invoice_no' => (string) Invoice::nextNumber(lock: true),
                'customer_name' => $quotation->prepared_for,
                'date' => now()->toDateString(),
            ] + Invoice::taxTotals($items->sum('total'), (float) $quotation->tax_rate));
            $invoice->items()->createMany($items->toArray());
            $stock->record($invoice);
            $quotation->invoice()->associate($invoice)->save();

            return $invoice;
        });

        return redirect()->route('invoices.index')
            ->with('success', "Quotation #{$quotation->quotation_no} converted to invoice #{$invoice->invoice_no}.");
    }

    private function save(Request $request, Quotation $quotation): JsonResponse
    {
        $data = $request->validate([
            'quotation_no' => ['required', 'integer', Rule::unique('quotations', 'quotation_no')->ignore($quotation->id)],
            'prepared_for' => ['required', 'string', 'max:255'],
            'prepared_by' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'validity_date' => ['required', 'date_format:Y-m-d'],
            'terms_conditions' => ['nullable', 'string'],
            'item_no' => ['required', 'array', 'min:1'],
            'item_no.*' => ['required', 'string', 'max:50'],
            // One description, brand, quantity and price per item row
            'description' => ['required', 'array', 'size:'.count((array) $request->input('item_no'))],
            'description.*' => ['required', 'string'],
            'brand' => ['required', 'array', 'size:'.count((array) $request->input('item_no'))],
            'brand.*' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'array', 'size:'.count((array) $request->input('item_no'))],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'array', 'size:'.count((array) $request->input('item_no'))],
            'unit_price.*' => ['required', 'numeric', 'min:0'],
            'product_id' => ['array'],
            'product_id.*' => ['nullable', 'integer', 'exists:products,id'],
            'apply_tax' => ['nullable', 'boolean'],
        ], [
            'quotation_no.unique' => 'Quotation number already exists.',
        ]);

        $items = [];
        foreach ($data['item_no'] as $i => $itemNo) {
            $quantity = (int) $data['quantity'][$i];
            $unitPrice = round((float) $data['unit_price'][$i], 2);
            $items[] = [
                'product_id' => isset($data['product_id'][$i]) ? (int) $data['product_id'][$i] : null,
                'item_no' => $itemNo,
                'description' => trim($data['description'][$i] ?? ''),
                'brand' => trim($data['brand'][$i] ?? ''),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }

        $net = array_sum(array_column($items, 'total'));
        if ($net <= 0) {
            throw ValidationException::withMessages(['subtotal' => 'Subtotal must be a positive number.']);
        }
        $totals = Quotation::taxTotals($net, $quotation->rateFor($request->boolean('apply_tax') && $quotation->offersTax()));

        DB::transaction(function () use ($quotation, $data, $items, $totals) {
            $quotation->fill([
                'quotation_no' => $data['quotation_no'],
                'prepared_for' => trim($data['prepared_for']),
                'prepared_by' => trim($data['prepared_by']),
                'date' => $data['date'],
                'validity_date' => $data['validity_date'],
                'terms_conditions' => trim((string) ($data['terms_conditions'] ?? '')) ?: config('sjpos.quotation.default_terms'),
            ] + $totals)->save();

            $quotation->items()->delete();
            $quotation->items()->createMany($items);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation saved successfully.',
            'redirect' => route('quotations.index'),
        ]);
    }
}

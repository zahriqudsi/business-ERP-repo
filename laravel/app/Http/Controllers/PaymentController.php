<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentMade;
use App\Models\PaymentReceived;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Payments received from customers (against invoices) and made to suppliers
 * (against purchases). Each payment moves the linked document's amount_paid.
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type') === 'made' ? 'made' : 'received';

        $payments = $type === 'received'
            ? PaymentReceived::with('invoice:id,invoice_no')->orderByDesc('payment_date')->orderByDesc('id')->get()
            : PaymentMade::with('purchase:id,purchase_no')->orderByDesc('payment_date')->orderByDesc('id')->get();

        return view('payments.index', [
            'type' => $type,
            'payments' => $payments,
            'total' => $payments->sum('amount'),
        ]);
    }

    public function createReceived(Request $request): View
    {
        return view('payments.create', [
            'type' => 'received',
            'documents' => Invoice::where('status', 'confirmed')->whereColumn('subtotal', '>', 'amount_paid')
                ->orderByDesc('date')->get(['id', 'invoice_no', 'customer_name', 'subtotal', 'amount_paid']),
            'selectedId' => (int) $request->query('invoice_id'),
        ]);
    }

    public function createMade(Request $request): View
    {
        return view('payments.create', [
            'type' => 'made',
            'documents' => Purchase::where('status', 'confirmed')->whereColumn('subtotal', '>', 'amount_paid')
                ->orderByDesc('date')->get(['id', 'purchase_no', 'supplier_name', 'subtotal', 'amount_paid']),
            'selectedId' => (int) $request->query('purchase_id'),
        ]);
    }

    public function storeReceived(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'customer_name' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($data) {
            if (! empty($data['invoice_id'])) {
                $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);
                $this->ensureFitsBalance($invoice, (float) $data['amount']);
                $invoice->applyPayment((float) $data['amount']);
            }
            PaymentReceived::create($data);
        });

        return redirect()->route('payments.index', ['type' => 'received'])->with('success', 'Payment saved!');
    }

    public function storeMade(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            'purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
            'supplier_name' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($data) {
            if (! empty($data['purchase_id'])) {
                $purchase = Purchase::lockForUpdate()->findOrFail($data['purchase_id']);
                $this->ensureFitsBalance($purchase, (float) $data['amount']);
                $purchase->applyPayment((float) $data['amount']);
            }
            PaymentMade::create($data);
        });

        return redirect()->route('payments.index', ['type' => 'made'])->with('success', 'Payment saved!');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            'party_name' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($type, $id, $data) {
            $payment = $this->find($type, $id);
            $oldAmount = (float) $payment->amount;
            $document = $this->linkedDocument($type, $payment);

            // The payment's old amount is freed up again, so the new amount may use it
            if ($document && (float) $data['amount'] > $document->balance + $oldAmount) {
                $this->rejectOverpayment($document->balance + $oldAmount);
            }

            $payment->update([
                $type === 'received' ? 'customer_name' : 'supplier_name' => $data['party_name'],
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Swap the old amount for the new one on the linked invoice/purchase
            $document?->applyPayment((float) $data['amount'] - $oldAmount);
        });

        return redirect()->route('payments.index', ['type' => $type])->with('info', 'Payment updated!');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        DB::transaction(function () use ($type, $id) {
            $payment = $this->find($type, $id);
            $this->linkedDocument($type, $payment)?->applyPayment(-(float) $payment->amount);
            $payment->delete();
        });

        return redirect()->route('payments.index', ['type' => $type])
            ->with('warning', 'Payment deleted and invoice/purchase balance reversed.');
    }

    private function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'method' => ['required', Rule::in(array_keys(PaymentReceived::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * A new payment must go to a confirmed document and may not exceed what is still owed.
     */
    private function ensureFitsBalance(Invoice|Purchase $document, float $amount): void
    {
        if ($document->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'amount' => "This document is {$document->status} and can't take new payments.",
            ]);
        }

        if ($amount > $document->balance) {
            $this->rejectOverpayment($document->balance);
        }
    }

    private function rejectOverpayment(float $balance): never
    {
        throw ValidationException::withMessages([
            'amount' => 'The amount is more than the balance due ('.rs($balance).').',
        ]);
    }

    private function find(string $type, int $id): PaymentReceived|PaymentMade
    {
        return $type === 'received' ? PaymentReceived::findOrFail($id) : PaymentMade::findOrFail($id);
    }

    private function linkedDocument(string $type, PaymentReceived|PaymentMade $payment): Invoice|Purchase|null
    {
        return $type === 'received'
            ? ($payment->invoice_id ? Invoice::lockForUpdate()->find($payment->invoice_id) : null)
            : ($payment->purchase_id ? Purchase::lockForUpdate()->find($payment->purchase_id) : null);
    }
}

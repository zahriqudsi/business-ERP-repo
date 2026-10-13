<?php

namespace App\Http\Controllers;

use App\Models\VendorBill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VendorBillController extends Controller
{
    public function index(): View
    {
        // Auto-mark overdue
        VendorBill::where('status', 'unpaid')->whereDate('due_date', '<', today())->update(['status' => 'overdue']);

        $summary = DB::table('vendor_bills')->selectRaw("
            COALESCE(SUM(amount), 0) AS total,
            COALESCE(SUM(CASE WHEN status IN ('unpaid','overdue') THEN amount - amount_paid ELSE 0 END), 0) AS unpaid,
            COALESCE(SUM(CASE WHEN status = 'overdue' THEN amount - amount_paid ELSE 0 END), 0) AS overdue,
            COUNT(CASE WHEN status IN ('unpaid','overdue') THEN 1 END) AS cnt_pending
        ")->first();

        return view('bills.index', [
            'summary' => $summary,
            'bills' => VendorBill::orderByRaw("FIELD(status, 'overdue', 'unpaid', 'partially_paid', 'paid')")
                ->orderBy('due_date')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('bills.form', ['bill' => new VendorBill([
            'supplier_name' => $request->query('supplier_name', ''),
            'bill_date' => now()->toDateString(),
            'amount_paid' => 0,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        VendorBill::create($this->validated($request));

        return redirect()->route('bills.index')->with('success', 'Bill saved!');
    }

    public function edit(VendorBill $bill): View
    {
        return view('bills.form', compact('bill'));
    }

    public function update(Request $request, VendorBill $bill): RedirectResponse
    {
        $bill->update($this->validated($request));

        return redirect()->route('bills.index')->with('success', 'Bill saved!');
    }

    public function destroy(VendorBill $bill): RedirectResponse
    {
        $bill->delete();

        return redirect()->route('bills.index')->with('warning', 'Bill deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:100'],
            'bill_no' => ['nullable', 'string', 'max:50'],
            'bill_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['amount_paid'] = (float) ($data['amount_paid'] ?? 0);
        $data['status'] = VendorBill::statusFor((float) $data['amount'], $data['amount_paid'], $data['due_date'] ?? null);

        return $data;
    }
}

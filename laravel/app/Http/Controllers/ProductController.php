<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\QuotationItem;
use App\Services\StockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public const FILTERS = ['all' => 'All products', 'low' => 'Low / out of stock', 'services' => 'Services', 'inactive' => 'Inactive'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $filter = array_key_exists($request->query('filter'), self::FILTERS) ? $request->query('filter') : 'all';

        $products = Product::query()
            ->when($filter === 'all', fn (Builder $q) => $q->where('is_active', true))
            ->when($filter === 'low', fn (Builder $q) => $q->lowStock())
            ->when($filter === 'services', fn (Builder $q) => $q->where('track_stock', false)->where('is_active', true))
            ->when($filter === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        $summary = DB::table('products')->where('is_active', true)->selectRaw('
            COUNT(*) AS products,
            COALESCE(SUM(CASE WHEN track_stock AND stock_qty > 0 THEN stock_qty * cost_price ELSE 0 END), 0) AS stock_value,
            COALESCE(SUM(CASE WHEN track_stock AND stock_qty > 0 THEN stock_qty * unit_price ELSE 0 END), 0) AS retail_value
        ')->first();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'filter' => $filter,
            'summary' => $summary,
            'lowStockCount' => Product::lowStock()->count(),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(['track_stock' => true, 'is_active' => true]),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request, StockLedger $stock): RedirectResponse
    {
        $request->validate(['opening_stock' => ['nullable', 'integer', 'min:0']]);
        $data = $this->validated($request);
        $openingStock = $request->integer('opening_stock');

        $product = DB::transaction(function () use ($data, $openingStock, $stock) {
            $product = Product::create($data);
            if ($product->track_stock && $openingStock > 0) {
                $stock->adjust($product, $openingStock, 'Opening stock');
            }

            return $product;
        });

        return redirect()->route('products.show', $product)->with('success', 'Product added.');
    }

    public function show(Product $product): View
    {
        return view('products.show', [
            'product' => $product,
            'movements' => $product->movements()->with('user:id,username')->paginate(25),
            'soldThisMonth' => (int) -$product->movements()->toBase()->where('type', 'sale')
                ->where('created_at', '>=', now()->startOfMonth())->sum('quantity'),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', ['product' => $product, 'categories' => $this->categories()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('products.show', $product)->with('info', 'Product updated.');
    }

    /**
     * Products already used on documents are kept for history; mark them inactive instead.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $used = InvoiceItem::where('product_id', $product->id)->exists()
            || QuotationItem::where('product_id', $product->id)->exists()
            || PurchaseItem::where('product_id', $product->id)->exists();

        if ($used) {
            return back()->withErrors(['product' => "“{$product->name}” is used on invoices, quotations or purchases. Untick “Active” to hide it instead."]);
        }

        $product->delete();

        return redirect()->route('products.index')->with('warning', 'Product deleted.');
    }

    /**
     * Manual stock correction: add, remove, or set to a counted quantity.
     */
    public function adjust(Request $request, Product $product, StockLedger $stock): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['add', 'remove', 'set'])],
            'quantity' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $product->track_stock) {
            return back()->withErrors(['quantity' => 'This product is a service and has no stock.']);
        }

        $change = match ($data['mode']) {
            'add' => $data['quantity'],
            'remove' => -$data['quantity'],
            'set' => $data['quantity'] - $product->stock_qty,
        };

        if ($change === 0) {
            return back()->with('info', 'Stock unchanged.');
        }

        $note = $data['note'] ?? ($data['mode'] === 'set' ? 'Stock count' : null);
        $stock->adjust($product, $change, $note);
        ActivityLog::record('stock_adjusted', "{$product->name}: ".($change > 0 ? '+' : '').$change.($note ? " ({$note})" : ''), 'product', $product->id);

        return back()->with('success', 'Stock '.($change > 0 ? 'increased' : 'reduced').' by '.abs($change).'.');
    }

    /**
     * Existing categories, suggested when typing a new one.
     *
     * @return list<string>
     */
    private function categories(): array
    {
        return Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
        ], [
            'sku.unique' => 'Another product already uses this SKU.',
        ]);

        return [
            'cost_price' => (float) ($data['cost_price'] ?? 0),
            'track_stock' => $request->boolean('track_stock'),
            'is_active' => $request->boolean('is_active'),
        ] + $data;
    }
}

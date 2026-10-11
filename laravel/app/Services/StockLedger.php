<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Keeps product stock in step with documents. Each confirmed/paid invoice or purchase owns one
 * movement per stocked line; re-recording a document replaces its movements, so edits and
 * deletions can never double-count.
 */
class StockLedger
{
    /**
     * Statuses whose documents move stock. Drafts and cancelled documents don't.
     */
    private const MOVING_STATUSES = ['confirmed', 'paid'];

    /**
     * Replace the stock movements of an invoice (stock out) or purchase (stock in) from its current items.
     */
    public function record(Invoice|Purchase $document): void
    {
        [$sourceType, $sign, $type] = $document instanceof Invoice ? ['invoice', -1, 'sale'] : ['purchase', 1, 'purchase'];
        $affected = $this->forget($document, recalculate: false);

        if (in_array($document->status, self::MOVING_STATUSES, true)) {
            $stocked = Product::whereIn('id', $document->items()->whereNotNull('product_id')->pluck('product_id'))
                ->where('track_stock', true)->pluck('id')->flip();

            $quantities = $document->items()->whereNotNull('product_id')->get(['product_id', 'quantity'])
                ->filter(fn ($item) => $stocked->has($item->product_id) && (int) $item->quantity > 0)
                ->groupBy('product_id')
                ->map(fn ($items) => $items->sum('quantity'));

            foreach ($quantities as $productId => $quantity) {
                StockMovement::create([
                    'product_id' => $productId,
                    'quantity' => $sign * (int) $quantity,
                    'type' => $type,
                    'source_type' => $sourceType,
                    'source_id' => $document->id,
                    'user_id' => auth()->id(),
                ]);
                $affected[] = $productId;
            }
        }

        $this->recalculate($affected);
    }

    /**
     * Remove a document's movements (e.g. before deleting it).
     *
     * @return list<int> ids of the products whose stock changed
     */
    public function forget(Invoice|Purchase $document, bool $recalculate = true): array
    {
        $query = StockMovement::where('source_type', $document instanceof Invoice ? 'invoice' : 'purchase')
            ->where('source_id', $document->id);
        $affected = $query->pluck('product_id')->unique()->values()->all();
        $query->delete();

        if ($recalculate) {
            $this->recalculate($affected);
        }

        return $affected;
    }

    /**
     * A manual stock correction (count, damage, returns...). $quantity may be negative.
     */
    public function adjust(Product $product, int $quantity, ?string $note): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'type' => 'adjustment',
            'note' => $note,
            'user_id' => auth()->id(),
        ]);

        $this->recalculate([$product->id]);
    }

    /**
     * Refresh the cached stock_qty from the ledger.
     *
     * @param  list<int>  $productIds
     */
    private function recalculate(array $productIds): void
    {
        $productIds = array_values(array_unique($productIds));
        if ($productIds === []) {
            return;
        }

        Product::whereIn('id', $productIds)->update([
            'stock_qty' => DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id)'),
        ]);
    }
}

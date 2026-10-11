<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('products')]
#[Fillable(['sku', 'name', 'brand', 'category', 'description', 'unit_price', 'cost_price', 'track_stock', 'reorder_level', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity;

    public function activityType(): string
    {
        return 'product';
    }

    public function activityLabel(): string
    {
        return 'Product '.$this->name;
    }

    protected function casts(): array
    {
        return [
            'unit_price' => 'float',
            'cost_price' => 'float',
            'track_stock' => 'boolean',
            'stock_qty' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * Stocked products at or below their reorder level (out-of-stock counts when no level is set).
     *
     * @param  Builder<Product>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->where('track_stock', true)->where('is_active', true)
            ->whereRaw('stock_qty <= COALESCE(reorder_level, 0)');
    }

    /**
     * "In stock", "Low stock", "Out of stock" or "Service".
     */
    public function getStockStatusAttribute(): string
    {
        return match (true) {
            ! $this->track_stock => 'Service',
            $this->stock_qty <= 0 => 'Out of stock',
            $this->stock_qty <= (int) $this->reorder_level => 'Low stock',
            default => 'In stock',
        };
    }

    /**
     * Label shown in the line-item product picker.
     */
    public function getPickerLabelAttribute(): string
    {
        return trim(($this->sku ? $this->sku.' · ' : '').$this->name.($this->brand ? ' ('.$this->brand.')' : ''));
    }

    /**
     * Data the line-item picker needs for every active product.
     *
     * @return list<array{id: int, label: string, name: string, brand: string, price: float, cost: float, stock: int|null}>
     */
    public static function forPicker(): array
    {
        return static::where('is_active', true)->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->picker_label,
                'name' => $product->name,
                'brand' => (string) $product->brand,
                'price' => $product->unit_price,
                'cost' => $product->cost_price,
                'stock' => $product->track_stock ? $product->stock_qty : null,
            ])
            ->all();
    }
}

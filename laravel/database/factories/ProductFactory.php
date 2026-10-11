<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->numberBetween(1000, 50000);

        return [
            'sku' => fake()->unique()->bothify('SKU-####'),
            'name' => fake()->randomElement(['SSD', 'RAM', 'Keyboard', 'Charger', 'Battery', 'Screen']).' '.fake()->unique()->numerify('###'),
            'brand' => fake()->randomElement(['Samsung', 'Kingston', 'Dell', 'HP', 'Lenovo']),
            'category' => fake()->randomElement(['Storage', 'Memory', 'Accessories', 'Parts']),
            'unit_price' => $cost * 1.3,
            'cost_price' => $cost,
            'track_stock' => true,
            'reorder_level' => 2,
        ];
    }

    /**
     * A service (e.g. a repair) that has no stock.
     */
    public function service(): static
    {
        return $this->state(fn () => ['track_stock' => false, 'reorder_level' => null, 'cost_price' => 0]);
    }
}

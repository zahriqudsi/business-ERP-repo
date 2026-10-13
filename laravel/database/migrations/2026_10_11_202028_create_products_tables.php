<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product catalog and stock ledger. Document line items gain an optional product link;
 * existing free-text lines stay unlinked.
 */
return new class extends Migration
{
    private const ITEM_TABLES = ['invoice_items', 'quotation_items', 'purchase_items'];

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sku', 50)->nullable()->unique();
            $table->string('name', 255);
            $table->string('brand', 100)->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Selling price');
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->boolean('track_stock')->default(true)->comment('False for services such as repairs');
            $table->integer('stock_qty')->default(0)->comment('Cached sum of stock_movements.quantity');
            $table->integer('reorder_level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->integer('quantity')->comment('Positive adds stock, negative removes it');
            $table->enum('type', ['sale', 'purchase', 'adjustment']);
            $table->string('source_type', 20)->nullable()->comment('invoice or purchase');
            $table->unsignedInteger('source_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['source_type', 'source_id']);
        });

        foreach (self::ITEM_TABLES as $itemTable) {
            Schema::table($itemTable, function (Blueprint $table) {
                $table->unsignedInteger('product_id')->nullable()->index();
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::ITEM_TABLES as $itemTable) {
            Schema::table($itemTable, function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            });
        }

        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sales tax on invoices and quotations. `subtotal` keeps meaning "amount the customer pays"
 * (balances, payments and reports all use it); `net_amount` is the line total before tax.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices', 'quotations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('net_amount', 10, 2)->nullable()->after('subtotal')->comment('Line total before tax');
                $table->decimal('tax_rate', 5, 2)->default(0)->after('net_amount');
                $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            });

            // Existing documents had no tax
            DB::table($tableName)->update(['net_amount' => DB::raw('subtotal')]);
        }
    }

    public function down(): void
    {
        foreach (['invoices', 'quotations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['net_amount', 'tax_rate', 'tax_amount']);
            });
        }
    }
};

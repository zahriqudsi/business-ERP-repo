<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The invoice a quotation was converted into, so it can't be converted twice.
 * Deleting that invoice clears the link and lets the quotation be converted again.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Legacy databases have a signed INT invoices.id, fresh installs an unsigned one; the key must match
        $invoiceIdType = collect(Schema::getColumns('invoices'))->firstWhere('name', 'id')['type'];
        $unsigned = str_contains($invoiceIdType, 'unsigned');

        Schema::table('quotations', function (Blueprint $table) use ($unsigned) {
            $table->integer('invoice_id', unsigned: $unsigned)->nullable()->after('terms_conditions');
            $table->foreign('invoice_id', 'fk_quotation_invoice')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign('fk_quotation_invoice');
            $table->dropColumn('invoice_id');
        });
    }
};

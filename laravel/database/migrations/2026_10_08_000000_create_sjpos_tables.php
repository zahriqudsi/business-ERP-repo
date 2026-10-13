<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recreates the legacy `sjpos` schema exactly.
 *
 * Every table is guarded with hasTable(), so running `php artisan migrate`
 * against the existing production database is a no-op for these tables —
 * it only records the migration. On a fresh database it builds the schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 100)->unique();
                $table->string('password', 255);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->index('idx_user_id');
                $table->string('otp_hash', 255);
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->dateTime('expires_at');
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->increments('id');
                $table->string('customer_name', 100)->nullable();
                $table->date('date')->nullable();
                $table->string('invoice_no', 50)->nullable()->unique('idx_invoice_no');
                $table->decimal('subtotal', 10, 2)->nullable();
                $table->text('terms_conditions')->nullable();
                $table->enum('status', ['draft', 'confirmed', 'paid', 'cancelled'])->default('confirmed');
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
            });
        }

        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('invoice_id')->index();
                $table->string('item_no', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('brand', 50)->nullable();
                $table->integer('quantity')->nullable();
                $table->decimal('unit_price', 10, 2)->nullable();
                $table->decimal('total', 10, 2)->nullable();

                $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('bank_details')) {
            Schema::create('bank_details', function (Blueprint $table) {
                $table->increments('id');
                $table->string('account_name', 100)->nullable();
                $table->string('branch', 100)->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->unsignedInteger('invoice_id')->nullable()->index('fk_invoice');

                $table->foreign('invoice_id', 'fk_invoice_id')->references('id')->on('invoices');
            });
        }

        if (! Schema::hasTable('invoice_notes')) {
            Schema::create('invoice_notes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('invoice_no', 50)->index();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->foreign('invoice_no')->references('invoice_no')->on('invoices')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('quotations')) {
            Schema::create('quotations', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('quotation_no');
                $table->string('prepared_for', 255);
                $table->string('prepared_by', 255);
                $table->date('date');
                $table->date('validity_date');
                $table->decimal('subtotal', 10, 2);
                $table->text('terms_conditions');
            });
        }

        if (! Schema::hasTable('quotation_items')) {
            Schema::create('quotation_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('quotation_id')->index();
                $table->string('item_no', 50);
                $table->text('description');
                $table->string('brand', 255);
                $table->integer('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total', 10, 2);
            });
        }

        if (! Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->increments('id');
                $table->string('supplier_name', 100);
                $table->string('purchase_no', 50)->nullable();
                $table->date('date');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->enum('status', ['draft', 'confirmed', 'paid', 'cancelled'])->default('confirmed');
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('purchase_id')->index();
                $table->text('description')->nullable();
                $table->string('brand', 50)->nullable();
                $table->integer('quantity')->nullable()->default(1);
                $table->decimal('unit_price', 10, 2)->nullable()->default(0);
                $table->decimal('total', 10, 2)->nullable()->default(0);

                $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('payments_received')) {
            Schema::create('payments_received', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('invoice_id')->nullable()->index('fk_pr_invoice');
                $table->string('customer_name', 100)->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->date('payment_date');
                $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'online'])->nullable()->default('bank_transfer');
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->foreign('invoice_id', 'fk_pr_invoice')->references('id')->on('invoices')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('payments_made')) {
            Schema::create('payments_made', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('purchase_id')->nullable()->index('fk_pm_purchase');
                $table->string('supplier_name', 100)->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->date('payment_date');
                $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'online'])->nullable()->default('bank_transfer');
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->foreign('purchase_id', 'fk_pm_purchase')->references('id')->on('purchases')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('vendor_bills')) {
            Schema::create('vendor_bills', function (Blueprint $table) {
                $table->increments('id');
                $table->string('supplier_name', 100);
                $table->string('bill_no', 50)->nullable();
                $table->date('bill_date');
                $table->date('due_date')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'overdue'])->default('unpaid');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('job_notes')) {
            Schema::create('job_notes', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('job_no')->unique('uniq_job_no');
                $table->string('customer_name', 255);
                $table->string('contact_person', 255)->nullable();
                $table->string('contact_no', 50)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('item', 255);
                $table->string('serial_no', 255)->nullable();
                $table->text('fault');
                $table->string('accessories_received', 255)->nullable();
                $table->text('device_condition')->nullable();
                $table->text('remarks')->nullable();
                $table->date('collected_date');
                $table->string('collected_by', 255);
                $table->text('terms_conditions');
                $table->mediumText('customer_signature')->nullable()->comment('PNG data URL captured from the signature pad');
                $table->string('signed_name', 255)->nullable();
                $table->dateTime('signed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: these tables hold the business's live data.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments a business made for its plan (bank transfer, cash...), recorded by platform staff.
 * Each one extends the business's paid_until by its number of months.
 */
return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('platform_admin_id')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('LKR');
            $table->unsignedSmallInteger('months');
            $table->string('method', 30);
            $table->string('reference', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->date('paid_on');
            $table->timestamp('covers_until')->comment('paid_until after this payment');
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};

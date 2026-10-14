<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per business. Each has its own database, named by its id rather than its subdomain.
 */
return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 150);
            $table->string('slug', 63)->unique()->comment('Subdomain');
            $table->string('database', 64)->unique();
            $table->enum('status', ['provisioning', 'trial', 'active', 'past_due', 'suspended', 'cancelled', 'failed'])->default('provisioning');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('timezone', 64)->default('Asia/Colombo');
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('data_purge_after')->nullable()->comment('Set on cancel; the database is dropped after this');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

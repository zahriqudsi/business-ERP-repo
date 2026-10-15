<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What happened on the platform: sign-ups, suspensions, plan changes, support sign-ins.
 */
return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::create('platform_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_admin_id')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->foreignUlid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('action', 40);
            $table->string('description');
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_activity');
    }
};

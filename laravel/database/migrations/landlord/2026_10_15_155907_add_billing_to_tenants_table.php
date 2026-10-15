<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How long a business has paid for, and why it was paused (so a payment only resumes billing pauses).
 */
return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('paid_until')->nullable()->after('trial_ends_at');
            $table->string('suspended_reason', 20)->nullable()->after('suspended_at')->comment('billing or manual');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['paid_until', 'suspended_reason']);
        });
    }
};

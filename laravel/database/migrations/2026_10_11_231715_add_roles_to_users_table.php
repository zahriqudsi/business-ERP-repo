<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Several logins with roles. Accounts that already exist become owners, so nobody loses access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 100)->nullable()->after('username');
            $table->string('email', 255)->nullable()->after('name');
            $table->enum('role', ['owner', 'manager', 'staff'])->default('staff')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable();
        });

        DB::table('users')->update(['role' => 'owner']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'role', 'is_active', 'last_login_at']);
        });
    }
};

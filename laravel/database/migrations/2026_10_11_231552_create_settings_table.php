<?php

use Database\Seeders\SjTechnologySettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Business settings edited on the Settings page (key = path under config "sjpos", value = JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // An existing install (it already has invoices) keeps the details that used to be hard-coded.
        // A fresh install starts from the neutral defaults and is set up on the Settings page.
        if (DB::table('invoices')->exists()) {
            DB::table('settings')->insert(collect(SjTechnologySettings::VALUES)
                ->map(fn ($value, string $key) => ['key' => $key, 'value' => json_encode($value), 'updated_at' => now()])
                ->values()->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who created, changed or deleted what, and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('action', 30)->comment('created, updated, deleted, signed_in, ...');
            $table->string('subject_type', 30)->nullable()->comment('invoice, quotation, product, ...');
            $table->unsignedInteger('subject_id')->nullable();
            $table->string('description', 255);
            $table->json('changes')->nullable()->comment('field => [old, new] for updates');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log of documents emailed to customers, shown on each document's page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('document_type', 20)->comment('invoice, quotation, job or statement');
            $table->unsignedInteger('document_id')->comment('For statements, the customer id');
            $table->string('recipient', 255);
            $table->string('cc', 500)->nullable();
            $table->string('subject', 255);
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamp('sent_at')->useCurrent();

            $table->index(['document_type', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_emails');
    }
};

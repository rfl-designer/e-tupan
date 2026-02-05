<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->string('subject');
            $table->string('mailable_class');
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('driver')->nullable();
            $table->foreignId('resent_from_id')->nullable()->constrained('email_logs')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('recipient');
            $table->index('mailable_class');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};

<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('payment_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->string('gateway', 50);
            $table->string('action', 100);
            $table->string('status', 50);
            $table->string('transaction_id')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index('order_id');
            $table->index(['gateway', 'action']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};

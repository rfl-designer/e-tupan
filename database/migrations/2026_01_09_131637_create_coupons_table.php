<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Type: percentage, fixed, free_shipping
            $table->string('type');

            // Value (percentage 0-100 or fixed amount in cents)
            $table->unsignedBigInteger('value')->nullable();

            // Limits
            $table->unsignedBigInteger('minimum_order_value')->nullable(); // cents
            $table->unsignedBigInteger('maximum_discount')->nullable(); // cents (for percentage type)
            $table->unsignedInteger('usage_limit')->nullable(); // total uses
            $table->unsignedInteger('usage_limit_per_user')->nullable();

            // Validity
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Tracking
            $table->unsignedInteger('times_used')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};

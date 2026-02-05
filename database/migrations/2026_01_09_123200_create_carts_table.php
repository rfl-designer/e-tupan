<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignUuid('coupon_id')->nullable();

            // Shipping
            $table->string('shipping_zipcode', 9)->nullable();
            $table->string('shipping_method')->nullable();
            $table->unsignedBigInteger('shipping_cost')->nullable(); // cents
            $table->integer('shipping_days')->nullable();

            // Status
            $table->string('status')->default('active');

            // Cached totals (in cents)
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            // Tracking
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index('status');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};

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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Customer identification
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_cpf', 14)->nullable();
            $table->string('guest_phone', 20)->nullable();

            // Reference to original cart
            $table->uuid('cart_id')->nullable();

            // Order number (human-readable)
            $table->string('order_number')->unique();

            // Status
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');

            // Shipping address
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();

            // Shipping address snapshot (for when address is deleted)
            $table->string('shipping_recipient_name')->nullable();
            $table->string('shipping_zipcode', 9)->nullable();
            $table->string('shipping_street')->nullable();
            $table->string('shipping_number', 20)->nullable();
            $table->string('shipping_complement')->nullable();
            $table->string('shipping_neighborhood')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state', 2)->nullable();

            // Shipping method
            $table->string('shipping_method')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->unsignedInteger('shipping_days')->nullable();

            // Coupon
            $table->uuid('coupon_id')->nullable();
            $table->string('coupon_code')->nullable();

            // Values (in cents)
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            // Tracking
            $table->string('tracking_number')->nullable();

            // Additional info
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Timestamps for status changes
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('placed_at');
            $table->index('order_number');
            $table->index('guest_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

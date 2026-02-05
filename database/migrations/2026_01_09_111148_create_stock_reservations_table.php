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
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship (Product or ProductVariant)
            $table->morphs('stockable');

            // Reserved quantity
            $table->unsignedInteger('quantity');

            // Reference to cart (string/UUID before Cart exists)
            $table->string('cart_id');

            // Control timestamps
            $table->timestamp('expires_at');
            $table->timestamp('converted_at')->nullable(); // When converted to sale

            $table->timestamps();

            // Indexes for performance
            $table->index('cart_id');
            $table->index('expires_at');
            $table->index(['stockable_type', 'stockable_id', 'cart_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};

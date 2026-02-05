<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();

            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price'); // price at the moment of addition (cents)
            $table->unsignedBigInteger('sale_price')->nullable(); // promotional price (cents)

            $table->timestamps();

            // Unique constraint: same product/variant can only be added once per cart
            $table->unique(['cart_id', 'product_id', 'variant_id'], 'cart_items_unique');
            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};

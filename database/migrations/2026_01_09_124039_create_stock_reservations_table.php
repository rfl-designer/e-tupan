<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stock_reservations')) {
            return;
        }

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('stockable_type');
            $table->unsignedBigInteger('stockable_id');
            $table->integer('quantity');
            $table->string('cart_id')->nullable(); // UUID from carts table
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('converted_at')->nullable(); // When the order was placed
            $table->timestamps();

            $table->index(['stockable_type', 'stockable_id']);
            $table->index(['stockable_type', 'stockable_id', 'cart_id']);
            $table->index('cart_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};

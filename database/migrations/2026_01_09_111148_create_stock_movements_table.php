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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship (Product or ProductVariant)
            $table->morphs('stockable');

            // Movement type (entrada, saida, ajuste, venda, estorno, reserva, liberacao)
            $table->string('movement_type');

            // Quantities
            $table->integer('quantity'); // Quantity moved (can be negative)
            $table->integer('quantity_before'); // Balance before
            $table->integer('quantity_after'); // Balance after

            // Reference (order, cart, etc.)
            $table->nullableMorphs('reference');

            // Audit fields
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamps();

            // Indexes for performance
            $table->index('movement_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

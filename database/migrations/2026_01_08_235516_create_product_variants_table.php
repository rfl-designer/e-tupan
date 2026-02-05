<?php declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();

            // Price in cents - nullable means inherit from parent product
            $table->unsignedBigInteger('price')->nullable();

            // Inventory
            $table->integer('stock_quantity')->default(0);

            // Dimensions
            $table->decimal('weight', 10, 3)->nullable(); // kg
            $table->decimal('length', 10, 2)->nullable(); // cm
            $table->decimal('width', 10, 2)->nullable(); // cm
            $table->decimal('height', 10, 2)->nullable(); // cm

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

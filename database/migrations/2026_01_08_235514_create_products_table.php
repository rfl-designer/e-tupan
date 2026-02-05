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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('type')->default('simple'); // simple, variable
            $table->string('status')->default('draft'); // draft, active, inactive

            // Prices in cents (integer) to avoid precision issues
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->timestamp('sale_start_at')->nullable();
            $table->timestamp('sale_end_at')->nullable();
            $table->unsignedBigInteger('cost')->nullable();

            // Inventory
            $table->string('sku')->nullable()->unique();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('allow_backorders')->default(false);

            // Dimensions
            $table->decimal('weight', 10, 3)->nullable(); // kg
            $table->decimal('length', 10, 2)->nullable(); // cm
            $table->decimal('width', 10, 2)->nullable(); // cm
            $table->decimal('height', 10, 2)->nullable(); // cm

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('status');
            $table->index('type');
            $table->index(['status', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

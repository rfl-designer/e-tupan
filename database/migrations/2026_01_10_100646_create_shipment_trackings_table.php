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
        Schema::create('shipment_trackings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shipment_id')->constrained()->cascadeOnDelete();

            // Event information from carrier
            $table->string('event_code')->nullable();
            $table->string('event_description');
            $table->string('status');

            // Location information
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('BR');

            // Additional details
            $table->text('notes')->nullable();
            $table->json('raw_data')->nullable();

            // Timestamps
            $table->timestamp('event_at');
            $table->timestamps();

            // Indexes
            $table->index(['shipment_id', 'event_at']);
            $table->index('event_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_trackings');
    }
};

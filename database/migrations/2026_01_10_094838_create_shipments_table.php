<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();

            // Melhor Envio IDs
            $table->string('quote_id')->nullable();
            $table->string('cart_id')->nullable(); // ME cart ID
            $table->string('shipment_id')->nullable(); // ME shipment ID after checkout

            // Shipping service info
            $table->string('carrier_code'); // e.g., correios_pac
            $table->string('carrier_name');
            $table->string('service_code'); // API service code
            $table->string('service_name');

            // Costs (in cents)
            $table->unsignedBigInteger('shipping_cost');
            $table->unsignedBigInteger('insurance_cost')->default(0);

            // Delivery estimate
            $table->integer('delivery_days_min');
            $table->integer('delivery_days_max');
            $table->date('estimated_delivery_at')->nullable();

            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();

            // Label
            $table->string('label_url')->nullable();
            $table->timestamp('label_generated_at')->nullable();

            // Recipient data (snapshot from order)
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_document')->nullable(); // CPF

            // Address (snapshot from order)
            $table->string('address_zipcode', 9);
            $table->string('address_street');
            $table->string('address_number');
            $table->string('address_complement')->nullable();
            $table->string('address_neighborhood');
            $table->string('address_city');
            $table->string('address_state', 2);

            // Package info
            $table->decimal('weight', 8, 3); // kg
            $table->integer('height'); // cm
            $table->integer('width'); // cm
            $table->integer('length'); // cm

            // Status
            $table->string('status')->default('pending');

            // Timestamps
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('tracking_number');
            $table->index('shipment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

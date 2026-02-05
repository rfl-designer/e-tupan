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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();

            // Method and Gateway
            $table->string('method'); // credit_card, pix, bank_slip
            $table->string('gateway')->default('mercado_pago');

            // Status
            $table->string('status')->default('pending');

            // Values (in cents)
            $table->unsignedBigInteger('amount');
            $table->unsignedInteger('installments')->default(1);
            $table->unsignedBigInteger('installment_amount')->nullable(); // Per-installment value in cents

            // Gateway data
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();

            // Credit card specific data
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand')->nullable();

            // Pix specific data
            $table->text('pix_qr_code')->nullable();
            $table->text('pix_qr_code_base64')->nullable();
            $table->string('pix_code')->nullable();

            // Bank slip specific data
            $table->string('bank_slip_url')->nullable();
            $table->string('bank_slip_barcode')->nullable();
            $table->string('bank_slip_digitable_line')->nullable();

            // Expiration (for Pix and Bank Slip)
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Refund data
            $table->unsignedBigInteger('refunded_amount')->default(0);
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('status');
            $table->index('gateway_payment_id');
            $table->index('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

<?php

declare(strict_types = 1);

use App\Domain\Checkout\DTOs\PaymentResult;
use App\Domain\Checkout\Enums\PaymentStatus;

it('creates a successful payment result', function () {
    $result = PaymentResult::success('txn_123', PaymentStatus::Approved, ['key' => 'value']);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe(PaymentStatus::Approved);
    expect($result->transactionId)->toBe('txn_123');
    expect($result->metadata)->toBe(['key' => 'value']);
    expect($result->isSuccessful())->toBeTrue();
    expect($result->isPending())->toBeFalse();
});

it('creates a pending payment result', function () {
    $result = PaymentResult::pending('txn_456');

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe(PaymentStatus::Processing);
    expect($result->isSuccessful())->toBeTrue();
    expect($result->isPending())->toBeTrue();
});

it('creates a failed payment result', function () {
    $result = PaymentResult::failed('card_declined', 'Cartao recusado');

    expect($result->success)->toBeFalse();
    expect($result->status)->toBe(PaymentStatus::Failed);
    expect($result->errorCode)->toBe('card_declined');
    expect($result->errorMessage)->toBe('Cartao recusado');
    expect($result->isSuccessful())->toBeFalse();
});

it('creates a declined payment result', function () {
    $result = PaymentResult::declined('insufficient_funds', 'Saldo insuficiente');

    expect($result->success)->toBeFalse();
    expect($result->status)->toBe(PaymentStatus::Declined);
    expect($result->isSuccessful())->toBeFalse();
});

it('converts to array correctly', function () {
    $result = PaymentResult::success('txn_789', PaymentStatus::Approved, ['gateway' => 'test']);

    $array = $result->toArray();

    expect($array)->toHaveKeys(['success', 'status', 'transaction_id', 'error_code', 'error_message', 'metadata']);
    expect($array['success'])->toBeTrue();
    expect($array['status'])->toBe('approved');
    expect($array['transaction_id'])->toBe('txn_789');
});

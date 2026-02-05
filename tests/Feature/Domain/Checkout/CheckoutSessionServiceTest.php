<?php

declare(strict_types = 1);

use App\Domain\Checkout\Services\CheckoutSessionService;
use Illuminate\Support\Facades\Session;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->sessionService = new CheckoutSessionService();
});

describe('CheckoutSessionService', function () {
    describe('save and get', function () {
        it('saves checkout data to session', function () {
            $data = [
                'guestData' => [
                    'email' => 'john@example.com',
                    'name'  => 'John Doe',
                    'cpf'   => '529.982.247-25',
                    'phone' => '(11) 99999-9999',
                ],
                'addressData' => [
                    'shipping_zipcode' => '01310-100',
                    'shipping_street'  => 'Av Paulista',
                    'shipping_number'  => '1000',
                ],
                'shippingData' => [
                    'shipping_method' => 'sedex',
                    'shipping_cost'   => 2500,
                ],
                'paymentMethod' => 'credit_card',
            ];

            $this->sessionService->save($data);

            expect(Session::has('checkout_data'))->toBeTrue();
            expect(Session::has('checkout_timestamp'))->toBeTrue();
        });

        it('retrieves saved checkout data', function () {
            $data = [
                'guestData' => [
                    'email' => 'john@example.com',
                    'name'  => 'John Doe',
                ],
            ];

            $this->sessionService->save($data);
            $retrieved = $this->sessionService->get();

            expect($retrieved)->toBe($data);
        });

        it('returns empty array when no data saved', function () {
            $retrieved = $this->sessionService->get();

            expect($retrieved)->toBe([]);
        });
    });

    describe('clear', function () {
        it('clears checkout data from session', function () {
            $this->sessionService->save(['test' => 'data']);

            expect(Session::has('checkout_data'))->toBeTrue();

            $this->sessionService->clear();

            expect(Session::has('checkout_data'))->toBeFalse();
            expect(Session::has('checkout_timestamp'))->toBeFalse();
        });
    });

    describe('isExpired', function () {
        it('returns false for fresh data', function () {
            $this->sessionService->save(['test' => 'data']);

            expect($this->sessionService->isExpired())->toBeFalse();
        });

        it('returns true when no data exists', function () {
            expect($this->sessionService->isExpired())->toBeTrue();
        });

        it('returns true when data is older than timeout', function () {
            Session::put('checkout_data', ['test' => 'data']);
            Session::put('checkout_timestamp', now()->subMinutes(31)->timestamp);

            expect($this->sessionService->isExpired())->toBeTrue();
        });

        it('returns false when data is within timeout', function () {
            Session::put('checkout_data', ['test' => 'data']);
            Session::put('checkout_timestamp', now()->subMinutes(29)->timestamp);

            expect($this->sessionService->isExpired())->toBeFalse();
        });

        it('uses 30 minute timeout by default', function () {
            Session::put('checkout_data', ['test' => 'data']);
            Session::put('checkout_timestamp', now()->subMinutes(30)->timestamp);

            expect($this->sessionService->isExpired())->toBeFalse();

            Session::put('checkout_timestamp', now()->subMinutes(31)->timestamp);

            expect($this->sessionService->isExpired())->toBeTrue();
        });
    });

    describe('saveStep', function () {
        it('saves individual step data', function () {
            $this->sessionService->saveStep('guestData', [
                'email' => 'john@example.com',
            ]);

            $data = $this->sessionService->get();

            expect($data['guestData'])->toBe(['email' => 'john@example.com']);
        });

        it('merges with existing data', function () {
            $this->sessionService->saveStep('guestData', [
                'email' => 'john@example.com',
            ]);

            $this->sessionService->saveStep('addressData', [
                'shipping_zipcode' => '01310-100',
            ]);

            $data = $this->sessionService->get();

            expect($data['guestData'])->toBe(['email' => 'john@example.com']);
            expect($data['addressData'])->toBe(['shipping_zipcode' => '01310-100']);
        });

        it('updates existing step data', function () {
            $this->sessionService->saveStep('guestData', [
                'email' => 'john@example.com',
            ]);

            $this->sessionService->saveStep('guestData', [
                'email' => 'jane@example.com',
                'name'  => 'Jane Doe',
            ]);

            $data = $this->sessionService->get();

            expect($data['guestData'])->toBe([
                'email' => 'jane@example.com',
                'name'  => 'Jane Doe',
            ]);
        });
    });

    describe('getStep', function () {
        it('retrieves specific step data', function () {
            $this->sessionService->save([
                'guestData'   => ['email' => 'john@example.com'],
                'addressData' => ['shipping_zipcode' => '01310-100'],
            ]);

            $guestData = $this->sessionService->getStep('guestData');

            expect($guestData)->toBe(['email' => 'john@example.com']);
        });

        it('returns default when step does not exist', function () {
            $this->sessionService->save([
                'guestData' => ['email' => 'john@example.com'],
            ]);

            $addressData = $this->sessionService->getStep('addressData', ['default' => 'value']);

            expect($addressData)->toBe(['default' => 'value']);
        });

        it('returns empty array by default when step does not exist', function () {
            $addressData = $this->sessionService->getStep('addressData');

            expect($addressData)->toBe([]);
        });
    });

    describe('hasData', function () {
        it('returns true when data exists and is not expired', function () {
            $this->sessionService->save(['test' => 'data']);

            expect($this->sessionService->hasData())->toBeTrue();
        });

        it('returns false when no data exists', function () {
            expect($this->sessionService->hasData())->toBeFalse();
        });

        it('returns false when data is expired', function () {
            Session::put('checkout_data', ['test' => 'data']);
            Session::put('checkout_timestamp', now()->subMinutes(31)->timestamp);

            expect($this->sessionService->hasData())->toBeFalse();
        });
    });

    describe('refreshTimestamp', function () {
        it('refreshes the session timestamp', function () {
            Session::put('checkout_data', ['test' => 'data']);
            Session::put('checkout_timestamp', now()->subMinutes(25)->timestamp);

            $this->sessionService->refreshTimestamp();

            $timestamp = Session::get('checkout_timestamp');
            $diff      = now()->timestamp - $timestamp;

            expect($diff)->toBeLessThan(5);
        });
    });
});

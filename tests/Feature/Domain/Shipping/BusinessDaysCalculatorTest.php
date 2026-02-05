<?php

declare(strict_types = 1);

use App\Domain\Shipping\Services\BusinessDaysCalculator;
use Carbon\Carbon;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2025-01-06')); // Monday
});

afterEach(function (): void {
    Carbon::setTestNow();
});

describe('BusinessDaysCalculator', function (): void {
    it('adds business days correctly skipping weekends', function (): void {
        $calculator = new BusinessDaysCalculator();

        // Monday + 5 business days = next Monday
        $result = $calculator->addBusinessDays(Carbon::parse('2025-01-06'), 5);

        expect($result->toDateString())->toBe('2025-01-13'); // Next Monday
    });

    it('handles adding zero business days', function (): void {
        $calculator = new BusinessDaysCalculator();

        $result = $calculator->addBusinessDays(Carbon::parse('2025-01-06'), 0);

        expect($result->toDateString())->toBe('2025-01-06');
    });

    it('starts from next business day if starting on weekend', function (): void {
        $calculator = new BusinessDaysCalculator();

        // Saturday + 1 business day = Tuesday
        $result = $calculator->addBusinessDays(Carbon::parse('2025-01-11'), 1);

        expect($result->toDateString())->toBe('2025-01-14'); // Tuesday (Monday + 1)
    });

    it('calculates delivery date from today', function (): void {
        $calculator = new BusinessDaysCalculator();

        // Today is Monday, + 3 business days = Thursday
        $result = $calculator->calculateDeliveryDate(3);

        expect($result->toDateString())->toBe('2025-01-09');
    });

    it('adds calendar days when configured', function (): void {
        config(['shipping.handling_type' => 'calendar_days']);

        $calculator = new BusinessDaysCalculator();

        // Monday + 5 calendar days = Saturday
        $result = $calculator->addDays(Carbon::parse('2025-01-06'), 5);

        expect($result->toDateString())->toBe('2025-01-11');
    });

    it('adds business days by default', function (): void {
        config(['shipping.handling_type' => 'business_days']);

        $calculator = new BusinessDaysCalculator();

        // Monday + 5 business days = next Monday
        $result = $calculator->addDays(Carbon::parse('2025-01-06'), 5);

        expect($result->toDateString())->toBe('2025-01-13');
    });

    it('checks if date is a business day', function (): void {
        $calculator = new BusinessDaysCalculator();

        expect($calculator->isBusinessDay(Carbon::parse('2025-01-06')))->toBeTrue(); // Monday
        expect($calculator->isBusinessDay(Carbon::parse('2025-01-11')))->toBeFalse(); // Saturday
        expect($calculator->isBusinessDay(Carbon::parse('2025-01-12')))->toBeFalse(); // Sunday
    });

    it('gets next business day from weekend', function (): void {
        $calculator = new BusinessDaysCalculator();

        // Saturday -> Monday
        $result = $calculator->nextBusinessDay(Carbon::parse('2025-01-11'));
        expect($result->toDateString())->toBe('2025-01-13');

        // Sunday -> Monday
        $result = $calculator->nextBusinessDay(Carbon::parse('2025-01-12'));
        expect($result->toDateString())->toBe('2025-01-13');
    });

    it('returns same day if already business day', function (): void {
        $calculator = new BusinessDaysCalculator();

        // Monday -> Monday
        $result = $calculator->nextBusinessDay(Carbon::parse('2025-01-06'));
        expect($result->toDateString())->toBe('2025-01-06');
    });

    it('counts business days between dates', function (): void {
        $calculator = new BusinessDaysCalculator();

        // Monday to Friday = 4 business days (not counting start)
        $result = $calculator->countBusinessDays(
            Carbon::parse('2025-01-06'),
            Carbon::parse('2025-01-10'),
        );

        expect($result)->toBe(4);
    });

    it('counts business days skipping weekend', function (): void {
        $calculator = new BusinessDaysCalculator();

        // Monday to next Monday = 5 business days (not counting start)
        $result = $calculator->countBusinessDays(
            Carbon::parse('2025-01-06'),
            Carbon::parse('2025-01-13'),
        );

        expect($result)->toBe(5);
    });

    it('formats delivery estimate with single day', function (): void {
        $calculator = new BusinessDaysCalculator();

        $result = $calculator->formatDeliveryEstimate(1, 1);

        expect($result)->toBe('1 dia util');
    });

    it('formats delivery estimate with range', function (): void {
        $calculator = new BusinessDaysCalculator();

        $result = $calculator->formatDeliveryEstimate(3, 5);

        expect($result)->toBe('3 a 5 dias uteis');
    });

    it('formats delivery estimate with same min and max', function (): void {
        $calculator = new BusinessDaysCalculator();

        $result = $calculator->formatDeliveryEstimate(5, 5);

        expect($result)->toBe('5 dias uteis');
    });
});

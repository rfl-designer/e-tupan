<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use Carbon\Carbon;

class BusinessDaysCalculator
{
    /**
     * Check if a date is a business day (not Saturday or Sunday).
     */
    public function isBusinessDay(Carbon $date): bool
    {
        return !$date->isWeekend();
    }

    /**
     * Get the next business day from a given date.
     * Returns the same date if it's already a business day.
     */
    public function nextBusinessDay(Carbon $date): Carbon
    {
        $result = $date->copy();

        while (!$this->isBusinessDay($result)) {
            $result->addDay();
        }

        return $result;
    }

    /**
     * Add business days to a date.
     */
    public function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $this->nextBusinessDay($date->copy());

        if ($days === 0) {
            return $result;
        }

        while ($days > 0) {
            $result->addDay();

            if ($this->isBusinessDay($result)) {
                $days--;
            }
        }

        return $result;
    }

    /**
     * Add days based on configuration (business days or calendar days).
     */
    public function addDays(Carbon $date, int $days): Carbon
    {
        $handlingType = config('shipping.handling_type', 'business_days');

        if ($handlingType === 'calendar_days') {
            return $date->copy()->addDays($days);
        }

        return $this->addBusinessDays($date, $days);
    }

    /**
     * Calculate delivery date from today.
     */
    public function calculateDeliveryDate(int $businessDays): Carbon
    {
        return $this->addBusinessDays(Carbon::now(), $businessDays);
    }

    /**
     * Count business days between two dates (not including start date).
     */
    public function countBusinessDays(Carbon $start, Carbon $end): int
    {
        $count   = 0;
        $current = $start->copy()->addDay();

        while ($current->lte($end)) {
            if ($this->isBusinessDay($current)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Format delivery estimate as human-readable string.
     */
    public function formatDeliveryEstimate(int $minDays, int $maxDays): string
    {
        if ($minDays === $maxDays) {
            if ($minDays === 1) {
                return '1 dia util';
            }

            return "{$minDays} dias uteis";
        }

        return "{$minDays} a {$maxDays} dias uteis";
    }
}

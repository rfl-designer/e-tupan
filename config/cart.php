<?php

declare(strict_types = 1);

return [
    /*
    |--------------------------------------------------------------------------
    | Stock Reservation TTL
    |--------------------------------------------------------------------------
    |
    | The time in minutes that a stock reservation will be held for a cart item.
    | After this time, the reservation expires and the stock becomes available
    | for other customers.
    |
    */
    'reservation_ttl' => env('CART_RESERVATION_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Abandoned Cart Threshold
    |--------------------------------------------------------------------------
    |
    | The number of hours of inactivity after which a cart is considered
    | abandoned. This is used by the MarkAbandonedCartsJob.
    |
    */
    'abandoned_threshold_hours' => env('CART_ABANDONED_THRESHOLD_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Old Cart Cleanup Days
    |--------------------------------------------------------------------------
    |
    | Abandoned carts older than this many days will be permanently deleted
    | by the CleanOldCartsJob.
    |
    */
    'cleanup_abandoned_days' => env('CART_CLEANUP_ABANDONED_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Empty Cart Cleanup Days
    |--------------------------------------------------------------------------
    |
    | Empty carts older than this many days will be permanently deleted
    | by the CleanOldCartsJob.
    |
    */
    'cleanup_empty_days' => env('CART_CLEANUP_EMPTY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Free Shipping Threshold
    |--------------------------------------------------------------------------
    |
    | Cart subtotal (in cents) required for free shipping. Set to null to disable.
    |
    */
    'free_shipping_threshold' => env('CART_FREE_SHIPPING_THRESHOLD', null),
];

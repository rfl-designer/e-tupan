<?php

declare(strict_types = 1);

return [
    /*
    |--------------------------------------------------------------------------
    | Reservation TTL
    |--------------------------------------------------------------------------
    |
    | The default time-to-live (in minutes) for stock reservations. After this
    | time, reservations will be automatically released.
    |
    */
    'reservation_ttl' => (int) env('INVENTORY_RESERVATION_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Allow Negative Stock
    |--------------------------------------------------------------------------
    |
    | When set to true, stock adjustments can result in negative stock quantities.
    | This is useful for scenarios where you need to track overselling.
    |
    */
    'allow_negative_stock' => (bool) env('INVENTORY_ALLOW_NEGATIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Default Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | The default threshold for low stock alerts. Products with stock below this
    | value will be flagged as low stock unless they have a custom threshold.
    |
    */
    'default_low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Low Stock Notification Recipients
    |--------------------------------------------------------------------------
    |
    | The email addresses to send low stock notifications to. Multiple emails
    | can be provided as a comma-separated string in the environment variable.
    |
    */
    'notification_recipients' => array_filter(
        explode(',', env('LOW_STOCK_NOTIFICATION_EMAILS', '')),
    ),

    /*
    |--------------------------------------------------------------------------
    | Enable Low Stock Notifications
    |--------------------------------------------------------------------------
    |
    | When set to true, the system will send daily email notifications about
    | products that are below their low stock threshold.
    |
    */
    'send_low_stock_notifications' => (bool) env('LOW_STOCK_NOTIFICATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Clean Expired Reservations Interval
    |--------------------------------------------------------------------------
    |
    | The interval (in minutes) at which expired reservations should be cleaned up.
    |
    */
    'clean_expired_reservations_interval' => (int) env('INVENTORY_CLEAN_RESERVATIONS_INTERVAL', 5),
];

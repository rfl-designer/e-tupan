<?php declare(strict_types = 1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used for
    | processing payments. You may set this to any of the gateways defined
    | in the "gateways" configuration array below.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the payment gateways used by your
    | application plus their respective settings.
    |
    */

    'gateways' => [

        'mock' => [
            'driver' => 'mock',
        ],

        'mercadopago' => [
            'driver'         => 'mercadopago',
            'access_token'   => env('MERCADOPAGO_ACCESS_TOKEN'),
            'public_key'     => env('MERCADOPAGO_PUBLIC_KEY'),
            'sandbox'        => env('MERCADOPAGO_SANDBOX', true),
            'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Configure which payment methods are enabled for your application.
    |
    */

    'methods' => [
        'credit_card' => [
            'enabled'               => true,
            'max_installments'      => 12,
            'min_installment_value' => 500, // R$ 5.00 in cents
            'interest_rate'         => 0, // 0% interest (customer pays)
        ],

        'pix' => [
            'enabled'             => true,
            'discount_percentage' => 0, // Optional discount for PIX
            'expiration_minutes'  => 30,
        ],

        'bank_slip' => [
            'enabled'        => true,
            'days_to_expire' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment webhook handling.
    |
    */

    'webhook' => [
        'tolerance'      => 300, // Seconds of tolerance for webhook signature validation
        'retry_attempts' => 3,
        'retry_delay'    => 60, // Seconds between retry attempts
    ],

    /*
    |--------------------------------------------------------------------------
    | Installment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for credit card installments.
    |
    */

    'installments' => [
        'interest_free'    => 3, // Number of interest-free installments
        'interest_rate'    => 1.99, // Monthly interest rate percentage
        'max_installments' => 12,
        'min_value'        => 500, // Minimum installment value in cents
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment logging and audit.
    |
    */

    'logging' => [
        'enabled'        => env('PAYMENT_LOGGING', true),
        'retention_days' => env('PAYMENT_LOG_RETENTION', 90),
    ],

];

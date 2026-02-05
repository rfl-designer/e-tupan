<?php

declare(strict_types = 1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Shipping Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default shipping provider that will be used
    | to calculate shipping quotes and generate labels.
    |
    | Supported: "melhor_envio", "mock"
    |
    */
    'default_provider' => env('SHIPPING_PROVIDER', 'melhor_envio'),

    /*
    |--------------------------------------------------------------------------
    | Shipping Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each available shipping provider.
    |
    */
    'providers' => [
        'melhor_envio' => [
            'token'      => env('MELHOR_ENVIO_TOKEN'),
            'sandbox'    => env('MELHOR_ENVIO_SANDBOX', true),
            'company_id' => env('MELHOR_ENVIO_COMPANY_ID'),
        ],
        'mock' => [
            'enabled' => env('SHIPPING_MOCK_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Origin Address (Store Address)
    |--------------------------------------------------------------------------
    |
    | The address from which all shipments originate.
    |
    */
    'origin' => [
        'zipcode'      => env('SHIPPING_ORIGIN_ZIPCODE'),
        'street'       => env('SHIPPING_ORIGIN_STREET'),
        'number'       => env('SHIPPING_ORIGIN_NUMBER'),
        'complement'   => env('SHIPPING_ORIGIN_COMPLEMENT'),
        'neighborhood' => env('SHIPPING_ORIGIN_NEIGHBORHOOD'),
        'city'         => env('SHIPPING_ORIGIN_CITY'),
        'state'        => env('SHIPPING_ORIGIN_STATE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Carriers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which carriers are enabled and their specific settings.
    |
    */
    'carriers' => [
        'correios_pac' => [
            'enabled'         => true,
            'code'            => 1,
            'name'            => 'Correios PAC',
            'additional_days' => 0,
            'price_margin'    => 0,
        ],
        'correios_sedex' => [
            'enabled'         => true,
            'code'            => 2,
            'name'            => 'Correios SEDEX',
            'additional_days' => 0,
            'price_margin'    => 0,
        ],
        'jadlog_package' => [
            'enabled'         => true,
            'code'            => 3,
            'name'            => 'Jadlog Package',
            'additional_days' => 0,
            'price_margin'    => 0,
        ],
        'jadlog_com' => [
            'enabled'         => false,
            'code'            => 4,
            'name'            => 'Jadlog .Com',
            'additional_days' => 0,
            'price_margin'    => 0,
        ],
        'loggi' => [
            'enabled'         => false,
            'code'            => 5,
            'name'            => 'Loggi',
            'additional_days' => 0,
            'price_margin'    => 0,
        ],
        'azul_cargo' => [
            'enabled'         => false,
            'code'            => 6,
            'name'            => 'Azul Cargo',
            'additional_days' => 0,
            'price_margin'    => 0,
        ],
        'latam_cargo' => [
            'enabled'         => false,
            'code'            => 7,
            'name'            => 'LATAM Cargo',
            'additional_days' => 0,
            'price_margin'    => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Package Dimensions
    |--------------------------------------------------------------------------
    |
    | Default dimensions used when product dimensions are not specified.
    |
    */
    'defaults' => [
        'weight' => 0.3,    // kg
        'height' => 2,      // cm
        'width'  => 11,      // cm
        'length' => 16,     // cm
    ],

    /*
    |--------------------------------------------------------------------------
    | Handling Days
    |--------------------------------------------------------------------------
    |
    | Additional days added to the carrier's estimated delivery time
    | to account for order processing and packaging.
    |
    */
    'handling_days' => env('SHIPPING_HANDLING_DAYS', 1),
    'handling_type' => 'business_days', // business_days or calendar_days

    /*
    |--------------------------------------------------------------------------
    | Free Shipping Configuration
    |--------------------------------------------------------------------------
    |
    | Configure free shipping thresholds and options.
    |
    */
    'free_shipping' => [
        'enabled'    => env('FREE_SHIPPING_ENABLED', false),
        'min_amount' => env('FREE_SHIPPING_MIN_AMOUNT', 0), // in cents
        'carrier'    => env('FREE_SHIPPING_CARRIER', 'correios_pac'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | TTL (time to live) settings for cached data in seconds.
    |
    */
    'cache' => [
        'quotes_ttl'   => 300,    // 5 minutes
        'cep_ttl'      => 86400,     // 24 hours
        'carriers_ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for receiving tracking webhooks from Melhor Envio.
    |
    */
    'webhook' => [
        'tolerance' => 300, // 5 minutes tolerance for timestamp validation
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Limits
    |--------------------------------------------------------------------------
    |
    | Maximum dimensions accepted by carriers.
    |
    */
    'limits' => [
        'max_weight' => 30,     // kg
        'max_length' => 100,    // cm
        'max_width'  => 100,     // cm
        'max_height' => 100,    // cm
        'min_length' => 11,     // cm
        'min_width'  => 2,       // cm
        'min_height' => 2,      // cm
    ],
];

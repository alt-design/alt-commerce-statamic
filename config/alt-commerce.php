<?php

return [

    'payment_gateways' => [
        'enabled' => explode(',', env('ALT_COMMERCE_ENABLED_PAYMENT_GATEWAYS')),
        'available' => [
            'braintree' => [
                'driver' => 'braintree',
                'currency' => ['GBP'],
                'mode' => env('ALT_COMMERCE_BRAINTREE_MODE', 'sandbox'),
                'merchant_accounts' => [
                    'GBP' => env('ALT_COMMERCE_BRAINTREE_GBP_MERCHANT_ACCOUNT_ID'),
                    'USD' => env('ALT_COMMERCE_BRAINTREE_USD_MERCHANT_ACCOUNT_ID'),
                    'EUR' => env('ALT_COMMERCE_BRAINTREE_EUR_MERCHANT_ACCOUNT_ID'),
                    'AUD' => env('ALT_COMMERCE_BRAINTREE_AUD_MERCHANT_ACCOUNT_ID')
                ],
                'merchant_id' => env('ALT_COMMERCE_BRAINTREE_MERCHANT_ID'),
                'public_key' => env('ALT_COMMERCE_BRAINTREE_PUBLIC_KEY'),
                'private_key' => env('ALT_COMMERCE_BRAINTREE_PRIVATE_KEY'),
            ],

        ]
    ],
    'customer' => \App\Models\User::class,

    'order_pipelines' => [

        'default' => [
            'create' => [
                'connection' => 'sync',
                'tasks' => [
                    \AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\ApplyCouponRedemption::class,
                ]
            ],
            'process' => [
                'connection' => 'default',
                'queue' => 'process-order',
                'tasks' => []
            ],
            'complete' => [
                'connection' => 'default',
                'queue' => 'complete-order',
                'tasks' => [],
            ]
        ],
    ]
];
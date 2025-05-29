<?php

use AltDesign\AltCommerceStatamic\Commerce\Basket\ContextMiddlewareRunner;
use AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware\AddItems;
use AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware\ApplyCouponCode;
use AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware\SetCountryCode;
use AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware\SetCurrency;
use AltDesign\AltCommerceStatamic\Commerce\Basket\Request\RequestBasketDriverFactory;
use AltDesign\AltCommerceStatamic\Commerce\Basket\Session\SessionBasketDriverFactory;
use AltDesign\AltCommerceStatamic\OrderProcessor\Conditions\OrderIsDraft;
use AltDesign\AltCommerceStatamic\OrderProcessor\Conditions\OrderIsProcessed;
use AltDesign\AltCommerceStatamic\OrderProcessor\Conditions\OrderIsProcessing;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\ApplyCouponRedemption;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\UpdateStatusToComplete;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\UpdateStatusToProcessed;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\UpdateStatusToProcessing;

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

    'baskets' => [
        'drivers' => [
            'request' => RequestBasketDriverFactory::class,
            'session' => SessionBasketDriverFactory::class
        ],
        'contexts' => [
            'default' => [
                'driver' => 'session',
                'key' => 'alt-commerce-basket',
            ],
            'cp-order' => [
                'driver' => 'request',
                'with' => ContextMiddlewareRunner::class,
                'middleware' => [
                    SetCountryCode::class,
                    SetCurrency::class,
                    AddItems::class,
                    ApplyCouponCode::class,
                ]
            ]
        ],
    ],

    'order-pipelines' => [

        'default' => [
            'mode' => 'sequential',
            'stages' => [
                'create' => [
                    'connection' => 'sync',
                    'condition' => OrderIsDraft::class,
                    'tasks' => [
                        ApplyCouponRedemption::class,
                        UpdateStatusToProcessing::class,
                    ]
                ],
                'process' => [
                    'connection' => 'default',
                    'queue' => 'process-order',
                    'condition' => OrderIsProcessing::class,
                    'tasks' => [
                        UpdateStatusToProcessed::class,
                    ]
                ],
                'complete' => [
                    'connection' => 'default',
                    'queue' => 'complete-order',
                    'condition' => OrderIsProcessed::class,
                    'tasks' => [
                        UpdateStatusToComplete::class,
                    ],
                ]
            ]
        ],
    ]
];
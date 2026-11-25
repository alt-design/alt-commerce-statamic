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
        'enabled' => explode(',', env('ALT_COMMERCE_ENABLED_PAYMENT_GATEWAYS', 'stripe')),
        'available' => [
            'stripe' => [
                'driver' => 'stripe',
                'currency' => ['GBP'],
                'public_key' => env('STRIPE_KEY'),
                'secret_key' => env('STRIPE_SECRET'),
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
                    'queue' => 'process-order',
                    'condition' => OrderIsProcessing::class,
                    'tasks' => [
                        UpdateStatusToProcessed::class,
                    ]
                ],
                'complete' => [
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
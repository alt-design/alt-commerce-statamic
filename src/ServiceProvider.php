<?php

namespace AltDesign\AltCommerceStatamic;


use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCouponPipeline;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Contracts\OrderFactory;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\Resolver;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Contracts\VisitorLocator;
use AltDesign\AltCommerceStatamic\Commerce\Coupon\StatamicCouponRepository;
use AltDesign\AltCommerceStatamic\Commerce\Coupon\ValidateCustomerRedemptionLimit;
use AltDesign\AltCommerceStatamic\Commerce\Coupon\ValidateRedemptionLimit;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderFactory;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\Commerce\Product\ProductFactory;
use AltDesign\AltCommerceStatamic\Commerce\Product\ProductQueryBuilder;
use AltDesign\AltCommerceStatamic\Commerce\Product\StatamicProductRepository;
use AltDesign\AltCommerceStatamic\Commerce\SessionBasketRepository;
use AltDesign\AltCommerceStatamic\Contracts\CurrencyConvertor;
use AltDesign\AltCommerceStatamic\Contracts\OrderTransformer;
use AltDesign\AltCommerceStatamic\CP\Actions\AddOrderNote;
use AltDesign\AltCommerceStatamic\CP\Actions\DeleteOrderNote;
use AltDesign\AltCommerceStatamic\CP\Actions\UpdateOrderStatusToRefunded;
use AltDesign\AltCommerceStatamic\Fieldtypes\MultiCurrencyPricing;
use AltDesign\AltCommerceStatamic\Fieldtypes\TaxRateSelector;
use AltDesign\AltCommerceStatamic\OrderProcessor\Pipelines\CreateOrderPipeline;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\ApplyCouponRedemption;
use AltDesign\AltCommerceStatamic\Tags\Order;
use AltDesign\AltCommerceStatamic\Tags\Price;
use AltDesign\AltCommerceStatamic\Transformers\BaseOrderTransformer;
use Illuminate\Support\Facades\File;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Stache\Stache;
use Statamic\Statamic;


class ServiceProvider extends AddonServiceProvider
{
    protected $viewNamespace = 'alt-commerce';

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $tags = [
        Price::class,
        Order::class,
    ];

    protected $fieldtypes = [
        MultiCurrencyPricing::class,
        TaxRateSelector::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
            'resources/css/addon.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    protected array $filesToPublish = [
        __DIR__.'/../resources/blueprints/coupon_code.yaml' => 'resources/blueprints/collections/coupon_codes/coupon_code.yaml',
        __DIR__.'/../resources/blueprints/coupon_redemption.yaml' => 'resources/blueprints/collections/coupon_redemption/coupon_redemptions.yaml',
        __DIR__.'/../resources/collections/coupon_codes.yaml' => 'content/collections/coupon_codes.yaml',
        __DIR__.'/../resources/collections/coupon_redemptions.yaml' => 'content/collections/coupon_redemptions.yaml',
    ];

    public function register(): void
    {
        $this->app->singleton(Settings::class, Support\Settings::class);
        $this->app->bind(CouponRepository::class, StatamicCouponRepository::class);
        $this->app->bind(ProductRepository::class, StatamicProductRepository::class);
        $this->app->bind(OrderRepository::class, StatamicOrderRepository::class);
        $this->app->bind(OrderFactory::class, StatamicOrderFactory::class);
        $this->app->bind(OrderTransformer::class, BaseOrderTransformer::class);
        $this->app->bind(CurrencyConvertor::class, Support\CurrencyConvertor::class);
        $this->app->bind(BasketRepository::class, SessionBasketRepository::class);
        $this->app->bind(VisitorLocator::class, Support\VisitorLocator::class);
        $this->app->singleton(BasketManager::class);

        $this->app->bind(Resolver::class, fn() => new class() implements Resolver {
            public function resolve(string $abstract): mixed
            {
                return app($abstract);
            }
        });

        $this->app->singleton(GatewayBroker::class, function() {
            return new GatewayBroker(app(Resolver::class), config('alt-commerce.payment_gateways'));
        });

        $this->app->bind(ProductQueryBuilder::class, fn($app) =>
            new ProductQueryBuilder(
                store: $this->app->make(Stache::class)->store('entries'),
                factory: $app->make(ProductFactory::class)
            )
        );

        $this->app->tag([
            ApplyCouponRedemption::class,
        ], CreateOrderPipeline::TAG);


        ValidateCouponPipeline::register(
            new ValidateRedemptionLimit(),
            new ValidateCustomerRedemptionLimit(),
        );
    }

    public function bootAddon(): void
    {
        Nav::extend(function ($nav) {
            $nav->content('Alt Commerce')
                ->section('Settings')
                ->route('alt-commerce.settings.index')
                ->can('view alt-commerce')
                ->icon('<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#9c9c9c"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M21 10L15 4M21 10H3M21 10L19.6431 16.7845C19.2692 18.6542 17.6275 20 15.7208 20H8.27922C6.37249 20 4.73083 18.6542 4.35689 16.7845L3 10M3 10L9 4" stroke="#8f8f8f" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>');

            $nav->content('Reports')
                ->section('Alt Commerce')
                ->route('alt-commerce::reports.index')
                ->can('view alt-commerce')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="512" height="512" x="0" y="0" viewBox="0 0 36 36" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M4 33.99h2.5c1.103 0 2-.898 2-2v-3.46c0-1.103-.897-2-2-2H4c-1.103 0-2 .897-2 2v3.46c0 1.102.897 2 2 2zm0-5.46h2.5l.002 3.46H4zM12.5 33.99H15c1.103 0 2-.898 2-2v-12.4c0-1.102-.897-2-2-2h-2.5c-1.103 0-2 .898-2 2v12.4c0 1.102.897 2 2 2zm0-14.4H15l.002 12.4H12.5zM21 21.07c-1.103 0-2 .897-2 2v8.92c0 1.102.897 2 2 2h2.5c1.103 0 2-.898 2-2v-8.92c0-1.103-.897-2-2-2zm0 10.92v-8.92h2.5l.002 8.92zM34 31.99V12.57c0-1.103-.897-2-2-2h-2.5c-1.103 0-2 .897-2 2v19.42c0 1.102.897 2 2 2H32c1.103 0 2-.898 2-2zm-4.5-19.42H32l.002 19.42H29.5zM5.947 16.737l7.546-7.546a1.003 1.003 0 0 1 1.134-.197l5.956 2.801a3 3 0 0 0 3.398-.592l7.487-7.486a1 1 0 1 0-1.414-1.414L22.567 9.79a1 1 0 0 1-1.132.197l-5.956-2.803a3.012 3.012 0 0 0-3.4.594l-7.546 7.546a1 1 0 1 0 1.414 1.414z" fill="#7d7d7d" opacity="0.9490196078431372" data-original="#000000"/></g></svg>');

        });

        AddOrderNote::register();
        DeleteOrderNote::register();
        UpdateOrderStatusToRefunded::register();

        Statamic::afterInstalled(function () {
            foreach ($this->filesToPublish as $source => $target) {
                if (File::exists($source)) {
                    File::ensureDirectoryExists(dirname($target));
                    File::copy($source, $target);
                }
            }
        });
    }
}

<?php

namespace AltDesign\AltCommerceStatamic\Tests\Feature;

use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerceStatamic\Commerce\Product\ProductFactory;
use AltDesign\AltCommerceStatamic\Concerns\EloquentCustomer;
use AltDesign\AltCommerceStatamic\ServiceProvider;
use AltDesign\AltCommerceStatamic\Support\SequenceOrderNumberGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Permission;
use Statamic\Facades\User;
use Statamic\Statamic;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class FreshInstallTest extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected string $site;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('auth.providers.users', ['driver' => 'statamic']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->site = sys_get_temp_dir().'/alt-commerce-'.uniqid();
        File::ensureDirectoryExists($this->site);
        Storage::fake();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->site);

        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function shippedFiles(): array
    {
        return [
            'coupon codes collection' => ['content/collections/coupon_codes.yaml'],
            'coupon codes blueprint' => ['resources/blueprints/collections/coupon_codes/coupon_code.yaml'],
            'coupon redemptions collection' => ['content/collections/coupon_redemptions.yaml'],
            'coupon redemptions blueprint' => ['resources/blueprints/collections/coupon_redemptions/coupon_redemption.yaml'],
            'orders collection' => ['content/collections/orders.yaml'],
            'orders blueprint' => ['resources/blueprints/collections/orders/order.yaml'],
            'products collection' => ['content/collections/products.yaml'],
            'products blueprint' => ['resources/blueprints/collections/products/product.yaml'],
        ];
    }

    #[DataProvider('shippedFiles')]
    public function test_installing_publishes_the_collections_a_shop_needs(string $path): void
    {
        $this->install();

        $this->assertFileExists($this->site.'/'.$path);
    }

    public function test_installing_leaves_a_sites_own_collection_alone(): void
    {
        $this->write('content/collections/products.yaml', 'title: Our products');
        $this->write('resources/blueprints/collections/products/our_product.yaml', 'title: Our product');

        $this->install();

        $this->assertSame('title: Our products', File::get($this->site.'/content/collections/products.yaml'));
        $this->assertFileDoesNotExist($this->site.'/resources/blueprints/collections/products/product.yaml');
    }

    public function test_installing_adds_no_blueprint_to_a_collection_the_site_already_has(): void
    {
        $this->write('content/collections/orders.yaml', 'title: Our orders');

        $this->install();

        $this->assertDirectoryDoesNotExist($this->site.'/resources/blueprints/collections/orders');
    }

    public function test_an_order_number_can_be_reserved_before_the_settings_are_saved(): void
    {
        $this->assertMatchesRegularExpression('/^\d{5}$/', app(SequenceOrderNumberGenerator::class)->reserve());
    }

    public function test_a_product_without_a_price_can_be_made(): void
    {
        Collection::make('products')->save();
        $entry = tap(Entry::make()->collection('products')->slug('no-price')->data(['title' => 'No price']))->save();

        $this->assertSame('No price', app(ProductFactory::class)->make($entry)->name());
    }

    public function test_the_default_gateway_is_stripe(): void
    {
        $this->assertSame(['stripe'], config('alt-commerce.payment_gateways.enabled'));
        $this->assertSame(['stripe'], array_keys(config('alt-commerce.payment_gateways.available')));
    }

    public function test_the_permission_says_that_it_covers_changes(): void
    {
        Permission::boot();

        $this->assertSame('Manage Alt Commerce', Permission::get('view alt-commerce')->label());
    }

    public function test_an_order_can_be_created_and_edited_in_the_control_panel(): void
    {
        $customer = $this->openShop();

        $id = $this->postJson('/cp/alt-commerce/orders', $this->orderForm($customer->id()))->assertOk()->json('id');

        $order = Entry::find($id);
        $this->assertSame('Sam Example', $order->get('customer_name'));
        $this->assertSame('Sam Billing', $order->get('billing_full_name'));
        $this->assertSame('DE74 2UD', $order->get('billing_postcode'));
        $this->assertSame(1000, $order->get('total'));

        $this->putJson('/cp/alt-commerce/orders/'.$id, $this->orderForm($customer->id()))->assertOk();

        $order = Entry::find($id);
        $this->assertSame('Sam Billing', $order->get('billing_full_name'));
        $this->assertSame('DE74 2UD', $order->get('billing_postcode'));
    }

    public function test_the_order_screen_can_look_up_a_customer_stored_as_a_statamic_user(): void
    {
        $customer = $this->openShop();

        $this->getJson('/cp/alt-commerce/customer-lookup?id='.$customer->id())
            ->assertOk()
            ->assertExactJson(['id' => $customer->id(), 'name' => 'Sam Example', 'email' => 'sam@example.com']);
    }

    public function test_a_customer_with_no_name_is_shown_by_email_address(): void
    {
        $this->openShop();
        $customer = tap(User::make()->email('no-name@example.com'))->save();

        $this->getJson('/cp/alt-commerce/customer-lookup?id='.$customer->id())
            ->assertOk()
            ->assertJson(['name' => 'no-name@example.com']);
    }

    public function test_the_order_screen_can_look_up_a_customer_stored_as_an_eloquent_user(): void
    {
        $this->openShop();
        $model = new class extends Authenticatable implements Customer
        {
            use EloquentCustomer;

            protected $table = 'users';

            protected $guarded = [];

            public $timestamps = false;
        };
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
        });
        $customer = $model->newInstance(['name' => 'Eli Example', 'email' => 'eli@example.com', 'password' => 'hashed']);
        $customer->save();
        config(['auth.providers.users.model' => $model::class]);

        $this->getJson('/cp/alt-commerce/customer-lookup?id='.$customer->id)
            ->assertOk()
            ->assertExactJson(['id' => (string) $customer->id, 'name' => 'Eli Example', 'email' => 'eli@example.com']);
    }

    public function test_the_order_tag_lists_the_orders_of_a_customer_stored_as_a_statamic_user(): void
    {
        $customer = $this->openShop();
        $this->order('order-1', $customer->id());
        $this->order('order-2', 'somebody-else');

        $this->actingAs($customer);

        $this->assertSame(['order-1'], array_column(Statamic::tag('order:all')->fetch(), 'id'));
    }

    protected function install(): void
    {
        $basePath = $this->app->basePath();

        $this->app->setBasePath($this->site);
        (new ServiceProvider($this->app))->publishCollections();
        $this->app->setBasePath($basePath);
    }

    protected function write(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($this->site.'/'.$path));
        File::put($this->site.'/'.$path, $contents);
    }

    protected function openShop(): \Statamic\Contracts\Auth\User
    {
        $this->install();
        BlueprintFacade::setDirectory($this->site.'/resources/blueprints');
        Collection::make('orders')->save();
        Collection::make('products')->save();
        Entry::make()->collection('products')->id('product-1')->slug('widget')->data([
            'title' => 'Widget',
            'pricing' => [['currency' => 'GBP', 'amount' => 10]],
        ])->save();

        $this->actingAs(tap(User::make()->email('admin@example.com')->makeSuper())->save());

        return tap(User::make()->email('sam@example.com')->data(['name' => 'Sam Example']))->save();
    }

    protected function order(string $id, string $customerId): void
    {
        Entry::make()->collection('orders')->id($id)->slug($id)->data([
            'title' => $id,
            'customer_id' => $customerId,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderForm(string $customerId): array
    {
        return [
            'title' => 'order',
            'customer_id' => [$customerId],
            'customer_name' => 'Sam Example',
            'customer_email' => 'sam@example.com',
            'currency' => 'GBP',
            'order_date' => '2026-11-24T10:00:00.000Z',
            'billing_full_name' => 'Sam Billing',
            'billing_postcode' => 'DE74 2UD',
            'billing_country_code' => 'GBR',
            'items' => [[
                '_id' => 'row-1',
                'type' => 'line_item',
                'enabled' => true,
                'product' => ['product-1'],
                'price' => '10.00',
                'quantity' => 1,
                'tax_auto' => true,
            ]],
        ];
    }
}

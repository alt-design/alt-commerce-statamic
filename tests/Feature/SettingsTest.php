<?php

namespace AltDesign\AltCommerceStatamic\Tests\Feature;

use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerceStatamic\Facades\Basket;
use AltDesign\AltCommerceStatamic\ServiceProvider;
use AltDesign\AltCommerceStatamic\Support\SequenceOrderNumberGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Statamic\Facades\YAML;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class SettingsTest extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected string $site;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('auth.providers.users', ['driver' => 'statamic']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->site = sys_get_temp_dir().'/alt-commerce-'.uniqid();
        $this->app->instance('filesystems.paths.standard', $this->site);
        Storage::fake();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->site);

        parent::tearDown();
    }

    public function test_a_country_chosen_in_the_control_panel_is_used_for_new_baskets(): void
    {
        $this->actingAs(tap(User::make()->email('admin@example.com')->makeSuper())->save());

        $this->postJson('/cp/alt-commerce/settings', ['default_country' => 'FRA'])->assertOk();

        $this->app->forgetInstance(Settings::class);
        $this->assertSame('FR', Basket::context('default')->countryCode());
    }

    /**
     * @return array<string, array{array<string, string|null>, string}>
     */
    public static function savedCountries(): array
    {
        return [
            'nothing saved' => [[], 'gb'],
            'cleared' => [['default_country' => null], 'gb'],
            'saved by the control panel' => [['default_country' => 'GBR'], 'GB'],
            'set by hand' => [['default_country_code' => 'IE'], 'IE'],
            'set by hand as well as saved' => [['default_country_code' => 'ie', 'default_country' => 'GBR'], 'ie'],
            'set by hand with two letters' => [['default_country' => 'GB'], 'GB'],
        ];
    }

    /**
     * @param  array<string, string|null>  $saved
     */
    #[DataProvider('savedCountries')]
    public function test_default_country(array $saved, string $expected): void
    {
        $this->settings($saved);

        $this->assertSame($expected, app(Settings::class)->defaultCountryCode());
    }

    /**
     * @return array<string, array{string, int|null, string}>
     */
    public static function orderNumbers(): array
    {
        return [
            'first order' => ['00001', null, '00001'],
            'first order from a higher start' => ['00100', null, '00100'],
            'carries on from the last order' => ['00001', 5, '00006'],
            'last order is the start' => ['00100', 100, '00101'],
            'start moved up later' => ['00100', 50, '00100'],
            'start moved down later' => ['00001', 250, '00251'],
        ];
    }

    #[DataProvider('orderNumbers')]
    public function test_next_order_number(string $start, ?int $lastOrder, string $expected): void
    {
        $this->settings(['order_number_start_sequence' => $start]);

        if ($lastOrder) {
            Storage::put('alt-commerce/cache.json', json_encode(['current_order_number' => $lastOrder]));
        }

        $this->assertSame($expected, app(SequenceOrderNumberGenerator::class)->reserve());
    }

    public function test_each_order_gets_the_next_number(): void
    {
        $this->settings(['order_number_prefix' => 'WP']);
        $orderNumbers = app(SequenceOrderNumberGenerator::class);

        $this->assertSame(
            ['WP-00001', 'WP-00002', 'WP-00003'],
            [$orderNumbers->reserve(), $orderNumbers->reserve(), $orderNumbers->reserve()],
        );
    }

    public function test_numbers_already_in_use_are_skipped_when_the_counter_is_lost(): void
    {
        $this->settings(['order_number_prefix' => 'WP']);
        Collection::make('orders')->save();

        foreach (['WP-00001', 'WP-00002'] as $orderNumber) {
            Entry::make()->collection('orders')->slug($orderNumber)->data(['order_number' => $orderNumber])->save();
        }

        $orderNumbers = app(SequenceOrderNumberGenerator::class);

        $this->assertSame(['WP-00003', 'WP-00004'], [$orderNumbers->reserve(), $orderNumbers->reserve()]);
    }

    /**
     * @param  array<string, string|null>  $settings
     */
    protected function settings(array $settings): void
    {
        File::ensureDirectoryExists($this->site.'/content/alt-commerce');
        File::put($this->site.'/content/alt-commerce/settings.yaml', YAML::dump($settings));
    }
}

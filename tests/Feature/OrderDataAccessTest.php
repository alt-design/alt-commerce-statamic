<?php

namespace AltDesign\AltCommerceStatamic\Tests\Feature;

use AltDesign\AltCommerceStatamic\CP\Actions\AddOrderNote;
use AltDesign\AltCommerceStatamic\CP\Actions\AdjustStock;
use AltDesign\AltCommerceStatamic\ServiceProvider;
use AltDesign\AltCommerceStatamic\Tags\Order as OrderTag;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class OrderDataAccessTest extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $fixtures = __DIR__.'/../__fixtures__/dev-null';

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('auth.providers.users.driver', 'statamic');
        $app['config']->set('statamic.users.repositories.file.paths.roles', $fixtures.'/roles.yaml');
        $app['config']->set('statamic.users.repositories.file.paths.groups', $fixtures.'/groups.yaml');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function userWithPermissions(array $permissions): UserContract
    {
        $role = Role::make('test-role')->title('Test role')->permissions($permissions);
        $role->save();

        return tap(User::make()->email('editor@example.com')->assignRole($role))->save();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function controlPanelUrls(): array
    {
        return [
            'orders' => ['/cp/alt-commerce/orders'],
            'customers' => ['/cp/alt-commerce/customers'],
            'customer lookup' => ['/cp/alt-commerce/customer-lookup'],
            'settings' => ['/cp/alt-commerce/settings'],
            'order item export' => ['/cp/alt-commerce/exports/order-item'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('controlPanelUrls')]
    public function test_control_panel_user_without_the_permission_is_refused(string $url): void
    {
        $this->actingAs($this->userWithPermissions(['access cp']))
            ->getJson($url)
            ->assertForbidden();
    }

    public function test_control_panel_user_with_the_permission_is_let_through(): void
    {
        $response = $this->actingAs($this->userWithPermissions(['access cp', 'view alt-commerce']))
            ->getJson('/cp/alt-commerce/settings');

        $response->assertOk();
    }

    public function test_order_and_stock_actions_need_the_permission(): void
    {
        $without = $this->userWithPermissions(['access cp']);

        $this->assertFalse(app(AddOrderNote::class)->authorize($without, null));
        $this->assertFalse(app(AdjustStock::class)->authorize($without, null));

        $without->makeSuper()->save();

        $this->assertTrue(app(AddOrderNote::class)->authorize($without, null));
        $this->assertTrue(app(AdjustStock::class)->authorize($without, null));
    }

    public function test_order_tag_lists_nothing_when_nobody_is_logged_in(): void
    {
        Collection::make('orders')->save();
        Entry::make()->collection('orders')->id('order-1')->data(['customer_id' => 'someone-else', 'order_number' => 'ORD-1'])->save();

        $tag = app(OrderTag::class);
        $tag->setProperties(['parser' => null, 'content' => '', 'context' => [], 'params' => [], 'tag' => 'order:all', 'tag_method' => 'all']);

        $this->assertSame([], $tag->all());
    }
}

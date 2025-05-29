<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderFactory;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\Facades\Basket;
use AltDesign\AltCommerceStatamic\Support\GatewayUrlGenerator;
use AltDesign\AltCommerceStatamic\Support\SequenceOrderNumberGenerator;
use AltDesign\AltCommerceStatamic\Support\Settings;
use App\Commerce\OrderTransformer;
use App\Models\User;
use Carbon\Carbon;
use League\ISO3166\ISO3166;
use Ramsey\Uuid\Uuid;
use Statamic\Facades\Action;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Http\Controllers\CP\Collections\ExtractsFromEntryFields;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;

class OrderController
{
    use ExtractsFromEntryFields,
        QueriesFilters;

    protected \Statamic\Entries\Collection $collection;

    public function __construct(
        protected StatamicOrderRepository $orderRepository,
        protected OrderTransformer $orderTransformer,
        protected StatamicOrderFactory $orderFactory,
        protected GatewayUrlGenerator     $gatewayUrlGenerator,
        protected Settings                $settings,
        protected SequenceOrderNumberGenerator $sequenceOrderNumberGenerator,
    )
    {
        $this->collection = Collection::find('orders');
    }

    public function index()
    {
        return view('alt-commerce::order-index');
    }

    public function create()
    {
        return view('alt-commerce::order', [
            'endpoint' => cp_route('alt-commerce::order.show'),
        ]);
    }

    public function edit(string $orderId)
    {
        return view('alt-commerce::order', [
            'endpoint' => cp_route('alt-commerce::order.show', ['orderId' => $orderId] ),
        ]);
    }

    public function show(string|null $orderId = null)
    {
        $blueprint = $this->collection->entryBlueprint();
        $fields = $blueprint->fields();

        $entry = null;
        $order = null;
        if ($orderId) {
            $order = $this->orderRepository->find($orderId);
            $entry = Entry::query()->where('collection', 'orders')->find($orderId);
            $fields = $fields->addValues($entry->data()->all());
        }

        $fields = $fields->preProcess();
        return [
            'id' => $orderId,
            'notes' => $order ? $order->notes : [],
            'logs' => $order ? $order->logs : [],
            'transactions' => $order ? $order->transactions : [],
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
            'saveUrl' => $orderId ?
                cp_route('alt-commerce::order.update', ['orderId' => $orderId]) :
                cp_route('alt-commerce::order.store'),
            'saveMethod' => $orderId ? 'put' : 'post',
            'gatewayUrls' => $orderId ? $this->gatewayUrlGenerator->forOrder($order) : [],
            'itemActions' => $orderId ? Action::for($entry, ['collection' => $this->collection->handle(), 'view' => 'form']) : [],
            'itemActionUrl' => cp_route('collections.entries.actions.run', 'orders'),
            'basketLookupUrl' => cp_route('alt-commerce::basket.lookup'),
            'productLookupUrl' => cp_route('alt-commerce::product.lookup'),
        ];
    }

    public function update(string $orderId)
    {
        $blueprint = Collection::findByHandle('orders')->entryBlueprint();
        $fields = $blueprint->fields()->addValues(request()->all());
        $fields->validate();
        $data = $fields->process()->values();


        $basket = Basket::context('cp-order')->current();
        $order = $this->orderRepository->find($orderId);
        $order->orderDate = Carbon::parse($data['order_date'])->toDateTimeImmutable();
        $order->customer = User::query()->findOrFail($data['customer_id']);
        $order->billingAddress = new Address(
            company: $data['billing_company'] ?? null,
            fullName: $data['billing_name'] ?? null,
            countryCode: $data['billing_country_code'] ? (new ISO3166())->alpha3($data['billing_country_code'])['alpha2'] : null,
            postalCode: $data['billing_postal_code'] ?? null,
            region: $data['billing_region'] ?? null,
            locality: $data['billing_locality'] ?? null,
            street: $data['billing_street'] ?? null,
            phoneNumber: $data['billing_phone_number'] ?? null,
        );
        $order->lineItems = $basket->lineItems;
        $order->taxItems = $basket->taxItems;
        $order->discountItems = $basket->discountItems;
        $order->deliveryItems = $basket->deliveryItems;
        $order->feeItems = $basket->feeItems;
        $order->billingItems = $basket->billingItems;
        $order->subTotal = $basket->subTotal;
        $order->taxTotal = $basket->taxTotal;
        $order->deliveryTotal = $basket->deliveryTotal;
        $order->discountTotal = $basket->discountTotal;
        $order->feeTotal = $basket->feeTotal;
        $order->total = $basket->total;

        $this->orderRepository->saveWithEntryData(
            order: $order,
            data: $data->toArray(),
        );

        return $this->show($orderId);
    }


    public function store()
    {
        $blueprint = $this->collection->entryBlueprint();
        $fields = $blueprint->fields()->addValues([
            ...request()->all(),
            'title' => 'order',
        ]);

        $fields->validate();
        $data = $fields->process()->values();

        $id = Uuid::uuid4()->toString();

        $orderNumber = $this->sequenceOrderNumberGenerator->reserve();
        $order = $this->orderFactory->createFromBasket(
            orderNumber: $orderNumber,
            basket: Basket::context('cp-order')->current(),
            customer: User::query()->findOrFail($data['customer_id']),
            billingAddress: new Address(
                company: $data['billing_company'] ?? null,
                fullName: $data['billing_name'] ?? null,
                countryCode: $data['billing_country_code'] ? (new ISO3166())->alpha3($data['billing_country_code'])['alpha2'] : null,
                postalCode: $data['billing_postal_code'] ?? null,
                region: $data['billing_region'] ?? null,
                locality: $data['billing_locality'] ?? null,
                street: $data['billing_street'] ?? null,
                phoneNumber: $data['billing_phone_number'] ?? null,
            ),
            orderId: $id,
            orderDate: Carbon::parse($data['order_date'])->toDateTimeImmutable()
        );

        $this->orderRepository->saveWithEntryData(
            order: $order,
            data: $data->toArray(),
        );

        return $this->show($id);
    }

}
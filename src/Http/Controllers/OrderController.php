<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\Facades\Basket;
use AltDesign\AltCommerceStatamic\Support\GatewayUrlGenerator;
use AltDesign\AltCommerceStatamic\Support\Settings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use League\ISO3166\ISO3166;
use Statamic\Facades\Action;
use Statamic\Facades\Blueprint;
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
        protected GatewayUrlGenerator     $gatewayUrlGenerator,
        protected Settings                $settings
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
        $sections = [
            $this->generateSection('Order Details', [
                'currency' => [
                    'display' => 'Currency',
                    'type' => 'select',
                    'options' => $this->settings->supportedCurrencies(),
                    'default' => $this->settings->defaultCurrency(),
                    'width' => 50,
                    'validate' => 'required',
                ],
                'order_date' => [
                    'display' => 'Order Date',
                    'type' => 'date',
                    'width' => 50,
                ],
                'customer' => [
                    'max_items' => 1,
                    'mode' => 'typeahead',
                    'type' => 'users',
                    'display' => 'Customer',
                    'columns' => ['email']
                ],
                'customer_name' => [
                    'display' => 'Customer Name',
                    'type' => 'text',
                    'width' => 50,
                    'validate' => 'required',
                ],
                'customer_email' => [
                    'display' => 'Customer Email',
                    'type' => 'text',
                    'width' => 50,
                    'validate' => 'required',
                ],
            ]),
            $this->generateSection('Billing Details', [
                'billing_company' => [
                    'display' => 'Company',
                    'type' => 'text',
                    'width' => 33,
                ],
                'billing_name' => [
                    'display' => 'Billing Name',
                    'type' => 'text',
                    'width' => 33,
                ],
                'billing_phone_number' => [
                    'display' => 'Billing Phone Number',
                    'type' => 'text',
                    'width' => 33,
                ],
                'billing_street' => [
                    'display' => 'Street Address' ,
                    'type' => 'text',
                    'width' => 50,
                ],
                'billing_locality' => [
                    'display' => 'Locality',
                    'type' => 'text',
                    'width' => 50,
                ],
                'billing_region' => [
                    'display' => 'Region',
                    'type' => 'text',
                    'width' => 50,
                ],
                'billing_postal_code' => [
                    'display' => 'Postal Code',
                    'type' => 'text',
                    'width' => 50,
                ],
                'billing_country_code' => [
                    'display' => 'Country',
                    'type' => 'dictionary',
                    'dictionary' => 'countries',
                    'max_items' => 1,
                    'width' => 50,
                    'validate' => 'required',
                    'default' => (new ISO3166)->alpha2($this->settings->defaultCountryCode())['alpha3'],
                ],
            ]),
            $this->generateSection('Line Items', [
                'line_items' => [
                    'type' => 'replicator',
                    'button_label' => 'Add Line Item',
                    'sets' => [
                        'new_set_group' => [
                            'display' => 'New Set',
                            'sets' => [
                                'line_items' => [
                                    'display' => 'Line Item',
                                    'fields' => [
                                        [
                                            'handle' => 'product',
                                            'field' => [
                                                'max_items' => 1,
                                                'mode' => 'typeahead',
                                                'type' => 'entries',
                                                'display' => 'Product',
                                                'collections' => [
                                                    'products',
                                                ],
                                            ],
                                        ],
                                        [
                                            'handle' => 'price',
                                            'field' => [
                                                'type' => 'float',
                                                'display' => 'Price',
                                                'width' => 33,
                                            ]
                                        ],
                                        [
                                            'handle' => 'quantity',
                                            'field' => [
                                                'type' => 'integer',
                                                'display' => 'Quantity',
                                                'default' => 1,
                                                'width' => 33,

                                            ],
                                        ],
                                        [
                                            'handle' => 'subtotal',
                                            'field' => [
                                                'type' => 'float',
                                                'display' => 'Sub total',
                                                'width' => 33,
                                                'visibility' => 'computed'
                                            ]
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]),
            $this->generateSection('Discounts', [
                 'manual_discount_amount' => [
                     'type' => 'float',
                     'display' => 'Manual Discount Amount',
                     'width' => 50
                 ],
                'discount_code' => [
                    'type' => 'text',
                    'display' => 'Apply Discount Code',
                    'width' => 50
                ]
            ]),
            [
                'display' => 'Shipping',
                ...$this->additionalFieldsBlueprint(),
            ],
        ];

        return view('alt-commerce::order-create', [
            'sections' => $sections
        ]);
    }

    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'order_date' => 'nullable|array',
            'order_date.date' => 'nullable|date',
            'order_date.time' => 'nullable|date_format:H:i',
            'customer' => 'nullable|array',
            'customer_email' => 'required|email',
            'customer_name' => 'required|string',
            'billing_company' => 'nullable|string',
            'billing_name' => 'nullable|string',
            'billing_phone_number' => 'nullable|string',
            'billing_street' => 'nullable|string',
            'billing_locality' => 'nullable|string',
            'billing_region' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_country_code' => 'required|string',
        ]);

        $additionalBlueprint = Blueprint::find('alt-commerce::additional_order_fields');

        $additionalDataValidator = Validator::make(request()->all(), $additionalBlueprint->fields()->validator()->rules());

        $errors = new MessageBag();
        try {

            $errors->merge($validator->errors()->toArray());
            $errors->merge($additionalDataValidator->errors()->toArray());

            $validated = $validator->validated();
            $orderDate = !empty($validated['order_date']['date']) ? Carbon::parse($validated['order_date']['date']) : now();

            return Basket::context('manual-order-generation')->createOrder(
                customer: $this->getCustomer($validated),
                additional: [
                    'billing_address' => new Address(
                        company: $validated['billing_company'] ?? null,
                        fullName: $validated['billing_name'] ?? null,
                        countryCode: (new ISO3166())->alpha3($validated['billing_country_code'])['alpha2'],
                        postalCode: $validated['billing_postal_code'] ?? null,
                        region: $validated['billing_region'] ?? null,
                        locality: $validated['billing_locality'] ?? null,
                        street: $validated['billing_street'] ?? null,
                        phoneNumber: $validated['billing_phone_number'] ?? null,
                    ),
                    'payment_method' => 'manual',
                    'payment_gateway' => 'manual',
                    'payment_gateway_id' => '',

                    ...$additionalDataValidator->validated()
                ],
                orderDate: $orderDate->toDateTimeImmutable()
            );

        } catch (ValidationException $e) {
            $errors->merge($e->errors());
        } finally {

            if ($errors->isNotEmpty()) {
                throw ValidationException::withMessages($errors->toArray());
            }
        }


    }

    public function show(string $orderId)
    {
        $entry = Entry::query()->find($orderId);

        $order = $this->orderRepository->find($orderId);

        return view('alt-commerce::order-show', [
            'order' => $order,
            'gatewayUrls' =>  $this->gatewayUrlGenerator->forOrder($order),
            'actions' => Action::for($entry, ['collection' => $this->collection->handle(), 'view' => 'form']),
            'additionalFields' => $this->additionalFieldsBlueprint($order)
        ]);
    }

    protected function additionalFieldsBlueprint(StatamicOrder|null $order = null): array
    {
        $blueprint = Blueprint::find('alt-commerce::additional_order_fields');
        $fields = $blueprint->fields()->addValues($order?->additional ?? [])->preProcess();
        return [
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values()
        ];
    }

    protected function generateSection(string|null $display, array $fields): array
    {
        $blueprint = Blueprint::makeFromFields($fields);
        $fields = $blueprint->fields()->preProcess();

        return [
            'display' => $display,
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values()
        ];
    }

    protected function getCustomer(array $validated)
    {
        // todo need a bit of thought here about how best to implement a customer repository
        // majority of the time, this will be related to users of an application
        // will need a way of retrieving / saving which is a little more abstracted rather than relying on an eloquent model.

        $class = \App\Models\User::class;

        if (!class_implements($class, Model::class)) {
            throw new \Exception('Customer class must implement Illuminate\Database\Eloquent\Model');
        }


        if (!empty($validated['customer'][0])) {
            return ($class::query())->find($validated['customer'][0]);
        }

        if ($customer = ($class::query())->where('email', $validated['customer_email'])->first()) {
            return $customer;
        }

        $customer = ($class::query())->create([
            'email' => $validated['customer_email'],
            'name' => $validated['customer_name'],
        ]);

        $customer->roles()->create([
            'role_id' => 'guest'
        ]);

        return $customer;
    }
}
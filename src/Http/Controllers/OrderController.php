<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerce\Exceptions\CouponNotFoundException;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\ManualOrder\ManualOrderGenerator;
use AltDesign\AltCommerceStatamic\Support\GatewayUrlGenerator;
use AltDesign\AltCommerceStatamic\Support\Settings;
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
        protected Settings                $settings,
        protected ManualOrderGenerator    $manualOrderService,
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
        try {
            $order = $this->manualOrderService->createOrderFromRequest(request());
            return [
                'id' => $order->id
            ];

        } catch (CouponNotFoundException|CouponNotValidException $e) {
            throw ValidationException::withMessages([
                'discount_code' => ['Invalid discount code']
            ]);
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

}
<?php

namespace AltDesign\AltCommerceStatamic\ManualOrder;

use AltDesign\AltCommerce\Actions\AddToBasketAction;
use AltDesign\AltCommerce\Actions\ApplyCouponAction;
use AltDesign\AltCommerce\Actions\ApplyManualDiscountAction;
use AltDesign\AltCommerce\Actions\CreateOrderAction;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateDiscountItems;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemDiscounts;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemSubtotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemTax;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTaxItems;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\RecalculateBasketPipeline;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\RuleEngine\RuleManager;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderFactory;
use AltDesign\AltCommerceStatamic\Commerce\Product\StatamicProductRepository;
use AltDesign\AltCommerceStatamic\OrderProcessor\Pipelines\CreateOrderPipeline;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use League\ISO3166\ISO3166;
use Statamic\Facades\Blueprint;

class ManualOrderGenerator
{
    protected AddToBasketAction $addToBasketAction;
    protected RecalculateBasketAction $recalculateBasketAction;
    protected CreateOrderAction $createOrderAction;
    protected ApplyCouponAction $applyCouponAction;
    protected ApplyManualDiscountAction $applyManualDiscountAction;

    public function __construct(
        protected ManualOrderBasketRepository $basketRepository,
        protected StatamicProductRepository $productRepository,
        protected BasketFactory $basketFactory,
        protected OrderRepository $orderRepository,
        protected StatamicOrderFactory $orderFactory,
        protected CouponRepository $couponRepository,
        protected RuleManager $ruleManager,
        protected CalculateLineItemSubtotals $calculateLineItemSubtotals,
        protected CalculateDiscountItems $calculateDiscountItems,
        protected CalculateLineItemDiscounts $calculateLineItemDiscounts,
        protected CalculateLineItemTax $calculateLineItemTax,
        protected CalculateTaxItems $calculateTaxItems,
        protected CalculateTotals $calculateTotals,

    )
    {

        $this->recalculateBasketAction = new RecalculateBasketAction(
            recalculateBasketPipeline:  new RecalculateBasketPipeline(
                basketRepository:  $this->basketRepository,
                calculateLineItemSubtotals: $this->calculateLineItemSubtotals,
                calculateDiscountItems: $this->calculateDiscountItems,
                calculateLineItemDiscounts: $this->calculateLineItemDiscounts,
                calculateLineItemTax: $this->calculateLineItemTax,
                calculateTaxItems: $this->calculateTaxItems,
                calculateTotals: $this->calculateTotals,
            )
        );

        $this->addToBasketAction = new AddToBasketAction(
            basketRepository: $this->basketRepository,
            productRepository: $this->productRepository,
            recalculateBasketAction: $this->recalculateBasketAction
        );

        $this->createOrderAction = new CreateOrderAction(
            basketRepository: $this->basketRepository,
            orderRepository: $this->orderRepository,
            orderFactory: $this->orderFactory
        );

        $this->applyCouponAction = new ApplyCouponAction(
            basketRepository: $this->basketRepository,
            couponRepository: $this->couponRepository,
            recalculateBasketAction: $this->recalculateBasketAction,
            ruleManager: $this->ruleManager,
        );

        $this->applyManualDiscountAction = new ApplyManualDiscountAction(
            basketRepository: $this->basketRepository,
            recalculateBasketAction: $this->recalculateBasketAction,
        );
    }


    public function createBasketFromRequest(Request $request): Basket
    {
        $validated = $request->validate([
            'currency' => 'required|string',
            'line_items' => 'required|array',
            'line_items.*.product' => 'required|array',
            'line_items.*.quantity' => 'required|integer|min:1',
            'line_items.*.price' => 'required|numeric',
            'manual_discount_amount' => 'nullable|numeric',
            'discount_code' => 'nullable|string',
            'billing_country_code' => 'required|string',
        ]);

        $validated['billing_country_code'] = (new ISO3166())->alpha3($validated['billing_country_code'])['alpha2'];

        $basket = $this->basketFactory->create(
            currency: $validated['currency'],
            countryCode: $validated['billing_country_code'],
        );

        $this->basketRepository->setBasket($basket);

        foreach ($validated['line_items'] as $lineItem) {
            $this->addToBasketAction->handle(
                productId: $lineItem['product'][0],
                quantity: $lineItem['quantity'],
                price: $lineItem['price'] * 100,
            );
        }

        if ($discountCode = $validated['discount_code'] ??  null) {
            $this->applyCouponAction->handle(coupon: $discountCode);
        }

        if ($discountAmount = $validated['manual_discount_amount'] ?? null) {
            $this->applyManualDiscountAction->handle(amount: $discountAmount * 100);
        }

        return $basket;
    }

    public function createOrderFromRequest(Request $request): Order
    {

        $additionalBlueprint = Blueprint::find('alt-commerce::additional_order_fields');
        $validator = Validator::make($request->all(), [
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

        $additionalDataValidator = Validator::make($request->all(), $additionalBlueprint->fields()->validator()->rules());

        $errors = new MessageBag();
        try {

            $this->createBasketFromRequest($request);

            $errors->merge($validator->errors()->toArray());
            $errors->merge($additionalDataValidator->errors()->toArray());

            $validated = $validator->validated();
            $orderDate = !empty($validated['order_date']['date']) ? Carbon::parse($validated['order_date']['date']) : now();
            $order = $this->createOrderAction->handle(
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
                orderDate: $orderDate->toDateTimeImmutable(),
            );

            CreateOrderPipeline::dispatchSync($order->orderNumber);

            return $order;

        } catch (ValidationException $e) {
            $errors->merge($e->errors());
        } finally {

            if ($errors->isNotEmpty()) {
                throw ValidationException::withMessages($errors->toArray());
            }
        }
    }

    protected function getCustomer(array $validated)
    {
        // todo need a bit of thought here about how best to implement a customer repository
        // majority of the time, this will be related to users of an application
        // will need a way of retrieving / saving which is a little more abstracted rather than relying on an eloquent model.

        $class = config('alt-commerce.customer');

        if (!class_implements($class, Model::class)) {
            throw new \Exception('Customer class must implement Illuminate\Database\Eloquent\Model');
        }

        if (!empty($validated['customer'][0])) {
            return ($class::query())->find($validated['customer'][0]);
        }

        if ($customer = ($class::query())->where('email', $validated['customer_email'])->first()) {
            return $customer;
        }

        return ($class::query())->create([
            'email' => $validated['customer_email'],
            'name' => $validated['customer_name'],
        ]);
    }
}
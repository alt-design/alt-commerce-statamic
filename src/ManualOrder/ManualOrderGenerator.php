<?php

namespace AltDesign\AltCommerceStatamic\ManualOrder;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Exceptions\BasketException;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
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


    public function __construct()
    {


    }


    /**
     * @throws BasketException
     * @throws CouponNotValidException
     * @throws ProductNotFoundException
     * @throws CurrencyNotSupportedException
     */
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

        $context = \AltDesign\AltCommerceStatamic\Facades\Basket::context('manual-order-generation');
        $context->updateBasketCurrency($validated['currency']);
        $context->updateBasketCountry($validated['billing_country_code']);

        return $context->current();

    }

    /**
     * @throws BasketException
     * @throws CouponNotValidException
     * @throws CurrencyNotSupportedException
     * @throws ProductNotFoundException
     * @throws ValidationException
     */
    public function createOrderFromRequest(Request $request): Order
    {

        //todo needs implementing (mostly) in certikit

        throw new \Exception('Not implemented');
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


            $context = \AltDesign\AltCommerceStatamic\Facades\Basket::context('manual-order-generation');

            return $this->createOrderAction->handle(
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
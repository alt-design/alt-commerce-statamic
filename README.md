# Alt Commerce

> Sell products from a Statamic site: baskets, coupons, tax, stock, orders and Stripe payments.

Alt Commerce adds products and orders to the control panel, and gives your own templates and controllers what they need for a basket and checkout. It does not add a storefront. The rules live in [alt-design/alt-commerce](https://github.com/alt-design/alt-commerce), which is installed with it.

## Features

- Products with prices in more than one currency, tax rules and coupons
- Counted stock with a history, backorders and lead times
- Orders you can create and edit in the control panel, with notes and logs
- Card payments with Stripe, authorised first and charged once the order exists
- Order pipelines for the work that follows a sale
- A CSV export of the items sold

## Requirements

- PHP 8.3 or later with the `intl`, `curl`, `json` and `mbstring` extensions
- Statamic 6.35 or later
- A database, for stock
- A Stripe account, to take card payments
- A queue worker, if your queue connection is not `sync`

Customers can be Statamic users or Eloquent users. Statamic's free edition allows one user, so customers as Statamic users need Statamic Pro.

## How to Install

``` bash
composer require alt-design/alt-commerce-statamic
php artisan migrate
```

Installing adds four collections with their blueprints: `products`, `orders`, `coupon_codes` and `coupon_redemptions`. Anything your site already has is left alone, so you can change the blueprints.

Add your Stripe keys to `.env`:

``` env
STRIPE_KEY=
STRIPE_SECRET=
```

Then go to **Settings > Alt Commerce** and set your currencies and tax rules.

To change the configuration, publish it to `config/alt-commerce.php`:

``` bash
php artisan vendor:publish --tag=alt-commerce-config
```

Stripe takes payments in GBP by default. For other currencies, add them to `payment_gateways.available.stripe.currency` in the configuration and to the supported currencies in the settings.

## A working shop

This example takes an order from a logged in customer. Amounts in a basket and an order are in the smallest unit of the currency, so 1000 is £10.00.

### 1. Product page

Give the `products` collection a route and a template, in `content/collections/products.yaml`:

``` yaml
route: '/products/{slug}'
template: products/show
```

In `resources/views/products/show.antlers.html`:

``` antlers
<h1>{{ title }}</h1>

{{ stock }}
    {{ if purchasable }}
        <form method="POST" action="/basket">
            {{ csrf_field }}
            <input type="hidden" name="product" value="{{ id }}">
            <label for="quantity">Quantity</label>
            <input id="quantity" type="number" name="quantity" value="1" min="1">
            <button>Add to basket</button>
        </form>
    {{ else }}
        <p>Out of stock</p>
    {{ /if }}
{{ /stock }}
```

### 2. Routes

``` php
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::post('/basket', [CheckoutController::class, 'add']);

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show']);
    Route::post('/checkout', [CheckoutController::class, 'pay']);
    Route::statamic('/account', 'account');
});
```

The `auth` middleware sends visitors who are not logged in to a route named `login`. A new Statamic site does not have one, so add your login page.

### 3. Basket and checkout

``` php
namespace App\Http\Controllers;

use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Contracts\CustomerRepository;
use AltDesign\AltCommerce\Exceptions\OrderAlreadyPlacedException;
use AltDesign\AltCommerce\Exceptions\PaymentOutcomeUnknownException;
use AltDesign\AltCommerceStatamic\Facades\Basket;
use AltDesign\AltCommerceStatamic\Facades\Payment;
use AltDesign\AltCommerceStatamic\OrderProcessor\OrderProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Statamic\View\View;

class CheckoutController
{
    public function add(Request $request): RedirectResponse
    {
        $request->validate([
            'product' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        Basket::context('default')->addToBasket($request->input('product'), $request->integer('quantity'));

        return redirect('/checkout');
    }

    public function show(): View|RedirectResponse
    {
        if (Basket::isEmpty()) {
            return redirect('/');
        }

        $payment = Payment::currency(Basket::currency())->authToken();

        return (new View)->template('checkout')->layout('layout')->with([
            'lines' => collect(Basket::lineItems())
                ->map(fn (LineItem $line) => ['name' => $line->productName, 'quantity' => $line->quantity])
                ->all(),
            'total' => number_format(Basket::total() / 100, 2),
            'payment' => $payment->id,
            'client_secret' => $payment->client_secret,
            'stripe_key' => config('alt-commerce.payment_gateways.available.stripe.public_key'),
        ]);
    }

    public function pay(Request $request, CustomerRepository $customers, OrderProcessor $processor): RedirectResponse
    {
        $request->validate([
            'payment' => ['required', 'string'],
            'name' => ['required', 'string'],
            'postcode' => ['required', 'string'],
        ]);

        try {
            $order = Basket::context('default')->createOrder(
                customer: $customers->find(auth()->id()),
                billingAddress: new Address(
                    fullName: $request->input('name'),
                    countryCode: 'GB',
                    postalCode: $request->input('postcode'),
                ),
                additional: ['payment_method' => 'card'],
            );

            Basket::context('default')->attemptPayment($order, $request->input('payment'));
        } catch (PaymentOutcomeUnknownException|OrderAlreadyPlacedException $exception) {
            report($exception);

            return redirect('/account')->with('error', 'Please contact us before trying again.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect('/checkout')->with('error', 'Your payment was not taken. Please try again.');
        }

        Basket::context('default')->emptyBasket();
        $processor->process($order);

        return redirect('/account');
    }
}
```

`PaymentOutcomeUnknownException` means it is not known whether the payment was taken. `OrderAlreadyPlacedException` means the order has been placed, paid or is being checked. Do not offer another payment for either. Check the payment in Stripe first.

The payment ID comes back from the browser, so the amount and currency are checked against the order before the card is charged.

A card that was authorised but not charged is released when Stripe's authorisation expires. Cancel the payment intent to release it sooner.

### 4. Payment form

Stripe confirms the card in the browser, then the form is sent. In `resources/views/checkout.antlers.html`:

``` antlers
<h1>Checkout</h1>

<p role="alert">{{ session:error }}</p>

<ul>
    {{ lines }}
        <li>{{ quantity }} x {{ name }}</li>
    {{ /lines }}
</ul>

<p>Total: £{{ total }}</p>

<form id="checkout" method="POST" action="/checkout">
    {{ csrf_field }}
    <input type="hidden" name="payment" value="{{ payment }}">
    <label for="name">Name</label>
    <input id="name" name="name" autocomplete="name" required>
    <label for="postcode">Postcode</label>
    <input id="postcode" name="postcode" autocomplete="postal-code" required>
    <div id="card"></div>
    <p id="card-error" role="alert"></p>
    <button id="pay">Pay</button>
</form>

<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ stripe_key }}');
    const card = stripe.elements().create('card', { hidePostalCode: true });
    const form = document.getElementById('checkout');
    const pay = document.getElementById('pay');

    card.mount('#card');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        pay.disabled = true;

        const { error } = await stripe.confirmCardPayment('{{ client_secret }}', { payment_method: { card } });

        if (error) {
            document.getElementById('card-error').textContent = error.message;
            pay.disabled = false;
            return;
        }

        form.submit();
    });
</script>
```

### 5. Customer's orders

In `resources/views/account.antlers.html`:

``` antlers
<p role="alert">{{ session:error }}</p>

{{ order:all }}
    <p>{{ order_number }}: {{ order_status }}, {{ price:format :currency="currency" :amount="total" }}</p>
{{ /order:all }}
```

The tag returns the whole order, including staff notes and payment details, so print only the fields a customer should see.

## Customers

With Statamic users there is nothing to set up. With Eloquent users, set `auth.providers.users.model`, then add the contract and the trait to your model:

``` php
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerceStatamic\Concerns\EloquentCustomer;

class User extends Authenticatable implements Customer
{
    use EloquentCustomer;
}
```

To keep customers anywhere else, bind your own `AltDesign\AltCommerce\Contracts\CustomerRepository`.

## Tags

| Tag | What it does |
| --- | --- |
| `{{ order:all }}` | Lists the logged in customer's orders, newest first. Takes `status`, `order_number` and `limit`. Lists nothing when nobody is logged in |
| `{{ stock }}` | Gives `purchasable`, `available`, `level`, `tracked` and `show_lead_time` for the product on the page |
| `{{ price:format }}` | Formats an `amount`. Pass a `currency` select field, such as the one on an order, not a plain code |

## Stock

Each product has a stock policy: untracked, tracked (stops selling at zero) or backorder (keeps selling and shows a lead time). Adjust the level on the product's Stock tab, or from the command line:

``` bash
php artisan alt-commerce:stock my-product 50
php artisan alt-commerce:stock my-product 50 --set --reason="Stock take"
```

The `low_stock` widget lists tracked products that are running low. Add it to `widgets` in `config/statamic/cp.php`.

## Order pipeline

`OrderProcessor::process()` runs the stages in `order-pipelines`. The first runs straight away. The next two use your default queue connection, on the queues `process-order` and `complete-order`:

``` bash
php artisan queue:work --queue=process-order,complete-order,default
```

Add your own tasks, such as emails, to a stage in the published configuration.

## Permissions

Users need **Manage Alt Commerce** to open and change the settings, orders, stock and reports. Super users have it. The collections also follow Statamic's own collection permissions.

## Where data is kept

| Data | Where |
| --- | --- |
| Orders, with the customer's name, email address, phone numbers and addresses, staff notes and logs, and the Stripe payment | Entries in the `orders` collection |
| Coupon redemptions | Entries in the `coupon_redemptions` collection |
| Stock levels and history | The `stock_levels` and `stock_movements` tables |
| Settings | `content/alt-commerce/settings.yaml` |
| The last order number | `alt-commerce/cache.json` on your default disk |
| Baskets | The visitor's session |

Orders and redemptions are files in `content` unless you use the Eloquent driver. If you commit `content` to git, keep the `orders` and `coupon_redemptions` folders out of it.

## External services

Nothing is sent to Alt Design. Payment requests go to Stripe with the basket's total and currency. If your checkout passes them, a description and the customer's email address, name, phone number and delivery address go too. A checkout page like the one above loads Stripe.js, which is covered by [Stripe's privacy policy](https://stripe.com/privacy).

## Testing

``` bash
vendor/bin/phpunit
```

## Changelog and licence

Changes are listed in [CHANGELOG.md](CHANGELOG.md). Alt Commerce is open source under the [MIT licence](LICENSE).

For help, email support@alt-design.net or [open an issue](https://github.com/alt-design/alt-commerce-statamic/issues).

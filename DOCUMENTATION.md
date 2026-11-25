# Alt Commerce

The full guide to Alt Commerce. To install it and add your first product, start with the [README](README.md).

## Settings

Go to **Settings > Alt Commerce**. They are saved in `content/alt-commerce/settings.yaml`.

| Setting | Default | What it is for |
| --- | --- | --- |
| Trading Name | `Alt Commerce` | The name of your shop. Not used with Stripe |
| Supported Currencies | `GBP` | The currencies a product can have a price in |
| Default Currency | `GBP` | The currency a new basket starts in |
| Default Country | United Kingdom | The country a new basket starts in, which decides its tax |
| Tax Rules | none | The rates you can choose on a product |
| Start sequence | `00001` | The first order number |
| Prefix | `alt`, once you save the settings | Goes before each order number, such as `alt-00001` |

## Products, prices and tax

- Prices are entered in pounds, or the main unit of the currency, and do not include tax.
- Choose a **Tax Rate** on each product that is taxed. A product with none has no tax added.
- Give each tax rule one rate. Its country filter is the list of countries it applies to. Leave it empty for all countries.
- Tax follows the basket's country, not the billing address.
- In PHP and Antlers, amounts in a basket or an order are in hundredths, so 1000 is £10.00.

## Stock

Choose a **Stock Policy** on the product's Stock tab:

| Policy | What happens at zero |
| --- | --- |
| Untracked | Stock is not counted |
| Tracked | It stops selling |
| Backorder | It keeps selling and shows a lead time |

Adjust the level on the same tab, or from the command line with the product's slug or ID:

``` bash
php artisan alt-commerce:stock my-product 50
php artisan alt-commerce:stock my-product 50 --set --reason="Stock take"
```

The first adds 50. The second sets the level to 50.

The `low_stock` widget lists tracked products with 5 or fewer left. Add it to `widgets` in `config/statamic/cp.php`.

## Building a checkout

This example takes an order from a logged in customer. Add your Stripe keys to `.env` first, as in the [README](README.md#take-card-payments).

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

### 6. Coupons

Create a coupon in **Collections > Coupon Codes**, then apply it to the basket:

``` php
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;

try {
    Basket::context('default')->applyCoupon($request->input('coupon'));
} catch (CouponNotValidException) {
    return redirect('/checkout')->with('error', 'That coupon cannot be used.');
}
```

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

## Configuration

Publish the config file to change the payment gateway, where baskets are kept or the order pipeline:

``` bash
php artisan vendor:publish --tag=alt-commerce-config
```

Stripe takes payments in GBP. For other currencies, add them to `payment_gateways.available.stripe.currency`, and to Supported Currencies in the settings.

## Order pipeline

`OrderProcessor::process()` runs the stages in `order-pipelines`. The first runs straight away. The next two use your default queue connection, on the queues `process-order` and `complete-order`:

``` bash
php artisan queue:work --queue=process-order,complete-order,default
```

Add your own tasks, such as emails, to a stage in the published configuration.

## Where data is kept

| Data | Where |
| --- | --- |
| Orders, with the customer's ID, name, email address, phone numbers and addresses, staff notes and logs, and the Stripe payment | Entries in the `orders` collection |
| Coupon redemptions, with the customer's ID and email address, and the order's ID | Entries in the `coupon_redemptions` collection |
| Stock levels | The `stock_levels` table |
| Stock history, with the order's ID, any note, and the ID of whoever was logged in, a customer or a member of staff | The `stock_movements` table |
| Settings | `content/alt-commerce/settings.yaml` |
| The last order number | `alt-commerce/cache.json` on your default disk |
| Baskets | The visitor's session |

Any other details your checkout passes when it creates an order are saved with it.

Orders and redemptions are files in `content` unless you use the Eloquent driver. If you commit `content` to git, keep the `orders` and `coupon_redemptions` folders out of it.

## External services

Nothing is sent to Alt Design.

| Service | When | What is sent |
| --- | --- | --- |
| Stripe | A payment is created, checked or taken | The basket's total and currency. If your checkout passes them, a description and the customer's email address, name, phone number and delivery address |
| Stripe.js | Your checkout page loads it, as the example above does | What Stripe collects from the visitor's browser. See [Stripe's privacy policy](https://stripe.com/privacy) |

## Support

Email support@alt-design.net or [open an issue](https://github.com/alt-design/alt-commerce-statamic/issues).

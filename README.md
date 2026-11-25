# Alt Commerce

> Sell products from a Statamic site: baskets, coupons, tax, stock, orders and Stripe payments.

Alt Commerce adds products and orders to the control panel, and gives your own templates and controllers what they need for a basket and checkout. It does not add a storefront.

## Features

- Products with prices in more than one currency, tax rates and coupons
- Counted stock with a history, backorders and lead times
- Orders you can create and edit in the control panel, with notes and logs
- Card payments with Stripe, authorised first and charged once the order exists
- Order pipelines for the work that follows a sale
- A CSV export of the items sold

## Requirements

- Statamic 6.35 or later
- PHP 8.3 or later with the `intl`, `curl`, `json` and `mbstring` extensions
- A database, for stock
- A Stripe account, to take card payments

Customers can be Statamic users or Eloquent users. Statamic's free edition allows one user, so customers as Statamic users need Statamic Pro.

## How to Install

Run the following commands from your project root:

``` bash
composer require alt-design/alt-commerce-statamic
php artisan migrate
```

This adds the products, orders and coupon collections. Any your site already has are left alone.

## Basic usage

1. Go to **Settings > Alt Commerce**, choose your supported and default currency and your country, and add a tax rule if you charge tax.
2. Go to **Collections > Products** and add a product with a price. Prices do not include tax, so choose its **Tax Rate** if it is taxed.
3. Go to **Collections > Orders** and create an order to try it out.

### Take card payments

Add your Stripe keys to `.env`:

``` env
STRIPE_KEY=
STRIPE_SECRET=
```

Then follow [Building a checkout](DOCUMENTATION.md#building-a-checkout), which has the product page, basket, payment form and order history to copy into your site.

### Process orders

If your queue connection is not `sync`, run a worker for the addon's queues:

``` bash
php artisan queue:work --queue=process-order,complete-order,default
```

## Permissions

Users need **Manage Alt Commerce** to open and change the settings, orders, stock and reports. Super users have it already. The collections also follow Statamic's own collection permissions.

## External services

Nothing is sent to Alt Design. Payments go to Stripe. [DOCUMENTATION.md](DOCUMENTATION.md#external-services) lists what is sent, and [where customer data is kept](DOCUMENTATION.md#where-data-is-kept), for your privacy policy.

## Documentation

[DOCUMENTATION.md](DOCUMENTATION.md) covers the settings, tax, stock, the checkout, customers, tags, configuration and the order pipeline. Changes are listed in [CHANGELOG.md](CHANGELOG.md).

## Testing

``` bash
composer install
./vendor/bin/phpunit
```

## Questions etc

Drop us a big shout-out if you have any questions, comments, or concerns. We're always looking to improve our addons, so if you have any feature requests, we'd love to hear them.

Bug reports and support requests go to [GitHub issues](https://github.com/alt-design/alt-commerce-statamic/issues), or email support@alt-design.net.

### Starter Kits
- [Alt Starter Kit](https://statamic.com/starter-kits/alt-design/alt-starter-kit)

### Addons
- [Alt Admin Bar Addon](https://github.com/alt-design/Alt-Admin-Bar-Addon)
- [Alt AI Addon](https://github.com/alt-design/Alt-AI-Addon)
- [Alt Akismet Addon](https://github.com/alt-design/Alt-Akismet-Addon)
- [Alt Commerce Addon](https://github.com/alt-design/alt-commerce-statamic)
- [Alt Cookies Addon](https://github.com/alt-design/Alt-Cookies-Addon)
- [Alt Google 2FA Addon](https://github.com/alt-design/Alt-Google-2fa-Addon)
- [Alt Google Maps Addon](https://github.com/alt-design/Alt-google-maps-addon)
- [Alt Inbound Addon](https://github.com/alt-design/Alt-Inbound-Addon)
- [Alt Live Editor Addon](https://github.com/alt-design/Alt-Live-Editor-Addon)
- [Alt Password Protect Addon](https://github.com/alt-design/Alt-Password-Protect-Addon)
- [Alt Redirect Addon](https://github.com/alt-design/Alt-Redirect-Addon)
- [Alt SEO Addon](https://github.com/alt-design/Alt-SEO-Addon)
- [Alt Sitemap Addon](https://github.com/alt-design/Alt-Sitemap-Addon)
- [Alt Tabs Addon](https://github.com/alt-design/Alt-Tabs-Addon)
- [RiffRaff Addon](https://github.com/alt-design/Alt-RiffRaff-Addon)

## Licence

MIT, copyright Alt Design Ltd. See [LICENSE](LICENSE).

## Postcardware

Send us a postcard from your hometown if you like this addon. We love getting mail from other cool peeps!

Alt Design  
St Helens House  
Derby  
DE1 3EE  
UK

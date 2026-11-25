# Changelog

All notable changes to this project are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows [Semantic Versioning](https://semver.org/). Before 1.0, a minor version can include breaking changes.

## [Unreleased]

### Added

- The `orders` and `products` collections and their blueprints, added on install when a site has none.
- Customers can be Statamic users, as well as Eloquent users.
- MIT licence, documentation and this changelog.

### Changed

- Stripe is the gateway in the default configuration, in place of Braintree.
- The permission is now labelled Manage Alt Commerce. Its handle, `view alt-commerce`, is the same.
- The customer lookup in the control panel returns the ID, name and email address only.
- Statamic 6.35 or later is required.

### Fixed

- Saving an order in the control panel no longer clears the billing name and postcode.
- Order numbers can be made before the settings are saved.
- A product with no price no longer causes an error when it is saved.

### Upgrading

- If you use Braintree and have not published `config/alt-commerce.php`, publish it and add your Braintree settings before you update.
- If `ALT_COMMERCE_ENABLED_PAYMENT_GATEWAYS` is not set, `stripe` is now enabled, and `STRIPE_KEY` and `STRIPE_SECRET` are needed to take a payment.

## [0.2.1] - 2026-11-24

### Changed

- The order page shows notes and logs in one list, newest first.

### Fixed

- The Delete button on notes, which did nothing in 0.2.0.

### Upgrading

- The control panel assets have changed. Commit them if your site keeps `public/vendor/alt-commerce-statamic` in git.

## [0.2.0] - 2026-11-24

### Changed

- Requires `alt-design/alt-commerce` 0.2.
- Order data and the control panel screens need the `view alt-commerce` permission.
- `{{ order:all }}` lists the logged in customer's own orders, and nothing when nobody is logged in.
- The order pipeline uses the site's default queue connection, on the queues `process-order` and `complete-order`.
- Notes and logs are shown as text.

### Upgrading

- Your checkout needs to handle two new exceptions. See the [core 0.2.0 notes](https://github.com/alt-design/alt-commerce/releases/tag/v0.2.0).
- Make sure a worker handles both queues.

## [0.1.0-alpha] to [0.1.6-alpha] - 2026-08-07 to 2026-10-05

### Added

- Statamic 6 support and stock management.
- Minimum spend, excluded products and optional included products on coupons.
- Order status in the export.
- A warning before leaving the editor with an unapplied stock adjustment.

### Changed

- Pending orders are shown as "Pending payment".
- The coupon files are published only when a site has none.

### Fixed

- Prices are rounded to the nearest penny.

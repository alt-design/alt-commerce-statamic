<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Product;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\RecurrentBillingSchema;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Contracts\PricingSchema;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerceStatamic\Concerns\HasGatewayEntities;
use AltDesign\AltCommerceStatamic\Contracts\CurrencyConvertor;
use AltDesign\AltCommerceStatamic\Support\Settings;
use League\ISO3166\ISO3166;
use Statamic\Entries\Entry;
use Statamic\Fields\Value;

class ProductFactory
{
    use HasGatewayEntities;

    public function __construct(
        protected Settings $settings,
        protected CurrencyConvertor $currencyConvertor,
        protected PriceCollectionFactory $priceCollectionFactory
    )
    {

    }

    public function make(Entry $entry): Product
    {

        return new StatamicProduct(
            id: $entry->id,
            name: $entry->title,
            data: $entry->toAugmentedCollection()->mapWithKeys(fn (Value $value, $key) => [$key => $value->raw()])->toArray(),
            taxable: (bool)$entry->taxable,
            taxRules: $this->taxRules($entry),
            price: $this->pricingSchema($entry),
        );
    }

    protected function pricingSchema(Entry $entry): PricingSchema
    {
        if ($entry->get('billing_type') === 'single') {
            return new FixedPriceSchema(
                prices: $this->priceCollectionFactory->create($entry->get('pricing')),
            );
        }

        if ($entry->get('billing_type') === 'recurring') {
            $plans = [];
            foreach ($entry->get('recurring_pricing') as $item) {
                if (!$item['enabled']) {
                    continue;
                }

                $prices = $this->priceCollectionFactory->create($item['pricing']);
                $gatewayEntities = $this->extractGatewayEntities($entry, 'billing_plan', $item['id']);

                $plans[] = new BillingPlan(
                    id: $item['id'],
                    name: $item['title'],
                    prices: $prices,
                    billingInterval: new Duration(
                        amount: $item['billing_cycle'],
                        unit: DurationUnit::from($item['billing_cycle_unit']),
                    ),
                    createdAt: new \DateTimeImmutable($item['created_at'] ?? null),
                    updatedAt: new \DateTimeImmutable($item['updated_at'] ?? null),
                    trialPeriod: $item['trial_enabled'] ?
                        new Duration(
                            amount: $item['trial_duration'],
                            unit: DurationUnit::from($item['trial_duration_unit']),
                        ) : null,
                    gatewayEntities: $gatewayEntities,
                );
            }

            return new RecurrentBillingSchema(
                plans: $plans
            );
        }

        throw new \Exception('Billing type not supported');
    }


    /**
     * @param Entry $entry
     * @return TaxRule[]
     */
    protected function taxRules(Entry $entry): array
    {
        $id = $entry->tax_rate;
        if (empty($id)) {
            return [];
        }


        $taxRule = $this->getTaxRule($id);
        if (empty($taxRule)) {
            return [];
        }

        $compiled = [];
        foreach ($taxRule['rates'] as $rate) {
            $countries = [];
            foreach ($rate['country_filter'] ?? [] as $country) {
                $countries[] = $this->parseCountryCode($country);
            }
            $compiled[] = new TaxRule(
                name: $taxRule['name'],
                rate: $rate['percentage'],
                countryFilter: $countries,
            );
        }

        return $compiled;

    }

    protected function parseCountryCode(string $code): string
    {
        if (strlen($code) === 3) {
            $code = (new ISO3166)->alpha3($code)['alpha2'];
        }
        return $code;
    }

    protected function getTaxRule($id): ?array
    {
        foreach ($this->settings->taxRules() as $rule) {
            if ($rule['id'] === $id) {
                return $rule;
            }
        }
        return null;
    }

}
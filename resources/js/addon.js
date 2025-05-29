import TaxRateSelector from "./fieldtypes/TaxRateSelector.vue";
import MultiCurrencyPricing from "./fieldtypes/MultiCurrencyPricing.vue";
import OrderView from "./components/OrderView.vue"
import Money from "./fieldtypes/Money.vue";

Statamic.booting(() => {
    // Should be named [snake_case_handle]-fieldtype
    Statamic.$components.register('tax_rate_selector-fieldtype', TaxRateSelector);
    Statamic.$components.register('multi_currency_pricing-fieldtype', MultiCurrencyPricing)
    Statamic.$components.register('money-fieldtype', Money)
    Statamic.$components.register('order-view', OrderView)
});

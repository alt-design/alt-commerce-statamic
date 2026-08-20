import TaxRateSelector from "./fieldtypes/TaxRateSelector.vue";
import MultiCurrencyPricing from "./fieldtypes/MultiCurrencyPricing.vue";
import OrderView from "./components/OrderView.vue"
import SettingsForm from "./components/SettingsForm.vue"
import ReportsView from "./components/ReportsView.vue"
import Money from "./fieldtypes/Money.vue";
import Stock from "./fieldtypes/Stock.vue";
import OrderStatusIndex from "./fieldtypes/OrderStatusIndex.vue";

Statamic.booting(() => {
    // Should be named [snake_case_handle]-fieldtype
    Statamic.$components.register('tax_rate_selector-fieldtype', TaxRateSelector);
    Statamic.$components.register('multi_currency_pricing-fieldtype', MultiCurrencyPricing)
    Statamic.$components.register('money-fieldtype', Money)
    Statamic.$components.register('stock-fieldtype', Stock)
    Statamic.$components.register('order_status-fieldtype-index', OrderStatusIndex)
    Statamic.$components.register('order-view', OrderView)
    Statamic.$components.register('alt-commerce-settings-form', SettingsForm)
    Statamic.$components.register('reports-view', ReportsView)
});

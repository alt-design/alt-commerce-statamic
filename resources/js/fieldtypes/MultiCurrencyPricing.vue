<script>
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';
import { Input } from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],
    components: { Input },
    data() {
        return {
            items: [],
        };
    },
    computed: {
        currencies() {
            return this.meta.currencies
        },
    },
    beforeMount() {
        if (this.value) {
            this.items = this.value
        }
    },
    watch: {
        items: {
            deep: true,
            handler(val) {
                this.update(val)
            },
        }
    },
    methods: {
        updateAmount (currency, amount) {
            const existing = this.items.find(item => item.currency === currency)
            if (existing) {
                existing.amount = amount
            } else {
                this.items.push({currency, amount})
            }
        },

        getValue(currency) {
            return this.items.find(item => item.currency === currency)?.amount
        }
    }
};
</script>

<template>
    <div>
        <div v-for="currency in currencies" :key="currency.code" class="flex items-center my-1">
            <Input
                :model-value="getValue(currency.code)"
                placeholder="Amount"
                :prepend="currency.code"
                @update:model-value="(val) => updateAmount(currency.code, val)"
            />
        </div>
    </div>
</template>

<script>
export default {
    mixins: [Fieldtype],
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
        items(val) {
            this.update(val)
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
        <div v-for="currency in currencies" class="flex items-center my-1">
            <text-input @input="(val) => updateAmount(currency.code, val)" :value="getValue(currency.code)" placeholder="Amount" class="ml-1" :prepend="currency.code" />
        </div>
    </div>
</template>

<style scoped>

</style>
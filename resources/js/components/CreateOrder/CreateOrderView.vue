<script>
import Price from "../Price.vue";

export default {
    components: {Price},
    props: [
        'sections',
        'productLookupUrl',
        'customerLookupUrl',
        'basketLookupUrl',
        'saveUrl'
    ],
    data() {
        return {
            blueprint: {
                tabs: [{
                    sections: [],
                }]
            },
            errors: {},
            values: {},
            meta: {},
            customerCache: {},
            productCache: {},
            basketSummary: null,
            basketSummaryDebounceTimer: null,
            basketSummaryKey: null,
        }
    },

    methods: {
        async submit() {
            try {
                const {data} = await this.$axios.post(this.saveUrl, this.values)

                this.$toast.success('Order created', { duration: 3000 });

                // redirect to order show page
                setTimeout(() => {
                    window.location.href = '/cp/collections/orders/entries/' + data.id
                }, 500)

            } catch(error) {
                if (error?.response?.status === 422) {
                    this.errors = error.response.data.errors
                    this.$toast.error('Please check the order for errors.');
                } else {
                    this.$toast.error('An unknown error occurred.');
                }
            }
        },

        parseBlueprint()
        {
            this.sections.forEach(item => {
                item.blueprint.tabs[0].sections.forEach(section => {
                    this.blueprint.tabs[0].sections.push({
                        display: item.display,
                        ...section,
                    })
                })
                for (let i in item.meta) {
                    this.meta[i] = item.meta[i]
                }
                for (let i in item.values) {
                    this.values[i] = item.values[i]
                }
            })
        },

        async updateSummary() {

            if (this.basketSummaryDebounceTimer) {
                clearTimeout(this.basketSummaryDebounceTimer)
            }

            this.basketSummaryDebounceTimer = setTimeout(() => {
                this.grabBasketSummary()
                    .then(data => {
                        this.basketSummary = data
                        this.basketSummaryKey = new Date().getTime();
                    })
                    .catch(() => this.basketSummary = null)
            }, 500)
        },

        async grabBasketSummary() {
            if (!this.values.line_items?.length) {
                return null
            }

            const {data} = await this.$axios.get(this.basketLookupUrl, {params: this.values})
            console.log('Got basket summary', data)
            return data
        },

        async grabProduct(id) {
            if (this.productCache[id]) {
                return this.productCache[id]
            }
            const {data} = await this.$axios.get(this.productLookupUrl, {params: {id: id}})

            if (!data) {
                throw 'unable to find product with id: ' + id
            }

            this.productCache[id] = data
            return data
        },

        async grabCustomer(id) {
            if (this.customerCache[id]) {
                return this.customerCache[id]
            }
            const {data} = await this.$axios.get(this.customerLookupUrl, {params: {id: id}})

            if (!data) {
                throw 'unable to find customer with id: ' + id
            }

            this.customerCache[id] = data
            return data
        },

        getChangedKeys(arr1, arr2, compare) {
            const keys = new Set([...arr1.map(item => item._id), ...arr2.map(item => item._id)]);

            return [...keys].filter(id => {
                const item1 = arr1.find(item => item._id === id);
                const item2 = arr2.find(item => item._id === id);

                if (!item1 || !item2) return true;

                return compare(item1) !== compare(item2)

            });
        },

        _setFieldValue(handle, value, store) {

            if (handle === 'customer' && value.length) {
                this.grabCustomer(value[0]).then(customer => {
                    store('customer_name', customer.name)
                    store('customer_email', customer.email)
                })
            }

            if (handle === 'line_items') {

                // Lookup products that have changed
                const productDiff = this.getChangedKeys(this.values.line_items ?? [], value, (item) => (item.product || []).join(",") )
                value.filter(x => productDiff.includes(x._id)).forEach(item => {
                    if (item.product.length) {
                        this.grabProduct(item.product[0]).then(product => {
                            const pricing = (product.pricing ?? []).find(x => x.currency === this.values.currency)
                            item.price = pricing.amount
                            item.subtotal = item.quantity * item.price
                            this.updateSummary()
                        })
                    } else {
                        item.price = null
                    }
                })

                // recalculate subtotal on all line items
                value.forEach(item => {
                    item.subtotal = item.price ? item.price * item.quantity : null
                })
            }

            this.updateSummary()

            store(handle, value)
        },
    },

    beforeMount() {
        this.parseBlueprint()
    },

    mounted() {
    }
}
</script>
<template>
    <publish-container
        ref="container"
        :blueprint="blueprint"
        v-model="values"
        :meta="meta"
        :errors="errors"
        v-slot="{ setFieldValue, setFieldMeta }"
    >
        <div>
            <div class="flex items-center mb-6">
                <h1 class="flex-1">
                    Create Order
                </h1>
                <button type="submit" class="btn-primary" @click="submit">
                    Save
                </button>
            </div>

            <publish-sections
                :sections="blueprint.tabs[0].sections"
                @updated="(handle, value) => _setFieldValue(handle, value, setFieldValue)"
                @meta-updated="setFieldMeta" />

            <div class="card p-6 mt-5">
                <table v-if="basketSummary" :key="basketSummaryKey">
                    <tbody>
                    <tr>
                       <td>Subtotal</td>
                        <td> <Price :amount="basketSummary.subTotal " :currency="values.currency" /></td>
                    </tr>
                    <tr v-for="item in basketSummary.taxItems">
                        <td>{{item.name }} ({{ item.rate}}%)</td>
                        <td> <Price :amount="item.amount" :currency="values.currency" /></td>
                    </tr>
                    <tr v-if="basketSummary.discountTotal > 0">
                        <td>Discount</td>
                        <td> <Price :amount="basketSummary.discountTotal " :currency="values.currency" /></td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td> <Price :amount="basketSummary.total" :currency="values.currency" /></td>
                    </tr>
                    </tbody>
                </table>
                <div class="py-5 text-gray-500 text-center text-sm" v-else>
                    Summary will appear here
                </div>
            </div>

        </div>
    </publish-container>

</template>
<style scoped>
td {
    @apply p-2 text-lg;
}
td:last-child {
    @apply font-bold pl-8;
}
</style>
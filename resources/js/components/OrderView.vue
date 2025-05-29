<script>
import HasActions from 'statamic/components/publish/HasActions';
import OrderNotes from "./OrderNotes.vue";
import OrderLogs from "./OrderLogs.vue";
import OrderTransactions from "./OrderTransactions.vue";
export default {
    mixins: [HasActions],
    components: {OrderTransactions, OrderLogs, OrderNotes},
    props: [
        'endpoint'
    ],
    data() {
        return {

            loading: true,
            id: null,
            notes: null,
            logs: null,
            transactions: null,
            blueprint: null,
            meta: null,
            values: null,
            valuesMutable: null,
            basketLookupUrl: null,
            productLookupUrl: null,
            customerLookupUrl: null,
            saveUrl: null,
            saveMethod: null,
            gatewayUrls: null,
            errors: {},

            entityCache: {},
            commitStoreCallback: null,
            axiosController: {},
        }
    },
    computed: {
        isCreating() {
            return !this.id
        }
    },

    methods: {
        async submit() {
            try {
                const {data} = await this.$axios.request({
                    method: this.saveMethod,
                    url: this.saveUrl,
                    data: this.valuesMutable,
                })

                this.$toast.success('Order Saved', {duration: 3000});

                this.$dirty.disableWarning()

                if (this.isCreating) {
                    window.location.href = '/cp/collections/orders/entries/' + data.id
                }

            } catch (error) {
                if (error?.response?.status === 422) {
                    this.errors = error.response.data.errors
                    this.$toast.error('Please check the order for errors.');
                } else {
                    this.$toast.error('An unknown error occurred.');
                }
            }
        },


        async recalculate() {

            if (this.axiosController.recalculate) {
                this.axiosController.recalculate.abort()
            }

            this.axiosController.recalculate = new AbortController()

            try {
                const {data} = await this.$axios.get(this.basketLookupUrl, {
                    params: this.valuesMutable,
                    signal: this.axiosController.recalculate.signal
                })

                this.valuesMutable.sub_total = data.subTotal
                this.valuesMutable.tax_total = data.taxTotal
                this.valuesMutable.discount_total = data.discountTotal
                this.valuesMutable.total = data.total


                this.valuesMutable.items.forEach(item => {
                    if (item.type !== 'line_item' || !item.tax_auto) {
                        return
                    }
                    const lineItem = data.lineItems.find(x => x.productId === item.product[0])
                    item.tax_amount = lineItem.taxTotal / 100
                    item.tax_rate = lineItem.taxRate
                    item.tax_name = lineItem.taxName
                })


            } catch(error) {
                if (this.$axios.isCancel(error) || error.name === 'CanceledError') {
                    return
                }
                console.log(error)
                this.valuesMutable.sub_total = null
                this.valuesMutable.total = null
                this.valuesMutable.tax_total = null
                this.valuesMutable.discount_total = null
            }
        },


        async grabEntity(type, id) {
            const key = `${type}-${id}`
            if (this.entityCache[key]) {
                return this.entityCache[key]
            }

            let url;
            if (type === 'customer') {
                url = this.customerLookupUrl
            } else if (type === 'product') {
                url = this.productLookupUrl
            }

            if (!url) {
                throw 'invalid entity type'
            }


            const {data} = await this.$axios.get(url, {params: {id}})
            if (!data) {
                throw `unable to find ${type} with id: ${id}`
            }

            this.entityCache[key] = data
            return data
        },

        async prefillCustomer(customerId) {
            const customer = customerId ? await this.grabEntity('customer', customerId) : null
            this.valuesMutable.customer_name = customer?.name
            this.valuesMutable.customer_email = customer?.email

        },

        async prefillLineItems(payload) {

            for (let i in payload.value) {

                const item = payload.value[i]
                if (item.type !== 'line_item') {
                    continue;
                }

                const prev = this.valuesMutable.items[i] ?? null

                if (!item.product.length) {
                    item.price = null
                    item.quantity = 1
                    item.subtotal = null
                    continue;
                }


                if (!item.quantity || prev?.product[0] !== item.product[0]) {
                    item.quantity = 1
                }

                if (!item.price || prev?.product[0] !== item.product[0]) {
                    const product = await this.grabEntity('product', item.product[0])
                    const pricing = (product.pricing ?? []).find(x => x.currency === this.values.currency)
                    item.price = pricing.amount
                }

                item.subtotal = item.quantity * item.price
            }
        },

        async deleteNote({note, resolve}) {
            const payload = {
                action: 'delete_order_note',
                context: {
                    collection: 'orders',
                    view: 'form'
                },
                selections: [this.id],
                values: {
                    note
                }
            };

            this.$axios
                .post(this.itemActionUrl, payload, { responseType: 'blob' })
                .then((response) => {
                    const index = this.notes.findIndex(n => n.id === note.id)
                    if (index !== -1) {
                        this.notes.splice(index, 1)
                    }
                    response.data.text().then(data => {
                        data = JSON.parse(data);
                        this.$toast.success(data.message);
                    });
                    resolve()
                })
                .catch((error) => {
                    error.response.data.text().then(data => {
                        data = JSON.parse(data);
                        this.$toast.error(data.message);
                    });
                })
                .finally(() => {
                    resolve()
                });
        },

        async commitStore(type, payload, options) {
            if (type === 'publish/order/setFieldValue') {
                if (payload.handle === 'items') {
                    await this.prefillLineItems(payload)
                }
            }
            return this.commitStoreCallback.call(this, type, payload, options);
        },

        async setup(data) {

            this.id = data.id
            this.gatewayUrls = data.gatewayUrls
            this.meta = data.meta
            this.notes = data.notes
            this.logs = data.logs
            this.transactions = data.transactions
            this.saveMethod = data.saveMethod
            this.saveUrl = data.saveUrl
            this.values = data.values
            this.valuesMutable = data.values
            this.basketLookupUrl = data.basketLookupUrl
            this.productLookupUrl = data.productLookupUrl
            this.customerLookupUrl = data.customerLookupUrl
            this.loading = false
            this.itemActions = data.itemActions
            this.itemActionUrl = data.itemActionUrl

            const blueprint = data.blueprint;
            blueprint.tabs.forEach(tab => {
                tab.sections.forEach(section => {
                    section.fields.forEach(field => {
                        if (this.isCreating && ['order_number', 'order_status'].includes(field.handle)) {
                            field.visibility = 'hidden'
                        }

                        if (!this.isCreating && field.handle === 'currency') {
                            field.visibility = 'read_only'
                        }
                    })
                })
            })

            this.blueprint = blueprint
        },

        extractFields(blueprint) {

            const fields = []
            blueprint.tabs.forEach(tab => {

                tab.sections.forEach(section => {

                    section.fields.forEach(field => {
                        fields.push(field)
                    })
                })
            })

            return fields

        }
    },


    beforeMount() {

        // Fired after value has been committed
        this.$store.subscribe((mutation) => {
            if (mutation.type !== 'publish/order/setFieldValue') {
                return
            }

            if (['items', 'coupon_code'].includes(mutation.payload.handle)) {
                this.recalculate()
            }

            if (mutation.payload.handle === 'customer_id') {
                this.prefillCustomer(mutation.payload.value[0])
            }
        })

        // Fired before value has been committed
        this.commitStoreCallback = this.$store.commit
        this.$store.commit = this.commitStore

        Statamic.$callbacks.add('orderActionRan', (data) => {
            data.actions.forEach((action) => {
                if (action.type === 'note-added') {
                    this.notes.unshift(JSON.parse(action.note))
                }

                if (action.type=== 'log-added') {
                    this.logs.unshift(JSON.parse(action.log))
                }

                if (action.type === 'note-deleted') {
                    const index = this.notes.findIndex(n => n.id === action.id)
                    if (index !== -1) {
                        this.notes.splice(index, 1)
                    }
                }

                if (action.type === 'status-updated') {
                    this.status = action.status
                }
            })
        });

        this.$axios.get(this.endpoint).then(({data}) => this.setup(data))
    },

    mounted() {




    },
}
</script>
<template>
    <publish-container
        v-if="!loading"
        ref="container"
        :blueprint="blueprint"
        v-model="valuesMutable"
        :meta="meta"
        name="order"
        :key="this.id"
        :errors="errors"
        v-slot="{ setFieldValue, setFieldMeta }"
    >
        <div>
            <div class="flex items-center mb-6">
                <h1 class="flex-1">
                    <template v-if="!isCreating">Edit Order {{ values.order_number }}</template>
                    <template v-else>Create order</template>
                </h1>
                <dropdown-list v-if="itemActions">
                    <data-list-inline-actions
                        :actions="itemActions"
                        :item="id"
                        :url="itemActionUrl"
                        @completed="actionCompleted"
                    />
                </dropdown-list>
                <button type="submit" class="btn-primary" @click="submit">
                    Save
                </button>
            </div>

            <publish-tabs
                @updated="setFieldValue"
                @meta-updated="setFieldMeta"
            :enable-sidebar="true"/>


            <OrderTransactions v-if="!isCreating" v-bind="{transactions, gatewayUrls}" :currency="values.currency"/>

            <OrderNotes class="mt-5" v-if="!isCreating" :notes="notes" @deleteNote="deleteNote" />

            <OrderLogs class="mt-5"  v-if="!isCreating" :logs="logs"/>

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
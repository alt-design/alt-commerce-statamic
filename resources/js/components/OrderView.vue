<script>
import { HasActionsMixin, ItemActions } from '@statamic/cms';
import { PublishContainer, PublishTabs, Button, Dropdown, DropdownMenu, DropdownItem } from '@statamic/cms/ui';
import OrderNotes from "./OrderNotes.vue";
import OrderLogs from "./OrderLogs.vue";
import OrderTransactions from "./OrderTransactions.vue";

export default {
    mixins: [HasActionsMixin],
    components: {
        PublishContainer,
        PublishTabs,
        Button,
        Dropdown,
        DropdownMenu,
        DropdownItem,
        ItemActions,
        OrderTransactions,
        OrderLogs,
        OrderNotes,
    },
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
            lastValues: null,
            basketLookupUrl: null,
            productLookupUrl: null,
            customerLookupUrl: null,
            saveUrl: null,
            saveMethod: null,
            gatewayUrls: null,
            itemActions: null,
            itemActionUrl: null,
            errors: {},

            entityCache: {},
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

                this.$refs.container?.clearDirtyState()

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

        // Replaces the v5 Vuex store subscription: the publish container emits full
        // values on every field commit, so changed handles are found by diffing.
        async onValuesUpdated(values) {
            const prev = this.lastValues ?? {}
            this.valuesMutable = values

            const itemsChanged = JSON.stringify(values.items) !== JSON.stringify(prev.items)
            const couponChanged = values.coupon_code !== prev.coupon_code
            const customerChanged = JSON.stringify(values.customer_id) !== JSON.stringify(prev.customer_id)

            this.lastValues = JSON.parse(JSON.stringify(values))

            if (itemsChanged) {
                await this.prefillLineItems(values.items, prev.items ?? [])
            }

            if (itemsChanged || couponChanged) {
                this.recalculate()
            }

            if (customerChanged) {
                this.prefillCustomer(values.customer_id?.[0])
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
                    if (!lineItem) {
                        return
                    }

                    item.tax_amount = lineItem.taxTotal / 100
                    item.tax_rate = lineItem.taxRate
                    item.tax_name = lineItem.taxName
                })


            } catch(error) {
                if (this.$axios.isCancel(error) || error.name === 'CanceledError') {
                    return
                }
                console.error(error)
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

        async prefillLineItems(items, prevItems) {

            for (let i in items) {

                const item = items[i]
                if (item.type !== 'line_item') {
                    continue;
                }

                const prev = prevItems[i] ?? null

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
                    const currency = this.valuesMutable.currency ?? this.values.currency;
                    const product = await this.grabEntity('product', item.product[0])
                    const pricing = (product.pricing ?? []).find(x => x.currency === currency)
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
            this.lastValues = JSON.parse(JSON.stringify(data.values))
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
    },


    beforeMount() {

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
}
</script>
<template>
    <PublishContainer
        v-if="!loading"
        ref="container"
        :blueprint="blueprint"
        :model-value="valuesMutable"
        @update:model-value="onValuesUpdated"
        :meta="meta"
        name="order"
        :key="id"
        :errors="errors"
    >
        <div>
            <div class="flex items-center mb-6">
                <h1 class="flex-1">
                    <template v-if="!isCreating">Edit Order {{ values.order_number }}</template>
                    <template v-else>Create order</template>
                </h1>
                <ItemActions
                    v-if="itemActions"
                    :url="itemActionUrl"
                    :actions="itemActions"
                    :item="id"
                    @completed="actionCompleted"
                    v-slot="{ actions }"
                >
                    <Dropdown>
                        <template #trigger>
                            <Button icon="dots-horizontal" icon-only :aria-label="__('Actions')" />
                        </template>
                        <DropdownMenu>
                            <DropdownItem
                                v-for="action in actions"
                                :key="action.handle"
                                :text="__(action.title)"
                                :variant="action.dangerous ? 'destructive' : 'default'"
                                @click="action.run"
                            />
                        </DropdownMenu>
                    </Dropdown>
                </ItemActions>
                <div class="flex gap-x-1">
                    <slot name="action-buttons-prefix" :data="$data"/>
                    <Button variant="primary" @click="submit">Save</Button>
                </div>
            </div>

            <PublishTabs />


            <OrderTransactions v-if="!isCreating" v-bind="{transactions, gatewayUrls}" :currency="values.currency"/>

            <OrderNotes class="mt-5" v-if="!isCreating" :notes="notes" @deleteNote="deleteNote" />

            <OrderLogs class="mt-5"  v-if="!isCreating" :logs="logs"/>


        </div>
    </PublishContainer>

</template>
<style scoped>
td {
    padding: 0.5rem;
    font-size: 1.125rem;
}
td:last-child {
    font-weight: 700;
    padding-left: 2rem;
}
</style>

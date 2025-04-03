<script>
import OrderSummary from "./OrderSummary.vue";
import OrderCustomerDetails from "./OrderCustomerDetails.vue";
import OrderLineItems from "./OrderLineItems.vue";
import OrderTransactions from "./OrderTransactions.vue";
import OrderSubscriptions from "./OrderSubscriptions.vue";
import useReplaceableComponent from "../library/useReplaceableComponent";
import OrderAdditionalFields from "./OrderAdditionalFields.vue";
import OrderNotes from "./OrderNotes.vue";
import OrderHeader from "./OrderHeader.vue";
import HasActions from 'statamic/components/publish/HasActions.js';
import OrderLogs from "./OrderLogs.vue";
export default {
    mixins: [HasActions],
    props: {
        initialOrder: Object,
        gatewayUrls: Array,
        additionalFields: Object
    },
    data() {
        return {
            order: this.initialOrder,
        }
    },
    methods: {
        async deleteNote({note, resolve}) {
            const payload = {
                action: 'delete_order_note',
                context: {
                    collection: 'orders',
                    view: 'form'
                },
                selections: [this.order.id],
                values: {
                    note
                }
            };

            this.$axios
                .post(this.itemActionUrl, payload, { responseType: 'blob' })
                .then((response) => {
                    const index = this.order.notes.findIndex(n => n.id === note.id)
                    if (index !== -1) {
                        this.order.notes.splice(index, 1)
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
        }
    },
    render(createElement) {

        Statamic.$callbacks.add('orderActionRan', (data) => {
            data.actions.forEach((action) => {
                if (action.type === 'note-added') {
                    this.order.notes.unshift(JSON.parse(action.note))
                    this.$set(this.order, 'notes', [...this.order.notes]);
                }

                if (action.type=== 'log-added') {
                    this.order.logs.unshift(JSON.parse(action.log))
                    this.$set(this.order, 'logs', [...this.order.logs]);
                }

                if (action.type === 'note-deleted') {
                    const index = this.order.notes.findIndex(n => n.id === action.id)
                    if (index !== -1) {
                        this.order.notes.splice(index, 1)
                    }
                }

                if (action.type === 'status-updated') {
                    this.order.status = action.status
                }
            })
        });

        const defaultArgs = {
            props: {
                ...this.$props,
                order: this.order,
                blueprint: this.additionalFields.blueprint,
                fields: this.additionalFields.fields,
                values: this.additionalFields.values,
            },
            on: {
                deleteNote: this.deleteNote
            }
        }

        const stack = [
            useReplaceableComponent('order-header', OrderHeader, defaultArgs),
            useReplaceableComponent('order-summary', OrderSummary, defaultArgs),
            useReplaceableComponent('order-customer-details', OrderCustomerDetails, defaultArgs),
            useReplaceableComponent('order-line-items', OrderLineItems, defaultArgs),
            useReplaceableComponent('order-subscription-items', OrderSubscriptions, defaultArgs),
            useReplaceableComponent('order-transactions', OrderTransactions, defaultArgs),
            useReplaceableComponent('order-additional-fields', OrderAdditionalFields, defaultArgs),
            useReplaceableComponent('order-notes', OrderNotes, defaultArgs),
            useReplaceableComponent('order-logs', OrderLogs, defaultArgs)
        ]

        return createElement('div',
            {
                attrs: {
                    class: 'order-view'
                }
            },
            stack.map(item => createElement(item.component.value, {...item.props.value}))
        )
    }
}

</script>
<style scoped>
.order-view > * + * {
    margin-top: 20px;
}
</style>
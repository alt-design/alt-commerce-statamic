<script setup>
import Price from "./Price.vue";
import useReplaceableComponent from "../library/useReplaceableComponent";

const props = defineProps(['order'])
const {component, props: componentProps} = useReplaceableComponent('order-summary-extension', null, props)
</script>

<template>
    <div class="card p-6">
        <table>
            <tbody>
            <tr>
                <td>
                    <label>Order number</label>
                </td>
                <td class="font-bold">
                    {{ order.orderNumber }}
                </td>
            </tr>
            <tr>
                <td>
                    <label>Created</label>
                </td>
                <td>
                    {{ new Date(order.createdAt.date).toLocaleString() }}
                </td>
            </tr>
            <tr>
                <td>
                    <label>Status</label>
                </td>
                <td class="uppercase">{{ order.status }}</td>
            </tr>
            <tr v-if="order.subTotal !== order.total">
                <td>
                    <label>Sub total</label>
                </td>
                <td>
                    <Price :amount="order.subTotal" :currency="order.currency"/>
                </td>
            </tr>
            <tr v-if="order.taxTotal > 0">
                <td>
                    <label>Tax total</label>
                </td>
                <td>
                    <Price :amount="order.taxTotal" :currency="order.currency"/>
                </td>
            </tr>
            <tr v-if="order.deliveryTotal > 0">
                <td>
                    <label>Delivery total</label>
                </td>
                <td>
                    <Price :amount="order.deliveryTotal" :currency="order.currency"/>
                </td>
            </tr>
            <tr v-if="order.discountTotal > 0">
                <td>
                    <label>Discount total</label>
                </td>
                <td>
                    <Price :amount="order.discountTotal" :currency="order.currency"/>
                </td>
            </tr>
            <tr>
                <td>
                    <label>Total</label>
                </td>
                <td class="font-bold">
                    <Price :amount="order.total" :currency="order.currency"/>
                </td>
            </tr>
            </tbody>
        </table>


        <component v-if="component" :is="component" v-bind="componentProps"/>
    </div>
</template>

<style scoped>
:deep td {
    @apply py-0.5 pr-6 min-w-32 text-nowrap;
}

</style>
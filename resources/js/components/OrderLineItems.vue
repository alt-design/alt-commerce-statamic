<script setup>

import Price from "./Price.vue";

const props = defineProps(['order'])
</script>

<template>
    <div v-if="order.lineItems.length" class="card p-6">
        <table class="w-full">
            <thead class="">
            <tr class="border-b">
                <th style="text-align: left">Line Items</th>
                <th>Price</th>
                <th>Quantity</th>
                <th style="text-align: right">Sub Total</th>
            </tr>
            </thead>
            <tbody>

            <tr class="border-b" v-for="item in order.lineItems">
                <td style="text-align: left">{{ item.productName }}</td>
                <td class="text-center">
                    <Price :currency="order.currency" :amount="item.amount"/>
                </td>
                <td class="text-center">{{ item.quantity }}</td>
                <td style="text-align: right">
                    <Price :currency="order.currency" :amount="item.subTotal"/>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
td a {
    @apply underline decoration-dotted;
}
td {
    @apply p-2;
}
</style>
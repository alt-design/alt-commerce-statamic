<script setup>
import Price from "./Price.vue";

const props = defineProps(['order', 'gatewayUrls'])
</script>

<template>
    <div class="card p-6" v-if="order.subscriptions.length">
        <table class="w-full">
            <thead class="">
            <tr class="border-b">
                <th style="text-align: left">Subscriptions</th>
                <th>Gateway</th>
                <th style="text-align:right">Amount</th>
            </tr>
            </thead>
            <tbody>

            <tr class="border-b" v-for="item in order.subscriptions">
                <td>
                    {{ new Date(item.createdAt.date).toLocaleString()}}
                </td>
                <td class="text-center">
                    <a :href="gatewayUrls.find(x => x.type === 'subscription' && x.id === item.id)?.url" target="_blank">
                        {{ item.gatewayId }}
                    </a>
                </td>
                <td style="text-align: right">
                    <Price :currency="order.currency" :amount="item.additional.price * 100"/>
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
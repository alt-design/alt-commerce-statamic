<script setup>
import Price from "./Price.vue";
const props = defineProps(['transactions', 'gatewayUrls', 'currency'])
</script>

<template>
    <div class="card p-6 mt-5" v-if="transactions.length">
        <table class="w-full">
            <thead>
            <tr class="border-b">
                <th style="text-align: left">Transactions</th>
                <th>Gateway</th>
                <th>Type</th>
                <th>Status</th>
                <th style="text-align: right">Amount</th>
            </tr>
            </thead>
            <tbody>

            <tr class="border-b" v-for="item in transactions">

                <td style="text-align: left">
                    {{ new Date(item.createdAt.date).toLocaleString() }}
                </td>
                <td class="text-center" style="text-align: left">
                    <a :href="gatewayUrls.find(x => x.type === 'transaction' && x.id === item.id)?.url" target="_blank">
                        {{ item.additional.id}}
                    </a>
                </td>
                <td class="uppercase text-center" >
                    {{ item.type }}
                </td>
                <td class="text-center uppercase">
                    {{ item.status }}
                </td>
                <td class="" style="text-align: right">
                    <Price :currency="currency" :amount="item.amount"/>
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
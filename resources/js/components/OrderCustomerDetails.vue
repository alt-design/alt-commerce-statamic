<script setup>

import Address from "./Address.vue";
const props = defineProps(['order'])
</script>

<template>
    <div class="card p-6 flex">
        <div class="w-1/3">
            <div>
                <label>Customer details</label>
                <div>{{ order.customer.name }}</div>
                <div>{{ order.customer.email }}</div>
            </div>
        </div>
        <div class="w-1/3">
            <div>
                <label>Billing address</label>
                <Address :address="order.billingAddress"/>
            </div>
        </div>
        <div class="w-1/3">
            <div>
                <label>Shipping address</label>
                <Address :address="order.shippingAddress"/>
            </div>
        </div>
    </div>

</template>

<style scoped>

</style>
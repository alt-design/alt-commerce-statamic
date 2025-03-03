<script setup>
import {computed} from 'vue';
const props = defineProps(['address'])

const hasAddress = computed(() => props.address && Object.keys(props.address).length > 0)
</script>

<template>
    <div>
        <template v-if="hasAddress">
            <div>{{ address.company}}</div>
            <div>{{ address.fullName}}</div>
            <div>{{ address.street}}</div>
            <div>{{ address.region}}</div>
            <div>{{ address.locality}}</div>
            <div>{{ address.postalCode}}</div>
            <div>{{ address.countryCode}}</div>
        </template>
        <div v-else class="text-gray-500 text-sm">No address</div>
    </div>
</template>

<style scoped>

</style>
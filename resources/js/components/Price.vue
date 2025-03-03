<script setup>
import {ref, watch} from "vue";
const props = defineProps({
    amount: Number,
    currency: String,
    locale: {
        default: 'en-GB'
    }
})

const formatted = ref()
const recalculate = () => {
    formatted.value = new Intl.NumberFormat(props.locale, {
        style: 'currency',
        currency: props.currency,
    }).format(props.amount / 100)
}
watch(() => props.currency, recalculate)
watch(() => props.amount, recalculate)

recalculate()
</script>

<template>
<span>{{ formatted }}</span>
</template>

<style scoped>

</style>
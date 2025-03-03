<script setup>
const props = defineProps({
    order: {},
    blueprint: {
        default: () => ({})
    },
    meta: {
        default: () => ({})
    },
    values: {
        default: () => ({})
    }
})

</script>

<template>
    <div class="card">
        <publish-container
            ref="container"
            :blueprint="blueprint"
            v-model="values"
            :meta="meta"
            v-slot="{ setFieldValue, setFieldMeta }">

            <publish-fields :read-only="true" :fields="blueprint.tabs[0].sections[0].fields" />

        </publish-container>
    </div>
</template>

<style scoped>

</style>
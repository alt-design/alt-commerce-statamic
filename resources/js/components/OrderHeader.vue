<script>
import HasActions from 'statamic/components/publish/HasActions';
export default {
    mixins: [HasActions],
    props: {
        order: Object,
    }
}
</script>

<template>
    <div class="card flex  justify-between items-baseline">
        <h1 class="text-2xl font-bold">Order #{{ order.orderNumber }}</h1>
        <dropdown-list>
            <data-list-inline-actions
                :actions="itemActions"
                :item="order.id"
                :url="itemActionUrl"
                @completed="actionCompleted"
            />
        </dropdown-list>
    </div>
</template>

<style scoped>

</style>
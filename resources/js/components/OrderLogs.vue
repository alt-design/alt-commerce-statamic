<script>

export default {
    props: {
        logs: {
            type: Array,
            default: () => []
        }
    },
}

</script>

<template>
<div class="card order-logs p-6" v-if="logs.length">
    <h2>Logs</h2>
    <ul class="list">
        <li v-for="log in logs" :key="log.id" class="py-3">
            <div class="text-xs text-gray-600">{{ new Date(log.createdAt).toLocaleString() }}</div>
            <div class="text-sm" v-html="log.content" />

        </li>
    </ul>
</div>
</template>

<script>
const STATUS_COLORS = {
    draft: { background: '#f0f0f1', color: '#52525b' },
    pending: { background: '#fef3c7', color: '#b45309' },
    processing: { background: '#dbeafe', color: '#1d4ed8' },
    processed: { background: '#ccfbf1', color: '#0f766e' },
    complete: { background: '#dcfce7', color: '#15803d' },
    cancelled: { background: '#fee2e2', color: '#b91c1c' },
    refunded: { background: '#f3e8ff', color: '#7e22ce' },
};

export default {
    props: ['value'],

    computed: {
        label() {
            return this.value ? this.value.charAt(0).toUpperCase() + this.value.slice(1) : null;
        },
        colors() {
            return STATUS_COLORS[this.value] ?? STATUS_COLORS.draft;
        },
    },
};
</script>

<template>
    <span v-if="label" class="order-status-badge" :style="{ backgroundColor: colors.background, color: colors.color }">
        <span class="order-status-badge-dot"></span>
        {{ label }}
    </span>
    <span v-else>&mdash;</span>
</template>

<style scoped>
.order-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 9px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.4;
    white-space: nowrap;
}
.order-status-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    background-color: currentColor;
}
</style>

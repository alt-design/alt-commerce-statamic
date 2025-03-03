<script>
export default {
    mixins: [Fieldtype],
    data() {
        return {
            id: null,
        };
    },
    beforeMount() {
        if (this.value) {
            this.id = this.value
        }
    },
    watch: {
        id(val) {
            this.update(val)
        }
    },
};
</script>
<template>
    <select-input :options="this.meta" v-model="id"/>
</template>
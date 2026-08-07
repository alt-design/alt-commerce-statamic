<script>
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';
import { Select } from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],
    components: { Select },
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
    <Select :options="meta" v-model="id" />
</template>

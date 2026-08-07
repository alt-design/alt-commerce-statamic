<script>
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';
import { Input } from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],
    components: { Input },
};
</script>

<template>
    <Input
        type="text"
        :id="fieldId"
        :model-value="value"
        :read-only="isReadOnly"
        @update:model-value="updateDebounced"
        @focus="$emit('focus')"
        @blur="$emit('blur')"
    />
</template>

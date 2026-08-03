<script>
// Replaces the v5 global `publish-form` component, which no longer exists in Statamic 6.
import { PublishContainer, PublishTabs, Button } from '@statamic/cms/ui';

export default {
    components: { PublishContainer, PublishTabs, Button },
    props: {
        action: String,
        blueprint: Object,
        meta: Object,
        values: Object,
    },
    data() {
        return {
            currentValues: this.values,
            errors: {},
            saving: false,
        }
    },
    methods: {
        async save() {
            this.saving = true
            try {
                await this.$axios.post(this.action, this.currentValues)
                this.errors = {}
                this.$toast.success('Settings saved', {duration: 3000})
                this.$refs.container?.clearDirtyState()
            } catch (error) {
                if (error?.response?.status === 422) {
                    this.errors = error.response.data.errors
                    this.$toast.error('Please check the settings for errors.')
                } else {
                    this.$toast.error('An unknown error occurred.')
                }
            } finally {
                this.saving = false
            }
        }
    }
}
</script>

<template>
    <PublishContainer
        ref="container"
        name="alt-commerce-settings"
        :blueprint="blueprint"
        v-model="currentValues"
        :meta="meta"
        :errors="errors"
        as-config
    >
        <div class="flex items-center justify-end mb-4">
            <Button variant="primary" :loading="saving" @click="save">Save</Button>
        </div>
        <PublishTabs />
    </PublishContainer>
</template>

<script>
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';
import { Input, Select, Button } from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],
    components: { Input, Select, Button },
    data() {
        return {
            level: this.meta.level,
            movements: this.meta.movements || [],
            quantity: null,
            reason: null,
            note: '',
            saving: false,
            error: null,
        };
    },
    computed: {
        tracked() {
            return this.meta.policy !== 'untracked';
        },
        policyLabel() {
            return this.meta.policy.charAt(0).toUpperCase() + this.meta.policy.slice(1);
        },
        canApply() {
            const n = Number(this.quantity);
            return !!this.meta.adjust_url && Number.isInteger(n) && n !== 0 && !this.saving;
        },
        reasonOptions() {
            return [{ value: null, label: 'No reason' }, ...(this.meta.reasons || [])];
        },
    },
    methods: {
        async apply() {
            if (!this.canApply) return;
            this.saving = true;
            this.error = null;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(this.meta.adjust_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        quantity: Number(this.quantity),
                        reason: this.reason,
                        note: this.note || null,
                    }),
                });
                if (!res.ok) throw new Error('Adjustment failed');
                const data = await res.json();
                this.level = data.level;
                this.movements = data.movements;
                this.quantity = null;
                this.note = '';
                this.reason = null;
            } catch (e) {
                this.error = e.message || 'Something went wrong';
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-bold leading-none">{{ tracked ? (level ?? 0) : '—' }}</span>
                <span class="text-sm text-gray-500">in stock</span>
            </div>
            <span class="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-500 dark:border-gray-700">{{ policyLabel }}</span>
        </div>

        <p v-if="!meta.product_id" class="mt-4 text-sm text-gray-500">
            Save the product first to manage stock.
        </p>

        <template v-else>
            <p v-if="!tracked" class="mt-4 text-sm text-gray-500">
                This product isn&rsquo;t tracked. Set the Stock Policy to Tracked or Backorder to count units. Adjustments below still record to the ledger.
            </p>

            <div class="mt-5 flex flex-wrap items-end gap-4">
                <div class="w-32">
                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Adjustment</label>
                    <Input
                        type="number"
                        placeholder="e.g. 50"
                        :model-value="quantity"
                        @update:model-value="quantity = $event"
                        @keydown.enter.prevent="apply"
                    />
                </div>
                <div class="w-72">
                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Reason</label>
                    <Select :options="reasonOptions" v-model="reason" />
                </div>
                <Button variant="primary" :loading="saving" :disabled="!canApply" @click="apply">Apply</Button>
            </div>

            <p v-if="error" class="mt-3 text-sm text-red-500">{{ error }}</p>

            <div v-if="movements.length" class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-800">
                <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">Recent movements</div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    <li v-for="(m, i) in movements" :key="i" class="flex items-center justify-between py-1.5 text-sm">
                        <span :class="m.quantity < 0 ? 'font-medium text-red-500' : 'font-medium text-green-600'">
                            {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}
                        </span>
                        <span class="capitalize text-gray-400">{{ m.reason || '—' }}</span>
                    </li>
                </ul>
            </div>
        </template>
    </div>
</template>

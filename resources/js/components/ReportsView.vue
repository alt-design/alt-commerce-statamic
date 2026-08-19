<script>
import { Button, Input } from '@statamic/cms/ui';

export default {
    components: { Button, Input },

    props: {
        exportUrl: { type: String, required: true },
    },

    data() {
        return {
            dateFrom: '',
            dateTo: '',
        };
    },

    methods: {
        // A plain GET navigation: the browser downloads the CSV without the
        // Inertia app intercepting it (a blade <form> POST gets swallowed by
        // the SPA, which is how this page silently broke in Statamic 6).
        download() {
            const params = new URLSearchParams();
            if (this.dateFrom) params.set('date_from', this.dateFrom);
            if (this.dateTo) params.set('date_to', this.dateTo);

            const query = params.toString();
            window.location.assign(query ? `${this.exportUrl}?${query}` : this.exportUrl);
        },
    },
};
</script>

<template>
    <div>
        <header class="mb-6">
            <h1>{{ __('Reports') }}</h1>
        </header>

        <div class="card p-4">
            <h2 class="mb-1 font-medium">{{ __('Order Items CSV Export') }}</h2>
            <p class="mb-4 text-sm text-gray-600">{{ __('One row per item sold on completed orders. Leave the dates empty to export everything.') }}</p>

            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('Date From') }}</label>
                    <Input type="date" v-model="dateFrom" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('Date To') }}</label>
                    <Input type="date" v-model="dateTo" />
                </div>
                <Button variant="primary" @click="download">{{ __('Export CSV') }}</Button>
            </div>
        </div>
    </div>
</template>

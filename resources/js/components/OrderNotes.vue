<script>
import { ConfirmationModal } from '@statamic/cms/ui';

export default {

    components: { ConfirmationModal },
    emits: ['deleteNote'],
    props: {
        notes: {
            type: Array,
            default: () => [],
        }
    },
    data() {
        return {
            showConfirmationModal: false,
            noteToDelete: null,
            deletingNote: false,
        }
    },
    methods: {
        confirmDelete(note) {
            this.showConfirmationModal = true
            this.noteToDelete = note
        },

        async commitDelete() {

            this.deletingNote = true

            try {
                await new Promise((resolve, reject) => {
                    this.$emit('deleteNote', {
                        note: this.noteToDelete,
                        resolve,
                        reject,
                    })
                })
            } finally {
                this.deletingNote = false
                this.showConfirmationModal = false
                this.noteToDelete = null
            }
        },

        formattedContent(content) {
            return content.split("\n").map(line => `<p>${line}</p>`).join("");
        }
    }
}


</script>

<template>

<div class="card order-notes" v-if="notes.length">
    <h2>Notes</h2>
    <div class="note-list">
        <div v-for="note in notes" :key="note.id" class="py-6">

            <div class="author text-gray-600">
                <div class="font-medium">{{ note.userName }}</div>
                <div class="text-xs">{{ new Date(note.createdAt).toLocaleString() }}</div>

                <button class="text-red-500" @click="confirmDelete(note)">Delete</button>
            </div>

            <div class="text-sm ml-3 mt-3" v-html="formattedContent(note.content)"/>

        </div>
    </div>

    <ConfirmationModal
        v-if="showConfirmationModal"
        title="Delete Note"
        :danger="true"
        :busy="deletingNote"
        @confirm="commitDelete"
        @cancel="showConfirmationModal = false"
    />



</div>
</template>

<style scoped>
.order-notes {
    padding: 1.5rem;
}
.order-notes .note-list > * + * {
    border-top: 1px solid rgb(0 0 0 / 0.1);
}
.order-notes :deep(p) {
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}
.order-notes .author {
    display: flex;
    gap: 0.5rem;
    font-size: 0.875rem;
    align-items: baseline;
}
</style>

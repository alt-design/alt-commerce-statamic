<script>
import { Badge, ConfirmationModal } from '@statamic/cms/ui';

export default {

    components: { Badge, ConfirmationModal },
    emits: ['deleteNote', 'addNote'],
    props: {
        notes: {
            type: Array,
            default: () => [],
        },
        logs: {
            type: Array,
            default: () => [],
        }
    },
    data() {
        return {
            showConfirmationModal: false,
            noteToDelete: null,
            deletingNote: false,
            newNote: '',
            addingNote: false,
        }
    },
    computed: {
        entries() {
            return [...this.notes, ...this.logs].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
        },
    },
    methods: {
        isNote(entry) {
            return this.notes.includes(entry)
        },

        async submitNote() {
            const content = this.newNote.trim()
            if (!content || this.addingNote) {
                return
            }

            this.addingNote = true

            try {
                await new Promise((resolve, reject) => {
                    this.$emit('addNote', {
                        content,
                        resolve,
                        reject,
                    })
                })
                this.newNote = ''
            } finally {
                this.addingNote = false
            }
        },

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
    }
}


</script>

<template>

<div class="card order-notes">
    <h2>Notes and logs</h2>

    <div class="add-note">
        <textarea
            aria-label="Note"
            v-model="newNote"
            rows="2"
            placeholder="Add a note about this order..."
            class="add-note-input"
            @keydown.meta.enter="submitNote"
            @keydown.ctrl.enter="submitNote"
        ></textarea>
        <button type="button" class="add-note-button" :disabled="addingNote || !newNote.trim()" @click="submitNote">
            {{ addingNote ? 'Adding...' : 'Add note' }}
        </button>
    </div>

    <ul class="note-list" v-if="entries.length">
        <li v-for="entry in entries" :key="entry.id" class="py-6">

            <div class="author text-gray-600 dark:text-gray-400">
                <Badge size="sm" :text="isNote(entry) ? 'Note' : 'Log'" />
                <div v-if="isNote(entry)" class="font-medium">{{ entry.userName }}</div>
                <time class="text-xs" :datetime="entry.createdAt">{{ new Date(entry.createdAt).toLocaleString() }}</time>

                <button
                    v-if="isNote(entry)"
                    type="button"
                    class="text-red-600 dark:text-red-400"
                    :aria-label="`Delete note by ${entry.userName}, ${new Date(entry.createdAt).toLocaleString()}`"
                    @click="confirmDelete(entry)"
                >Delete</button>
            </div>

            <div class="text-sm mt-3">
                <p v-for="(line, index) in entry.content.split('\n')" :key="index">{{ line }}</p>
            </div>

        </li>
    </ul>

    <ConfirmationModal
        :open="showConfirmationModal"
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
.add-note {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    margin: 0.75rem 0 0.5rem;
}
.add-note-input {
    flex: 1;
    border: 1px solid rgb(0 0 0 / 0.15);
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
.add-note-button {
    border: 1px solid rgb(0 0 0 / 0.15);
    border-radius: 6px;
    padding: 0.45rem 0.9rem;
    font-size: 0.875rem;
    white-space: nowrap;
}
.add-note-button:disabled {
    opacity: 0.5;
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

<script>
import { ConfirmationModal } from '@statamic/cms/ui';

export default {

    components: { ConfirmationModal },
    emits: ['deleteNote', 'addNote'],
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
            newNote: '',
            addingNote: false,
        }
    },
    methods: {
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

        formattedContent(content) {
            return content.split("\n").map(line => `<p>${line}</p>`).join("");
        }
    }
}


</script>

<template>

<div class="card order-notes">
    <h2>Notes</h2>

    <div class="add-note">
        <textarea
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

    <div class="note-list" v-if="notes.length">
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

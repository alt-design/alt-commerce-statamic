<script>

export default {

    emits: ['deleteNote'],
    props: {
        order: Object,
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

<div class="card order-notes" v-if="order.notes.length">
    <h2>Notes</h2>
    <div class="note-list">
        <div v-for="note in order.notes" :key="note.id" class="py-6">

            <div class="author text-gray-600">
                <div class="font-medium">{{ note.userName }}</div>
                <div class="text-xs">{{ new Date(note.createdAt).toLocaleString() }}</div>

                <button class="text-red-500" @click="confirmDelete(note)">Delete</button>
            </div>

            <div class="text-sm ml-3 mt-3" v-html="formattedContent(note.content)"/>

        </div>
    </div>

    <confirmation-modal
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
    @apply p-6;

    .note-list {
        @apply divide-y;
    }

    :deep p {
        @apply py-1;
    }

    .author {
        @apply flex gap-x-2 text-sm items-baseline;
    }

}
</style>
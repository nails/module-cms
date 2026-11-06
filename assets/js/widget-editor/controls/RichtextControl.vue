<template>
    <p class="cms-we__summary">{{ summary }}</p>
    <button type="button" class="btn btn-default btn-sm cms-we__edit" @click="open">
        <i class="fa fa-pencil-alt"></i> Edit
    </button>
    <Modal v-if="richOpen" :title="field.label" @close="richOpen = false">
        <RichTextModal ref="richEditor" :value="value || ''" :basic="field.editor === 'basic'" @save="save" @dirty="richDirty = $event" @close="richOpen = false"></RichTextModal>
        <template #actions>
            <span v-if="!richDirty" class="cms-we__rich-note">No changes yet.</span>
            <button type="button" class="btn btn-primary btn-sm" :disabled="!richDirty" @mousedown.prevent="richEditor.save()">Save</button>
            <button type="button" class="btn btn-default btn-sm" @mousedown.prevent="richOpen = false">Cancel</button>
        </template>
    </Modal>
</template>

<script>
import { computed, ref } from 'vue';
import Modal from '../Modal.vue';
import RichTextModal from '../RichTextModal.vue';
import { plainText } from '../fields.js';

/**
 * Rich text the canvas does not edit in place: a summary in the inspector, and the Tiptap editor in a modal
 */
export default {
    name: 'RichtextControl',
    components: { Modal, RichTextModal },
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
    setup(props, { emit }) {
        const richOpen = ref(false);
        const richDirty = ref(false);
        const richEditor = ref(null);
        const summary = computed(() => plainText(props.value) || 'Nothing yet');

        function open() {
            richDirty.value = false;
            richOpen.value = true;
        }

        function save(html) {
            richOpen.value = false;
            emit('update', html);
        }

        return { richOpen, richDirty, richEditor, summary, open, save };
    },
};
</script>

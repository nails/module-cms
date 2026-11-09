<template>
    <div class="cms-we__rich">
        <RichMenu v-if="editor" :editor="editor" :basic="basic" attached></RichMenu>
        <editor-content :editor="editor" class="cms-we__rich-content"></editor-content>
    </div>
</template>

<script>
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import RichMenu from './RichMenu.vue';
import { richExtensions } from './rich-schema.js';

/**
 * Fallback for a rich text field that is not edited on the canvas. The menubar is the same one the canvas uses.
 * `basic` is the formatting-only menu. The schema drops anything it does not keep, and that HTML is only
 * written back once the user edits.
 */
export default {
    name: 'RichTextModal',
    components: { EditorContent, RichMenu },
    props: {
        /** The field's stored HTML */
        value: { type: String, default: '' },
        basic: { type: Boolean, default: false },
    },
    emits: ['save', 'dirty', 'close'],
    setup(props, { emit }) {
        const editor = shallowRef(null);
        const dirty = ref(false);

        function save() {
            if (!dirty.value || !editor.value) {
                return;
            }
            emit('save', editor.value.getHTML());
        }

        onMounted(() => {
            editor.value = new Editor({
                content: props.value,
                extensions: richExtensions(props.basic),
                editorProps: {
                    // ProseMirror claims Escape and calls preventDefault. Safari then does not bubble it,
                    // so the editor's own Escape handler never runs and the dialog stays open.
                    handleKeyDown(_view, event) {
                        if (event.key === 'Escape') {
                            emit('close');
                            return true;
                        }
                        return false;
                    },
                },
                onUpdate: () => {
                    dirty.value = true;
                    emit('dirty', true);
                },
            });
        });

        onBeforeUnmount(() => {
            if (editor.value) {
                editor.value.destroy();
            }
        });

        return { editor, dirty, save };
    },
};
</script>

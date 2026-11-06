<template>
    <div class="cms-we-rich-surface">
        <div class="cms-we-rich-surface__body">
            <editor-content :editor="editor"></editor-content>
            <p v-if="isEmpty" class="cms-we-rich-surface__hint">Write something…</p>
        </div>
        <BubbleMenu
            v-if="editor"
            class="cms-we-bubble"
            :editor="editor"
            :update-delay="0"
            :append-to="bubbleParent"
            :options="{ placement: 'top', strategy: 'fixed', offset: 8 }"
        >
            <RichMenu :editor="editor" :basic="basic"></RichMenu>
        </BubbleMenu>
    </div>
</template>

<script>
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import { BubbleMenu } from '@tiptap/vue-3/menus';
import RichMenu from './RichMenu.vue';
import { richExtensions } from './rich-schema.js';

/**
 * Rich text edited on the canvas. The menu floats over a text selection, so opening the field does not move it.
 * `basic` uses the formatting-only menu and schema.
 */
export default {
    name: 'RichTextSurface',
    components: { BubbleMenu, EditorContent, RichMenu },
    props: {
        value: { type: String, default: '' },
        basic: { type: Boolean, default: false },
    },
    emits: ['update'],
    setup(props, { emit }) {
        const editor = shallowRef(null);
        const isEmpty = ref(true);

        function focus() {
            if (editor.value) {
                editor.value.commands.focus('end');
            }
        }

        /** Body, so the scrolling stage does not clip the menu and the editor's flex layout does not stretch it. */
        function bubbleParent() {
            return document.body;
        }

        onMounted(() => {
            editor.value = new Editor({
                content: props.value,
                extensions: richExtensions(props.basic),
                editorProps: {
                    attributes: {
                        'aria-label': 'Rich text',
                    },
                },
                onCreate: ({ editor: instance }) => {
                    isEmpty.value = instance.isEmpty;
                },
                onUpdate: ({ editor: instance }) => {
                    isEmpty.value = instance.isEmpty;
                    emit('update', instance.getHTML());
                },
                onTransaction: ({ editor: instance }) => {
                    isEmpty.value = instance.isEmpty;
                },
            });
        });

        onBeforeUnmount(() => {
            if (editor.value) {
                editor.value.destroy();
            }
        });

        return { editor, isEmpty, focus, bubbleParent };
    },
};
</script>

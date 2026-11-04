<template>
    <div class="cms-we__rich">
        <div v-if="editor" class="cms-we__rich-tools" role="toolbar" aria-label="Formatting">
            <button
                v-for="tool in tools"
                :key="tool.label"
                type="button"
                class="btn btn-default btn-sm"
                :class="{ 'is-active': isActive(tool.name, tool.attrs) }"
                :aria-pressed="isActive(tool.name, tool.attrs) ? 'true' : 'false'"
                :title="tool.label"
                @click="tool.run()"
            >
                {{ tool.label }}
            </button>
        </div>

        <editor-content :editor="editor" class="cms-we__rich-content"></editor-content>

        <footer class="cms-we__rich-foot">
            <span v-if="!dirty" class="cms-we__rich-note">No changes yet.</span>
            <button type="button" class="btn btn-primary btn-sm" :disabled="!dirty" @click="save">Save</button>
            <button type="button" class="btn btn-default btn-sm" @click="$emit('cancel')">Cancel</button>
        </footer>
    </div>
</template>

<script>
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';

/**
 * The rich text schema (D18): paragraphs, bold, italic, links, lists and headings. Anything else in the stored
 * HTML is dropped by the schema when it loads, and the cleaned HTML is only written back once the user edits.
 */
function schemaKit() {
    return StarterKit.configure({
        heading: { levels: [2, 3] },
        blockquote: false,
        code: false,
        codeBlock: false,
        horizontalRule: false,
        strike: false,
        underline: false,
        link: { openOnClick: false, autolink: false },
    });
}

export default {
    name: 'RichTextModal',
    components: { EditorContent },
    props: {
        /** The field's stored HTML */
        value: { type: String, default: '' },
    },
    emits: ['save', 'cancel'],
    setup(props, { emit }) {
        const editor = shallowRef(null);
        const dirty = ref(false);
        const version = ref(0);

        function isActive(name, attrs) {
            // Reading version makes the toolbar update with the selection
            // eslint-disable-next-line no-unused-expressions
            version.value;
            return Boolean(editor.value && editor.value.isActive(name, attrs));
        }

        function chain() {
            return editor.value.chain().focus();
        }

        function setLink() {
            const current = editor.value.getAttributes('link').href || '';
            const href = window.prompt('Link address (leave empty to remove the link)', current);
            if (href === null) {
                return;
            }
            if (href.trim() === '') {
                chain().extendMarkRange('link').unsetLink().run();
                return;
            }
            chain().extendMarkRange('link').setLink({ href: href.trim() }).run();
        }

        const tools = [
            { label: 'Bold', name: 'bold', run: () => chain().toggleBold().run() },
            { label: 'Italic', name: 'italic', run: () => chain().toggleItalic().run() },
            { label: 'Heading', name: 'heading', attrs: { level: 2 }, run: () => chain().toggleHeading({ level: 2 }).run() },
            { label: 'Subheading', name: 'heading', attrs: { level: 3 }, run: () => chain().toggleHeading({ level: 3 }).run() },
            { label: 'Paragraph', name: 'paragraph', run: () => chain().setParagraph().run() },
            { label: 'Bullet list', name: 'bulletList', run: () => chain().toggleBulletList().run() },
            { label: 'Numbered list', name: 'orderedList', run: () => chain().toggleOrderedList().run() },
            { label: 'Link', name: 'link', run: setLink },
        ];

        function save() {
            if (!dirty.value || !editor.value) {
                emit('cancel');
                return;
            }
            emit('save', editor.value.getHTML());
        }

        onMounted(() => {
            editor.value = new Editor({
                content: props.value,
                extensions: [schemaKit()],
                onUpdate: () => {
                    dirty.value = true;
                },
                onTransaction: () => {
                    version.value++;
                },
            });
        });

        onBeforeUnmount(() => {
            if (editor.value) {
                editor.value.destroy();
            }
        });

        return { editor, dirty, tools, isActive, save };
    },
};
</script>

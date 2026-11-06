<template>
    <div class="cms-we-menu" :class="{ 'is-attached': attached }" role="toolbar" aria-label="Formatting">
        <form v-if="linkOpen" class="cms-we-menu__link" @submit.prevent="applyLink">
            <input
                ref="linkInput"
                v-model="linkHref"
                type="text"
                placeholder="Link address"
                aria-label="Link address"
                @keydown.escape.stop.prevent="linkOpen = false"
            >
            <button type="button" class="is-text" @mousedown.prevent="applyLink">Apply</button>
            <button type="button" class="is-text" @mousedown.prevent="removeLink">Remove</button>
            <span class="cms-we-menu__rule"></span>
            <button type="button" title="Back" @mousedown.prevent="linkOpen = false">
                <i class="fa fa-times"></i>
            </button>
        </form>

        <template v-else>
        <div class="cms-we-menu__group">
            <button type="button" title="Undo" :disabled="!canUndo" @mousedown.prevent="run('undo')">
                <i class="fa fa-undo"></i>
            </button>
            <button type="button" title="Redo" :disabled="!canRedo" @mousedown.prevent="run('redo')">
                <i class="fa fa-repeat"></i>
            </button>
        </div>

        <template v-if="!basic">
        <span class="cms-we-menu__rule"></span>

        <div class="cms-we-menu__group">
            <div class="cms-we-menu__drop">
                <button
                    ref="headingButton"
                    type="button"
                    class="is-text"
                    title="Text style"
                    aria-haspopup="true"
                    :aria-expanded="headingOpen ? 'true' : 'false'"
                    @mousedown.prevent="headingOpen = !headingOpen"
                >
                    {{ headingLabel }}
                    <i class="fa fa-caret-down"></i>
                </button>
                <div v-if="headingOpen" class="cms-we-menu__list" role="menu">
                    <button type="button" role="menuitem" :class="{ 'is-active': isActive('paragraph') }" @mousedown.prevent="setBlock('paragraph')">Paragraph</button>
                    <button
                        v-for="level in headings"
                        :key="level"
                        type="button"
                        role="menuitem"
                        :class="{ 'is-active': isActive('heading', { level: level }) }"
                        @mousedown.prevent="setBlock('heading', level)"
                    >
                        Heading {{ level }}
                    </button>
                </div>
            </div>
            <button type="button" title="Bullet list" :class="{ 'is-active': isActive('bulletList') }" @mousedown.prevent="run('toggleBulletList')">
                <i class="fa fa-list-ul"></i>
            </button>
            <button type="button" title="Numbered list" :class="{ 'is-active': isActive('orderedList') }" @mousedown.prevent="run('toggleOrderedList')">
                <i class="fa fa-list-ol"></i>
            </button>
        </div>

        <span class="cms-we-menu__rule"></span>

        <div class="cms-we-menu__group">
            <button type="button" title="Blockquote" :aria-pressed="isActive('blockquote') ? 'true' : 'false'" :class="{ 'is-active': isActive('blockquote') }" @mousedown.prevent="run('toggleBlockquote')">
                <i class="fa fa-quote-left"></i>
            </button>
            <button type="button" title="Code block" :aria-pressed="isActive('codeBlock') ? 'true' : 'false'" :class="{ 'is-active': isActive('codeBlock') }" @mousedown.prevent="run('toggleCodeBlock')">
                <i class="fa fa-code"></i>
            </button>
        </div>
        </template>

        <span class="cms-we-menu__rule"></span>

        <div class="cms-we-menu__group">
            <button type="button" title="Bold" :aria-pressed="isActive('bold') ? 'true' : 'false'" :class="{ 'is-active': isActive('bold') }" @mousedown.prevent="run('toggleBold')">
                <i class="fa fa-bold"></i>
            </button>
            <button type="button" title="Italic" :aria-pressed="isActive('italic') ? 'true' : 'false'" :class="{ 'is-active': isActive('italic') }" @mousedown.prevent="run('toggleItalic')">
                <i class="fa fa-italic"></i>
            </button>
            <button type="button" title="Link" :aria-pressed="isActive('link') ? 'true' : 'false'" :class="{ 'is-active': isActive('link') }" @mousedown.prevent="toggleLink">
                <i class="fa fa-link"></i>
            </button>
        </div>
        </template>
    </div>
</template>

<script>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { RICH_HEADINGS } from './rich-schema.js';

/**
 * The menubar from TipTap's simple editor template, cut to this editor's schema:
 * undo, paragraph or heading (levels 1 to 6), lists, blockquote, code block, bold, italic and link.
 * On the canvas it sits in a bubble that shows when text is selected. In the modal it stays in the flow.
 * `basic` is the CKEditor wysiwyg-basic menu: undo, bold, italic and link.
 */
export default {
    name: 'RichMenu',
    props: {
        editor: { type: Object, required: true },
        /** In the modal the bar fills the width. On the canvas the bubble supplies the position. */
        attached: { type: Boolean, default: false },
        /** Formatting only: undo, bold, italic and a link. */
        basic: { type: Boolean, default: false },
    },
    setup(props) {
        const version = ref(0);
        const headingOpen = ref(false);
        const linkOpen = ref(false);
        const linkHref = ref('');
        const linkInput = ref(null);
        const headingButton = ref(null);

        function bump() {
            version.value += 1;
        }

        function isActive(name, attrs) {
            return Boolean(props.editor && version.value >= 0 && props.editor.isActive(name, attrs));
        }

        const headingLabel = computed(() => {
            if (version.value < 0 || !props.editor) {
                return 'Paragraph';
            }
            for (let i = 0; i < RICH_HEADINGS.length; i++) {
                if (props.editor.isActive('heading', { level: RICH_HEADINGS[i] })) {
                    return 'Heading ' + RICH_HEADINGS[i];
                }
            }
            return 'Paragraph';
        });

        const canUndo = computed(() => Boolean(props.editor && version.value >= 0 && props.editor.can().undo()));

        const canRedo = computed(() => Boolean(props.editor && version.value >= 0 && props.editor.can().redo()));

        function chain() {
            return props.editor.chain().focus();
        }

        function run(command) {
            headingOpen.value = false;
            chain()[command]().run();
        }

        function setBlock(kind, level) {
            headingOpen.value = false;
            if (kind === 'heading') {
                chain().setHeading({ level }).run();
                return;
            }
            chain().setParagraph().run();
        }

        function toggleLink() {
            headingOpen.value = false;
            linkHref.value = props.editor.getAttributes('link').href || '';
            linkOpen.value = !linkOpen.value;
            if (linkOpen.value) {
                nextTick(() => {
                    if (linkInput.value) {
                        linkInput.value.focus();
                    }
                });
            }
        }

        function applyLink() {
            const href = linkHref.value.trim();
            if (href === '') {
                removeLink();
                return;
            }
            chain().extendMarkRange('link').setLink({ href }).run();
            linkOpen.value = false;
        }

        function removeLink() {
            chain().extendMarkRange('link').unsetLink().run();
            linkOpen.value = false;
        }

        function onPointer(event) {
            if (!headingOpen.value) {
                return;
            }
            const button = headingButton.value;
            if (button && (event.target === button || button.contains(event.target))) {
                return;
            }
            if (button && button.parentElement && button.parentElement.contains(event.target)) {
                return;
            }
            headingOpen.value = false;
        }

        // The bubble is placed from its height. Opening the link row changes that height.
        watch(linkOpen, () => {
            nextTick(() => window.dispatchEvent(new Event('resize')));
        });

        watch(() => props.editor, (editor, previous) => {
            if (previous) {
                previous.off('transaction', bump);
            }
            if (editor) {
                editor.on('transaction', bump);
            }
        }, { immediate: true });

        onMounted(() => {
            window.addEventListener('mousedown', onPointer, true);
        });

        onBeforeUnmount(() => {
            window.removeEventListener('mousedown', onPointer, true);
            if (props.editor) {
                props.editor.off('transaction', bump);
            }
        });

        return {
            headings: RICH_HEADINGS,
            headingOpen,
            headingLabel,
            headingButton,
            linkOpen,
            linkHref,
            linkInput,
            canUndo,
            canRedo,
            isActive,
            run,
            setBlock,
            toggleLink,
            applyLink,
            removeLink,
        };
    },
};
</script>

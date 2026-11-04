<template>
    <div class="cms-we-canvas" :class="{ 'is-empty': !items.length }">
        <div class="cms-we-canvas__page">
            <iframe
                v-show="items.length"
                ref="frame"
                class="cms-we-canvas__frame"
                title="Canvas"
                tabindex="-1"
                sandbox="allow-same-origin"
                :srcdoc="shell"
                :style="{ height: height + 'px' }"
                @load="onLoad"
            ></iframe>

            <div
                v-show="items.length"
                ref="overlay"
                class="cms-we-canvas__overlay"
                :style="{ cursor, height: height + 'px' }"
                @mousemove="onPointer"
                @mouseleave="clearHover"
                @click="onClick"
            >
                <div v-if="fieldBox && !inline.active" class="cms-we-canvas__field" :style="boxStyle(fieldBox, 3)"></div>
                <div
                    v-if="hoverBox && hover !== selected"
                    class="cms-we-canvas__box is-hover"
                    :style="boxStyle(hoverBox, 8)"
                ></div>
                <div
                    v-if="selectedBox"
                    ref="selectedEl"
                    class="cms-we-canvas__box is-selected"
                    :style="boxStyle(selectedBox, 8)"
                ></div>

                <div
                    v-if="toolBox && !inline.active"
                    class="cms-we-canvas__tools"
                    :style="toolStyle(toolBox)"
                    @mousemove.stop
                    @click.stop
                >
                    <em>{{ labelFor(toolUid) }}</em>
                    <button type="button" title="Duplicate" @click="$emit('duplicate', toolUid)"><i class="fa fa-clone"></i></button>
                    <button type="button" title="Remove" @click="$emit('remove', toolUid)"><i class="fa fa-trash"></i></button>
                </div>

                <div
                    v-for="gap in gaps"
                    :key="'gap-' + gap.index"
                    class="cms-we-canvas__gap"
                    :style="{ top: gap.y + 'px' }"
                    @mouseenter="clearHover"
                    @mousemove.stop
                    @click.stop="$emit('insert-at', gap.index)"
                >
                    <button type="button" title="Insert a widget here"><i class="fa fa-plus"></i></button>
                </div>

                <div
                    v-if="inline.active"
                    ref="inlineEl"
                    class="cms-we-canvas__inline"
                    contenteditable="plaintext-only"
                    spellcheck="true"
                    :style="inline.style"
                    @mousemove.stop
                    @click.stop
                    @input="onInlineInput"
                    @keydown.stop="onInlineKeydown"
                    @paste="onInlinePaste"
                    @blur="finishInline"
                ></div>
            </div>

            <div v-if="!items.length" class="cms-we-canvas__empty">
                <p>Nothing here yet.</p>
            </div>

            <button type="button" class="cms-we-canvas__add" @click="$emit('insert-at', items.length)">
                <i class="fa fa-plus"></i> Add widget
            </button>
        </div>
    </div>
</template>

<script>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { buildCanvasShell, renderSection } from './canvas.js';

/** Changes settle for this long before previews are requested again (D5) */
const DEBOUNCE_MS = 300;

/**
 * The preview cache is keyed by instance uid. An entry is reused only while the instance's slug and data are
 * unchanged, so a change re-renders that instance and its containing top-level instance, and nothing else.
 */
function keyOf(item) {
    return JSON.stringify([item.slug, item.data]);
}

function parseItem(doc, html) {
    const holder = doc.createElement('div');
    holder.innerHTML = html;
    return holder.firstElementChild;
}

function findItem(root, uid) {
    return Array.from(root.children).find((el) => el.getAttribute('data-uid') === uid) || null;
}

function closestEl(target, selector) {
    return target && typeof target.closest === 'function' ? target.closest(selector) : null;
}

function rectOf(el) {
    const rect = el.getBoundingClientRect();
    return { top: rect.top, left: rect.left, width: rect.width, height: rect.height };
}

/** Text fields hold one line */
function oneLine(text) {
    return String(text || '').replace(/\s*\n+\s*/g, ' ');
}

/** The first background behind an element that is not transparent, so inline text is edited on its own colour */
function backgroundOf(el, win) {
    for (let node = el; node && node.nodeType === 1; node = node.parentElement) {
        const colour = win.getComputedStyle(node).backgroundColor;
        if (colour && colour !== 'transparent' && !/rgba\(\s*0,\s*0,\s*0,\s*0\s*\)/.test(colour)) {
            return colour;
        }
    }
    return '#fff';
}

/** Puts the caret where the pointer was, when the browser can say where that is */
function placeCaret(el, x, y) {
    let range = null;
    if (document.caretRangeFromPoint) {
        range = document.caretRangeFromPoint(x, y);
    } else if (document.caretPositionFromPoint) {
        const position = document.caretPositionFromPoint(x, y);
        if (position) {
            range = document.createRange();
            range.setStart(position.offsetNode, position.offset);
        }
    }
    if (!range || !el.contains(range.startContainer)) {
        range = document.createRange();
        range.selectNodeContents(el);
        range.collapse(false);
    }
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
}

const COPIED_STYLES = [
    'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'lineHeight', 'letterSpacing', 'wordSpacing',
    'textTransform', 'textAlign', 'color', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
];

export default {
    name: 'Canvas',
    props: {
        /** Top-level instances of the active area: [{uid, slug, data, locked}] */
        items: { type: Array, required: true },
        /** Raw catalogue canvas_assets */
        stylesheets: { type: Array, default: () => [] },
        /** POST endpoint for previews */
        previewUrl: { type: String, required: true },
        /** Area slug, passed to the preview context (D21) */
        area: { type: String, default: '' },
        /** The top-level instance holding the selection */
        selected: { type: String, default: null },
        /** (uid) => the widget's label */
        labelFor: { type: Function, required: true },
    },
    emits: ['edit', 'rich', 'insert-at', 'select', 'duplicate', 'remove'],
    setup(props, { emit }) {
        const frame = ref(null);
        const overlay = ref(null);
        const selectedEl = ref(null);
        const inlineEl = ref(null);
        const cache = new Map();
        /** The HTML last written into the frame for each instance, so unchanged instances are not touched */
        const rendered = new Map();
        /** Top-level uids edited inline since the last change to items. Their previews are not requested. */
        const inline = new Set();
        const shell = computed(() => buildCanvasShell({ stylesheets: props.stylesheets }));

        const height = ref(0);
        const rects = ref({});
        const gaps = ref([]);
        const hover = ref(null);
        const fieldBox = ref(null);
        const cursor = ref('default');
        const editing = reactive({ active: false, uid: null, path: '', field: '', style: {} });
        /** The frame element being edited inline. It is hidden while the editor's copy is over it. */
        let editTarget = null;
        /** Set when the canvas made the selection, so the page is not scrolled to what was just clicked */
        let selectedHere = false;

        let timer = null;
        let sequence = 0;
        let mounted = false;
        let loaded = false;
        let observer = null;
        let frameRequest = null;

        const hoverBox = computed(() => (hover.value ? rects.value[hover.value] || null : null));
        const selectedBox = computed(() => (props.selected ? rects.value[props.selected] || null : null));
        const toolUid = computed(() => (hoverBox.value ? hover.value : (selectedBox.value ? props.selected : null)));
        const toolBox = computed(() => (toolUid.value ? rects.value[toolUid.value] || null : null));

        function doc() {
            return frame.value && frame.value.contentDocument;
        }

        function sectionFor(item) {
            if (item.locked) {
                return { uid: item.uid, html: '', error: null, locked: true, pending: false };
            }
            const entry = cache.get(item.uid);
            if (!entry) {
                return { uid: item.uid, html: '', error: null, locked: false, pending: true };
            }
            return { uid: item.uid, html: entry.html, error: entry.error, locked: false, pending: false };
        }

        /**
         * Makes the frame match the items: removes instances which have gone, adds new ones, reorders, and
         * replaces the HTML of any instance whose preview has changed. The instance being edited inline is left
         * alone; it is patched once editing ends.
         */
        function sync() {
            const document = doc();
            if (!loaded || !document || !document.body) {
                return;
            }

            const root = document.body;
            Array.from(root.querySelectorAll('.cms-we-gap')).forEach((el) => el.remove());

            const wanted = new Set(props.items.map((item) => item.uid));
            Array.from(root.children).forEach((el) => {
                const uid = el.getAttribute('data-uid');
                if (uid !== null && !wanted.has(uid)) {
                    el.remove();
                    rendered.delete(uid);
                }
            });

            let previous = null;
            props.items.forEach((item) => {
                const html = renderSection(sectionFor(item));
                let el = findItem(root, item.uid);

                if (!el) {
                    el = parseItem(document, html);
                    rendered.set(item.uid, html);
                    root.insertBefore(el, previous ? previous.nextElementSibling : root.firstElementChild);
                } else if (rendered.get(item.uid) !== html && !(editTarget && el.contains(editTarget))) {
                    const next = parseItem(document, html);
                    el.replaceWith(next);
                    rendered.set(item.uid, html);
                    el = next;
                }

                const expected = previous ? previous.nextElementSibling : root.firstElementChild;
                if (el !== expected) {
                    root.insertBefore(el, expected);
                }
                previous = el;
            });

            props.items.forEach((item, index) => {
                const el = findItem(root, item.uid);
                if (el) {
                    const gap = document.createElement('div');
                    gap.className = 'cms-we-gap';
                    gap.setAttribute('data-index', String(index));
                    root.insertBefore(gap, el);
                }
            });

            measure();
        }

        /** Sizes the frame to its content and records where each instance and gap is, for the overlay */
        function measure() {
            const document = doc();
            if (!loaded || !document || !document.body) {
                return;
            }
            const body = document.body;
            const next = Math.ceil(body.getBoundingClientRect().height);
            if (next !== height.value) {
                height.value = next;
            }

            const found = {};
            Array.from(body.children).forEach((el) => {
                const uid = el.getAttribute('data-uid');
                if (uid !== null) {
                    found[uid] = rectOf(el);
                }
            });
            rects.value = found;

            gaps.value = Array.from(body.querySelectorAll('.cms-we-gap')).map((el) => {
                const rect = el.getBoundingClientRect();
                return { index: Number(el.getAttribute('data-index')), y: rect.top + rect.height / 2 };
            });
        }

        async function refreshPreviews() {
            const stale = props.items.filter((item) => {
                if (item.locked) {
                    return false;
                }
                const entry = cache.get(item.uid);
                return !entry || entry.key !== keyOf(item);
            });

            const live = new Set(props.items.map((item) => item.uid));
            Array.from(cache.keys()).forEach((uid) => {
                if (!live.has(uid)) {
                    cache.delete(uid);
                }
            });

            if (stale.length === 0) {
                sync();
                return;
            }

            const current = ++sequence;
            const keys = stale.map(keyOf);

            try {
                const response = await fetch(props.previewUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({
                        context: { area: props.area },
                        items: stale.map((item) => ({ slug: item.slug, data: item.data })),
                    }),
                });
                const body = await response.json();

                if (current !== sequence) {
                    // A newer change superseded this request
                    return;
                }
                if (!response.ok || !Array.isArray(body.data)) {
                    throw new Error((body && (body.error || body.message)) || 'Previews could not be rendered');
                }

                stale.forEach((item, index) => {
                    const result = body.data[index] || {};
                    cache.set(item.uid, {
                        key: keys[index],
                        html: typeof result.html === 'string' ? result.html : '',
                        error: result.error || null,
                    });
                });
            } catch (e) {
                if (current !== sequence) {
                    return;
                }
                // No key, so the next change retries these instances
                stale.forEach((item) => {
                    cache.set(item.uid, { key: null, html: '', error: e.message });
                });
            }

            sync();
        }

        function schedule() {
            clearTimeout(timer);
            timer = setTimeout(refreshPreviews, DEBOUNCE_MS);
        }

        /** Inline edits are already on screen, so their instance's cached preview is kept as it is */
        function acceptInlineEdits() {
            inline.forEach((uid) => {
                const item = props.items.find((candidate) => candidate.uid === uid);
                const entry = cache.get(uid);
                if (item && entry && entry.key !== null) {
                    cache.set(uid, { ...entry, key: keyOf(item) });
                }
            });
            inline.clear();
        }

        // --------------------------------------------------------------------------
        // The overlay. The frame cannot run script, and the browser does not deliver its events to listeners
        // either, so the pointer is read here and looked up in the frame.

        function hitTest(event) {
            const document = doc();
            if (!document || !overlay.value) {
                return { item: null, field: null };
            }
            const box = overlay.value.getBoundingClientRect();
            const el = document.elementFromPoint(event.clientX - box.left, event.clientY - box.top);
            return {
                item: closestEl(el, '.cms-canvas-item'),
                field: closestEl(el, '[data-cms-edit]'),
            };
        }

        function onPointer(event) {
            if (frameRequest) {
                return;
            }
            const clientX = event.clientX;
            const clientY = event.clientY;
            frameRequest = requestAnimationFrame(() => {
                frameRequest = null;
                const { item, field } = hitTest({ clientX, clientY });
                const uid = item ? item.getAttribute('data-uid') : null;
                const locked = uid ? Boolean((props.items.find((entry) => entry.uid === uid) || {}).locked) : false;
                hover.value = uid;
                fieldBox.value = field && !locked ? rectOf(field) : null;
                if (field && !locked) {
                    cursor.value = field.getAttribute('data-cms-edit') === 'text' ? 'text' : 'pointer';
                } else {
                    cursor.value = item ? 'pointer' : 'default';
                }
            });
        }

        function clearHover() {
            hover.value = null;
            fieldBox.value = null;
            cursor.value = 'default';
        }

        function onClick(event) {
            finishInline();
            const { item, field } = hitTest(event);
            if (!item) {
                emit('select', null);
                return;
            }

            const uid = item.getAttribute('data-uid');
            const locked = Boolean((props.items.find((entry) => entry.uid === uid) || {}).locked);
            const path = field ? field.getAttribute('data-cms-path') || '' : '';

            selectedHere = true;
            emit('select', { uid, path: locked ? '' : path });

            if (!field || locked) {
                return;
            }
            const name = field.getAttribute('data-cms-field');
            if (field.getAttribute('data-cms-edit') === 'rich') {
                emit('rich', { uid, path, field: name, value: field.innerHTML });
            } else if (field.getAttribute('data-cms-edit') === 'text') {
                startInline(field, uid, path, name, event);
            }
        }

        // --------------------------------------------------------------------------
        // Inline text: an editable copy of the field, styled like it, sits over the field while it is edited

        async function startInline(field, uid, path, name, event) {
            const win = doc().defaultView;
            const style = win.getComputedStyle(field);
            const rect = field.getBoundingClientRect();
            const copied = {};
            COPIED_STYLES.forEach((property) => {
                copied[property] = style[property];
            });

            editTarget = field;
            editing.uid = uid;
            editing.path = path;
            editing.field = name;
            editing.style = {
                ...copied,
                top: rect.top + 'px',
                left: rect.left + 'px',
                width: rect.width + 'px',
                minHeight: rect.height + 'px',
                background: backgroundOf(field, win),
            };
            editing.active = true;

            await nextTick();
            if (!inlineEl.value) {
                return;
            }
            inlineEl.value.textContent = field.textContent;
            field.style.visibility = 'hidden';
            fieldBox.value = null;
            inlineEl.value.focus();
            placeCaret(inlineEl.value, event.clientX, event.clientY);
        }

        function onInlineInput() {
            if (!editTarget || !inlineEl.value) {
                return;
            }
            const value = oneLine(inlineEl.value.textContent);
            editTarget.textContent = value;
            inline.add(editing.uid);
            emit('edit', { uid: editing.uid, path: editing.path, field: editing.field, value });
        }

        function onInlineKeydown(event) {
            if (event.key === 'Enter' || event.key === 'Escape') {
                event.preventDefault();
                finishInline();
            }
        }

        function onInlinePaste(event) {
            event.preventDefault();
            const text = oneLine(event.clipboardData ? event.clipboardData.getData('text/plain') : '');
            document.execCommand('insertText', false, text);
        }

        function finishInline() {
            if (!editing.active) {
                return;
            }
            if (editTarget) {
                editTarget.style.visibility = '';
            }
            editTarget = null;
            editing.active = false;
            editing.uid = null;
            sync();
        }

        // --------------------------------------------------------------------------

        function boxStyle(box, inset) {
            return {
                top: (box.top - inset / 2) + 'px',
                left: (box.left - inset) + 'px',
                width: (box.width + inset * 2) + 'px',
                height: (box.height + inset) + 'px',
            };
        }

        function toolStyle(box) {
            return { top: (box.top - 4) + 'px', left: (box.left + box.width + 8) + 'px' };
        }

        function onLoad() {
            const document = doc();
            if (!document) {
                return;
            }
            loaded = true;
            rendered.clear();
            if (observer) {
                observer.disconnect();
            }
            if (window.ResizeObserver && document.body) {
                observer = new ResizeObserver(() => measure());
                observer.observe(document.body);
            }
            sync();
        }

        watch(() => props.items, () => {
            if (!mounted) {
                return;
            }
            acceptInlineEdits();
            sync();
            schedule();
        }, { deep: true });

        watch(() => props.area, () => {
            if (mounted) {
                clearTimeout(timer);
                refreshPreviews();
            }
        });

        watch(() => props.selected, async () => {
            if (selectedHere) {
                selectedHere = false;
                return;
            }
            await nextTick();
            if (selectedEl.value && selectedEl.value.scrollIntoView) {
                selectedEl.value.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });

        onMounted(() => {
            mounted = true;
            window.addEventListener('resize', measure);
            refreshPreviews();
        });

        onBeforeUnmount(() => {
            mounted = false;
            clearTimeout(timer);
            window.removeEventListener('resize', measure);
            if (observer) {
                observer.disconnect();
            }
            if (frameRequest) {
                cancelAnimationFrame(frameRequest);
            }
        });

        return {
            frame,
            overlay,
            selectedEl,
            inlineEl,
            shell,
            height,
            gaps,
            hover,
            fieldBox,
            cursor,
            inline: editing,
            hoverBox,
            selectedBox,
            toolUid,
            toolBox,
            onLoad,
            onPointer,
            clearHover,
            onClick,
            onInlineInput,
            onInlineKeydown,
            onInlinePaste,
            finishInline,
            boxStyle,
            toolStyle,
        };
    },
};
</script>

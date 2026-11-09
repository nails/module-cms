<template>
    <div class="cms-we-canvas">
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
                <div
                    v-if="showHover"
                    class="cms-we-canvas__box is-hover"
                    :style="lineStyle(hoverBox)"
                ></div>
                <div
                    v-if="selectedBox"
                    ref="selectedEl"
                    class="cms-we-canvas__box is-selected"
                    :style="lineStyle(selectedBox)"
                ></div>

                <div
                    v-for="uid in refreshing"
                    :key="'generating-' + uid"
                    class="cms-we-canvas__generating"
                    role="status"
                    :style="boxStyle(uid)"
                >Generating preview</div>

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
                    v-for="wrap in wraps"
                    :key="'wrap-' + wrap.key"
                    class="cms-we-canvas__wrap"
                    :class="{ 'is-active': wrap.current }"
                    :style="rectStyle(wrap.rect)"
                ></div>
                <button
                    v-for="wrap in wraps"
                    v-show="wrap.shown"
                    :key="'wrap-tag-' + wrap.key"
                    type="button"
                    class="cms-we-canvas__wrap-tag"
                    :class="{ 'is-active': wrap.current }"
                    :style="{ top: wrap.rect.top + 'px', left: wrap.tagLeft + 'px' }"
                    :title="'Select this ' + wrap.label.toLowerCase()"
                    @mousemove.stop
                    @click.stop="$emit('select', { uid: wrap.top, path: wrap.path })"
                >
                    <i class="fa" :class="wrap.icon"></i>{{ wrap.label }}
                </button>
                <div
                    v-for="wrap in wraps"
                    v-show="wrap.near"
                    :key="'wrap-end-' + wrap.key"
                    class="cms-we-canvas__wrap-tag is-end"
                    :class="{ 'is-active': wrap.current }"
                    :style="{ top: (wrap.rect.top + wrap.rect.height) + 'px', left: (wrap.rect.left + 8) + 'px' }"
                >End of {{ wrap.label.toLowerCase() }}</div>

                <div
                    v-for="slot in emptySlots"
                    :key="'slot-add-' + slot.key"
                    class="cms-we-canvas__slot-add"
                    :style="rectStyle(slot.rect)"
                    @mousemove.stop
                    @click.stop="insertInto(slot, 0)"
                >
                    <button type="button"><i class="fa fa-plus"></i>{{ slot.label }}</button>
                </div>

                <div
                    v-for="point in slotGaps"
                    :key="'slot-gap-' + point.key"
                    class="cms-we-canvas__slot-gap"
                    :class="[point.upright ? 'is-upright' : 'is-across', { 'is-edge': point.edge }]"
                    :style="point.upright
                        ? { left: point.x + 'px', top: point.y + 'px', height: point.length + 'px' }
                        : { left: point.x + 'px', top: point.y + 'px', width: point.length + 'px' }"
                >
                    <button
                        type="button"
                        :title="point.title"
                        @mousemove.stop
                        @click.stop="insertInto(point, point.index)"
                    ><i class="fa fa-plus"></i></button>
                </div>

                <RichTextSurface
                    v-if="rich.active"
                    ref="richEl"
                    class="cms-we-canvas__rich"
                    :style="rich.style"
                    :value="rich.value"
                    :basic="rich.basic"
                    @update="onRichUpdate"
                    @mousedown.stop
                    @click.stop
                ></RichTextSurface>

                <div
                    v-if="inline.active"
                    ref="inlineEl"
                    class="cms-we-canvas__inline"
                    contenteditable="true"
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
                <i class="fa fa-puzzle-piece"></i>
                <p>Add a widget to start building this page.</p>
                <button type="button" @click="$emit('insert-at', 0)">Add a widget</button>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import {
    buildCanvasShell,
    canvasStylesheets,
    fontFaceRules,
    inlineFieldKeys,
    ownerPath,
    parseMarker,
    refreshingUids,
    renderSection,
    slotInsertPoints,
    storedAt,
} from './canvas.js';
import { isBlankHtml } from './rich-schema.js';
import RichTextSurface from './RichTextSurface.vue';

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

/** The box of what lies between two markers, or null when nothing there is drawn */
function rangeRect(document, start, end) {
    const range = document.createRange();
    range.setStartAfter(start);
    range.setEndBefore(end);
    const rect = range.getBoundingClientRect();
    return rect.width < 1 && rect.height < 1
        ? null
        : { top: rect.top, left: rect.left, width: rect.width, height: rect.height };
}

/** The box of an instance's content, inside the item's padding */
function contentRect(document, item) {
    const range = document.createRange();
    range.selectNodeContents(item);
    const rect = range.getBoundingClientRect();
    return { top: rect.top, left: rect.left, width: rect.width, height: rect.height };
}

/**
 * Reads the slot and child markers in one top-level instance.
 * A nested widget's box is everything between its markers. A slot records its children's boxes, in order,
 * and the placeholder it shows when it is empty.
 *
 * @return {{nodes: Object<string, Object>, slots: Array<Object>}} Nodes keyed by path
 */
function readMarkers(document, item, top) {
    const nodes = {};
    const slots = [];
    const opened = new Map();
    const bySlot = new Map();
    const walker = document.createTreeWalker(item, NodeFilter.SHOW_COMMENT);

    for (let comment = walker.nextNode(); comment; comment = walker.nextNode()) {
        const marker = parseMarker(comment.nodeValue);
        if (!marker) {
            continue;
        }
        const key = marker.kind + '\n' + marker.path;
        if (!marker.close) {
            opened.set(key, comment);
            if (marker.kind === 'slot') {
                const slot = {
                    key: top + '\n' + marker.path,
                    top,
                    path: marker.path,
                    owner: ownerPath(marker.path),
                    slot: marker.path.split('/').pop(),
                    rect: null,
                    empty: null,
                    children: [],
                };
                bySlot.set(marker.path, slot);
                slots.push(slot);
            }
            continue;
        }

        const start = opened.get(key);
        opened.delete(key);
        if (!start) {
            continue;
        }
        const rect = rangeRect(document, start, comment);
        if (marker.kind === 'node') {
            const segments = marker.path.split('/');
            const slot = bySlot.get(segments.slice(0, -1).join('/'));
            if (rect) {
                nodes[marker.path] = rect;
                if (slot) {
                    slot.children.push({ index: Number(segments[segments.length - 1]), rect });
                }
            }
            continue;
        }
        const slot = bySlot.get(marker.path);
        const holder = start.nextSibling;
        if (holder && holder.nodeType === 1 && holder.hasAttribute('data-cms-slot-empty')) {
            slot.empty = rectOf(holder);
        }
        slot.rect = rect;
    }

    return { nodes, slots };
}

/** How much room a wrapper's tag takes, so tags that start in one place sit side by side */
function tagWidth(label) {
    return 34 + label.length * 6.5;
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

/** Blocks whose type is copied from the preview, so the editor does not use the admin page's headings. */
const SAMPLE_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'a', 'blockquote', 'pre', 'code'];

const SAMPLE_PROPS = [
    'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'lineHeight', 'letterSpacing',
    'textTransform', 'color', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
];

const TYPE_STYLE_ID = 'cms-we-canvas-type';

function cssProp(name) {
    return name.replace(/[A-Z]/g, (letter) => '-' + letter.toLowerCase());
}

/**
 * A margin reset that beats the sampled tag rule.
 * `.ProseMirror p` sets margin with !important, so a bare `:first-child` reset never applies.
 *
 * @param {string} pseudo `first-child` or `last-child`
 * @param {string} property `margin-top` or `margin-bottom`
 * @return {string}
 */
function marginEdge(pseudo, property) {
    const selectors = SAMPLE_TAGS
        .map((tag) => '.cms-we-canvas__rich .ProseMirror > ' + tag + ':' + pseudo)
        .join(',');

    return selectors + '{' + property + ':0 !important}';
}

/** True when a child's margin collapses through the field, so the text sits on the field's edge. */
function marginCollapsed(field, el, edge) {
    const fieldRect = field.getBoundingClientRect();
    const rect = el.getBoundingClientRect();
    const delta = edge === 'top' ? rect.top - fieldRect.top : fieldRect.bottom - rect.bottom;
    return Math.abs(delta) < 1;
}

/**
 * The preview's computed type for each block, as rules for the editor.
 * Probes sit between two spacers, so a first-child or last-child margin rule is recorded on its own.
 *
 * @param {HTMLElement} field
 * @param {Window} win
 * @return {string}
 */
function previewTypeCss(field, win) {
    const owner = field.ownerDocument;
    const blocks = Array.from(field.children).filter((el) => !el.classList.contains('cms-we-hint'));
    const first = blocks[0] || null;
    const last = blocks[blocks.length - 1] || null;
    // A margin that collapses out of the field, or one the preview's first or last child does not
    // have, is not part of the text's box. The editor is a flow root, so it would pull that margin
    // inside and the text would jump. Drop it there.
    let zeroTop = Boolean(first && (win.getComputedStyle(first).marginTop === '0px' || marginCollapsed(field, first, 'top')));
    let zeroBottom = Boolean(last && (win.getComputedStyle(last).marginBottom === '0px' || marginCollapsed(field, last, 'bottom')));
    // Probes sit between these, so a first-child or last-child margin rule is not what gets copied.
    const lead = owner.createElement('div');
    const tail = owner.createElement('div');
    field.appendChild(lead);
    field.appendChild(tail);
    const rules = [];

    try {
        const fieldStyle = win.getComputedStyle(field);
        const smoothing = [];
        if (fieldStyle.webkitFontSmoothing) {
            smoothing.push('-webkit-font-smoothing:' + fieldStyle.webkitFontSmoothing + ' !important');
        }
        if (fieldStyle.MozOsxFontSmoothing) {
            smoothing.push('-moz-osx-font-smoothing:' + fieldStyle.MozOsxFontSmoothing + ' !important');
        }
        if (smoothing.length) {
            rules.push('.cms-we-canvas__rich .ProseMirror{' + smoothing.join(';') + '}');
        }

        SAMPLE_TAGS.forEach((tag) => {
            let host;
            let node;
            if (tag === 'li') {
                host = owner.createElement('ul');
                node = owner.createElement('li');
                host.appendChild(node);
            } else if (tag === 'a') {
                host = owner.createElement('p');
                node = owner.createElement('a');
                node.setAttribute('href', '#');
                host.appendChild(node);
            } else if (tag === 'code') {
                host = owner.createElement('pre');
                node = owner.createElement('code');
                host.appendChild(node);
            } else {
                node = owner.createElement(tag);
                host = node;
            }
            node.textContent = 'a';
            field.insertBefore(host, tail);
            try {
                const style = win.getComputedStyle(node);
                const decls = SAMPLE_PROPS
                    .filter((prop) => style[prop])
                    .map((prop) => cssProp(prop) + ':' + style[prop] + ' !important');
                if (tag === 'ul' || tag === 'ol') {
                    decls.push('list-style-type:' + style.listStyleType + ' !important');
                    decls.push('list-style-position:' + style.listStylePosition + ' !important');
                    decls.push('padding-left:' + style.paddingLeft + ' !important');
                }
                if (tag === 'a') {
                    decls.push('text-decoration:' + (style.textDecorationLine || style.textDecoration) + ' !important');
                }
                if (tag === 'blockquote') {
                    decls.push('padding-left:' + style.paddingLeft + ' !important');
                    decls.push('border-left:' + style.borderLeftWidth + ' ' + style.borderLeftStyle + ' ' + style.borderLeftColor + ' !important');
                }
                if (tag === 'pre' || tag === 'code') {
                    decls.push('padding:' + style.padding + ' !important');
                    decls.push('background-color:' + style.backgroundColor + ' !important');
                    decls.push('white-space:' + style.whiteSpace + ' !important');
                }
                rules.push('.cms-we-canvas__rich .ProseMirror ' + tag + '{' + decls.join(';') + '}');
            } finally {
                host.remove();
            }
        });
    } finally {
        lead.remove();
        tail.remove();
    }

    // An empty field shows a hint, and the editor's empty document is one paragraph. Probe that
    // paragraph on its own, which is where the caret will be.
    if (!first) {
        const hint = field.querySelector(':scope > .cms-we-hint');
        if (hint) {
            hint.remove();
        }
        const probe = owner.createElement('p');
        probe.textContent = 'a';
        field.appendChild(probe);
        try {
            const style = win.getComputedStyle(probe);
            zeroTop = style.marginTop === '0px' || marginCollapsed(field, probe, 'top');
            zeroBottom = style.marginBottom === '0px' || marginCollapsed(field, probe, 'bottom');
        } finally {
            probe.remove();
            if (hint) {
                field.appendChild(hint);
            }
        }
    }

    // The tag rule above sets margin with !important. A plain :first-child rule loses to it, and the
    // first line then sits below the placeholder. Name the tag so this one wins.
    if (zeroTop) {
        rules.push(marginEdge('first-child', 'margin-top'));
    }
    if (zeroBottom) {
        rules.push(marginEdge('last-child', 'margin-bottom'));
    }

    // all:revert sets -webkit-user-modify back to read-only, which drops the caret and ignores typing.
    rules.push('.cms-we-canvas__rich .ProseMirror,.cms-we-canvas__rich .ProseMirror *{-webkit-user-modify:read-write !important;caret-color:auto}');

    return rules.join('\n');
}

function setTypeStyle(css) {
    let style = document.getElementById(TYPE_STYLE_ID);
    if (!css) {
        if (style) {
            style.remove();
        }
        return;
    }
    if (!style) {
        style = document.createElement('style');
        style.id = TYPE_STYLE_ID;
    }
    // After the admin stylesheets, which are linked at the end of the body.
    (document.body || document.head).appendChild(style);
    style.textContent = css;
}

export default {
    name: 'Canvas',
    components: { RichTextSurface },
    props: {
        /** Top-level instances of the active area: [{uid, slug, data, locked}] */
        items: { type: Array, required: true },
        /** Raw catalogue canvas_assets */
        stylesheets: { type: Array, default: () => [] },
        /** POST endpoint for previews */
        previewUrl: { type: String, required: true },
        /** Area slug, passed to the preview context (D21) */
        area: { type: String, default: '' },
        /**
         * The selected widget's place on the canvas: {top, path}.
         * `path` is empty for a top-level widget.
         */
        selected: { type: Object, default: null },
        /** The hovered widget's place, from the canvas or the outline: {top, path} */
        hover: { type: Object, default: null },
        /** Bumped when the outline selects a widget, so the canvas scrolls it into view */
        scrollTo: { type: Number, default: 0 },
        /** Catalogue widgets by slug, for the labels and slots of wrapper widgets */
        widgets: { type: Object, default: () => ({}) },
    },
    emits: ['edit', 'rich', 'insert-at', 'insert-into', 'select', 'hover', 'inline-fields'],
    setup(props, { emit }) {
        const frame = ref(null);
        const overlay = ref(null);
        const selectedEl = ref(null);
        const inlineEl = ref(null);
        const richEl = ref(null);
        const cache = new Map();
        /** The HTML last written into the frame for each instance, so unchanged instances are not touched */
        const rendered = new Map();
        /** Top-level uids edited inline since the last change to items. Their previews are not requested. */
        const inline = new Set();
        /** Which tab is showing, per instance. The preview always starts on the first; a click is not stored. */
        const selectedTabs = new Map();
        const shell = computed(() => buildCanvasShell({ stylesheets: props.stylesheets }));

        const height = ref(0);
        const rects = ref({});
        /** Uids whose cached preview is out of date and a new one is on its way */
        const refreshing = ref([]);
        /** Bounding boxes of `[data-cms-path]` regions, keyed `topUid\\npath` */
        const pathRects = ref({});
        /** Nested widgets' boxes from the slot markers, keyed `topUid\\npath` */
        const nodeRects = ref({});
        /** Each instance's content box, inside the item's padding */
        const contentRects = ref({});
        /** Every slot on the canvas, with its children's boxes */
        const slots = ref([]);
        const gaps = ref([]);
        const cursor = ref('default');
        const editing = reactive({ active: false, uid: null, path: '', field: '', style: {} });
        const rich = reactive({ active: false, uid: null, path: '', field: '', value: '', basic: false, style: {} });
        /** The HTML last written from the rich text session, so closing restores what was edited */
        let richLast = '';
        /** The frame element being edited inline. It is hidden while the editor's copy is over it. */
        let editTarget = null;
        /** The field a click just finished editing, so that same click does not open it again. */
        let endedKey = '';
        let endedAt = 0;
        /** The last hover reported, so the pointer does not emit the same place on every move */
        let hoverKey = '';
        /** Field keys last reported for the selection, so the inspector is not updated with the same list */
        let inlineSent = null;
        /** One fetch of the canvas font faces, shared by every edit */
        let fontsPromise = null;

        let timer = null;
        let sequence = 0;
        let mounted = false;
        let loaded = false;
        let observer = null;
        let frameRequest = null;

        const hoverBox = computed(() => boxFor(props.hover));
        const selectedBox = computed(() => boxFor(props.selected));
        const showHover = computed(() => Boolean(hoverBox.value) && !samePlace(props.hover, props.selected));

        /** The hovered and selected widgets, keyed `topUid\\npath` */
        const current = computed(() => new Set(
            [props.hover, props.selected]
                .filter((place) => place && place.top)
                .map((place) => place.top + '\n' + (place.path || ''))
        ));

        /** Those widgets and the wrappers that directly hold them. Their slots show where to insert. */
        const near = computed(() => {
            const keys = new Set(current.value);
            [props.hover, props.selected].forEach((place) => {
                const up = place && place.top ? ownerPath(place.path || '') : null;
                if (up !== null) {
                    keys.add(place.top + '\n' + up);
                }
            });
            return keys;
        });

        function describe(top, path) {
            const item = props.items.find((entry) => entry.uid === top) || null;
            const stored = path === '' ? item : storedAt(item, path);
            const widget = stored ? props.widgets[stored.slug] || null : null;
            return {
                label: widget ? widget.label : (stored ? stored.slug : 'Wrapper'),
                icon: widget && /^fa-[a-z0-9-]+$/.test(widget.icon || '') ? widget.icon : 'fa-layer-group',
                slots: widget && widget.slots ? widget.slots : {},
            };
        }

        /**
         * A frame for each widget that has slots, so it reads as a wrapper. A top-level wrapper always shows its tag.
         * A nested one shows its tags while it, or a widget directly inside it, is hovered or selected, so the tags
         * do not sit over the content of every level at once.
         */
        const wraps = computed(() => {
            const owners = new Map();
            slots.value.forEach((slot) => {
                const key = slot.top + '\n' + slot.owner;
                if (!owners.has(key)) {
                    owners.set(key, { key, top: slot.top, path: slot.owner });
                }
            });

            const placed = [];
            return Array.from(owners.values()).map((wrap) => {
                const rect = wrap.path === ''
                    ? contentRects.value[wrap.top]
                    : nodeRects.value[wrap.key];
                if (!rect) {
                    return null;
                }
                const { label, icon } = describe(wrap.top, wrap.path);
                const shown = wrap.path === '' || near.value.has(wrap.key);
                const width = tagWidth(label);
                let left = rect.left + 8;
                let moved = shown;
                while (moved) {
                    moved = false;
                    placed.forEach((tag) => {
                        if (Math.abs(tag.top - rect.top) < 18 && left < tag.left + tag.width && left + width > tag.left) {
                            left = tag.left + tag.width + 4;
                            moved = true;
                        }
                    });
                }
                if (shown) {
                    placed.push({ top: rect.top, left, width });
                }
                return {
                    ...wrap,
                    rect,
                    label,
                    icon,
                    shown,
                    tagLeft: left,
                    current: current.value.has(wrap.key),
                    near: near.value.has(wrap.key),
                };
            }).filter(Boolean);
        });

        /** An add button over each empty slot. A wrapper with more than one slot names the slot. */
        const emptySlots = computed(() => slots.value
            .filter((slot) => slot.empty)
            .map((slot) => {
                const defined = describe(slot.top, slot.owner).slots;
                const names = Object.keys(defined);
                const name = defined[slot.slot] && defined[slot.slot].label ? defined[slot.slot].label : slot.slot;
                return {
                    ...slot,
                    rect: slot.empty,
                    label: names.length > 1 ? 'Add to ' + name : 'Add a widget',
                };
            }));

        /**
         * Insert points in the slots of the hovered or selected wrapper, and of the wrapper holding that widget.
         * A wrapper and the one inside it often share an edge. The inner slot keeps the middle of the line and the
         * outer one moves its "+" to the end, clear of the wrapper tags, so both can be reached.
         */
        const slotGaps = computed(() => {
            const points = [];
            const depth = (slot) => slot.path.split('/').length;
            slots.value
                .filter((slot) => slot.rect && slot.children.length && near.value.has(slot.top + '\n' + slot.owner))
                .sort((a, b) => depth(b) - depth(a))
                .forEach((slot) => {
                    const label = describe(slot.top, slot.owner).label;
                    const children = slot.children.slice().sort((a, b) => a.index - b.index);
                    slotInsertPoints(slot.rect, children).forEach((point) => {
                        const edge = points.some((other) => other.upright === point.upright
                            && Math.abs(other.x - point.x) < 6
                            && Math.abs(other.y - point.y) < 6);
                        points.push({
                            ...point,
                            edge,
                            title: 'Insert into the ' + label.toLowerCase() + ' here',
                            key: slot.key + '\n' + point.index,
                            top: slot.top,
                            owner: slot.owner,
                            slot: slot.slot,
                        });
                    });
                });
            return points;
        });

        function insertInto(target, index) {
            emit('insert-into', { uid: target.top, path: target.owner, slot: target.slot, index });
        }

        function doc() {
            return frame.value && frame.value.contentDocument;
        }

        function sectionFor(item) {
            if (item.locked) {
                return { uid: item.uid, html: '', error: null, locked: true, pending: false, slug: item.slug };
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
                    selectedTabs.delete(uid);
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
                // A fresh preview starts on the first tab. Put back the one the user was looking at.
                if (!(editTarget && el.contains(editTarget))) {
                    restoreTab(el, item.uid);
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

            // Below the last widget, so a widget can be added at the end of the page
            if (props.items.length) {
                const gap = document.createElement('div');
                gap.className = 'cms-we-gap';
                gap.setAttribute('data-index', String(props.items.length));
                root.appendChild(gap);
            }

            measure();
            publishInline();
        }

        /** Fields the preview marks as editable, for the selected instance only. */
        function publishInline() {
            const place = props.selected;
            const keys = place ? inlineFieldKeys(rendered.get(place.top) || '', place.path) : [];
            const token = keys.join('\n');
            if (token === inlineSent) {
                return;
            }
            inlineSent = token;
            emit('inline-fields', keys);
        }

        /**
         * Copies the canvas stylesheets' font faces into the admin page.
         * The editor is not inside the iframe, so it cannot use a face that only the iframe has loaded.
         */
        function installFonts() {
            if (fontsPromise) {
                return fontsPromise;
            }
            fontsPromise = (async () => {
                const rules = [];
                await Promise.all(canvasStylesheets(props.stylesheets).map(async (url) => {
                    try {
                        const absolute = new URL(url, window.location.href).href;
                        const response = await fetch(absolute, { credentials: 'same-origin' });
                        if (!response.ok) {
                            return;
                        }
                        fontFaceRules(await response.text(), absolute).forEach((rule) => rules.push(rule));
                    } catch (e) {
                        // The iframe still has the stylesheet. The editor uses the next family in the stack.
                    }
                }));
                if (!rules.length || !mounted) {
                    return;
                }
                let style = document.getElementById('cms-we-canvas-fonts');
                if (!style) {
                    style = document.createElement('style');
                    style.id = 'cms-we-canvas-fonts';
                    document.head.appendChild(style);
                }
                style.textContent = rules.join('\n');
                if (rich.active && document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(() => {
                        if (rich.active) {
                            fitRich();
                        }
                    });
                }
            })();
            return fontsPromise;
        }

        /** The field's width, and no wider than the page it sits on. */
        function boxWidth(rect) {
            const room = overlay.value ? overlay.value.clientWidth - rect.left : rect.width;
            return Math.max(0, Math.min(rect.width, room));
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
            const paths = {};
            const nodes = {};
            const contents = {};
            const slotList = [];
            Array.from(body.children).forEach((el) => {
                const uid = el.getAttribute('data-uid');
                if (uid !== null) {
                    found[uid] = rectOf(el);
                }
            });
            // A nested widget has no element of its own. Its fields share a path, and that region is its box.
            Array.from(body.querySelectorAll('.cms-canvas-item')).forEach((item) => {
                const top = item.getAttribute('data-uid');
                if (!top) {
                    return;
                }
                Array.from(item.querySelectorAll('[data-cms-path]')).forEach((el) => {
                    const path = el.getAttribute('data-cms-path') || '';
                    if (path === '') {
                        return;
                    }
                    const rect = rectOf(el);
                    if (rect.width < 1 && rect.height < 1) {
                        return;
                    }
                    const key = top + '\n' + path;
                    paths[key] = paths[key] ? unionRect(paths[key], rect) : rect;
                });
                const marked = readMarkers(document, item, top);
                Object.keys(marked.nodes).forEach((path) => {
                    nodes[top + '\n' + path] = marked.nodes[path];
                });
                slotList.push(...marked.slots);
                contents[top] = contentRect(document, item);
            });
            rects.value = found;
            pathRects.value = paths;
            nodeRects.value = nodes;
            contentRects.value = contents;
            slots.value = slotList;

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

            markRefreshing();

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

            markRefreshing();
            sync();
        }

        /** Dims any instance whose cached preview no longer matches what is stored */
        function markRefreshing() {
            refreshing.value = refreshingUids(props.items, cache, keyOf);
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
                return { item: null, field: null, action: null };
            }
            const box = overlay.value.getBoundingClientRect();
            const x = event.clientX - box.left;
            const y = event.clientY - box.top;
            const el = document.elementFromPoint(x, y);
            return {
                item: closestEl(el, '.cms-canvas-item'),
                field: closestEl(el, '[data-cms-edit]'),
                action: closestEl(el, '[data-cms-action]'),
                x,
                y,
            };
        }

        /** The innermost nested widget under a point, or '' for the top-level widget */
        function deepestPath(uid, x, y) {
            const prefix = uid + '\n';
            let best = '';
            Object.keys(nodeRects.value).forEach((key) => {
                if (!key.startsWith(prefix)) {
                    return;
                }
                const rect = nodeRects.value[key];
                const path = key.slice(prefix.length);
                const inside = x >= rect.left && x <= rect.left + rect.width && y >= rect.top && y <= rect.top + rect.height;
                if (inside && path.split('/').length > (best ? best.split('/').length : 0)) {
                    best = path;
                }
            });
            return best;
        }

        /** A field names the widget it belongs to. Elsewhere, the innermost widget under the pointer. */
        function pathAt(item, field, x, y) {
            if (field && item.contains(field)) {
                return field.getAttribute('data-cms-path') || '';
            }
            return deepestPath(item.getAttribute('data-uid') || '', x, y);
        }

        function onPointer(event) {
            if (frameRequest) {
                return;
            }
            const clientX = event.clientX;
            const clientY = event.clientY;
            frameRequest = requestAnimationFrame(() => {
                frameRequest = null;
                const { item, field, x, y } = hitTest({ clientX, clientY });
                const uid = item ? item.getAttribute('data-uid') : null;
                const locked = uid ? Boolean((props.items.find((entry) => entry.uid === uid) || {}).locked) : false;
                publishHover(item, locked ? '' : (item ? pathAt(item, field, x, y) : ''));
                if (field && !locked && !inactiveTab(field)) {
                    const kind = field.getAttribute('data-cms-edit');
                    cursor.value = kind === 'text' || kind === 'rich' || kind === 'rich-basic' ? 'text' : 'pointer';
                } else {
                    cursor.value = item ? 'pointer' : 'default';
                }
            });
        }

        function clearHover() {
            if (frameRequest) {
                cancelAnimationFrame(frameRequest);
                frameRequest = null;
            }
            cursor.value = 'default';
            publishHover(null, '');
        }

        /** Tells the outline which widget is under the pointer. The path names a nested widget. */
        function publishHover(item, path) {
            if (!item) {
                if (hoverKey !== '') {
                    hoverKey = '';
                    emit('hover', null);
                }
                return;
            }
            const uid = item.getAttribute('data-uid') || '';
            const key = uid + '\n' + path;
            if (key === hoverKey) {
                return;
            }
            hoverKey = key;
            emit('hover', { uid, path });
        }

        function markEnded(field) {
            if (!field) {
                endedKey = '';
            } else {
                const item = closestEl(field, '.cms-canvas-item');
                endedKey = (item ? item.getAttribute('data-uid') : '') + '\n'
                    + (field.getAttribute('data-cms-path') || '') + '\n'
                    + (field.getAttribute('data-cms-field') || '');
            }
            endedAt = performance.now();
        }

        function onClick(event) {
            finishInline();
            const { item, field, action, x, y } = hitTest(event);
            if (!item) {
                emit('select', null);
                return;
            }

            const uid = item.getAttribute('data-uid');
            const locked = Boolean((props.items.find((entry) => entry.uid === uid) || {}).locked);
            const path = pathAt(item, field, x, y);
            const name = field ? field.getAttribute('data-cms-field') : '';
            const justEnded = endedKey !== ''
                && performance.now() - endedAt < 500
                && endedKey === uid + '\n' + path + '\n' + name;

            emit('select', { uid, path: locked ? '' : path });

            // A tab that is not showing is selected first. Its title is edited only once it is the one on show.
            if (!locked && action && item.contains(action) && action.getAttribute('data-cms-action') === 'tab') {
                if (selectTab(action, uid)) {
                    return;
                }
            }

            if (field && !locked && !justEnded) {
                const edit = field.getAttribute('data-cms-edit');
                if (edit === 'rich' || edit === 'rich-basic') {
                    startRich(field, uid, path, name, edit === 'rich-basic');
                } else if (edit === 'text') {
                    startInline(field, uid, path, name, event);
                }
                return;
            }

            // The heading writes the panel's Starts open value. The canvas already shows it.
            if (!locked && action && item.contains(action) && action.getAttribute('data-cms-action') === 'toggle') {
                togglePanel(action, uid);
            }
        }

        /** A title sits inside its tab. It is a tab click until that tab is the one on show. */
        function inactiveTab(field) {
            const tab = closestEl(field, '[data-cms-action="tab"]');
            return Boolean(tab) && tab.getAttribute('aria-selected') !== 'true';
        }

        /**
         * Shows the pane for the tab that was clicked. Returns whether the pane changed.
         * The choice is remembered for this instance so the next preview keeps it, and it is not saved.
         */
        function selectTab(action, uid) {
            const widget = closestEl(action, '.nails-cms-widget-tabs');
            const raw = action.getAttribute('data-cms-tab');
            if (!widget || raw === null || !/^\d+$/.test(raw)) {
                return false;
            }
            const index = Number(raw);
            const items = tabItems(widget);
            const current = items.findIndex((item) => item.classList.contains('active'));
            if (index >= items.length || index === current) {
                return false;
            }
            selectedTabs.set(uid, index);
            showTab(widget, index);
            measure();
            return true;
        }

        /** The preview HTML starts on the first tab. This puts back the tab the user clicked. */
        function restoreTab(el, uid) {
            if (!selectedTabs.has(uid)) {
                return;
            }
            const widget = el.querySelector('.nails-cms-widget-tabs');
            const items = widget ? tabItems(widget) : [];
            if (!items.length) {
                selectedTabs.delete(uid);
                return;
            }
            const index = Math.min(selectedTabs.get(uid), items.length - 1);
            selectedTabs.set(uid, index);
            showTab(widget, index);
        }

        function tabItems(widget) {
            return Array.from(widget.querySelectorAll('.nav-tabs > .tab-item'));
        }

        function showTab(widget, index) {
            tabItems(widget).forEach((item, i) => {
                const on = i === index;
                item.classList.toggle('active', on);
                const link = item.querySelector('[data-cms-action="tab"]');
                if (link) {
                    link.setAttribute('aria-selected', on ? 'true' : 'false');
                }
            });
            Array.from(widget.querySelectorAll('.tab-content > .tab-pane')).forEach((pane, i) => {
                pane.classList.toggle('active', i === index);
            });
        }

        /** Opens or closes the panel and stores that as Starts open. */
        function togglePanel(action, uid) {
            const panel = closestEl(action, '.panel');
            const collapse = panel && panel.querySelector(':scope > .panel-collapse');
            const field = action.getAttribute('data-cms-field');
            if (!collapse || !field) {
                return;
            }
            const open = !collapse.classList.contains('in');
            collapse.classList.toggle('in', open);
            action.setAttribute('aria-expanded', open ? 'true' : 'false');
            measure();
            inline.add(uid);
            emit('edit', {
                uid,
                path: action.getAttribute('data-cms-path') || '',
                field,
                value: open,
            });
        }

        // --------------------------------------------------------------------------
        // Inline text: an editable copy of the field, styled like it, sits over the field while it is edited

        async function startInline(field, uid, path, name, event) {
            finishRich();
            const win = doc().defaultView;
            // An empty field shows a hint. Read the real type before copying it, then put the hint back.
            const empty = field.getAttribute('data-cms-empty') === '1';
            if (empty) {
                field.removeAttribute('data-cms-empty');
            }
            const style = win.getComputedStyle(field);
            const rect = field.getBoundingClientRect();
            const copied = {};
            COPIED_STYLES.forEach((property) => {
                copied[property] = style[property];
            });
            if (empty) {
                field.setAttribute('data-cms-empty', '1');
            }

            editTarget = field;
            editing.uid = uid;
            editing.path = path;
            editing.field = name;
            editing.style = {
                ...copied,
                boxSizing: 'border-box',
                top: rect.top + 'px',
                left: rect.left + 'px',
                width: boxWidth(rect) + 'px',
                minHeight: rect.height + 'px',
                background: backgroundOf(field, win),
            };
            editing.active = true;

            await nextTick();
            if (!inlineEl.value) {
                return;
            }
            inlineEl.value.textContent = empty ? '' : field.textContent;
            field.style.visibility = 'hidden';
            inlineEl.value.focus();
            placeCaret(inlineEl.value, event.clientX, event.clientY);
        }

        function onInlineInput() {
            if (!editTarget || !inlineEl.value) {
                return;
            }
            const value = oneLine(inlineEl.value.textContent);
            const placeholder = editTarget.getAttribute('data-cms-placeholder');
            if (value === '' && placeholder) {
                editTarget.textContent = placeholder;
                editTarget.setAttribute('data-cms-empty', '1');
            } else {
                editTarget.textContent = value;
                if (placeholder) {
                    editTarget.removeAttribute('data-cms-empty');
                }
            }
            inline.add(editing.uid);
            emit('edit', { uid: editing.uid, path: editing.path, field: editing.field, value });
        }

        function onInlineKeydown(event) {
            if ((event.metaKey || event.ctrlKey) && ['b', 'i', 'u'].includes(event.key.toLowerCase())) {
                event.preventDefault();
                return;
            }
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
            markEnded(editTarget);
            if (editTarget) {
                editTarget.style.visibility = '';
            }
            editTarget = null;
            editing.active = false;
            editing.uid = null;
            sync();
        }

        // --------------------------------------------------------------------------
        // Rich text on the canvas. The frame cannot run the editor, so it sits over the field.

        async function startRich(field, uid, path, name, basic) {
            finishInline();
            const win = doc().defaultView;
            const typeCss = previewTypeCss(field, win);
            const style = win.getComputedStyle(field);
            const rect = field.getBoundingClientRect();
            const copied = {};
            COPIED_STYLES.forEach((property) => {
                copied[property] = style[property];
            });

            editTarget = field;
            rich.uid = uid;
            rich.path = path;
            rich.field = name;
            rich.value = field.getAttribute('data-cms-empty') === '1' ? '' : field.innerHTML;
            rich.basic = Boolean(basic);
            richLast = rich.value;
            setTypeStyle(typeCss);
            rich.style = {
                ...copied,
                boxSizing: 'border-box',
                top: rect.top + 'px',
                left: rect.left + 'px',
                width: boxWidth(rect) + 'px',
                minHeight: rect.height + 'px',
                background: backgroundOf(field, win),
            };
            rich.active = true;

            // A collapsed margin sits outside the field. Hold that space on the field itself
            // before the content is cleared, or the text below jumps up.
            const blocks = Array.from(field.children).filter((el) => !el.classList.contains('cms-we-hint'));
            const firstBlock = blocks[0];
            const lastBlock = blocks[blocks.length - 1];
            const holdTop = firstBlock && marginCollapsed(field, firstBlock, 'top')
                ? win.getComputedStyle(firstBlock).marginTop
                : '';
            const holdBottom = lastBlock && marginCollapsed(field, lastBlock, 'bottom')
                ? win.getComputedStyle(lastBlock).marginBottom
                : '';

            await nextTick();
            if (holdTop) {
                field.style.marginTop = holdTop;
            }
            if (holdBottom) {
                field.style.marginBottom = holdBottom;
            }
            field.style.visibility = 'hidden';
            // min-height is the border box we measured. A content-box field would add its padding
            // on top of that and the panel would grow as soon as the field is edited.
            field.style.boxSizing = 'border-box';
            field.style.minHeight = rect.height + 'px';
            field.innerHTML = '';
            if (richEl.value && typeof richEl.value.focus === 'function') {
                richEl.value.focus();
            }
            fitRich();
            installFonts();
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(() => {
                    if (rich.active) {
                        fitRich();
                    }
                });
            }
            requestAnimationFrame(() => {
                if (rich.active) {
                    fitRich();
                }
            });
        }

        function fitRich() {
            const root = richEl.value && richEl.value.$el;
            if (!editTarget || !root) {
                return;
            }
            editTarget.style.minHeight = root.offsetHeight + 'px';
            measure();
        }

        function onRichUpdate(html) {
            if (!editTarget || !rich.active) {
                return;
            }
            const value = isBlankHtml(html) ? '' : html;
            nextTick(fitRich);
            if (value === richLast) {
                return;
            }
            richLast = value;
            inline.add(rich.uid);
            emit('edit', { uid: rich.uid, path: rich.path, field: rich.field, value });
        }

        function finishRich() {
            if (!rich.active) {
                return;
            }
            markEnded(editTarget);
            if (editTarget) {
                editTarget.style.visibility = '';
                editTarget.style.minHeight = '';
                editTarget.style.boxSizing = '';
                editTarget.style.marginTop = '';
                editTarget.style.marginBottom = '';
                editTarget.innerHTML = richLast === ''
                    ? '<p class="cms-we-hint">Write something…</p>'
                    : richLast;
                if (richLast === '') {
                    editTarget.setAttribute('data-cms-empty', '1');
                } else {
                    editTarget.removeAttribute('data-cms-empty');
                }
            }
            editTarget = null;
            rich.active = false;
            setTypeStyle('');
            sync();
            measure();
        }

        /**
         * Escape and a press outside the field end editing.
         * The listener is on the window, in the capture phase, so a field that stops the key still ends.
         */
        function onInlineEscape(event) {
            if (event.key !== 'Escape' || (!editing.active && !rich.active)) {
                return;
            }
            if (event.target && event.target.closest && event.target.closest('.cms-we-menu__link')) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            if (rich.active) {
                finishRich();
                return;
            }
            finishInline();
        }

        function insideRich(target) {
            const root = richEl.value && richEl.value.$el;
            if (root && (target === root || root.contains(target))) {
                return true;
            }
            // The bubble is portaled onto the editor, outside the field.
            return Boolean(target && target.closest && (target.closest('.cms-we-bubble') || target.closest('.cms-we-menu')));
        }

        function onInlinePointer(event) {
            if (editing.active && inlineEl.value) {
                if (event.target !== inlineEl.value && !inlineEl.value.contains(event.target)) {
                    finishInline();
                }
            }
            if (rich.active && !insideRich(event.target)) {
                finishRich();
            }
        }

        // --------------------------------------------------------------------------

        /** The sidebar line. It is always on the left edge of the canvas, as tall as the widget. */
        function lineStyle(box) {
            return {
                top: box.top + 'px',
                left: '0px',
                height: box.height + 'px',
            };
        }

        function samePlace(a, b) {
            return Boolean(a && b && a.top === b.top && (a.path || '') === (b.path || ''));
        }

        /** The widget's own box, or the region of a nested widget's fields, or the top-level widget that holds it */
        function boxFor(place) {
            if (!place || !place.top) {
                return null;
            }
            if (!place.path) {
                return rects.value[place.top] || null;
            }
            const prefix = place.top + '\n' + place.path;
            if (nodeRects.value[prefix]) {
                return nodeRects.value[prefix];
            }
            let box = null;
            Object.keys(pathRects.value).forEach((key) => {
                if (key === prefix || key.startsWith(prefix + '/')) {
                    box = box ? unionRect(box, pathRects.value[key]) : pathRects.value[key];
                }
            });
            return box || rects.value[place.top] || null;
        }

        function unionRect(a, b) {
            const top = Math.min(a.top, b.top);
            const left = Math.min(a.left, b.left);
            const right = Math.max(a.left + a.width, b.left + b.width);
            const bottom = Math.max(a.top + a.height, b.top + b.height);
            return { top, left, width: right - left, height: bottom - top };
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
            markRefreshing();
            schedule();
        }, { deep: true });

        watch(() => props.area, () => {
            if (mounted) {
                clearTimeout(timer);
                refreshPreviews();
            }
        });

        watch(() => props.scrollTo, async (token) => {
            if (!token) {
                return;
            }
            await nextTick();
            const el = selectedEl.value;
            if (!el) {
                return;
            }
            const scroller = el.closest('.cms-we__stage');
            if (!scroller) {
                return;
            }
            const elRect = el.getBoundingClientRect();
            const box = scroller.getBoundingClientRect();
            // Already on screen: leave the page where it is
            if (elRect.top >= box.top && elRect.bottom <= box.bottom) {
                return;
            }
            const delta = elRect.top < box.top
                ? elRect.top - box.top - 24
                : elRect.bottom - box.bottom + 24;
            scroller.scrollBy({ top: delta, behavior: 'smooth' });
        });

        watch(() => props.selected, () => publishInline());

        onMounted(() => {
            mounted = true;
            window.addEventListener('resize', measure);
            window.addEventListener('keydown', onInlineEscape, true);
            window.addEventListener('mousedown', onInlinePointer, true);
            installFonts();
            refreshPreviews();
        });

        onBeforeUnmount(() => {
            mounted = false;
            setTypeStyle('');
            clearTimeout(timer);
            window.removeEventListener('resize', measure);
            window.removeEventListener('keydown', onInlineEscape, true);
            window.removeEventListener('mousedown', onInlinePointer, true);
            if (observer) {
                observer.disconnect();
            }
            if (frameRequest) {
                cancelAnimationFrame(frameRequest);
            }
        });

        /** Covers one widget while its preview is rendering again */
        function boxStyle(uid) {
            const box = rects.value[uid];
            if (!box) {
                return { display: 'none' };
            }
            return {
                top: box.top + 'px',
                left: box.left + 'px',
                width: box.width + 'px',
                height: box.height + 'px',
            };
        }

        function rectStyle(rect) {
            return {
                top: rect.top + 'px',
                left: rect.left + 'px',
                width: rect.width + 'px',
                height: rect.height + 'px',
            };
        }

        return {
            frame,
            overlay,
            wraps,
            emptySlots,
            slotGaps,
            insertInto,
            rectStyle,
            selectedEl,
            inlineEl,
            richEl,
            shell,
            height,
            gaps,
            cursor,
            inline: editing,
            rich,
            onRichUpdate,
            showHover,
            hoverBox,
            selectedBox,
            onLoad,
            onPointer,
            clearHover,
            onClick,
            onInlineInput,
            onInlineKeydown,
            onInlinePaste,
            finishInline,
            lineStyle,
            refreshing,
            boxStyle,
        };
    },
};
</script>

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
import { buildCanvasShell, canvasStylesheets, fontFaceRules, inlineFieldKeys, renderSection } from './canvas.js';
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

/** True when a child's margin collapses through the field, so the text sits on the field's edge. */
function marginCollapsed(field, el, edge) {
    const fieldRect = field.getBoundingClientRect();
    const rect = el.getBoundingClientRect();
    const delta = edge === 'top' ? rect.top - fieldRect.top : fieldRect.bottom - rect.bottom;
    return Math.abs(delta) < 1;
}

/**
 * The preview's computed type for each block, as rules for the editor.
 * Probes are not the first child, so a widget's first-child margin rule is recorded on its own.
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
    // A margin that collapses out of the field is not part of the field's box. The editor is a
    // flow root, so it would pull that margin inside and the text would jump. Drop it there.
    let zeroTop = Boolean(first && (win.getComputedStyle(first).marginTop === '0px' || marginCollapsed(field, first, 'top')));
    let zeroBottom = Boolean(last && marginCollapsed(field, last, 'bottom'));
    const spacer = owner.createElement('div');
    field.appendChild(spacer);
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
            field.appendChild(host);
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
        spacer.remove();
    }

    if (!first) {
        const probe = owner.createElement('h2');
        probe.textContent = 'a';
        field.insertBefore(probe, field.firstChild);
        zeroTop = win.getComputedStyle(probe).marginTop === '0px' || marginCollapsed(field, probe, 'top');
        zeroBottom = marginCollapsed(field, probe, 'bottom');
        probe.remove();
    }

    if (zeroTop) {
        rules.push('.cms-we-canvas__rich .ProseMirror > :first-child{margin-top:0 !important}');
    }
    if (zeroBottom) {
        rules.push('.cms-we-canvas__rich .ProseMirror > :last-child{margin-bottom:0 !important}');
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
    },
    emits: ['edit', 'rich', 'insert-at', 'select', 'hover', 'inline-fields'],
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
        const shell = computed(() => buildCanvasShell({ stylesheets: props.stylesheets }));

        const height = ref(0);
        const rects = ref({});
        /** Bounding boxes of `[data-cms-path]` regions, keyed `topUid\\npath` */
        const pathRects = ref({});
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
            });
            rects.value = found;
            pathRects.value = paths;

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
                publishHover(item, locked ? null : field);
                if (field && !locked) {
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
            publishHover(null, null);
        }

        /** Tells the outline which widget is under the pointer. A field's path names a nested widget. */
        function publishHover(item, field) {
            if (!item) {
                if (hoverKey !== '') {
                    hoverKey = '';
                    emit('hover', null);
                }
                return;
            }
            const uid = item.getAttribute('data-uid') || '';
            const path = field && item.contains(field) ? (field.getAttribute('data-cms-path') || '') : '';
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
            const { item, field } = hitTest(event);
            if (!item) {
                emit('select', null);
                return;
            }

            const uid = item.getAttribute('data-uid');
            const locked = Boolean((props.items.find((entry) => entry.uid === uid) || {}).locked);
            const path = field ? field.getAttribute('data-cms-path') || '' : '';
            const name = field ? field.getAttribute('data-cms-field') : '';
            const justEnded = endedKey !== ''
                && performance.now() - endedAt < 500
                && endedKey === uid + '\n' + path + '\n' + name;

            emit('select', { uid, path: locked ? '' : path });

            if (!field || locked || justEnded) {
                return;
            }
            const edit = field.getAttribute('data-cms-edit');
            if (edit === 'rich' || edit === 'rich-basic') {
                startRich(field, uid, path, name, edit === 'rich-basic');
            } else if (edit === 'text') {
                startInline(field, uid, path, name, event);
            }
        }

        // --------------------------------------------------------------------------
        // Inline text: an editable copy of the field, styled like it, sits over the field while it is edited

        async function startInline(field, uid, path, name, event) {
            finishRich();
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
            inlineEl.value.textContent = field.textContent;
            field.style.visibility = 'hidden';
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

        return {
            frame,
            overlay,
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
        };
    },
};
</script>

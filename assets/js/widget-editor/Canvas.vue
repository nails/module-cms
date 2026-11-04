<template>
    <iframe
        ref="frame"
        class="cms-we__canvas"
        title="Canvas"
        sandbox="allow-same-origin"
        :srcdoc="shell"
        @load="onLoad"
    ></iframe>
</template>

<script>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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

/**
 * Builds an element from a section's HTML inside the canvas document, and makes its inline fields editable
 */
function parseItem(doc, html) {
    const holder = doc.createElement('div');
    holder.innerHTML = html;
    const el = holder.firstElementChild;
    el.querySelectorAll('[data-cms-edit="text"]').forEach((field) => {
        field.setAttribute('contenteditable', 'true');
    });
    return el;
}

function findItem(root, uid) {
    return Array.from(root.children).find((el) => el.getAttribute('data-uid') === uid) || null;
}

function closestEl(target, selector) {
    return target && typeof target.closest === 'function' ? target.closest(selector) : null;
}

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
    },
    emits: ['edit', 'rich'],
    setup(props, { emit }) {
        const frame = ref(null);
        const cache = new Map();
        /** The HTML last written into the frame for each instance, so unchanged instances are not touched */
        const rendered = new Map();
        /** Top-level uids edited inline since the last change to items. Their previews are not requested. */
        const inline = new Set();
        const shell = computed(() => buildCanvasShell({ stylesheets: props.stylesheets }));

        let timer = null;
        let sequence = 0;
        let mounted = false;
        let loaded = false;

        function sectionFor(item) {
            if (item.locked) {
                return { uid: item.uid, html: '', error: null, locked: true, pending: false };
            }
            const entry = cache.get(item.uid);
            if (!entry) {
                return { uid: item.uid, html: '', error: null, locked: false, pending: true };
            }
            return {
                uid: item.uid,
                html: entry.html,
                error: entry.error,
                locked: false,
                pending: false,
            };
        }

        /**
         * Makes the frame match the items: removes instances which have gone, adds new ones, reorders, and
         * replaces the HTML of any instance whose preview has changed. An instance with focus is left alone,
         * so typing is not interrupted; it is patched on the next sync after focus leaves.
         */
        function sync() {
            const doc = frame.value && frame.value.contentDocument;
            if (!loaded || !doc || !doc.body) {
                return;
            }

            const root = doc.body;
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
                    el = parseItem(doc, html);
                    rendered.set(item.uid, html);
                    root.insertBefore(el, previous ? previous.nextElementSibling : root.firstElementChild);
                } else if (rendered.get(item.uid) !== html && !el.contains(doc.activeElement)) {
                    const next = parseItem(doc, html);
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
        }

        async function refreshPreviews() {
            const stale = props.items.filter((item) => {
                if (item.locked) {
                    return false;
                }
                const entry = cache.get(item.uid);
                return !entry || entry.key !== keyOf(item);
            });

            // Drop cache entries for instances that have gone
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
        // Events inside the frame. The frame runs no scripts, so these listeners are attached from here.

        function onInput(event) {
            const field = closestEl(event.target, '[data-cms-field]');
            const item = closestEl(event.target, '.cms-canvas-item');
            if (!field || !item || field.getAttribute('data-cms-edit') !== 'text') {
                return;
            }
            const uid = item.getAttribute('data-uid');
            inline.add(uid);
            emit('edit', {
                uid,
                path: field.getAttribute('data-cms-path') || '',
                field: field.getAttribute('data-cms-field'),
                value: field.textContent,
            });
        }

        function onKeydown(event) {
            // Text fields are single line
            if (event.key === 'Enter' && closestEl(event.target, '[data-cms-edit="text"]')) {
                event.preventDefault();
            }
        }

        function onPaste(event) {
            if (!closestEl(event.target, '[data-cms-edit="text"]')) {
                return;
            }
            event.preventDefault();
            const text = (event.clipboardData ? event.clipboardData.getData('text/plain') : '')
                .replace(/\s*\n+\s*/g, ' ');
            frame.value.contentDocument.execCommand('insertText', false, text);
        }

        function onClick(event) {
            // Links in the canvas are not followed, which would navigate the frame away from the editor
            if (closestEl(event.target, 'a')) {
                event.preventDefault();
            }

            const rich = closestEl(event.target, '[data-cms-edit="rich"]');
            const item = rich ? closestEl(rich, '.cms-canvas-item') : null;
            if (rich && item) {
                emit('rich', {
                    uid: item.getAttribute('data-uid'),
                    path: rich.getAttribute('data-cms-path') || '',
                    field: rich.getAttribute('data-cms-field'),
                    value: rich.innerHTML,
                });
            }
        }

        function onFocusOut() {
            // Focus has not moved yet; sync once it has, so an instance changed while focused is patched
            setTimeout(sync, 0);
        }

        function onLoad() {
            const doc = frame.value && frame.value.contentDocument;
            if (!doc) {
                return;
            }
            loaded = true;
            rendered.clear();
            doc.addEventListener('input', onInput);
            doc.addEventListener('keydown', onKeydown);
            doc.addEventListener('paste', onPaste);
            doc.addEventListener('click', onClick);
            doc.addEventListener('focusout', onFocusOut);
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

        onMounted(() => {
            mounted = true;
            refreshPreviews();
        });

        onBeforeUnmount(() => {
            mounted = false;
            clearTimeout(timer);
        });

        return { frame, shell, onLoad };
    },
};
</script>

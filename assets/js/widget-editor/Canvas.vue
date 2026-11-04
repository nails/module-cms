<template>
    <iframe
        class="cms-we__canvas"
        title="Canvas"
        sandbox="allow-same-origin"
        :srcdoc="doc"
    ></iframe>
</template>

<script>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { buildCanvasDocument } from './canvas.js';

/** Changes settle for this long before previews are requested again (D5) */
const DEBOUNCE_MS = 300;

/**
 * The preview cache is keyed by instance uid. An entry is reused only while the instance's slug and data are
 * unchanged, so a change re-renders that instance and its containing top-level instance, and nothing else.
 */
function keyOf(item) {
    return JSON.stringify([item.slug, item.data]);
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
    setup(props) {
        const cache = new Map();
        const version = ref(0);
        let timer = null;
        let sequence = 0;
        let mounted = false;

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
                version.value++;
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

            version.value++;
        }

        function schedule() {
            clearTimeout(timer);
            timer = setTimeout(refreshPreviews, DEBOUNCE_MS);
        }

        const doc = computed(() => {
            // Reading version makes the document recompute when previews arrive
            // eslint-disable-next-line no-unused-expressions
            version.value;

            const sections = props.items.map((item) => {
                if (item.locked) {
                    return { uid: item.uid, html: '', error: null, locked: true, pending: false };
                }
                const entry = cache.get(item.uid);
                if (!entry || entry.key !== keyOf(item)) {
                    return { uid: item.uid, html: '', error: null, locked: false, pending: true };
                }
                return {
                    uid: item.uid,
                    html: entry.html,
                    error: entry.error,
                    locked: false,
                    pending: false,
                };
            });

            return buildCanvasDocument({ stylesheets: props.stylesheets, sections });
        });

        watch(() => props.items, () => {
            if (!mounted) {
                return;
            }
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

        return { doc };
    },
};
</script>

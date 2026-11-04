<template>
    <div class="cms-we" role="dialog" aria-label="Widget editor">
        <div class="cms-we__bar">
            <nav class="cms-we__areas">
                <button
                    v-for="area in view.areas"
                    :key="area.slug"
                    type="button"
                    class="cms-we__tab"
                    :class="{ 'is-active': area.slug === view.active }"
                    @click="setArea(area.slug)"
                >
                    {{ area.label }}
                </button>
            </nav>
            <div class="cms-we__tools">
                <button type="button" class="cms-we__tool" :disabled="!view.canUndo" @click="run('undo')">Undo</button>
                <button type="button" class="cms-we__tool" :disabled="!view.canRedo" @click="run('redo')">Redo</button>
                <button type="button" class="cms-we__tool" :disabled="!view.selected" @click="run('duplicate')">Duplicate</button>
                <button type="button" class="cms-we__tool" :disabled="!view.selected" @click="run('remove')">Remove</button>
                <button v-if="canSwitch" type="button" class="cms-we__tool" @click="switchBack">Use the previous editor</button>
                <button
                    v-for="(action, index) in session.actions"
                    :key="index"
                    type="button"
                    class="cms-we__tool"
                    @click="action.callback()"
                >
                    {{ action.label }}
                </button>
                <button type="button" class="cms-we__tool" @click="close">Close</button>
            </div>
        </div>

        <p v-if="draftOffer" class="cms-we__message">
            Unsaved changes from {{ draftOffer }} were kept in this browser.
            <button type="button" @click="restoreDraft">Restore</button>
            <button type="button" @click="discardDraft">Discard</button>
        </p>
        <p v-if="view.message" class="cms-we__message">{{ view.message }}</p>
        <p v-if="error" class="cms-we__message">{{ error }}</p>

        <div v-if="loading" class="cms-we__body">
            <p>Loading widgets…</p>
        </div>
        <div v-else class="cms-we__body">
            <div class="cms-we__outline">
                <p class="cms-we__slot-label">{{ view.count }} / {{ view.maxInstances }}</p>
                <ol
                    class="cms-we__tree"
                    @dragover.prevent
                    @drop.stop="onMove({ uid: $event.dataTransfer.getData('text/plain'), parentUid: null, slot: null, index: view.nodes.length })"
                >
                    <OutlineNode
                        v-for="(node, index) in view.nodes"
                        :key="node.uid"
                        :node="node"
                        :index="index"
                        :selected="view.selected"
                        :catalogue="catalogue"
                        @select="select"
                        @move="onMove"
                    />
                </ol>
            </div>

            <div class="cms-we__inspector">
                <p v-if="!selectedNode">Select a widget to edit it.</p>
                <div v-else-if="selectedNode.locked" class="cms-we__locked">
                    <strong>{{ selectedNode.slug }}</strong>
                    is kept as it was saved. It can be removed or reordered here, and edited in the editor that created it.
                </div>
                <p v-else-if="inspector.type === 'fields'">
                    This widget’s fields are edited on the canvas. That arrives with the canvas; until then it can be added, moved and removed, and its saved data is left as it is.
                </p>
                <p v-else-if="inspector.error">{{ inspector.error }}</p>
                <div v-else ref="formRoot" @input="onFormEvent" @change="onFormEvent"></div>
            </div>

            <div class="cms-we__organiser">
                <input
                    ref="search"
                    v-model="term"
                    class="cms-we__search"
                    type="search"
                    placeholder="Search widgets"
                    @keydown.esc.stop
                >
                <section v-for="group in groups" :key="group.label" class="cms-we__group">
                    <h3>{{ group.label }}</h3>
                    <button
                        v-for="slug in group.slugs"
                        :key="group.label + slug"
                        type="button"
                        class="cms-we__widget"
                        @click="insert(slug)"
                    >
                        {{ catalogue.widgets[slug].label }}
                        <span v-if="catalogue.widgets[slug].status && catalogue.widgets[slug].status !== 'active'" class="cms-we__status">
                            {{ catalogue.widgets[slug].status }}
                        </span>
                        <span
                            class="cms-we__status"
                            @click.stop="toggleFavourite(slug)"
                        >{{ catalogue.favourites.includes(slug) ? '★' : '☆' }}</span>
                    </button>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { createEditor } from './editor.js';
import { organiserGroups } from './rules.js';
import { clearDraft, draftKey, loadDraft, saveDraft } from './draft.js';
import { serialiseFields } from './serialise.js';
import OutlineNode from './OutlineNode.vue';

export default {
    name: 'EditorApp',
    components: { OutlineNode },
    props: {
        session: { type: Object, required: true },
    },
    setup(props) {
        const loading = ref(true);
        const error = ref('');
        const term = ref('');
        const catalogue = ref({ widgets: {}, groups: [], favourites: [], limits: {} });
        const view = ref({
            areas: [],
            active: '',
            selected: null,
            nodes: [],
            message: '',
            canUndo: false,
            canRedo: false,
            count: 0,
            maxInstances: 0,
        });
        const inspector = reactive({ type: null, error: '' });
        const formRoot = ref(null);
        const search = ref(null);
        const draftOffer = ref('');
        let editor = null;
        let draft = null;
        let applyingForm = false;

        const groups = computed(() => {
            const area = (props.session.areas || []).find((item) => item.slug === view.value.active);
            return organiserGroups(catalogue.value, area ? area.rules : {}, term.value);
        });

        const selectedNode = computed(() => findNode(view.value.nodes, view.value.selected));

        const canSwitch = computed(() => {
            const config = readConfig();
            return config.enabled.includes('v1') && config.active === 'v2';
        });

        const storageKey = computed(() => draftKey((props.session.areas || []).map((area) => area.slug)));

        function refresh() {
            view.value = editor.snapshot();
        }

        function endpoint(path) {
            const base = window.SITE_URL || '/';
            return base + 'api/cms/widgeteditor/' + path;
        }

        async function loadCatalogue() {
            const response = await fetch(endpoint('catalogue'), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const body = await response.json();
            if (!response.ok) {
                throw new Error((body && (body.error || body.message)) || 'The catalogue could not be loaded');
            }
            return body.data;
        }

        function publishDraft() {
            if (!editor || !window.localStorage) {
                return;
            }
            const areas = {};
            editor.snapshot().areas.forEach((area) => {
                areas[area.slug] = editor.stored(area.slug);
            });
            saveDraft(window.localStorage, storageKey.value, areas);
        }

        function boot(areaData) {
            const areas = (props.session.areas || []).map((area) => ({
                ...area,
                data: areaData && area.slug in areaData ? areaData[area.slug] : area.data,
            }));
            editor = createEditor(areas, catalogue.value, {
                onChange(slug, data) {
                    publishDraft();
                    if (typeof props.session.onChange === 'function') {
                        props.session.onChange(slug, data);
                    }
                },
            });
            const preferred = props.session.area;
            if (preferred && areas.some((area) => area.slug === preferred)) {
                editor.setActive(preferred);
            }
            refresh();
        }

        function restoreDraft() {
            if (draft) {
                boot(draft.areas);
                draftOffer.value = '';
            }
        }

        function discardDraft() {
            clearDraft(window.localStorage, storageKey.value);
            draft = null;
            draftOffer.value = '';
        }

        async function loadInspector() {
            inspector.type = null;
            inspector.error = '';
            const node = selectedNode.value;
            if (!node || node.locked) {
                return;
            }
            const widget = catalogue.value.widgets[node.slug];
            if (!widget) {
                return;
            }
            if (widget.inspector && widget.inspector.type === 'fields') {
                inspector.type = 'fields';
                return;
            }

            const response = await fetch(endpoint('inspector'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ items: [{ slug: node.slug, data: node.data }] }),
            });
            const body = await response.json();
            const item = body.data && body.data[0];
            if (!response.ok || !item) {
                inspector.error = 'The editor form could not be loaded';
                return;
            }
            if (item.error) {
                inspector.error = item.error;
                return;
            }
            if (item.type === 'fields') {
                inspector.type = 'fields';
                return;
            }
            inspector.type = 'html';
            await nextTick();
            if (!formRoot.value) {
                return;
            }
            applyingForm = true;
            formRoot.value.innerHTML = item.html || '';
            (item.assets || []).forEach(mountAsset);
            applyingForm = false;
        }

        function onFormEvent() {
            if (applyingForm || !formRoot.value || !view.value.selected) {
                return;
            }
            const fields = Array.from(formRoot.value.querySelectorAll('input, select, textarea')).map((element) => ({
                name: element.name,
                type: element.multiple ? 'select-multiple' : element.type,
                value: element.multiple
                    ? Array.from(element.selectedOptions).map((option) => option.value)
                    : element.value,
                checked: element.checked,
                disabled: element.disabled,
            }));
            const next = serialiseFields(fields);
            const current = editor.locate(view.value.selected);
            if (current && JSON.stringify(current.node.data) === JSON.stringify(next)) {
                return;
            }
            editor.update(view.value.selected, next, true);
            refresh();
        }

        function onKey(event) {
            const typing = event.target && /^(INPUT|TEXTAREA|SELECT)$/.test(event.target.tagName);
            const meta = event.metaKey || event.ctrlKey;

            if (event.key === 'Escape') {
                if (view.value.selected) {
                    select(null);
                } else {
                    close();
                }
                return;
            }
            if (typing) {
                return;
            }
            if (event.key === '/' ) {
                event.preventDefault();
                search.value && search.value.focus();
            } else if (meta && event.key.toLowerCase() === 'z') {
                event.preventDefault();
                run(event.shiftKey ? 'redo' : 'undo');
            } else if (meta && event.key.toLowerCase() === 'd') {
                event.preventDefault();
                run('duplicate');
            } else if (event.altKey && event.key === 'ArrowUp') {
                event.preventDefault();
                nudge(-1);
            } else if (event.altKey && event.key === 'ArrowDown') {
                event.preventDefault();
                nudge(1);
            } else if (event.key === 'Delete' || event.key === 'Backspace') {
                event.preventDefault();
                run('remove');
            }
        }

        function run(method) {
            if (!editor) {
                return;
            }
            editor[method]();
            refresh();
        }

        function nudge(delta) {
            if (view.value.selected) {
                editor.nudge(view.value.selected, delta);
                refresh();
            }
        }

        function setArea(slug) {
            editor.setActive(slug);
            refresh();
        }

        function select(uid) {
            editor.select(uid);
            refresh();
        }

        function insert(slug) {
            editor.insert(slug);
            refresh();
        }

        async function toggleFavourite(slug) {
            const saved = catalogue.value.favourites.includes(slug);
            const response = await fetch(endpoint('favourite/' + encodeURIComponent(slug)), {
                method: saved ? 'DELETE' : 'PUT',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const body = await response.json();
            if (response.ok && body.data && Array.isArray(body.data.favourites)) {
                catalogue.value = { ...catalogue.value, favourites: body.data.favourites };
            }
        }

        function onMove(detail) {
            editor.move(detail.uid, view.value.active, detail.parentUid, detail.slot, detail.index);
            refresh();
        }

        function close() {
            clearDraft(window.localStorage, storageKey.value);
            if (typeof props.session.onClose === 'function') {
                props.session.onClose();
            }
            if (window.NAILS_CMS_WIDGET_EDITOR) {
                window.NAILS_CMS_WIDGET_EDITOR.close();
            }
        }

        function switchBack() {
            fetch(endpoint('version'), {
                method: 'PUT',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ version: 'v1' }),
            }).then((response) => {
                if (response.ok) {
                    window.location.reload();
                }
            });
        }

        watch(() => view.value.selected, () => {
            loadInspector();
        });

        onMounted(async () => {
            window.addEventListener('keydown', onKey);
            try {
                catalogue.value = await loadCatalogue();
                draft = loadDraft(window.localStorage, storageKey.value);
                boot();
                if (draft && JSON.stringify(draft.areas) !== JSON.stringify(currentAreas())) {
                    draftOffer.value = 'earlier';
                }
                loading.value = false;
            } catch (e) {
                error.value = e.message;
                loading.value = false;
            }
        });

        onBeforeUnmount(() => {
            window.removeEventListener('keydown', onKey);
        });

        function currentAreas() {
            const areas = {};
            (props.session.areas || []).forEach((area) => {
                areas[area.slug] = area.data || [];
            });
            return areas;
        }

        return {
            loading,
            error,
            term,
            catalogue,
            view,
            groups,
            inspector,
            formRoot,
            search,
            draftOffer,
            selectedNode,
            canSwitch,
            setArea,
            select,
            insert,
            toggleFavourite,
            onMove,
            run,
            close,
            switchBack,
            restoreDraft,
            discardDraft,
            onFormEvent,
        };
    },
};

function readConfig() {
    const el = document.getElementById('cms-widget-editor-config');
    if (!el) {
        return { enabled: ['v2'], active: 'v2' };
    }
    try {
        return JSON.parse(el.textContent);
    } catch (e) {
        return { enabled: ['v2'], active: 'v2' };
    }
}

function findNode(nodes, uid) {
    if (!uid) {
        return null;
    }
    for (let i = 0; i < nodes.length; i++) {
        const node = nodes[i];
        if (node.uid === uid) {
            return node;
        }
        if (node.slots) {
            const names = Object.keys(node.slots);
            for (let s = 0; s < names.length; s++) {
                const found = findNode(node.slots[names[s]], uid);
                if (found) {
                    return found;
                }
            }
        }
    }
    return null;
}

function mountAsset(url) {
    if (typeof url !== 'string' || url === '' || document.querySelector('[data-cms-we-asset="' + CSS.escape(url) + '"]')) {
        return;
    }
    const style = /\.css(\?|$)/.test(url);
    const el = document.createElement(style ? 'link' : 'script');
    el.dataset.cmsWeAsset = url;
    if (style) {
        el.rel = 'stylesheet';
        el.href = url;
    } else {
        el.src = url;
    }
    document.head.appendChild(el);
}
</script>

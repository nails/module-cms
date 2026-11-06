<template>
    <div class="cms-we" role="dialog" aria-label="Widget editor">
        <div class="cms-we__bar">
            <div class="cms-we__title">
                <i class="fa fa-puzzle-piece cms-we__mark" title="Widgets"></i>
                <select
                    v-if="view.areas.length > 1"
                    class="cms-we__area"
                    :value="view.active"
                    title="Switch field"
                    @change="setArea($event.target.value)"
                >
                    <option v-for="area in view.areas" :key="area.slug" :value="area.slug">{{ area.label }}</option>
                </select>
                <strong v-else class="cms-we__area">{{ activeLabel }}</strong>
            </div>
            <div class="cms-we__tools">
                <button type="button" class="cms-we__btn is-icon" title="Undo (⌘Z)" :disabled="!view.canUndo" @click="run('undo')">
                    <i class="fa fa-rotate-left"></i>
                </button>
                <button type="button" class="cms-we__btn is-icon" title="Redo (⇧⌘Z)" :disabled="!view.canRedo" @click="run('redo')">
                    <i class="fa fa-rotate-right"></i>
                </button>
                <button
                    v-for="(action, index) in session.actions"
                    :key="index"
                    type="button"
                    class="cms-we__btn"
                    @click="action.callback()"
                >
                    {{ action.label }}
                </button>
                <button type="button" class="cms-we__btn" @click="requestCancel">Cancel</button>
                <button type="button" class="cms-we__btn is-primary" @click="close">
                    <i class="fa fa-check"></i> Done
                </button>
            </div>
        </div>

        <div v-if="draftOffer" class="cms-we__notice">
            <i class="fa fa-clock-rotate-left"></i>
            <span>Unsaved changes from {{ draftOffer }} were kept in this browser.</span>
            <button type="button" class="btn btn-default btn-xs" @click="restoreDraft">Restore</button>
            <button type="button" class="btn btn-default btn-xs" @click="discardDraft">Discard</button>
        </div>
        <div v-if="view.message" class="cms-we__notice is-warning">
            <i class="fa fa-triangle-exclamation"></i>
            <span>{{ view.message }}</span>
            <button type="button" class="cms-we__notice-close" title="Dismiss" @click="dismissMessage">
                <i class="fa fa-xmark"></i>
            </button>
        </div>
        <div v-if="error" class="cms-we__notice is-error">
            <i class="fa fa-circle-exclamation"></i>
            <span>{{ error }}</span>
            <button type="button" class="cms-we__notice-close" title="Dismiss" @click="dismissError">
                <i class="fa fa-xmark"></i>
            </button>
        </div>

        <div v-if="loading" class="cms-we__body is-loading">
            <p><i class="fa fa-spinner fa-spin"></i> Loading widgets…</p>
        </div>
        <div v-else class="cms-we__body" :class="{ 'is-outline-closed': !outlineOpen, 'is-inspector-closed': !inspectorOpen }">
            <nav class="cms-we-outline" aria-label="Outline">
                <header class="cms-we-outline__head">
                    <button
                        type="button"
                        class="cms-we-outline__collapse"
                        :title="outlineOpen ? 'Hide outline' : 'Show outline'"
                        @click="outlineOpen = !outlineOpen"
                    >
                        <i class="fa" :class="outlineOpen ? 'fa-angles-left' : 'fa-angles-right'"></i>
                    </button>
                    <span v-if="outlineOpen">Outline</span>
                    <em v-if="outlineOpen">{{ view.count }}</em>
                </header>
                <template v-if="outlineOpen">
                    <button
                        v-if="!view.nodes.length"
                        type="button"
                        class="cms-we-outline__add"
                        @click="openDrawer({ parentUid: null, slot: null, index: 0 })"
                        @dragover.prevent="markTail"
                        @drop.prevent.stop="onDropEnd"
                    >Add a widget</button>
                    <ol v-else
                        class="cms-we-outline__list"
                        @dragover.prevent
                        @drop.prevent="onDropEnd"
                    >
                        <OutlineNode
                            v-for="(node, index) in view.nodes"
                            :key="node.uid"
                            :node="node"
                            :index="index"
                            :selected="view.selected"
                            :hovered="hovered"
                            :catalogue="catalogue"
                            :drag="drag"
                            :folded-ids="folded"
                            @select="selectFromOutline"
                            @hover="onOutlineHover"
                            @toggle="toggleFold"
                            @move="onMove"
                            @insert="openDrawer"
                            @duplicate="duplicateNode"
                            @remove="requestRemove"
                        />
                        <li class="cms-we-outline__tail" style="padding-left: 8px">
                            <button
                                type="button"
                                class="cms-we-outline__gap is-tail"
                                :class="{
                                    'is-drop': drag.uid && drag.over === tailKey && !drag.invalid,
                                    'is-invalid': drag.uid && drag.over === tailKey && drag.invalid,
                                }"
                                :title="tailTitle"
                                @click="openDrawer({ parentUid: null, slot: null, index: view.nodes.length })"
                                @dragover.prevent="markTail"
                                @drop.prevent.stop="onDropEnd"
                            >
                                <i class="fa" :class="tailIcon"></i>
                            </button>
                        </li>
                    </ol>
                </template>
            </nav>

            <div class="cms-we__stage">
                <Canvas
                    :items="canvasItems"
                    :stylesheets="canvasStylesheets"
                    :preview-url="endpoint('preview')"
                    :area="view.active"
                    :selected="selectedPlace"
                    :hover="hoverPlace"
                    :scroll-to="scrollTo"
                    @select="onCanvasSelect"
                    @hover="onCanvasHover"
                    @edit="onCanvasEdit"
                    @rich="onCanvasRich"
                    @insert-at="onCanvasInsert"
                    @inline-fields="onInlineFields"
                />
            </div>

            <aside class="cms-we__inspector">
                <header class="cms-we__inspector-head">
                    <span v-if="inspectorOpen">Inspector</span>
                    <button
                        type="button"
                        class="cms-we__inspector-collapse"
                        :title="inspectorOpen ? 'Hide inspector' : 'Show inspector'"
                        @click="inspectorOpen = !inspectorOpen"
                    >
                        <i class="fa" :class="inspectorOpen ? 'fa-angles-right' : 'fa-angles-left'"></i>
                    </button>
                </header>
                <div v-show="inspectorOpen" class="cms-we__inspector-body">
                    <div v-if="selectedNode" class="cms-we__inspector-title">
                        <p v-if="selectedWidget && selectedWidget.groups && selectedWidget.groups.length" class="cms-we__kicker">
                            {{ selectedWidget.groups[0] }}
                        </p>
                        <h2>{{ selectedLabel }}</h2>
                        <p v-if="selectedDescription" class="cms-we__inspector-desc">{{ selectedDescription }}</p>
                    </div>
                    <div v-if="!selectedNode" class="cms-we__empty">
                        <i class="fa fa-arrow-pointer"></i>
                        <p>Select a widget on the page or in the outline to edit it.</p>
                    </div>
                    <div v-else-if="selectedNode.locked" class="cms-we__locked">
                        <strong><i class="fa fa-triangle-exclamation"></i> {{ selectedNode.slug }}</strong>
                        is kept as it was saved. It can be removed or reordered here, and edited in the editor that created it.
                    </div>
                    <FieldsInspector
                        v-else-if="inspector.type === 'fields'"
                        class="cms-we__settings"
                        :fields="selectedFields"
                        :values="inspectorValues"
                        @change="onFieldsChange"
                    ></FieldsInspector>
                    <p v-else-if="inspector.error">{{ inspector.error }}</p>
                    <div v-else-if="inspector.type === 'html'" class="cms-we__settings">
                        <p v-if="inspector.warning" class="cms-we__warning">{{ inspector.warning }}</p>
                        <div v-if="inspector.empty" class="cms-we__empty">
                            <i class="fa fa-i-cursor"></i>
                            <p>This widget has no settings. Click its text on the page to change it.</p>
                        </div>
                        <div ref="panelHost"></div>
                    </div>
                </div>
            </aside>
        </div>

        <WidgetDrawer
            :open="drawer.open"
            :term="term"
            :groups="groups"
            :catalogue="catalogue"
            @update:term="term = $event"
            @close="drawer.open = false"
            @insert="insert"
            @favourite="toggleFavourite"
        ></WidgetDrawer>

        <Modal v-if="rich.open" :title="'Edit ' + rich.field" @close="closeRich">
            <RichTextModal ref="richEditor" :value="rich.value" @save="saveRich" @dirty="richDirty = $event" @close="closeRich"></RichTextModal>
            <template #actions>
                <span v-if="!richDirty" class="cms-we__rich-note">No changes yet.</span>
                <button type="button" class="btn btn-primary btn-sm" :disabled="!richDirty" @mousedown.prevent="richEditor.save()">Save</button>
                <button type="button" class="btn btn-default btn-sm" @mousedown.prevent="closeRich">Cancel</button>
            </template>
        </Modal>

        <Modal v-if="discard.open" title="Discard changes" @close="discard.open = false">
            <p>Close the editor without saving your changes?</p>
            <template #actions>
                <button ref="discardKeep" type="button" class="btn btn-default btn-sm" @click="discard.open = false">Keep editing</button>
                <button type="button" class="btn btn-danger btn-sm" @click="confirmDiscard">Discard changes</button>
            </template>
        </Modal>

        <Modal v-if="confirm.open" title="Remove widget" @close="confirm.open = false">
            <p>Remove <strong>{{ confirm.label }}</strong> from {{ activeLabel }}?</p>
            <div v-if="confirm.children" class="alert alert--warning alert-warning">
                <p>The {{ confirm.children }} {{ confirm.children === 1 ? 'widget' : 'widgets' }} inside it will be removed too.</p>
            </div>
            <template #actions>
                <button type="button" class="btn btn-default btn-sm" @click="confirm.open = false">Cancel</button>
                <button ref="confirmButton" type="button" class="btn btn-danger btn-sm" @click="confirmRemove">Remove</button>
            </template>
        </Modal>
    </div>
</template>

<script>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

/** A refused change stays up this long, then goes away on its own */
const NOTICE_MS = 5000;
import { createEditor } from './editor.js';
import { organiserGroups } from './rules.js';
import { clearDraft, draftKey, loadDraft, saveDraft } from './draft.js';
import { serialiseFields } from './serialise.js';
import { placeOf, resolveNodeUid } from './canvas.js';
import { setAt, valueAt } from './fields.js';
import { placeKey } from './outline.js';
import OutlineNode from './OutlineNode.vue';
import Canvas from './Canvas.vue';
import Modal from './Modal.vue';
import RichTextModal from './RichTextModal.vue';
import FieldsInspector from './FieldsInspector.vue';
import WidgetDrawer from './WidgetDrawer.vue';
import { runLegacyCallback } from './legacy-callbacks.js';

export default {
    name: 'EditorApp',
        components: { Canvas, FieldsInspector, Modal, OutlineNode, RichTextModal, WidgetDrawer },
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
            stored: [],
            message: '',
            messageId: 0,
            canUndo: false,
            canRedo: false,
            count: 0,
            maxInstances: 0,
        });
        // callbacks, slug: the v1 scripts of the form in the inspector, and the widget they belong to.
        // upgraded: { uid, data } for a selection stored in an older shape, until its first edit.
        const inspector = reactive({ type: null, error: '', warning: '', slug: '', callbacks: null, empty: false, upgraded: null });
        // The HTML form lives in one element, which is moved between the inspector and the modal, so the values
        // typed in it are kept when it moves
        const formNode = document.createElement('div');
        formNode.className = 'cms-we__form';
        const panelHost = ref(null);
        /** Where the next insert lands. Set by an insert marker; `at` is null when it follows the selection. */
        const drawer = reactive({ open: false, at: null });
        const rich = reactive({ open: false, uid: null, field: '', value: '' });
        const richDirty = ref(false);
        const richEditor = ref(null);
        const confirm = reactive({ open: false, uid: null, label: '', children: 0 });
        const discard = reactive({ open: false });
        const discardKeep = ref(null);
        let baseline = null;
        let baselineData = null;
        const confirmButton = ref(null);
        /** Outline drag state, shared by every row */
        const drag = reactive({ uid: null, over: null, blocked: [], invalid: false });
        let noticeTimer = null;
        let errorTimer = null;
        const outlineOpen = ref(true);
        const inspectorOpen = ref(true);
        const folded = reactive({});
        const draftOffer = ref('');
        let editor = null;
        let draft = null;
        let applyingForm = false;

        const groups = computed(() => {
            const area = (props.session.areas || []).find((item) => item.slug === view.value.active);
            return organiserGroups(catalogue.value, area ? area.rules : {}, term.value);
        });

        const selectedNode = computed(() => findNode(view.value.nodes, view.value.selected));

        /** Field keys the canvas is editing in place for the selection. Those stay off the inspector. */
        const inlineKeys = ref([]);

        function onInlineFields(keys) {
            inlineKeys.value = Array.isArray(keys) ? keys : [];
        }

        // The fields schema of the selected widget, without fields already edited on the canvas
        const selectedFields = computed(() => {
            const node = selectedNode.value;
            const widget = node ? catalogue.value.widgets[node.slug] : null;
            const fields = widget && widget.inspector && Array.isArray(widget.inspector.fields)
                ? widget.inspector.fields
                : [];
            const hidden = new Set(inlineKeys.value);
            return fields.filter((field) => !hidden.has(field.key));
        });

        // Top-level instances of the active area, with their stored data, for the canvas
        const canvasItems = computed(() => view.value.nodes.map((node, index) => ({
            uid: node.uid,
            slug: node.slug,
            locked: node.locked,
            data: (view.value.stored[index] || {}).data || [],
        })));

        const canvasStylesheets = computed(() => catalogue.value.canvas_assets || []);

        const selectedWidget = computed(() => {
            const node = selectedNode.value;
            return node ? catalogue.value.widgets[node.slug] || null : null;
        });

        const selectedLabel = computed(() => {
            const node = selectedNode.value;
            if (!node) {
                return '';
            }
            return selectedWidget.value ? selectedWidget.value.label : node.slug;
        });

        const selectedDescription = computed(() => {
            const widget = selectedWidget.value;
            return widget ? widget.description || widget.summary || '' : '';
        });

        /** The widget hovered in either the outline or the canvas. Both draw it. */
        const hovered = ref(null);
        /** Bumped by an outline click, so the canvas scrolls that widget into view */
        const scrollTo = ref(0);
        const selectedPlace = computed(() => placeOf(view.value.nodes, view.value.selected));
        const hoverPlace = computed(() => placeOf(view.value.nodes, hovered.value));

        const activeLabel = computed(() => {
            const area = view.value.areas.find((item) => item.slug === view.value.active);
            return area ? area.label : '';
        });

        function labelFor(uid) {
            const node = findNode(view.value.nodes, uid);
            const widget = node ? catalogue.value.widgets[node.slug] : null;
            return widget ? widget.label : (node ? node.slug : '');
        }

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
            if (!baseline) {
                baseline = {};
                baselineData = {};
                editor.snapshot().areas.forEach((area) => {
                    const stored = editor.stored(area.slug);
                    baseline[area.slug] = JSON.stringify(stored);
                    baselineData[area.slug] = JSON.parse(baseline[area.slug]);
                });
            }
        }

        function isDirty() {
            if (!editor || !baseline) {
                return false;
            }
            return editor.snapshot().areas.some((area) => JSON.stringify(editor.stored(area.slug)) !== baseline[area.slug]);
        }

        /** Writes every area back to the data the editor opened with */
        function revertAreas() {
            if (!baselineData || typeof props.session.onChange !== 'function') {
                return;
            }
            Object.keys(baselineData).forEach((slug) => {
                props.session.onChange(slug, baselineData[slug]);
            });
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
            inspector.warning = '';
            inspector.callbacks = null;
            inspector.slug = '';
            inspector.empty = false;
            inspector.upgraded = null;
            const node = selectedNode.value;
            if (!node || node.locked) {
                return;
            }
            const widget = catalogue.value.widgets[node.slug];
            if (!widget) {
                return;
            }
            if (widget.inspector && widget.inspector.type === 'fields' && !widget.upgrades) {
                inspector.type = 'fields';
                return;
            }
            const uid = view.value.selected;

            const response = await fetch(endpoint('inspector'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ items: [{ slug: node.slug, data: node.data }] }),
            });
            const body = await response.json();
            if (view.value.selected !== uid) {
                return;
            }
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
                if (widget.upgrades && item.data && typeof item.data === 'object' && !Array.isArray(item.data)) {
                    inspector.upgraded = { uid, data: item.data };
                }
                inspector.type = 'fields';
                return;
            }
            inspector.type = 'html';
            await nextTick();
            applyingForm = true;
            // The form root is reused between widgets, so the last widget's handlers on it are removed first
            if (window.jQuery) {
                window.jQuery(formNode).off();
            }
            formNode.innerHTML = item.html || '';
            inspector.empty = !formNode.querySelector('input, select, textarea, button');
            (item.assets || []).forEach(mountAsset);
            applyingForm = false;
            await placeForm();

            // A v1 bridge's "dropped" script runs once the form is in the page, as it does in v1
            inspector.slug = node.slug;
            inspector.callbacks = item.callbacks || {};
            const warning = runLegacyCallback('dropped', inspector.callbacks.dropped, formNode);
            inspector.warning = warning ? 'This widget’s script could not run: ' + warning : '';
        }

        /** Removes the selected widget. A v1 bridge's "removed" script runs first, with the form it had. */
        function removeSelected() {
            const node = selectedNode.value;
            if (node && !node.locked && inspector.type === 'html' && inspector.slug === node.slug) {
                const warning = runLegacyCallback('removed', (inspector.callbacks || {}).removed, formNode);
                if (warning) {
                    console.warn('The removed script for ' + node.slug + ' failed: ' + warning);
                }
            }
            run('remove');
        }

        /** Puts the v1 form in the inspector */
        async function placeForm() {
            await nextTick();
            if (panelHost.value && formNode.parentNode !== panelHost.value) {
                panelHost.value.appendChild(formNode);
            }
        }

        function onFormEvent() {
            if (applyingForm || !view.value.selected) {
                return;
            }
            const fields = Array.from(formNode.querySelectorAll('input, select, textarea')).map((element) => ({
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
            const typing = event.target
                && (/^(INPUT|TEXTAREA|SELECT)$/.test(event.target.tagName) || event.target.isContentEditable);
            const meta = event.metaKey || event.ctrlKey;

            const inModal = event.target && event.target.closest && event.target.closest('.modal');
            if (event.key === 'Escape') {
                if (discard.open) {
                    discard.open = false;
                } else if (confirm.open) {
                    confirm.open = false;
                } else if (inModal || rich.open) {
                    if (rich.open) {
                        closeRich();
                    }
                } else if (drawer.open) {
                    drawer.open = false;
                } else if (view.value.selected) {
                    select(null);
                } else {
                    requestCancel();
                }
                return;
            }
            // While a modal is open its keys belong to it: Backspace in the rich text editor must not remove a widget
            if (typing || rich.open || confirm.open || discard.open || inModal || drawer.open) {
                return;
            }
            if (meta && event.key.toLowerCase() === 'z') {
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
            } else if ((event.key === 'Delete' || event.key === 'Backspace') && view.value.selected) {
                event.preventDefault();
                requestRemove(view.value.selected);
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

        /**
         * Writes one field of a node. Returns false when the value is unchanged, so a field the user did not
         * change is never written back (D19).
         */
        function writeField(uid, field, value) {
            const found = editor.locate(uid);
            if (!found || found.node.locked) {
                return false;
            }
            const current = editData(uid, found.node);
            if (JSON.stringify(valueAt(current, field)) === JSON.stringify(value)) {
                return false;
            }
            editor.update(uid, setAt(current, field, value), true);
            dropUpgraded(uid);
            return true;
        }

        /** The data an edit starts from: the upgraded data, the first time an instance in an older shape is edited */
        function editData(uid, node) {
            return inspector.upgraded && inspector.upgraded.uid === uid ? inspector.upgraded.data : (node.data || {});
        }

        /** Once edited, the instance holds the upgraded data, so its own data is used from then on */
        function dropUpgraded(uid) {
            if (inspector.upgraded && inspector.upgraded.uid === uid) {
                inspector.upgraded = null;
            }
        }

        /** What the fields inspector shows for the selection */
        const inspectorValues = computed(() => {
            const node = selectedNode.value;
            return node ? editData(view.value.selected, node) : {};
        });

        /**
         * One field changed in the fields inspector. It is merged with the node's current data, not with the data
         * the inspector was rendered from, so two quick changes cannot overwrite each other.
         */
        function onFieldsChange({ key, value }) {
            const uid = view.value.selected;
            const found = uid ? editor.locate(uid) : null;
            if (!found || found.node.locked) {
                return;
            }
            const current = editData(uid, found.node);
            if (JSON.stringify(current[key]) === JSON.stringify(value)) {
                return;
            }
            editor.update(uid, { ...current, [key]: value }, true);
            dropUpgraded(uid);
            refresh();
        }

        /** An inline text edit from the canvas. The canvas already shows the text, so it is not re-rendered. */
        function onCanvasEdit({ uid, path, field, value }) {
            const target = resolveNodeUid(view.value.nodes, uid, path);
            if (target && writeField(target, field, value)) {
                refresh();
            }
        }

        /** A click on a rich text field in the canvas opens it in the rich text modal */
        function onCanvasRich({ uid, path, field }) {
            const target = resolveNodeUid(view.value.nodes, uid, path);
            const found = target ? editor.locate(target) : null;
            if (!found || found.node.locked) {
                return;
            }
            rich.uid = target;
            rich.field = field;
            rich.value = String(valueAt(editData(target, found.node), field) ?? '');
            richDirty.value = false;
            rich.open = true;
        }

        function saveRich(html) {
            writeField(rich.uid, rich.field, html);
            closeRich();
            refresh();
        }

        function closeRich() {
            rich.open = false;
            rich.uid = null;
            rich.field = '';
            rich.value = '';
        }

        function setArea(slug) {
            editor.setActive(slug);
            refresh();
        }

        function select(uid) {
            editor.select(uid);
            refresh();
        }

        /** An outline click selects the widget and brings it into view on the canvas */
        function selectFromOutline(uid) {
            select(uid);
            scrollTo.value += 1;
        }

        function onOutlineHover(uid) {
            hovered.value = uid || null;
        }

        function onCanvasHover(target) {
            hovered.value = target
                ? (resolveNodeUid(view.value.nodes, target.uid, target.path) || target.uid)
                : null;
        }

        /** @param {?{parentUid: ?string, slot: ?string, index: number}} at Null to insert after the selection */
        function openDrawer(at) {
            drawer.at = at
                ? {
                    parentUid: at.parentUid || null,
                    slot: at.slot || null,
                    index: typeof at.index === 'number' ? at.index : view.value.nodes.length,
                }
                : null;
            drawer.open = true;
            term.value = '';
        }

        function onCanvasInsert(index) {
            openDrawer({ parentUid: null, slot: null, index });
        }

        /** A click in the canvas selects the instance under it, or the nested instance that owns the field */
        function onCanvasSelect(target) {
            if (!target) {
                select(null);
                return;
            }
            select(resolveNodeUid(view.value.nodes, target.uid, target.path) || target.uid);
        }

        function insert(slug) {
            const at = drawer.at ? { area: view.value.active, ...drawer.at } : undefined;
            editor.insert(slug, at);
            drawer.open = false;
            refresh();
        }

        function duplicateNode(uid) {
            editor.duplicate(uid);
            refresh();
        }

        /** Removal is confirmed first, even though it can be undone */
        function requestRemove(uid) {
            const node = findNode(view.value.nodes, uid);
            if (!node) {
                return;
            }
            confirm.uid = uid;
            confirm.label = labelFor(uid);
            confirm.children = countChildren(node);
            confirm.open = true;
            nextTick(() => {
                if (confirmButton.value) {
                    confirmButton.value.focus();
                }
            });
        }

        function confirmRemove() {
            const uid = confirm.uid;
            confirm.open = false;
            if (!uid) {
                return;
            }
            if (view.value.selected !== uid) {
                editor.select(uid);
                refresh();
            }
            removeSelected();
        }

        const tailKey = computed(() => placeKey(null, null, view.value.nodes.length));
        const tailActive = computed(() => Boolean(drag.uid) && drag.over === tailKey.value);
        const tailIcon = computed(() => {
            if (!tailActive.value) {
                return 'fa-plus';
            }
            return drag.invalid ? 'fa-ban' : 'fa-arrow-down';
        });
        const tailTitle = computed(() => {
            if (tailActive.value && drag.invalid) {
                return 'Cannot drop here';
            }
            return tailActive.value ? 'Move here' : 'Insert a widget here';
        });

        function markTail(event) {
            drag.over = tailKey.value;
            drag.invalid = false;
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
        }

        function dismissMessage() {
            window.clearTimeout(noticeTimer);
            if (editor) {
                editor.clearMessage();
            }
            if (view.value.message) {
                view.value = { ...view.value, message: '' };
            }
        }

        function dismissError() {
            window.clearTimeout(errorTimer);
            error.value = '';
        }

        function toggleFold(uid) {
            folded[uid] = !folded[uid];
        }

        /** Dropped on the outline below the last row: the widget goes to the end of the area */
        function onDropEnd() {
            const uid = drag.uid;
            drag.uid = null;
            drag.over = null;
            drag.blocked = [];
            drag.invalid = false;
            if (uid) {
                onMove({ uid, parentUid: null, slot: null, index: view.value.nodes.length });
            }
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

        function shutdown() {
            if (typeof props.session.onClose === 'function') {
                props.session.onClose();
            }
            if (window.NAILS_CMS_WIDGET_EDITOR) {
                window.NAILS_CMS_WIDGET_EDITOR.close();
            }
        }

        function close() {
            clearDraft(window.localStorage, storageKey.value);
            shutdown();
        }

        function requestCancel() {
            if (isDirty()) {
                discard.open = true;
                nextTick(() => {
                    if (discardKeep.value) {
                        discardKeep.value.focus();
                    }
                });
                return;
            }
            shutdown();
        }

        function confirmDiscard() {
            discard.open = false;
            revertAreas();
            clearDraft(window.localStorage, storageKey.value);
            shutdown();
        }

        watch(() => view.value.selected, () => {
            loadInspector();
        });

        watch(() => view.value.message, (message) => {
            window.clearTimeout(noticeTimer);
            if (message) {
                noticeTimer = window.setTimeout(dismissMessage, NOTICE_MS);
            }
        });

        watch(() => view.value.messageId, () => {
            if (!view.value.message) {
                return;
            }
            window.clearTimeout(noticeTimer);
            noticeTimer = window.setTimeout(dismissMessage, NOTICE_MS);
        });

        watch(error, (message) => {
            window.clearTimeout(errorTimer);
            if (message) {
                errorTimer = window.setTimeout(dismissError, NOTICE_MS);
            }
        });

        onMounted(async () => {
            window.addEventListener('keydown', onKey);
            formNode.addEventListener('input', onFormEvent);
            formNode.addEventListener('change', onFormEvent);
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
            window.clearTimeout(noticeTimer);
            window.clearTimeout(errorTimer);
            window.removeEventListener('keydown', onKey);
            formNode.removeEventListener('input', onFormEvent);
            formNode.removeEventListener('change', onFormEvent);
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
            inspectorValues,
            panelHost,
            drawer,
            rich,
            richDirty,
            richEditor,
            confirm,
            confirmButton,
            discard,
            discardKeep,
            drag,
            outlineOpen,
            inspectorOpen,
            folded,
            tailKey,
            toggleFold,
            onCanvasEdit,
            onCanvasRich,
            onCanvasInsert,
            onCanvasSelect,
            onCanvasHover,
            onOutlineHover,
            onFieldsChange,
            onInlineFields,
            selectedFields,
            selectedLabel,
            selectedWidget,
            selectedDescription,
            selectedPlace,
            hoverPlace,
            hovered,
            scrollTo,
            activeLabel,
            requestRemove,
            confirmRemove,
            onDropEnd,
            markTail,
            tailIcon,
            tailTitle,
            dismissMessage,
            dismissError,
            removeSelected,
            saveRich,
            closeRich,
            draftOffer,
            selectedNode,
            canvasItems,
            canvasStylesheets,
            endpoint,
            setArea,
            select,
            selectFromOutline,
            insert,
            openDrawer,
            duplicateNode,
            toggleFavourite,
            onMove,
            run,
            close,
            requestCancel,
            confirmDiscard,
            restoreDraft,
            discardDraft,
            onFormEvent,
        };
    },
};

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

function countChildren(node) {
    let total = 0;
    Object.values(node.slots || {}).forEach((children) => {
        children.forEach((child) => {
            total += 1 + countChildren(child);
        });
    });
    return total;
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

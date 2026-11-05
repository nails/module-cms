<template>
    <div v-if="field.type === 'repeater'" class="field">
        <span class="label">
            {{ field.label }}
            <span v-if="field.required" class="field-required" title="Required">*</span>
            <span v-if="field.tip" class="tip" :title="field.tip"><i class="fa fa-question-circle"></i></span>
        </span>
        <div class="cms-we__repeater">
            <div v-for="(row, index) in rows" :key="index" class="cms-we__row-item">
                <div class="cms-we__row-tools">
                    <strong>{{ field.label }} {{ index + 1 }}</strong>
                    <button type="button" class="btn btn-default btn-xs" :disabled="index === 0" @click="move(index, -1)">Up</button>
                    <button type="button" class="btn btn-default btn-xs" :disabled="index === rows.length - 1" @click="move(index, 1)">Down</button>
                    <button type="button" class="btn btn-default btn-xs" :disabled="!canRemove" @click="remove(index)">Remove</button>
                </div>
                <FieldControl
                    v-for="child in visibleFields(field.fields, row)"
                    :key="child.key"
                    :field="child"
                    :value="row[child.key]"
                    @update="setChild(index, child.key, $event)"
                ></FieldControl>
            </div>
            <button type="button" class="btn btn-default btn-sm" :disabled="!canAdd" @click="add">Add item</button>
            <p v-if="!canAdd && field.max !== null" class="cms-we__rich-note">The most is {{ field.max }}.</p>
            <p v-if="field.required && empty" class="cms-we__warning">Required. You can still save.</p>
        </div>
    </div>
    <div v-else class="field" :class="{ checkbox: field.type === 'toggle' }">
        <label>
            <span class="label">
                {{ field.label }}
                <span v-if="field.required" class="field-required" title="Required">*</span>
                <span v-if="field.tip" class="tip" :title="field.tip"><i class="fa fa-question-circle"></i></span>
            </span>
            <span class="input">
            <template v-if="field.type === 'richtext'">
                <p class="cms-we__rich-note">{{ summary || 'Nothing yet' }}</p>
                <button type="button" class="btn btn-default btn-sm" @click="openRich">Edit text</button>
                <Modal v-if="richOpen" :title="field.label" @close="richOpen = false">
                    <RichTextModal ref="richEditor" :value="value || ''" @save="saveRich" @dirty="richDirty = $event"></RichTextModal>
                    <template #actions>
                        <span v-if="!richDirty" class="cms-we__rich-note">No changes yet.</span>
                        <button type="button" class="btn btn-primary btn-sm" :disabled="!richDirty" @click="richEditor.save()">Save</button>
                        <button type="button" class="btn btn-default btn-sm" @click="richOpen = false">Cancel</button>
                    </template>
                </Modal>
            </template>

            <input
                v-else-if="field.type === 'text'"
                type="text"
                class="form-control"
                :maxlength="field.maxlength || undefined"
                :value="value ?? ''"
                @input="emit('update', $event.target.value)"
            >

            <textarea
                v-else-if="field.type === 'textarea'"
                class="form-control"
                rows="4"
                :maxlength="field.maxlength || undefined"
                :value="value ?? ''"
                @input="emit('update', $event.target.value)"
            ></textarea>

            <input
                v-else-if="field.type === 'number'"
                type="number"
                class="form-control"
                :min="field.min ?? undefined"
                :max="field.max ?? undefined"
                :value="value ?? ''"
                @input="emit('update', coerce(field, $event.target.value))"
            >

            <select
                v-else-if="field.type === 'select'"
                class="form-control"
                :value="value"
                @change="emit('update', $event.target.value)"
            >
                <option v-for="(label, key) in field.options" :key="key" :value="key">{{ label }}</option>
            </select>

            <input
                v-else-if="field.type === 'toggle'"
                type="checkbox"
                :checked="Boolean(value)"
                @change="emit('update', $event.target.checked)"
            >

            <small v-if="field.maxlength && (field.type === 'text' || field.type === 'textarea')" class="char-count" :data-max-length="field.maxlength">
                Max Length: {{ field.maxlength }}
            </small>

            <!--
                The CDN object picker. This is the server markup from module-cdn (cdn/views/picker.php). Only the hidden
                input is bound by Vue; the label and preview are filled in by the picker's own code, so they are left empty here.
            -->
            <template v-else-if="field.type === 'cdn_object'">
                <div ref="picker" class="cdn-object-picker" :data-bucket="field.bucket || null">
                    <input class="cdn-object-picker__input" type="hidden" :value="value ?? ''">
                    <button type="button" class="cdn-object-picker__btn btn btn-sm btn-primary">Browse</button>
                    <b class="cdn-object-picker__pending fa fa-spinner fa-spin"></b>
                    <div class="cdn-object-picker__label"></div>
                    <a class="cdn-object-picker__preview-link" href="#">
                        <div class="cdn-object-picker__preview"></div>
                    </a>
                    <b class="cdn-object-picker__remove fa fa-times-circle"></b>
                </div>
                <p v-if="!value" class="cms-we__rich-note">No file selected</p>
            </template>

            <p v-if="field.required && empty && field.type !== 'toggle'" class="cms-we__warning">Required. You can still save.</p>
            </span>
        </label>
    </div>
</template>

<script>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Modal from './Modal.vue';
import RichTextModal from './RichTextModal.vue';
import {
    addRow,
    canAddRow,
    canRemoveRow,
    coerce,
    isEmpty,
    moveRow,
    plainText,
    removeRow,
    setRowValue,
    visibleFields,
} from './fields.js';

export default {
    name: 'FieldControl',
    components: { Modal, RichTextModal },
    props: {
        /** A normalised field from the catalogue */
        field: { type: Object, required: true },
        /** The field's stored value */
        value: { type: null, default: undefined },
        /** Set when this control is already shown in the modal, so it is not wrapped again */
        inModal: { type: Boolean, default: false },
    },
    emits: ['update'],
    setup(props, { emit }) {
        const wideOpen = ref(false);
        const richOpen = ref(false);
        const richDirty = ref(false);
        const richEditor = ref(null);
        const picker = ref(null);

        const empty = computed(() => isEmpty(props.field, props.value));
        const rows = computed(() => (Array.isArray(props.value) ? props.value : []));

        // One line that stands in for a wide control in the inspector
        const summary = computed(() => {
            switch (props.field.type) {
                case 'repeater':
                    return rows.value.length === 1 ? '1 item' : rows.value.length + ' items';
                case 'richtext':
                    return plainText(props.value) || 'Nothing yet';
                case 'cdn_object':
                    return props.value ? 'Object #' + props.value : 'None selected';
                default:
                    return empty.value ? 'Nothing yet' : String(props.value);
            }
        });
        const canAdd = computed(() => canAddRow(props.field, rows.value));
        const canRemove = computed(() => canRemoveRow(props.field, rows.value));

        function add() {
            emit('update', addRow(props.field, rows.value));
        }

        function remove(index) {
            emit('update', removeRow(props.field, rows.value, index));
        }

        function move(index, delta) {
            emit('update', moveRow(rows.value, index, delta));
        }

        function setChild(index, key, value) {
            emit('update', setRowValue(rows.value, index, key, value));
        }

        function openRich() {
            richDirty.value = false;
            richOpen.value = true;
        }

        function saveRich(html) {
            richOpen.value = false;
            emit('update', html);
        }

        // The picker's own handlers (module-cdn ObjectPicker) set the hidden input and trigger these events.
        // Listening through jQuery is the only way to see them, because they are not DOM events.
        let onPicked = null;
        // Set while the value is changed from outside, so the picker's own events are not written back as an edit
        let syncing = false;

        /**
         * Brings the picker in line with the stored value: fetches the object's name and thumbnail when there is
         * one, or clears the picker when the value was emptied from outside (e.g. the row above was removed).
         */
        function syncPicker() {
            const $ = window.jQuery;
            const el = picker.value;
            if (!$ || !el) {
                return;
            }
            const input = el.querySelector('.cdn-object-picker__input');
            const wanted = props.value ? String(props.value) : '';

            if (wanted) {
                syncing = true;
                $(el).trigger('refresh');
                syncing = false;
            } else if (input && input.value !== '') {
                syncing = true;
                $(el).find('.cdn-object-picker__remove').trigger('click');
                syncing = false;
            }
        }

        onMounted(() => {
            if (props.field.type !== 'cdn_object' || !picker.value || !window.jQuery) {
                return;
            }
            onPicked = () => {
                if (syncing) {
                    return;
                }
                const input = picker.value.querySelector('.cdn-object-picker__input');
                emit('update', coerce(props.field, input ? input.value : null));
            };
            window.jQuery(picker.value).on('picked reset', onPicked);
            syncPicker();
        });

        watch(
            () => props.value,
            () => {
                if (props.field.type !== 'cdn_object' || !picker.value) {
                    return;
                }
                const input = picker.value.querySelector('.cdn-object-picker__input');
                const wanted = props.value ? String(props.value) : '';
                if (input && input.value !== wanted) {
                    syncPicker();
                }
            }
        );

        onBeforeUnmount(() => {
            if (onPicked && picker.value && window.jQuery) {
                window.jQuery(picker.value).off('picked reset', onPicked);
            }
        });

        return {
            wideOpen,
            richOpen,
            richDirty,
            richEditor,
            openRich,
            picker,
            summary,
            empty,
            rows,
            canAdd,
            canRemove,
            add,
            remove,
            move,
            setChild,
            saveRich,
            emit,
            coerce,
            visibleFields,
        };
    },
};
</script>

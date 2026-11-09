<template>
    <component :is="definition.component" v-if="inModal && definition" :field="field" :value="value" @update="onUpdate"></component>
    <div v-else class="field" :class="definition && definition.className">
        <component :is="wrapper">
            <span class="label">
                {{ field.label }}
                <span v-if="field.required" class="field-required" title="Required">*</span>
                <span v-if="field.tip" class="tip">
                    <span class="hint--right hint--medium" :aria-label="field.tip"><i class="fa fa-question-circle"></i></span>
                </span>
                <small v-if="field.subtitle" class="cms-we__subtitle">{{ field.subtitle }}</small>
            </span>
            <span class="input">
                <p v-if="!definition" class="cms-we__warning">
                    No control is registered for the field type "{{ field.type }}".
                </p>

                <template v-else-if="collapsed">
                    <p class="cms-we__summary">{{ oneLine }}</p>
                    <button type="button" class="btn btn-default btn-sm cms-we__edit" @click="wideOpen = true">
                        <i class="fa fa-pencil-alt"></i> Edit
                    </button>
                    <Modal v-if="wideOpen" :title="field.label" @close="wideOpen = false">
                        <FieldControl :field="field" :value="value" in-modal @update="emit('update', $event)"></FieldControl>
                        <template #actions>
                            <button type="button" class="btn btn-primary btn-sm" @mousedown.prevent="wideOpen = false">Done</button>
                        </template>
                    </Modal>
                </template>

                <component :is="definition.component" v-else :field="field" :value="value" @update="onUpdate"></component>
            </span>
        </component>
        <small v-if="field.info" class="info" :class="field.infoClass">{{ field.info }}</small>
    </div>
</template>

<script>
import { computed, ref } from 'vue';
import Modal from './Modal.vue';
import { fieldType } from './field-types.js';
import { coerce, summary } from './fields.js';

/**
 * One field in the inspector: its label, tip and info, around the control registered for its type
 * (see field-types.js). A wide field shows a short summary here and its control, without the label, in a modal.
 */
export default {
    name: 'FieldControl',
    components: { Modal },
    props: {
        /** A field from the catalogue */
        field: { type: Object, required: true },
        /** The field's stored value */
        value: { type: null, default: undefined },
        /** Set when this control is already shown in the modal, so only the control is drawn */
        inModal: { type: Boolean, default: false },
    },
    emits: ['update'],
    setup(props, { emit }) {
        const wideOpen = ref(false);
        const definition = computed(() => fieldType(props.field.type));
        const collapsed = computed(() => Boolean(props.field.wide && definition.value?.wide && !props.inModal));
        // A <label> forwards clicks to its first control, which would steal clicks from a control with several
        // parts, or from a modal opened inside it
        const wrapper = computed(() => (
            !definition.value || collapsed.value || definition.value.layout === 'block' ? 'div' : 'label'
        ));
        const oneLine = computed(() => summary(props.field, props.value));

        function onUpdate(raw) {
            emit('update', coerce(props.field, raw));
        }

        return { definition, collapsed, wrapper, wideOpen, oneLine, onUpdate, emit };
    },
};
</script>

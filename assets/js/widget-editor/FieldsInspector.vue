<template>
    <div class="cms-we__fields">
        <FieldControl
            v-for="field in shown"
            :key="field.key"
            :field="field"
            :value="values[field.key]"
            @update="onUpdate(field.key, $event)"
        ></FieldControl>

        <p v-if="missing.length" class="cms-we__warning">
            Still empty: {{ labels }}. You can still save.
        </p>
    </div>
</template>

<script>
import { computed } from 'vue';
import FieldControl from './FieldControl.vue';
import { missingRequired, visibleFields } from './fields.js';

export default {
    name: 'FieldsInspector',
    components: { FieldControl },
    props: {
        /** Normalised fields from the catalogue */
        fields: { type: Array, default: () => [] },
        /** The selected widget's data */
        values: { type: Object, default: () => ({}) },
    },
    emits: ['change'], // ({key, value}) one field changed
    setup(props, { emit }) {
        const shown = computed(() => visibleFields(props.fields, props.values));
        const missing = computed(() => missingRequired(props.fields, props.values));
        const labels = computed(() => missing.value.map((item) => item.label).join(', '));

        // A single-key patch, merged with the widget's current data by the editor (not with props.values)
        function onUpdate(key, value) {
            emit('change', { key, value });
        }

        return { shown, missing, labels, onUpdate };
    },
};
</script>

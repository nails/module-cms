<template>
    <select ref="select" class="cms-we__select" :value="value ?? ''" @change="onChange">
        <option v-for="key in keys" :key="key" :value="key">{{ field.options[key] }}</option>
    </select>
</template>

<script>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { optionKeys } from '../fields.js';

/**
 * A drop-down. Admin's page has Select2, so the control uses it; without it the select is left as it is.
 */
export default {
    name: 'SelectControl',
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
    setup(props, { emit }) {
        const select = ref(null);
        const keys = computed(() => optionKeys(props.field));
        // Set while the value is applied from outside, so Select2's own change is not written back
        let syncing = false;

        function jquery() {
            const $ = window.jQuery;
            return $ && $.fn && $.fn.select2 ? $ : null;
        }

        function onChange(event) {
            if (syncing) {
                return;
            }
            const element = select.value;
            emit('update', element ? element.value : event.target.value);
        }

        onMounted(() => {
            const $ = jquery();
            const element = select.value;
            if (!$ || !element) {
                return;
            }
            // Select2 reports the choice with jQuery's change. Vue's listener is the native event, which that does not fire,
            // so the drop-down can show a choice the field has not stored.
            $(element).on('change', onChange);
            syncing = true;
            $(element).select2({
                width: '100%',
                // A short list has no search box. The blank line it leaves reads as a broken field.
                minimumResultsForSearch: Infinity,
                dropdownCssClass: 'cms-we-select-drop',
            });
            syncing = false;
        });

        watch(
            () => props.value,
            (value) => {
                const $ = jquery();
                const element = select.value;
                if (!$ || !element || !$(element).data('select2')) {
                    return;
                }
                const next = value == null ? '' : String(value);
                if (element.value === next) {
                    return;
                }
                syncing = true;
                $(element).select2('val', next);
                syncing = false;
            }
        );

        onBeforeUnmount(() => {
            const $ = jquery();
            const element = select.value;
            if (!$ || !element) {
                return;
            }
            syncing = true;
            $(element).off('change', onChange);
            if ($(element).data('select2')) {
                $(element).select2('destroy');
            }
            syncing = false;
        });

        return { select, keys, onChange };
    },
};
</script>

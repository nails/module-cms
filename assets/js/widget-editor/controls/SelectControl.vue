<template>
    <select ref="select" class="cms-we__select" :value="value ?? ''" @change="onChange">
        <option v-for="(label, key) in field.options" :key="key" :value="key">{{ label }}</option>
    </select>
</template>

<script>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
        // Set while the value is applied from outside, so Select2's own change is not written back
        let syncing = false;

        function jquery() {
            const $ = window.jQuery;
            return $ && $.fn && $.fn.select2 ? $ : null;
        }

        function onChange(event) {
            if (!syncing) {
                emit('update', event.target.value);
            }
        }

        onMounted(() => {
            const $ = jquery();
            if (!$ || !select.value) {
                return;
            }
            syncing = true;
            $(select.value).select2({
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
            if ($ && element && $(element).data('select2')) {
                $(element).select2('destroy');
            }
        });

        return { select, onChange };
    },
};
</script>

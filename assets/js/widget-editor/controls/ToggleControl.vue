<template>
    <span class="admin-toggle" :class="{ 'admin-toggle--on': on }" aria-hidden="true">
        <span class="admin-toggle__thumb"></span>
    </span>
    <input class="admin-toggle__input" type="checkbox" :checked="on" tabindex="-1" @change="$emit('update', $event.target.checked)">
</template>

<script>
import { computed } from 'vue';

/**
 * The admin iOS-style switch. The checkbox is the control; the track is the admin-toggle chrome, and the field's
 * label (the wrapper around this control) toggles it.
 */
export default {
    name: 'ToggleControl',
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
    setup(props) {
        const on = computed(() => Boolean(props.value));
        return { on };
    },
};
</script>

<template>
    <input
        type="number"
        class="form-control"
        step="any"
        :min="field.min ?? undefined"
        :max="field.max ?? undefined"
        :value="value ?? ''"
        @input="$emit('update', $event.target.value)"
    >
</template>

<script>
export default {
    name: 'NumberControl',
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
};
</script>

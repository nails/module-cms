<template>
    <input
        type="text"
        class="form-control"
        :maxlength="field.maxlength || undefined"
        :value="value ?? ''"
        @input="$emit('update', $event.target.value)"
    >
    <small v-if="field.maxlength" class="cms-we__count">{{ (value ?? '').length }} / {{ field.maxlength }}</small>
</template>

<script>
export default {
    name: 'TextControl',
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
};
</script>

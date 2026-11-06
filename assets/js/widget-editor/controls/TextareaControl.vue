<template>
    <textarea
        class="form-control"
        rows="4"
        :maxlength="field.maxlength || undefined"
        :value="value ?? ''"
        @input="$emit('update', $event.target.value)"
    ></textarea>
    <small v-if="field.maxlength" class="cms-we__count">{{ (value ?? '').length }} / {{ field.maxlength }}</small>
</template>

<script>
export default {
    name: 'TextareaControl',
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
};
</script>

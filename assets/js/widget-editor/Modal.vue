<template>
    <div class="modal modal--open" role="dialog" aria-modal="true" :aria-label="title">
        <div class="modal__inner">
            <div class="modal__close" role="button" tabindex="0" aria-label="Close" @click="$emit('close')">&times;</div>
            <div class="modal__title">{{ title }}</div>
            <div class="modal__body">
                <slot></slot>
            </div>
            <div v-if="$slots.actions" class="modal__actions">
                <slot name="actions"></slot>
            </div>
        </div>
    </div>
</template>

<script>
/**
 * Admin's modal (module-admin `.modal`). It closes from its own control, so a click on the page behind it
 * does not discard what is being edited.
 */
export default {
    name: 'Modal',
    props: {
        title: { type: String, required: true },
    },
    emits: ['close'],
};
</script>

<template>
    <div class="cms-we__modal-backdrop">
        <div class="cms-we__modal" role="dialog" aria-modal="true" :aria-label="title">
            <header class="cms-we__modal-head">
                <strong>{{ title }}</strong>
                <button type="button" class="cms-we__tool" @click="$emit('close')">Close</button>
            </header>
            <div class="cms-we__modal-body">
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script>
/**
 * A dialog over the editor. It closes only from its own controls, so an unsaved change is not lost to a stray
 * click on the backdrop.
 */
export default {
    name: 'Modal',
    props: {
        title: { type: String, required: true },
    },
    emits: ['close'],
};
</script>

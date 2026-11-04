<template>
    <div v-if="open" class="cms-we-drawer">
        <div class="cms-we-drawer__backdrop" @click="$emit('close')"></div>
        <div class="cms-we-drawer__panel" role="dialog" aria-label="Insert widget">
            <header class="cms-we-drawer__head">
                <h2>Insert widget</h2>
                <button type="button" class="btn btn-default btn-sm" @click="$emit('close')">Close</button>
            </header>
            <div class="cms-we-drawer__search">
                <input
                    ref="search"
                    :value="term"
                    class="form-control"
                    type="search"
                    placeholder="Search widgets"
                    @input="$emit('update:term', $event.target.value)"
                >
            </div>
            <div class="cms-we-drawer__body">
                <section v-for="group in groups" :key="group.label" class="cms-we-drawer__group">
                    <h3>{{ group.label }}</h3>
                    <button
                        v-for="slug in group.slugs"
                        :key="group.label + slug"
                        type="button"
                        class="cms-we-drawer__widget"
                        @click="$emit('insert', slug)"
                    >
                        <i v-if="icon(slug)" :class="['fa', icon(slug)]"></i>
                        <span>
                            <strong>{{ catalogue.widgets[slug].label }}</strong>
                            <small v-if="catalogue.widgets[slug].summary">{{ catalogue.widgets[slug].summary }}</small>
                        </span>
                        <i
                            :class="['fa', 'fa-star', 'cms-we-drawer__pin', { 'is-on': catalogue.favourites.includes(slug) }]"
                            :title="catalogue.favourites.includes(slug) ? 'Remove from favourites' : 'Add to favourites'"
                            @click.stop="$emit('favourite', slug)"
                        ></i>
                    </button>
                </section>
                <p v-if="term && !groups.length" class="cms-we-drawer__empty">No widgets match “{{ term }}”.</p>
            </div>
        </div>
    </div>
</template>

<script>
import { nextTick, ref, watch } from 'vue';

export default {
    name: 'WidgetDrawer',
    props: {
        open: { type: Boolean, default: false },
        term: { type: String, default: '' },
        groups: { type: Array, default: () => [] },
        catalogue: { type: Object, required: true },
    },
    emits: ['close', 'insert', 'favourite', 'update:term'],
    setup(props) {
        const search = ref(null);

        watch(() => props.open, (open) => {
            if (open) {
                nextTick(() => {
                    if (search.value) {
                        search.value.focus();
                    }
                });
            }
        });

        function icon(slug) {
            const widget = props.catalogue.widgets[slug];
            return widget && widget.icon ? widget.icon : '';
        }

        return { search, icon };
    },
};
</script>

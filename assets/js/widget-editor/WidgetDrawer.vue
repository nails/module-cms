<template>
    <div class="cms-we-drawer" :class="{ 'is-open': open }" :aria-hidden="open ? 'false' : 'true'">
        <div class="cms-we-drawer__backdrop" @click="$emit('close')"></div>
        <div class="cms-we-drawer__panel" role="dialog" aria-label="Insert widget">
            <header class="cms-we-drawer__head">
                <h2>Insert widget</h2>
                <button type="button" class="cms-we-drawer__close" aria-label="Close" @click="$emit('close')">
                    <i class="fa fa-xmark"></i>
                </button>
            </header>
            <div class="cms-we-drawer__search">
                <i class="fa fa-magnifying-glass"></i>
                <input
                    ref="search"
                    :value="term"
                    type="search"
                    placeholder="Search widgets"
                    @input="$emit('update:term', $event.target.value)"
                    @keydown.enter.prevent="insertFirst"
                >
            </div>
            <div class="cms-we-drawer__body" @scroll="peek = null">
                <section v-for="group in groups" :key="group.label" class="cms-we-drawer__group">
                    <h3>{{ group.label }}</h3>
                    <button
                        v-for="slug in group.slugs"
                        :key="group.label + slug"
                        type="button"
                        class="cms-we-drawer__widget"
                        @click="$emit('insert', slug)"
                        @mouseenter="showPeek(slug, group.label, $event)"
                        @mouseleave="peek = null"
                    >
                        <span class="cms-we-drawer__icon"><i :class="['fa', icon(slug)]"></i></span>
                        <span class="cms-we-drawer__text">
                            <strong>{{ widget(slug).label }}</strong>
                            <small>{{ widget(slug).summary || widget(slug).description || '\u00a0' }}</small>
                        </span>
                        <i
                            :class="['fa', 'fa-star', 'cms-we-drawer__pin', { 'is-on': isFavourite(slug) }]"
                            :title="isFavourite(slug) ? 'Remove from favourites' : 'Add to favourites'"
                            @click.stop="$emit('favourite', slug)"
                        ></i>
                    </button>
                </section>
                <p v-if="!groups.length" class="cms-we-drawer__empty">
                    {{ term ? 'No widgets match “' + term + '”.' : 'No widgets can be added here.' }}
                </p>
            </div>
        </div>

        <div v-if="open && peek" class="cms-we-drawer__peek" :style="{ top: peek.top + 'px' }">
            <div v-if="peek.screenshot" class="cms-we-drawer__peek-shot">
                <img :src="peek.screenshot" :alt="peek.label">
            </div>
            <div v-else class="cms-we-drawer__peek-none"><i :class="['fa', peek.icon]"></i></div>
            <div class="cms-we-drawer__peek-body">
                <p class="cms-we-drawer__peek-kicker">{{ peek.group }}</p>
                <strong>{{ peek.label }}</strong>
                <small v-if="peek.description">{{ peek.description }}</small>
            </div>
        </div>
    </div>
</template>

<script>
import { nextTick, ref, watch } from 'vue';

/** Room the hover card needs below its row before it is moved up */
const PEEK_HEIGHT = 320;

export default {
    name: 'WidgetDrawer',
    props: {
        open: { type: Boolean, default: false },
        term: { type: String, default: '' },
        groups: { type: Array, default: () => [] },
        catalogue: { type: Object, required: true },
    },
    emits: ['close', 'insert', 'favourite', 'update:term'],
    setup(props, { emit }) {
        const search = ref(null);
        const peek = ref(null);

        watch(() => props.open, (open) => {
            peek.value = null;
            if (open) {
                nextTick(() => {
                    if (search.value) {
                        search.value.focus();
                    }
                });
            }
        });

        function widget(slug) {
            return props.catalogue.widgets[slug] || { label: slug };
        }

        function icon(slug) {
            return widget(slug).icon || 'fa-cube';
        }

        function isFavourite(slug) {
            return (props.catalogue.favourites || []).includes(slug);
        }

        function showPeek(slug, group, event) {
            const item = widget(slug);
            const shots = Array.isArray(item.screenshots) ? item.screenshots : [];
            const root = event.currentTarget.closest('.cms-we-drawer').getBoundingClientRect();
            const row = event.currentTarget.getBoundingClientRect();
            peek.value = {
                label: item.label,
                description: item.description || item.summary || '',
                icon: icon(slug),
                group,
                screenshot: shots.length ? shots[0].detail || shots[0].list : '',
                top: Math.max(12, Math.min(row.top - root.top, root.height - PEEK_HEIGHT)),
            };
        }

        function insertFirst() {
            const first = props.groups.find((group) => group.slugs.length);
            if (first) {
                emit('insert', first.slugs[0]);
            }
        }

        return { search, peek, widget, icon, isFavourite, showPeek, insertFirst };
    },
};
</script>

<template>
    <div class="cms-we__repeater">
        <button v-if="!rows.length" type="button" class="cms-we__repeater-empty" :disabled="!canAdd" @click="add">
            <i class="fa fa-plus"></i>
            <span>Add an item</span>
        </button>
        <div v-for="(row, index) in rows" :key="index" class="cms-we__row-item" :class="{ 'is-collapsed': isCollapsed(index) }">
            <div class="cms-we__row-bar">
                <button type="button" class="cms-we__row-toggle" :aria-expanded="!isCollapsed(index)" :title="isCollapsed(index) ? 'Expand' : 'Collapse'" @click="toggle(index)">
                    <i class="fa cms-we__row-chevron" :class="isCollapsed(index) ? 'fa-chevron-right' : 'fa-chevron-down'" aria-hidden="true"></i>
                    <strong>{{ rowTitle(field, row, index) }}</strong>
                </button>
                <span class="cms-we__row-actions">
                    <button type="button" title="Move up" :disabled="index === 0" @click="move(index, -1)">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                    <button type="button" title="Move down" :disabled="index === rows.length - 1" @click="move(index, 1)">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                    <button type="button" title="Remove" :disabled="!canRemove" @click="remove(index)">
                        <i class="fa fa-trash"></i>
                    </button>
                </span>
            </div>
            <div v-show="!isCollapsed(index)">
                <FieldControl
                    v-for="child in visibleFields(field.fields, row)"
                    :key="child.key"
                    :field="child"
                    :value="row[child.key]"
                    @update="setChild(index, child.key, $event)"
                ></FieldControl>
            </div>
        </div>
        <button v-if="rows.length" type="button" class="btn btn-default btn-sm cms-we__repeater-add" :disabled="!canAdd" @click="add">
            <i class="fa fa-plus"></i> Add item
        </button>
        <p v-if="!canAdd && field.max !== null && rows.length" class="cms-we__rich-note">The most is {{ field.max }}.</p>
    </div>
</template>

<script>
import { computed, ref } from 'vue';
import FieldControl from '../FieldControl.vue';
import {
    addRow,
    canAddRow,
    canRemoveRow,
    moveRow,
    removeRow,
    rowTitle,
    setRowValue,
    visibleFields,
} from '../fields.js';

/**
 * A list of rows. Each row's fields are drawn by FieldControl, so a row can hold any registered type except
 * another repeater. The heading is the first field's text, and clicking it collapses the row. With no minimum,
 * the last row can be removed.
 */
export default {
    name: 'RepeaterControl',
    components: { FieldControl },
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
    setup(props, { emit }) {
        const rows = computed(() => (Array.isArray(props.value) ? props.value : []));
        const canAdd = computed(() => canAddRow(props.field, rows.value));
        const canRemove = computed(() => canRemoveRow(props.field, rows.value));
        // Index => collapsed. Indexes shift with the rows they belong to.
        const collapsed = ref({});

        function isCollapsed(index) {
            return Boolean(collapsed.value[index]);
        }

        function toggle(index) {
            collapsed.value = { ...collapsed.value, [index]: !collapsed.value[index] };
        }

        function add() {
            emit('update', addRow(props.field, rows.value));
        }

        function remove(index) {
            const next = {};
            Object.keys(collapsed.value).forEach((key) => {
                const at = Number(key);
                if (at < index) {
                    next[at] = collapsed.value[at];
                } else if (at > index) {
                    next[at - 1] = collapsed.value[at];
                }
            });
            collapsed.value = next;
            emit('update', removeRow(props.field, rows.value, index));
        }

        function move(index, delta) {
            const other = index + delta;
            const next = { ...collapsed.value };
            const here = !!next[index];
            const there = !!next[other];
            next[index] = there;
            next[other] = here;
            collapsed.value = next;
            emit('update', moveRow(rows.value, index, delta));
        }

        return {
            rows,
            canAdd,
            canRemove,
            visibleFields,
            rowTitle,
            isCollapsed,
            toggle,
            add,
            remove,
            move,
            setChild: (index, key, value) => emit('update', setRowValue(rows.value, index, key, value)),
        };
    },
};
</script>

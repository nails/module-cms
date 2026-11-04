<template>
    <li class="cms-we-outline__node">
        <div
            class="cms-we-outline__item"
            :class="{
                'is-selected': node.uid === selected,
                'is-dragging': drag.uid === node.uid,
                'is-drop-before': dropEdge === 'before',
                'is-drop-after': dropEdge === 'after',
            }"
            :style="{ paddingLeft: indent + 'px' }"
            draggable="true"
            @click="$emit('select', node.uid)"
            @dragstart="onDragStart"
            @dragend="onDragEnd"
            @dragover.prevent="onDragOver"
            @drop.prevent.stop="onDrop"
        >
            <span class="cms-we-outline__num">{{ depth === 0 ? pad(index + 1) : '' }}</span>
            <span class="cms-we-outline__text">
                <strong>{{ label }}</strong>
                <small>{{ summary || '\u00a0' }}</small>
            </span>
            <span class="cms-we-outline__actions">
                <i v-if="node.locked" class="fa fa-lock" title="Kept as saved"></i>
                <button type="button" title="Duplicate" @click.stop="$emit('duplicate', node.uid)">
                    <i class="fa fa-clone"></i>
                </button>
                <button type="button" class="is-danger" title="Remove" @click.stop="$emit('remove', node.uid)">
                    <i class="fa fa-trash"></i>
                </button>
                <i class="fa fa-grip-vertical cms-we-outline__grip" title="Drag to reorder"></i>
            </span>
        </div>

        <div v-for="(children, slot) in node.slots" :key="slot" class="cms-we-outline__slot">
            <div class="cms-we-outline__slot-label" :style="{ paddingLeft: (indent + 34) + 'px' }">{{ slotLabel(slot) }}</div>
            <ol v-if="children.length" class="cms-we-outline__list">
                <OutlineNode
                    v-for="(child, childIndex) in children"
                    :key="child.uid"
                    :node="child"
                    :index="childIndex"
                    :depth="depth + 1"
                    :selected="selected"
                    :catalogue="catalogue"
                    :drag="drag"
                    :parent-uid="node.uid"
                    :slot-name="slot"
                    @select="$emit('select', $event)"
                    @move="$emit('move', $event)"
                    @insert="$emit('insert', $event)"
                    @duplicate="$emit('duplicate', $event)"
                    @remove="$emit('remove', $event)"
                />
            </ol>
            <button
                type="button"
                class="cms-we-outline__add"
                :class="{ 'is-drop': drag.uid && drag.over === node.uid + ':' + slot }"
                :style="{ paddingLeft: (indent + 34) + 'px' }"
                @click.stop="$emit('insert', { parentUid: node.uid, slot, index: children.length })"
                @dragover.prevent="drag.over = node.uid + ':' + slot"
                @drop.prevent.stop="onDropSlot(slot, children.length)"
            >
                <i class="fa fa-plus"></i> Add to {{ slotLabel(slot) }}
            </button>
        </div>
    </li>
</template>

<script>
import { computed } from 'vue';
import { dropTarget, pad, snippet } from './outline.js';

export default {
    name: 'OutlineNode',
    props: {
        node: { type: Object, required: true },
        index: { type: Number, required: true },
        depth: { type: Number, default: 0 },
        selected: { type: String, default: null },
        catalogue: { type: Object, required: true },
        /** Shared by every row: {uid: the row being dragged, over: 'uid:edge' or 'uid:slot' under the pointer} */
        drag: { type: Object, required: true },
        parentUid: { type: String, default: null },
        slotName: { type: String, default: null },
    },
    emits: ['select', 'move', 'insert', 'duplicate', 'remove'],
    setup(props, { emit }) {
        const widget = computed(() => props.catalogue.widgets[props.node.slug] || null);
        const label = computed(() => (widget.value ? widget.value.label : props.node.slug));
        const summary = computed(() => {
            const text = snippet(props.node.data);
            if (text !== '' || !props.node.slots) {
                return text;
            }
            const inside = Object.values(props.node.slots).reduce((total, children) => total + children.length, 0);
            return inside === 1 ? '1 widget inside' : inside + ' widgets inside';
        });
        const indent = computed(() => 16 + props.depth * 14);

        const dropEdge = computed(() => {
            if (!props.drag.uid || props.drag.uid === props.node.uid) {
                return null;
            }
            if (props.drag.over === props.node.uid + ':before') {
                return 'before';
            }
            return props.drag.over === props.node.uid + ':after' ? 'after' : null;
        });

        function slotLabel(slot) {
            const defined = widget.value && widget.value.slots ? widget.value.slots[slot] : null;
            return defined && defined.label ? defined.label : slot;
        }

        function onDragStart(event) {
            event.stopPropagation();
            event.dataTransfer.setData('text/plain', props.node.uid);
            event.dataTransfer.effectAllowed = 'move';
            props.drag.uid = props.node.uid;
        }

        function onDragEnd() {
            props.drag.uid = null;
            props.drag.over = null;
        }

        function onDragOver(event) {
            if (!props.drag.uid || props.drag.uid === props.node.uid) {
                return;
            }
            const rect = event.currentTarget.getBoundingClientRect();
            const target = dropTarget(props.index, event.clientY - rect.top, rect.height);
            props.drag.over = props.node.uid + ':' + target.edge;
        }

        function onDrop(event) {
            const uid = props.drag.uid;
            onDragEnd();
            if (!uid || uid === props.node.uid) {
                return;
            }
            const rect = event.currentTarget.getBoundingClientRect();
            const target = dropTarget(props.index, event.clientY - rect.top, rect.height);
            emit('move', { uid, parentUid: props.parentUid, slot: props.slotName, index: target.index });
        }

        function onDropSlot(slot, index) {
            const uid = props.drag.uid;
            onDragEnd();
            if (uid && uid !== props.node.uid) {
                emit('move', { uid, parentUid: props.node.uid, slot, index });
            }
        }

        return { label, summary, indent, dropEdge, slotLabel, pad, onDragStart, onDragEnd, onDragOver, onDrop, onDropSlot };
    },
};
</script>

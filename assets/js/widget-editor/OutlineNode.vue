<template>
    <li class="cms-we-outline__node">
        <button
            type="button"
            class="cms-we-outline__gap"
            :class="{ 'is-drop': marked(beforeKey), 'is-nested': depth > 0 }"
            :style="{ left: depth === 0 ? '8px' : '0' }"
            title="Insert a widget here"
            @click.stop="insertAt(parentUid, slotName, index)"
            @dragover.prevent="mark(parentUid, slotName, index)"
            @drop.prevent.stop="dropAt(parentUid, slotName, index)"
        >
            <i class="fa fa-plus"></i>
        </button>

        <div
            class="cms-we-outline__item"
            :class="{
                'is-selected': node.uid === selected,
                'is-dragging': drag.uid === node.uid,
                'is-nested': depth > 0,
            }"
            draggable="true"
            @click="$emit('select', node.uid)"
            @dragstart="onDragStart"
            @dragend="onDragEnd"
            @dragover.prevent="onDragOver"
            @drop.prevent.stop="onDrop"
        >
            <span v-if="depth === 0" class="cms-we-outline__num">{{ pad(index + 1) }}</span>
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

        <div
            v-for="(children, slot) in node.slots"
            :key="slot"
            class="cms-we-outline__slot"
            :class="{ 'is-folded': slotFolded(slot) }"
            :style="{ '--guide': guide }"
        >
            <div class="cms-we-outline__slot-body">
                <button
                    type="button"
                    class="cms-we-outline__slot-label"
                    :class="{ 'is-drop': slotFolded(slot) && marked(placeKey(node.uid, slot, 0)) }"
                    :title="slotFolded(slot) ? 'Expand' : 'Collapse'"
                    @click.stop="$emit('toggle', slotKey(slot))"
                    @dragover.prevent="mark(node.uid, slot, 0)"
                    @drop.prevent.stop="dropAt(node.uid, slot, 0)"
                >
                    <i class="fa fa-chevron-right cms-we-outline__caret" :class="{ 'is-open': !slotFolded(slot) }"></i>
                    {{ slotLabel(slot) }}
                </button>
                <template v-if="!slotFolded(slot)">
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
                            :folded-ids="foldedIds"
                            :parent-uid="node.uid"
                            :slot-name="slot"
                            @select="$emit('select', $event)"
                            @toggle="$emit('toggle', $event)"
                            @move="$emit('move', $event)"
                            @insert="$emit('insert', $event)"
                            @duplicate="$emit('duplicate', $event)"
                            @remove="$emit('remove', $event)"
                        />
                    </ol>
                    <div class="cms-we-outline__tail">
                        <button
                            type="button"
                            class="cms-we-outline__gap is-tail"
                            :class="{ 'is-drop': marked(placeKey(node.uid, slot, children.length)) }"
                            title="Insert a widget here"
                            @click.stop="insertAt(node.uid, slot, children.length)"
                            @dragover.prevent="mark(node.uid, slot, children.length)"
                            @drop.prevent.stop="dropAt(node.uid, slot, children.length)"
                        >
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </li>
</template>

<script>
import { computed } from 'vue';
import { pad, placeKey, rowDrop, snippet } from './outline.js';

export default {
    name: 'OutlineNode',
    props: {
        node: { type: Object, required: true },
        index: { type: Number, required: true },
        depth: { type: Number, default: 0 },
        selected: { type: String, default: null },
        catalogue: { type: Object, required: true },
        /** Shared by every row: {uid, over: placeKey, blocked: uids the drag cannot enter} */
        drag: { type: Object, required: true },
        /** Keys `uid:slot` for slots that are collapsed */
        foldedIds: { type: Object, required: true },
        parentUid: { type: String, default: null },
        slotName: { type: String, default: null },
    },
    emits: ['select', 'toggle', 'move', 'insert', 'duplicate', 'remove'],
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
        /** Where this row's slots draw their guide. A nested slot is already inside its parent's guide. */
        const guide = computed(() => (props.depth === 0 ? '28px' : '16px'));
        const beforeKey = computed(() => placeKey(props.parentUid, props.slotName, props.index));

        function slotKey(slot) {
            return props.node.uid + ':' + slot;
        }

        function slotFolded(slot) {
            return Boolean(props.foldedIds[slotKey(slot)]);
        }

        function slotLabel(slot) {
            const defined = widget.value && widget.value.slots ? widget.value.slots[slot] : null;
            return defined && defined.label ? defined.label : slot;
        }

        function marked(key) {
            return Boolean(props.drag.uid) && props.drag.over === key;
        }

        function contained(node) {
            const ids = [node.uid];
            if (node.slots) {
                Object.values(node.slots).forEach((children) => {
                    children.forEach((child) => ids.push(...contained(child)));
                });
            }
            return ids;
        }

        function onDragStart(event) {
            event.stopPropagation();
            event.dataTransfer.setData('text/plain', props.node.uid);
            event.dataTransfer.effectAllowed = 'move';
            props.drag.uid = props.node.uid;
            props.drag.blocked = contained(props.node);
        }

        function onDragEnd() {
            props.drag.uid = null;
            props.drag.over = null;
            props.drag.blocked = [];
        }

        /** The slot a drop in the lower half enters, when this container's children are open */
        function firstSlot() {
            if (!props.node.slots) {
                return null;
            }
            const open = Object.keys(props.node.slots).find((slot) => !slotFolded(slot));
            return open || null;
        }

        function targetFrom(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            return rowDrop({
                uid: props.node.uid,
                index: props.index,
                offsetY: event.clientY - rect.top,
                height: rect.height,
                parentUid: props.parentUid,
                slot: props.slotName,
                firstSlot: firstSlot(),
                blocked: props.drag.blocked.includes(props.node.uid),
            });
        }

        function onDragOver(event) {
            if (!props.drag.uid || props.drag.uid === props.node.uid) {
                return;
            }
            const target = targetFrom(event);
            props.drag.over = placeKey(target.parentUid, target.slot, target.index);
        }

        function onDrop(event) {
            const uid = props.drag.uid;
            const target = targetFrom(event);
            onDragEnd();
            if (uid && uid !== props.node.uid) {
                emit('move', { uid, parentUid: target.parentUid, slot: target.slot, index: target.index });
            }
        }

        /** A container cannot be dropped into itself, so those places are never marked */
        function mark(parentUid, slot, index) {
            if (props.drag.uid && !props.drag.blocked.includes(parentUid)) {
                props.drag.over = placeKey(parentUid, slot, index);
            }
        }

        function dropAt(parentUid, slot, index) {
            const uid = props.drag.uid;
            const blocked = props.drag.blocked.includes(parentUid);
            onDragEnd();
            if (uid && !blocked) {
                emit('move', { uid, parentUid, slot, index });
            }
        }

        function insertAt(parentUid, slot, index) {
            emit('insert', { parentUid, slot, index });
        }

        return {
            label,
            summary,
            guide,
            beforeKey,
            slotKey,
            slotFolded,
            slotLabel,
            marked,
            pad,
            placeKey,
            onDragStart,
            onDragEnd,
            onDragOver,
            onDrop,
            mark,
            dropAt,
            insertAt,
        };
    },
};
</script>

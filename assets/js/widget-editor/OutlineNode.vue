<template>
    <li class="cms-we-outline__node" :class="{ 'is-dragging': drag.uid === node.uid }">
        <button
            type="button"
            class="cms-we-outline__gap"
            :class="[marker(beforeKey).state, { 'is-nested': depth > 0 }]"
            :style="{ left: depth === 0 ? '8px' : '0' }"
            :title="marker(beforeKey).title"
            @click.stop="insertAt(parentUid, slotName, index)"
            @dragover.prevent="mark($event, parentUid, slotName, index)"
            @drop.prevent.stop="dropAt(parentUid, slotName, index)"
        >
            <i class="fa" :class="marker(beforeKey).icon"></i>
        </button>

        <div
            class="cms-we-outline__item"
            :class="{
                'is-selected': node.uid === selected,
                'is-hover': node.uid === hovered,
                'is-nested': depth > 0,
            }"
            draggable="true"
            @click="$emit('select', node.uid)"
            @mouseenter="$emit('hover', node.uid)"
            @mouseleave="$emit('hover', null)"
            @dragstart="onDragStart"
            @dragend="onDragEnd"
            @dragover.prevent="onDragOver"
            @drop.prevent.stop="onDrop"
        >
            <span v-if="depth === 0" class="cms-we-outline__num">{{ pad(index + 1) }}</span>
            <span class="cms-we-outline__text">
                <strong>
                    <i v-if="node.locked" class="fa fa-triangle-exclamation cms-we-outline__warn" title="Kept as saved"></i>
                    {{ label }}
                </strong>
                <small>{{ summary || '\u00a0' }}</small>
            </span>
            <span class="cms-we-outline__actions">
                <button
                    type="button"
                    class="cms-we-outline__nudge"
                    title="Move up"
                    :disabled="index === 0"
                    @click.stop="$emit('nudge', { uid: node.uid, delta: -1 })"
                >
                    <i class="fa fa-arrow-up"></i>
                </button>
                <button
                    type="button"
                    class="cms-we-outline__nudge"
                    title="Move down"
                    :disabled="index >= count - 1"
                    @click.stop="$emit('nudge', { uid: node.uid, delta: 1 })"
                >
                    <i class="fa fa-arrow-down"></i>
                </button>
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
                    :class="{
                        'is-drop': slotFolded(slot) && marked(placeKey(node.uid, slot, 0)),
                        'is-invalid': slotFolded(slot) && rejected(placeKey(node.uid, slot, 0)),
                    }"
                    :title="slotTitle(slot)"
                    @click.stop="$emit('toggle', slotKey(slot))"
                    @dragover.prevent="mark($event, node.uid, slot, 0)"
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
                            :hovered="hovered"
                            :catalogue="catalogue"
                            :drag="drag"
                            :folded-ids="foldedIds"
                            :parent-uid="node.uid"
                            :slot-name="slot"
                            :count="children.length"
                            @select="$emit('select', $event)"
                            @nudge="$emit('nudge', $event)"
                            @hover="$emit('hover', $event)"
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
                            :class="marker(placeKey(node.uid, slot, children.length)).state"
                            :title="marker(placeKey(node.uid, slot, children.length)).title"
                            @click.stop="insertAt(node.uid, slot, children.length)"
                            @dragover.prevent="mark($event, node.uid, slot, children.length)"
                            @drop.prevent.stop="dropAt(node.uid, slot, children.length)"
                        >
                            <i class="fa" :class="marker(placeKey(node.uid, slot, children.length)).icon"></i>
                            <span class="cms-we-outline__gap-label">Add widget</span>
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
        /** The widget hovered in the outline or on the canvas */
        hovered: { type: String, default: null },
        catalogue: { type: Object, required: true },
        /** Shared by every row: {uid, over: placeKey, blocked: uids the drag cannot enter} */
        drag: { type: Object, required: true },
        /** Keys `uid:slot` for slots that are collapsed */
        foldedIds: { type: Object, required: true },
        parentUid: { type: String, default: null },
        slotName: { type: String, default: null },
        /** How many widgets share this list, so move up and move down know their ends */
        count: { type: Number, default: 1 },
    },
    emits: ['select', 'hover', 'toggle', 'move', 'insert', 'duplicate', 'remove', 'nudge'],
    setup(props, { emit }) {
        const widget = computed(() => props.catalogue.widgets[props.node.slug] || null);
        const label = computed(() => (widget.value ? widget.value.label : props.node.slug));
        /** The content's text, then what a container holds, then the widget's own description, as the inspector shows it */
        const summary = computed(() => {
            const text = snippet(props.node.data);
            if (text !== '') {
                return text;
            }
            if (props.node.slots) {
                const inside = Object.values(props.node.slots).reduce((total, children) => total + children.length, 0);
                if (inside > 0) {
                    return inside === 1 ? '1 widget inside' : inside + ' widgets inside';
                }
            }
            const info = widget.value;
            return info ? info.description || info.summary || '' : '';
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
            return Boolean(props.drag.uid) && props.drag.over === key && !props.drag.invalid;
        }

        function rejected(key) {
            return Boolean(props.drag.uid) && props.drag.over === key && props.drag.invalid;
        }

        /** A gap shows + to insert. While a drag is over it, the icon says move, or that the place is refused. */
        function marker(key) {
            const invalid = rejected(key);
            const active = marked(key);
            return {
                state: { 'is-drop': active, 'is-invalid': invalid },
                icon: invalid ? 'fa-ban' : (active ? 'fa-arrow-down' : 'fa-plus'),
                title: invalid ? 'Cannot drop here' : (active ? 'Move here' : 'Insert a widget here'),
            };
        }

        function slotTitle(slot) {
            const key = placeKey(props.node.uid, slot, 0);
            if (rejected(key)) {
                return 'Cannot drop here';
            }
            if (marked(key)) {
                return 'Move here';
            }
            return slotFolded(slot) ? 'Expand' : 'Collapse';
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
            props.drag.invalid = false;
        }

        function onDragEnd() {
            props.drag.uid = null;
            props.drag.over = null;
            props.drag.blocked = [];
            props.drag.invalid = false;
        }

        function aim(event, parentUid, slot, index) {
            const invalid = Boolean(parentUid) && props.drag.blocked.includes(parentUid);
            props.drag.over = placeKey(parentUid, slot, index);
            props.drag.invalid = invalid;
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = invalid ? 'none' : 'move';
            }
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
            aim(event, target.parentUid, target.slot, target.index);
        }

        function onDrop(event) {
            const uid = props.drag.uid;
            const target = targetFrom(event);
            const invalid = Boolean(target.parentUid) && props.drag.blocked.includes(target.parentUid);
            onDragEnd();
            if (uid && uid !== props.node.uid && !invalid) {
                emit('move', { uid, parentUid: target.parentUid, slot: target.slot, index: target.index });
            }
        }

        /** A place inside the dragged widget is marked, and shown as refused */
        function mark(event, parentUid, slot, index) {
            if (props.drag.uid) {
                aim(event, parentUid, slot, index);
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
            rejected,
            marker,
            slotTitle,
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

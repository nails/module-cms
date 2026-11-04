<template>
    <li>
        <div
            class="cms-we__row"
            :class="{ 'is-selected': node.uid === selected }"
            draggable="true"
            @click.stop="$emit('select', node.uid)"
            @dragstart="onDragStart"
            @dragover.prevent
            @drop.stop="onDropRow"
        >
            <span>{{ label }}</span>
            <span v-if="node.locked" class="cms-we__status">locked</span>
        </div>
        <div
            v-for="(children, slot) in node.slots"
            :key="slot"
            class="cms-we__slot"
        >
            <div class="cms-we__slot-label">{{ slotLabel(slot) }}</div>
            <ol
                class="cms-we__slot-list"
                @dragover.prevent
                @drop.stop="onDropSlot(slot, children.length, $event)"
            >
                <OutlineNode
                    v-for="(child, index) in children"
                    :key="child.uid"
                    :node="child"
                    :selected="selected"
                    :catalogue="catalogue"
                    :parent-uid="node.uid"
                    :slot-name="slot"
                    :index="index"
                    @select="$emit('select', $event)"
                    @move="$emit('move', $event)"
                />
            </ol>
        </div>
    </li>
</template>

<script>
import { computed } from 'vue';

export default {
    name: 'OutlineNode',
    props: {
        node: { type: Object, required: true },
        selected: { type: String, default: null },
        catalogue: { type: Object, required: true },
        parentUid: { type: String, default: null },
        slotName: { type: String, default: null },
        index: { type: Number, required: true },
    },
    emits: ['select', 'move'],
    setup(props, { emit }) {
        const label = computed(() => {
            const widget = props.catalogue.widgets[props.node.slug];
            return widget ? widget.label : props.node.slug;
        });

        function slotLabel(slot) {
            const widget = props.catalogue.widgets[props.node.slug];
            const defined = widget && widget.slots ? widget.slots[slot] : null;
            return defined && defined.label ? defined.label : slot;
        }

        function onDragStart(event) {
            event.dataTransfer.setData('text/plain', props.node.uid);
            event.dataTransfer.effectAllowed = 'move';
        }

        function onDropRow(event) {
            emit('move', {
                uid: event.dataTransfer.getData('text/plain'),
                parentUid: props.parentUid,
                slot: props.slotName,
                index: props.index,
            });
        }

        function onDropSlot(slot, index, event) {
            emit('move', {
                uid: event.dataTransfer.getData('text/plain'),
                parentUid: props.node.uid,
                slot,
                index,
            });
        }

        return { label, slotLabel, onDragStart, onDropRow, onDropSlot };
    },
};
</script>

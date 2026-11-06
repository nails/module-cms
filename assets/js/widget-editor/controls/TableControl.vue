<template>
    <div ref="root" class="cms-we__table">
        <button v-if="!rows.length" type="button" class="cms-we__repeater-empty" @click="update(addTableRow(rows))">
            <i class="fa fa-plus"></i>
            <span>Add a row</span>
        </button>
        <template v-else>
            <div class="cms-we__table-frame">
                <div class="cms-we__table-scroll">
                    <table class="cms-we__table-sheet" :style="{ minWidth: (52 + width * 96) + 'px' }">
                        <colgroup>
                            <col class="cms-we__table-index-col">
                            <col v-for="column in width" :key="column">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="cms-we__table-corner" aria-hidden="true"></th>
                                <th
                                    v-for="column in width"
                                    :key="column"
                                    class="cms-we__table-head"
                                    :class="headClass('column', column - 1)"
                                    draggable="true"
                                    :title="'Drag to move column ' + columnName(column - 1)"
                                    @dragstart="startDrag($event, 'column', column - 1)"
                                    @dragover="overDrag($event, 'column', column - 1)"
                                    @drop="drop"
                                    @dragend="endDrag"
                                >
                                    <span class="cms-we__table-name">{{ columnName(column - 1) }}</span>
                                    <button
                                        type="button"
                                        class="cms-we__table-remove"
                                        :title="'Remove column ' + columnName(column - 1)"
                                        :aria-label="'Remove column ' + columnName(column - 1)"
                                        draggable="false"
                                        @click="update(removeTableColumn(rows, column - 1))"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, rowIndex) in rows"
                                :key="rowIndex"
                                :class="{ 'cms-we__table-heading-row': rowIndex < headerCount }"
                            >
                                <th
                                    scope="row"
                                    class="cms-we__table-head cms-we__table-head--row"
                                    :class="headClass('row', rowIndex)"
                                    draggable="true"
                                    :title="'Drag to move row ' + (rowIndex + 1)"
                                    @dragstart="startDrag($event, 'row', rowIndex)"
                                    @dragover="overDrag($event, 'row', rowIndex)"
                                    @drop="drop"
                                    @dragend="endDrag"
                                >
                                    <span class="cms-we__table-name">{{ rowIndex + 1 }}</span>
                                    <button
                                        type="button"
                                        class="cms-we__table-remove"
                                        :title="'Remove row ' + (rowIndex + 1)"
                                        :aria-label="'Remove row ' + (rowIndex + 1)"
                                        draggable="false"
                                        @click="update(removeTableRow(rows, rowIndex))"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </th>
                                <td
                                    v-for="column in width"
                                    :key="column"
                                    :class="cellClass(rowIndex, column - 1)"
                                    @dragover="overDrag($event, drag && drag.axis, drag && drag.axis === 'row' ? rowIndex : column - 1)"
                                    @drop="drop"
                                >
                                    <input
                                        type="text"
                                        class="cms-we__table-cell"
                                        :data-cell="rowIndex + ':' + (column - 1)"
                                        :aria-label="'Cell ' + columnName(column - 1) + (rowIndex + 1)"
                                        :value="row[column - 1] ?? ''"
                                        @focus="active = { row: rowIndex, column: column - 1 }"
                                        @blur="active = null"
                                        @keydown.enter.prevent="focusCell(rowIndex + ($event.shiftKey ? -1 : 1), column - 1)"
                                        @input="update(setTableCell(rows, rowIndex, column - 1, $event.target.value))"
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button
                    type="button"
                    class="cms-we__table-add cms-we__table-add--column"
                    title="Add column"
                    aria-label="Add column"
                    @click="update(addTableColumn(rows))"
                >
                    <i class="fa fa-plus"></i>
                </button>
            </div>
            <button
                type="button"
                class="cms-we__table-add cms-we__table-add--row"
                title="Add row"
                aria-label="Add row"
                @click="update(addTableRow(rows))"
            >
                <i class="fa fa-plus"></i>
            </button>
        </template>
    </div>
</template>

<script>
import { computed, inject, nextTick, ref } from 'vue';
import { INSTANCE_VALUES } from '../field-types.js';
import {
    addTableColumn,
    addTableRow,
    columnName,
    movedIndex,
    moveTableColumn,
    moveTableRow,
    removeTableColumn,
    removeTableRow,
    setTableCell,
    tableHeaderCount,
    tableRows,
    tableWidth,
} from '../table.js';

/**
 * A spreadsheet: lettered columns and numbered rows. A column or row header drags to move it, and shows a
 * remove button on hover. The bar on the right adds a column, the bar below adds a row. When the field names its
 * header rows field, those rows are shaded as headings.
 */
export default {
    name: 'TableControl',
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
    setup(props, { emit }) {
        const root = ref(null);
        const values = inject(INSTANCE_VALUES, null);

        // Rows are padded to the widest row, so every column can be edited
        const rows = computed(() => {
            const grid = tableRows(props.value);
            const width = tableWidth(grid);
            return grid.map((row) => [...row, ...new Array(width - row.length).fill('')]);
        });
        const width = computed(() => tableWidth(rows.value));
        const headerCount = computed(() => {
            const key = props.field.headerRows;
            return key && values ? tableHeaderCount((values.value || {})[key], rows.value.length) : 0;
        });

        /** The focused cell, whose row and column headers are marked */
        const active = ref(null);

        /** The row or column being dragged, and the gap it would be dropped in */
        const drag = ref(null);

        function startDrag(event, axis, index) {
            drag.value = { axis, from: index, gap: null };
            event.dataTransfer.effectAllowed = 'move';
            // Firefox does not start a drag without data
            event.dataTransfer.setData('text/plain', axis + ' ' + index);
        }

        function overDrag(event, axis, index) {
            if (!drag.value || drag.value.axis !== axis) {
                return;
            }
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            const box = event.currentTarget.getBoundingClientRect();
            const after = axis === 'column'
                ? event.clientX > box.left + box.width / 2
                : event.clientY > box.top + box.height / 2;
            drag.value.gap = index + (after ? 1 : 0);
        }

        function drop(event) {
            const current = drag.value;
            drag.value = null;
            if (!current || current.gap === null) {
                return;
            }
            event.preventDefault();
            const to = movedIndex(current.from, current.gap);
            if (to === current.from) {
                return;
            }
            update(current.axis === 'column'
                ? moveTableColumn(rows.value, current.from, to)
                : moveTableRow(rows.value, current.from, to));
        }

        function endDrag() {
            drag.value = null;
        }

        /** The drop line sits before the gap's row or column, or after the last one */
        function dropClass(axis, index, count) {
            const current = drag.value;
            if (!current || current.axis !== axis) {
                return {};
            }
            return {
                'is-dragging': current.from === index,
                'is-drop-before': current.gap === index,
                'is-drop-after': current.gap === count && index === count - 1,
            };
        }

        function headClass(axis, index) {
            const count = axis === 'column' ? width.value : rows.value.length;
            const isActive = active.value && active.value[axis] === index;
            return { ...dropClass(axis, index, count), 'is-active': Boolean(isActive) };
        }

        function cellClass(rowIndex, columnIndex) {
            return {
                ...dropClass('column', columnIndex, width.value),
                ...prefixed(dropClass('row', rowIndex, rows.value.length)),
            };
        }

        /** Row drop classes on a cell are kept apart from the column ones, so both can be drawn */
        function prefixed(classes) {
            return Object.fromEntries(Object.entries(classes).map(([name, on]) => [name.replace('is-', 'is-row-'), on]));
        }

        function focusCell(rowIndex, columnIndex) {
            if (rowIndex < 0 || rowIndex >= rows.value.length) {
                return;
            }
            nextTick(() => {
                const input = root.value && root.value.querySelector('[data-cell="' + rowIndex + ':' + columnIndex + '"]');
                if (input) {
                    input.focus();
                }
            });
        }

        function update(next) {
            emit('update', next);
        }

        return {
            root,
            rows,
            width,
            headerCount,
            active,
            drag,
            columnName,
            addTableRow,
            addTableColumn,
            removeTableRow,
            removeTableColumn,
            setTableCell,
            startDrag,
            overDrag,
            drop,
            endDrag,
            headClass,
            cellClass,
            focusCell,
            update,
        };
    },
};
</script>

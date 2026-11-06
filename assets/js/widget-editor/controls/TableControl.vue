<template>
    <div class="cms-we__table">
        <button v-if="!rows.length" type="button" class="cms-we__repeater-empty" @click="update(addTableRow(rows))">
            <i class="fa fa-plus"></i>
            <span>Add a row</span>
        </button>
        <div v-else class="cms-we__table-scroll">
            <table>
                <thead>
                    <tr>
                        <th v-for="column in width" :key="column">
                            <button type="button" :title="'Remove column ' + column" @click="update(removeTableColumn(rows, column - 1))">
                                <i class="fa fa-xmark"></i>
                            </button>
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in rows" :key="rowIndex">
                        <td v-for="column in width" :key="column">
                            <input
                                type="text"
                                class="form-control input-sm"
                                :aria-label="'Row ' + (rowIndex + 1) + ', column ' + column"
                                :value="row[column - 1] ?? ''"
                                @input="update(setTableCell(rows, rowIndex, column - 1, $event.target.value))"
                            >
                        </td>
                        <td class="cms-we__table-remove">
                            <button type="button" :title="'Remove row ' + (rowIndex + 1)" @click="update(removeTableRow(rows, rowIndex))">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="rows.length" class="cms-we__table-actions">
            <button type="button" class="btn btn-default btn-sm" @click="update(addTableRow(rows))">
                <i class="fa fa-plus"></i> Add row
            </button>
            <button type="button" class="btn btn-default btn-sm" @click="update(addTableColumn(rows))">
                <i class="fa fa-plus"></i> Add column
            </button>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue';
import {
    addTableColumn,
    addTableRow,
    removeTableColumn,
    removeTableRow,
    setTableCell,
    tableRows,
    tableWidth,
} from '../table.js';

/**
 * A grid of text inputs. The header row removes a column, the last cell of a row removes that row.
 */
export default {
    name: 'TableControl',
    props: {
        field: { type: Object, required: true },
        value: { type: null, default: undefined },
    },
    emits: ['update'],
    setup(props, { emit }) {
        // Rows are padded to the widest row, so every column can be edited
        const rows = computed(() => {
            const grid = tableRows(props.value);
            const width = tableWidth(grid);
            return grid.map((row) => [...row, ...new Array(width - row.length).fill('')]);
        });
        const width = computed(() => tableWidth(rows.value));

        return {
            rows,
            width,
            addTableRow,
            addTableColumn,
            removeTableRow,
            removeTableColumn,
            setTableCell,
            update: (next) => emit('update', next),
        };
    },
};
</script>

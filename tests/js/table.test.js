import { describe, expect, it } from 'vitest';
import { BUILTIN_RULES } from '../../assets/js/widget-editor/builtin-rules.js';
import {
    addTableColumn,
    addTableRow,
    removeTableColumn,
    removeTableRow,
    setTableCell,
    tableIsEmpty,
    tableRows,
    tableSummary,
} from '../../assets/js/widget-editor/table.js';

describe('table field', () => {
    it('reads anything that is not a list of rows as an empty table', () => {
        expect(tableRows(undefined)).toEqual([]);
        expect(tableRows('[]')).toEqual([]);
        expect(tableRows([[1, null], 'x'])).toEqual([['1', ''], []]);
    });

    it('adds rows as wide as the table, and one cell to an empty table', () => {
        expect(addTableRow([['a', 'b']])).toEqual([['a', 'b'], ['', '']]);
        expect(addTableRow([])).toEqual([['']]);
    });

    it('adds a column to every row, and a row to an empty table', () => {
        expect(addTableColumn([['a'], ['b']])).toEqual([['a', ''], ['b', '']]);
        expect(addTableColumn([])).toEqual([['']]);
    });

    it('removes a row or a column, and the last column leaves no rows', () => {
        expect(removeTableRow([['a'], ['b']], 0)).toEqual([['b']]);
        expect(removeTableColumn([['a', 'b'], ['c', 'd']], 1)).toEqual([['a'], ['c']]);
        expect(removeTableColumn([['a'], ['b']], 0)).toEqual([]);
    });

    it('sets one cell without changing the others', () => {
        const rows = [['a', 'b'], ['c', 'd']];
        expect(setTableCell(rows, 1, 0, 'x')).toEqual([['a', 'b'], ['x', 'd']]);
        expect(rows[1][0]).toBe('c');
    });

    it('summarises the size and counts a blank grid as empty', () => {
        expect(tableSummary([['a', 'b'], ['c', 'd'], ['e', 'f']])).toBe('3 rows × 2 columns');
        expect(tableSummary([['a']])).toBe('1 row × 1 column');
        expect(tableSummary([])).toBe('Nothing yet');
        expect(tableIsEmpty([['', ' ']])).toBe(true);
        expect(tableIsEmpty([['', 'a']])).toBe(false);
        expect(BUILTIN_RULES.table.summary([['a']])).toBe('1 row × 1 column');
    });
});

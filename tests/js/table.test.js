import { describe, expect, it } from 'vitest';
import { BUILTIN_RULES } from '../../assets/js/widget-editor/builtin-rules.js';
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

    it('names columns as a spreadsheet does', () => {
        expect([0, 1, 25, 26, 27, 51, 52, 701, 702].map(columnName))
            .toEqual(['A', 'B', 'Z', 'AA', 'AB', 'AZ', 'BA', 'ZZ', 'AAA']);
    });

    it('reads the header row count as a whole number no larger than the table', () => {
        expect(tableHeaderCount(1, 3)).toBe(1);
        expect(tableHeaderCount('2', 3)).toBe(2);
        expect(tableHeaderCount(9, 3)).toBe(3);
        expect(tableHeaderCount(1.7, 3)).toBe(1);
        expect(tableHeaderCount(-1, 3)).toBe(0);
        expect(tableHeaderCount(null, 3)).toBe(0);
        expect(tableHeaderCount(undefined, 3)).toBe(0);
        expect(tableHeaderCount('x', 3)).toBe(0);
    });
});

describe('dragging rows and columns', () => {
    it('turns the gap a drop lands in into the new index', () => {
        // Three items; gaps 0 to 3
        expect(movedIndex(0, 3)).toBe(2);
        expect(movedIndex(2, 0)).toBe(0);
        expect(movedIndex(1, 1)).toBe(1);
        expect(movedIndex(1, 2)).toBe(1);
    });

    it('moves a row without changing the others', () => {
        const rows = [['a'], ['b'], ['c']];
        expect(moveTableRow(rows, 0, 2)).toEqual([['b'], ['c'], ['a']]);
        expect(moveTableRow(rows, 2, 0)).toEqual([['c'], ['a'], ['b']]);
        expect(moveTableRow(rows, 1, 1)).toBe(rows);
        expect(rows).toEqual([['a'], ['b'], ['c']]);
    });

    it('moves a column in every row, filling a short row first', () => {
        expect(moveTableColumn([['a', 'b', 'c'], ['d', 'e', 'f']], 0, 2)).toEqual([['b', 'c', 'a'], ['e', 'f', 'd']]);
        expect(moveTableColumn([['a', 'b', 'c'], ['d']], 2, 0)).toEqual([['c', 'a', 'b'], ['', 'd', '']]);
    });
});

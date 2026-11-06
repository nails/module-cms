/**
 * Rules for the `table` field type: a list of rows, each a list of cell strings. Each function returns a new grid.
 */

/**
 * @param {unknown} value
 * @return {string[][]}
 */
export function tableRows(value) {
    return Array.isArray(value) ? value.map((row) => (Array.isArray(row) ? row.map((cell) => String(cell ?? '')) : [])) : [];
}

/** @return {number} The widest row's cell count */
export function tableWidth(rows) {
    return rows.reduce((width, row) => Math.max(width, row.length), 0);
}

/** A new empty row at the end, as wide as the table (one cell for an empty table) */
export function addTableRow(rows) {
    return [...rows, new Array(Math.max(tableWidth(rows), 1)).fill('')];
}

/** A new empty cell at the end of every row. An empty table gets one row. */
export function addTableColumn(rows) {
    return rows.length ? rows.map((row) => [...row, '']) : [['']];
}

export function removeTableRow(rows, index) {
    return rows.filter((_, i) => i !== index);
}

/** Removing the last column leaves no rows */
export function removeTableColumn(rows, index) {
    const next = rows.map((row) => row.filter((_, i) => i !== index));
    return tableWidth(next) === 0 ? [] : next;
}

export function setTableCell(rows, rowIndex, columnIndex, text) {
    return rows.map((row, r) => (r === rowIndex ? row.map((cell, c) => (c === columnIndex ? text : cell)) : row));
}

/** @return {string} e.g. "3 rows × 4 columns" */
export function tableSummary(value) {
    const rows = tableRows(value);
    if (!rows.length) {
        return 'Nothing yet';
    }
    const width = tableWidth(rows);
    return plural(rows.length, 'row') + ' × ' + plural(width, 'column');
}

/** Empty when every cell is blank */
export function tableIsEmpty(value) {
    return tableRows(value).every((row) => row.every((cell) => cell.trim() === ''));
}

function plural(count, word) {
    return count + ' ' + word + (count === 1 ? '' : 's');
}

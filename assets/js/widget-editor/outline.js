/**
 * Text for the outline: a short line of each widget's content under its label
 */

const SNIPPET_LENGTH = 48;

/** Fields whose names read as a heading are used before any other text */
const TITLE_KEY = /title|heading|label|name|headline/i;

/**
 * The first piece of text in a widget's data, with markup removed. A title-like field wins; nested data
 * (repeater rows) is searched after the widget's own fields.
 *
 * @param {unknown} data
 * @param {number} [length]
 * @return {string}
 */
export function snippet(data, length = SNIPPET_LENGTH) {

    let text = '';
    if (data && typeof data === 'object' && !Array.isArray(data)) {
        const titled = Object.keys(data).find((key) => TITLE_KEY.test(key) && typeof data[key] === 'string' && plain(data[key]) !== '');
        text = titled ? plain(data[titled]) : '';
    }
    if (text === '') {
        text = firstText(data, 0);
    }
    if (text === '') {
        return '';
    }

    return text.length > length ? text.slice(0, length - 1).trimEnd() + '…' : text;
}

/**
 * @param {unknown} value
 * @param {number} depth
 * @return {string}
 */
function firstText(value, depth) {

    if (typeof value === 'string') {
        return plain(value);
    }

    if (depth > 3 || value === null || typeof value !== 'object') {
        return '';
    }

    const entries = Array.isArray(value) ? value : Object.values(value);
    const nested = [];

    for (const entry of entries) {
        if (typeof entry === 'string') {
            const text = plain(entry);
            if (text !== '' && !/^\d+$/.test(text) && !/^[a-z0-9_-]+$/i.test(text)) {
                return text;
            }
        } else if (entry && typeof entry === 'object') {
            nested.push(entry);
        }
    }

    for (const entry of nested) {
        const text = firstText(entry, depth + 1);
        if (text !== '') {
            return text;
        }
    }

    return '';
}

/**
 * @param {string} value
 * @return {string}
 */
function plain(value) {
    return value
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, '\'')
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * Where a drop lands in a list, from the pointer's position over a row: the upper half places before the row,
 * the lower half after it. The index is in the list as it is before the move (tree.move adjusts it).
 *
 * @param {number} index    The row's index in its list
 * @param {number} offsetY  Pointer distance from the row's top
 * @param {number} height   Row height
 * @return {{index: number, edge: 'before'|'after'}}
 */
export function dropTarget(index, offsetY, height) {
    return offsetY < height / 2
        ? { index, edge: 'before' }
        : { index: index + 1, edge: 'after' };
}

/**
 * A drop position the outline can mark: which list, and the index in it.
 *
 * @param {string|null} parentUid
 * @param {string|null} slot
 * @param {number} index
 * @return {string}
 */
export function placeKey(parentUid, slot, index) {
    return (parentUid || '') + '|' + (slot || '') + '|' + index;
}

/**
 * Where a drag over a row really lands.
 *
 * The upper half is the place before this row, in its own list. The lower half of a leaf, or of a container
 * whose children are hidden, is the place after it. The lower half of an open container is the start of its
 * first slot: a line under the header would sit in front of the children, and nothing can be inserted there.
 * A container cannot be dropped into itself, so that case uses the place after the container instead, which
 * the outline draws below its children.
 *
 * @param {object} row
 * @param {string} row.uid
 * @param {number} row.index
 * @param {number} row.offsetY
 * @param {number} row.height
 * @param {string|null} row.parentUid
 * @param {string|null} row.slot
 * @param {string|null} row.firstSlot  Set when the container's children are visible
 * @param {boolean} row.blocked        The dragged widget contains this row, so it cannot enter the slot
 * @return {{parentUid: string|null, slot: string|null, index: number}}
 */
export function rowDrop(row) {
    if (row.offsetY < row.height / 2) {
        return { parentUid: row.parentUid || null, slot: row.slot || null, index: row.index };
    }
    if (row.firstSlot && !row.blocked) {
        return { parentUid: row.uid, slot: row.firstSlot, index: 0 };
    }
    return { parentUid: row.parentUid || null, slot: row.slot || null, index: row.index + 1 };
}

/**
 * Two-digit row numbers, as on the outline
 * @param {number} value
 * @return {string}
 */
export function pad(value) {
    return value < 10 ? '0' + value : String(value);
}

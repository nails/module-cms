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
 * Two-digit row numbers, as on the outline
 * @param {number} value
 * @return {string}
 */
export function pad(value) {
    return value < 10 ? '0' + value : String(value);
}

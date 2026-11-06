/**
 * Rules for a fields-schema widget's inspector
 *
 * The schema comes from the catalogue, already validated and with defaults filled (see Service\Fields in PHP).
 * Rules that depend on the type (what counts as empty, how a value is stored) come from the type's definition in
 * field-types.js. Nothing here blocks a save: a missing required field is a warning.
 */

import { fieldType } from './field-types.js';

/**
 * @typedef {Object} Field
 * @property {string} key
 * @property {string} type
 * @property {string} label
 * @property {string} tip
 * @property {boolean} required
 * @property {boolean} wide
 * @property {Object<string, unknown>} visibleWhen
 * @property {unknown} default
 * @property {Object<string, string>} [options]
 * @property {string[]} [order]   Option keys in the order PHP gave them
 * @property {number|null} [min]
 * @property {number|null} [max]
 * @property {Field[]} [fields]   Repeater children
 */

/**
 * Whether a field is shown for the given values. Every condition in visibleWhen must match.
 * @param {Field} field
 * @param {Object<string, unknown>} values
 * @return {boolean}
 */
export function isVisible(field, values) {
    const conditions = field.visibleWhen || {};
    return Object.keys(conditions).every((key) => values && values[key] === conditions[key]);
}

/**
 * @param {Field[]} fields
 * @param {Object<string, unknown>} values
 * @return {Field[]}
 */
export function visibleFields(fields, values) {
    return (fields || []).filter((field) => isVisible(field, values));
}

/**
 * Whether a value counts as missing for a required field. The type may decide (a toggle is never missing);
 * otherwise null, blank text and an empty list are missing.
 * @param {Field} field
 * @param {unknown} value
 * @return {boolean}
 */
export function isEmpty(field, value) {
    const definition = fieldType(field.type);
    return definition && definition.isEmpty ? definition.isEmpty(value, field) : isBlank(value);
}

/**
 * @param {unknown} value
 * @return {boolean}
 */
export function isBlank(value) {
    if (value === undefined || value === null) {
        return true;
    }
    if (typeof value === 'string') {
        return value.trim() === '';
    }
    if (Array.isArray(value)) {
        return value.length === 0;
    }
    return false;
}

/**
 * The required fields with no value, at any depth. A repeater row is checked by its own fields.
 *
 * @param {Field[]} fields
 * @param {Object<string, unknown>} values
 * @param {string} [prefix] Path prefix, e.g. 'aItems[0].'
 * @return {Array<{path: string, label: string}>}
 */
export function missingRequired(fields, values, prefix = '') {
    const out = [];

    visibleFields(fields, values).forEach((field) => {
        const path = prefix + field.key;
        const value = values ? values[field.key] : undefined;

        if (field.required && isEmpty(field, value)) {
            out.push({ path, label: field.label });
        }

        if (Array.isArray(field.fields) && Array.isArray(value)) {
            value.forEach((row, index) => {
                out.push(...missingRequired(field.fields, row || {}, path + '[' + index + '].'));
            });
        }
    });

    return out;
}

/**
 * A new repeater row, with each child's default
 * @param {Field} field A repeater
 * @return {Object<string, unknown>}
 */
export function newRow(field) {
    const row = {};
    (field.fields || []).forEach((child) => {
        row[child.key] = clone(child.default);
    });
    return row;
}

/** @return {boolean} */
export function canAddRow(field, rows) {
    return field.max === null || field.max === undefined || rows.length < field.max;
}

/** @return {boolean} */
export function canRemoveRow(field, rows) {
    return rows.length > (field.min || 0);
}

/**
 * @param {Field} field A repeater
 * @param {Object[]} rows
 * @return {Object[]} The rows with a new one added at the end, or the same rows at the maximum
 */
export function addRow(field, rows) {
    return canAddRow(field, rows) ? [...rows, newRow(field)] : rows;
}

/**
 * @param {Field} field A repeater
 * @param {Object[]} rows
 * @param {number} index
 * @return {Object[]}
 */
export function removeRow(field, rows, index) {
    return canRemoveRow(field, rows) ? rows.filter((_, i) => i !== index) : rows;
}

/**
 * Moves a row up (delta -1) or down (delta 1). A move past either end leaves the rows as they are.
 * @param {Object[]} rows
 * @param {number} index
 * @param {number} delta
 * @return {Object[]}
 */
export function moveRow(rows, index, delta) {
    const target = index + delta;
    if (target < 0 || target >= rows.length) {
        return rows;
    }
    const next = rows.slice();
    [next[index], next[target]] = [next[target], next[index]];
    return next;
}

/**
 * Returns a copy of the rows with one child value changed
 * @param {Object[]} rows
 * @param {number} index
 * @param {string} key
 * @param {unknown} value
 * @return {Object[]}
 */
export function setRowValue(rows, index, key, value) {
    return rows.map((row, i) => (i === index ? { ...row, [key]: value } : row));
}

/**
 * The value at a slash path. A path with no slash is a top-level key. `panels/0/title` is a repeater row.
 *
 * @param {Object<string, unknown>|unknown[]} data
 * @param {string} path
 * @return {unknown}
 */
export function valueAt(data, path) {
    const parts = String(path).split('/');
    let node = data;

    for (let i = 0; i < parts.length; i++) {
        if (node === null || node === undefined || typeof node !== 'object') {
            return undefined;
        }
        node = node[parts[i]];
    }

    return node;
}

/**
 * A copy of data with the value at path replaced. Arrays along the path stay arrays.
 *
 * @param {Object<string, unknown>|unknown[]} data
 * @param {string} path
 * @param {unknown} value
 * @return {Object<string, unknown>|unknown[]}
 */
export function setAt(data, path, value) {
    const parts = String(path).split('/');
    const root = Array.isArray(data) ? data.slice() : { ...(data || {}) };

    if (parts.length === 1) {
        root[parts[0]] = value;
        return root;
    }

    let cursor = root;
    for (let i = 0; i < parts.length - 1; i++) {
        const child = cursor[parts[i]];
        const copy = Array.isArray(child) ? child.slice() : { ...(child && typeof child === 'object' ? child : {}) };
        cursor[parts[i]] = copy;
        cursor = copy;
    }

    cursor[parts[parts.length - 1]] = value;
    return root;
}

/**
 * The row's heading: the first field's text, or its selected option. Empty falls back to "Item N".
 *
 * @param {Field} field A repeater
 * @param {Object<string, unknown>} row
 * @param {number} index
 * @return {string}
 */
export function rowTitle(field, row, index) {
    const fallback = 'Item ' + (index + 1);
    const first = (field.fields || [])[0];
    if (!first || !row) {
        return fallback;
    }

    const value = row[first.key];
    let text = '';
    if (first.options && value in first.options) {
        text = String(first.options[value]);
    } else if (first.type === 'richtext') {
        text = plainText(value);
    } else if (typeof value === 'string' || typeof value === 'number') {
        text = String(value);
    }

    text = text.replace(/\s+/g, ' ').trim();
    return text || fallback;
}

/**
 * A select's option keys, in the order PHP gave them. Without `order`, the object's own order is used.
 *
 * @param {Field} field
 * @return {string[]}
 */
export function optionKeys(field) {
    const options = field.options || {};
    const order = Array.isArray(field.order) ? field.order.filter((key) => key in options) : [];
    return order.length === Object.keys(options).length ? order : Object.keys(options);
}

/**
 * Converts what a control reports into the stored value for its type. A type without a rule stores what it reports.
 *
 * @param {Field} field
 * @param {unknown} raw
 * @return {unknown}
 */
export function coerce(field, raw) {
    const definition = fieldType(field.type);
    return definition && definition.coerce ? definition.coerce(raw, field) : raw;
}

/**
 * One line that stands in for a wide field in the inspector
 *
 * @param {Field} field
 * @param {unknown} value
 * @return {string}
 */
export function summary(field, value) {
    const definition = fieldType(field.type);
    if (definition && definition.summary) {
        return definition.summary(value, field);
    }
    return isEmpty(field, value) ? 'Nothing yet' : String(value);
}

/**
 * A short plain-text summary of rich text, for the inspector
 * @param {string} html
 * @return {string}
 */
export function plainText(html) {
    return String(html || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * @param {unknown} value
 * @return {unknown}
 */
function clone(value) {
    return value === undefined ? value : JSON.parse(JSON.stringify(value));
}

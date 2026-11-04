/**
 * Rules for a fields-schema widget's inspector (D23)
 *
 * The schema comes from the catalogue, already validated and with defaults filled (see Service\Fields in PHP).
 * These functions are pure, so the Vue controls only render and call them. Nothing here blocks a save: a missing
 * required field is a warning.
 */

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
 * @property {number|null} [min]
 * @property {number|null} [max]
 * @property {Field[]} [fields]   Repeater children
 * @property {string} [bucket]    cdn_object bucket
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
 * Whether a value counts as missing for a required field. A toggle is never missing: off is a value.
 * @param {Field} field
 * @param {unknown} value
 * @return {boolean}
 */
export function isEmpty(field, value) {
    if (field.type === 'toggle') {
        return false;
    }
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

        if (field.type === 'repeater' && Array.isArray(value)) {
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
 * Converts what a control reports into the stored value for its type. Nothing is clamped, so a value outside
 * min or max is kept and the editor does not block it.
 *
 * @param {Field} field
 * @param {unknown} raw
 * @return {unknown}
 */
export function coerce(field, raw) {
    switch (field.type) {
        case 'number': {
            if (raw === '' || raw === null || raw === undefined) {
                return null;
            }
            const number = Number(raw);
            return Number.isFinite(number) ? number : null;
        }
        case 'toggle':
            return Boolean(raw);
        case 'cdn_object': {
            const id = parseInt(raw, 10);
            return Number.isInteger(id) && id > 0 ? id : null;
        }
        default:
            return raw;
    }
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

/**
 * Serialises HTML form fields into widget data
 *
 * A port of the v1 WidgetEditor's serializeInputs(), with the DOM access removed so it can be tested
 * on its own. The caller turns each field into a descriptor; the rules for names, bracket notation,
 * checkboxes, radios and multi-selects are the v1 rules, kept on purpose (see the edge-case tests).
 *
 * @typedef {Object} FieldDescriptor
 * @property {string}           name     The input name, e.g. items[0][tags][]
 * @property {string}           type     The input type: text, checkbox, radio, select-multiple, etc.
 * @property {boolean}          checked  Whether a checkbox or radio is checked
 * @property {boolean}          disabled Disabled fields are skipped
 * @property {string|string[]}  value    The current value (an array for select-multiple)
 */

const INDEX_SEGMENT = /^(0|[1-9]\d*)$/;

/**
 * Serialises a list of field descriptors into a plain object
 * @param {FieldDescriptor[]} fields
 * @return {Object}
 */
export function serialiseFields(fields) {

    const out = {};

    fields.forEach((field) => {

        const name = field.name;
        if (!name || field.disabled) {
            return;
        }

        const type      = field.type;
        const path      = parseInputName(name);
        const isGrouped = path[path.length - 1] === '';

        //  Grouped inputs are stored under the bare property name, without the []
        const ref = resolveInputPath(out, isGrouped ? path.slice(0, -1) : path);

        if (type === 'radio') {
            //  Always represent the group; null indicates nothing is selected
            if (field.checked) {
                ref.target[ref.key] = field.value;
            } else if (!(ref.key in ref.target)) {
                ref.target[ref.key] = null;
            }
        } else if (type === 'select-multiple') {
            //  Multi-selects yield every selected option's value ([] when none)
            ref.target[ref.key] = Array.isArray(field.value) ? field.value : [];
        } else if (isGrouped) {
            //  Grouped inputs always yield an array; checkboxes contribute only when checked
            if (!Array.isArray(ref.target[ref.key])) {
                ref.target[ref.key] = [];
            }
            if (type !== 'checkbox' || field.checked) {
                ref.target[ref.key].push(field.value);
            }
        } else if (type === 'checkbox') {
            ref.target[ref.key] = Boolean(field.checked);
        } else {
            ref.target[ref.key] = field.value;
        }
    });

    return normaliseSerialisedValue(out);
}

/**
 * Splits an input name into its path segments; an empty segment is a bare [].
 * Names which are not valid bracket notation are one literal key.
 * @param {string} name
 * @return {string[]}
 */
export function parseInputName(name) {

    const matches = name.match(/^([^\[\]]+)((?:\[[^\[\]]*])*)$/);

    if (!matches) {
        return [name];
    }

    const path = [matches[1]];

    (matches[2].match(/\[[^\[\]]*]/g) || []).forEach((segment) => {
        path.push(segment.slice(1, -1));
    });

    return path;
}

/**
 * Finds the container a value belongs in, creating intermediate containers as it descends
 * (arrays where the next segment is indexed or appended, objects otherwise)
 * @param {Object}   root
 * @param {string[]} path
 * @return {{target: Object|Array, key: string|number}}
 */
function resolveInputPath(root, path) {

    let target = root;

    for (let i = 0; i < path.length - 1; i++) {

        const key         = resolveInputPathKey(target, path[i]);
        const childIsList = path[i + 1] === '' || isIndexSegment(path[i + 1]);

        if (target[key] === null || typeof target[key] !== 'object') {
            target[key] = childIsList ? [] : {};
        }

        target = target[key];
    }

    return {
        target,
        key: resolveInputPathKey(target, path[path.length - 1]),
    };
}

/**
 * A segment is a key, an index into an array, or (for []) the next free position
 * @param {Object|Array} target
 * @param {string}       segment
 * @return {string|number}
 */
function resolveInputPathKey(target, segment) {

    if (segment === '') {
        return Array.isArray(target) ? target.length : Object.keys(target).length;
    }

    return Array.isArray(target) && isIndexSegment(segment) ? Number(segment) : segment;
}

/**
 * Whether a segment is a canonical, non-negative integer
 * @param {string} segment
 * @return {boolean}
 */
function isIndexSegment(segment) {
    return INDEX_SEGMENT.test(segment);
}

/**
 * Arrays with gaps (a repeater whose rows are items[0] and items[2]) become objects keyed by index,
 * not arrays padded with nulls, as PHP would encode them
 * @param {*} value
 * @return {*}
 */
function normaliseSerialisedValue(value) {

    if (Array.isArray(value)) {

        const normalised = value.map((item) => normaliseSerialisedValue(item));

        //  Object.keys only reports indexes which are actually set
        if (Object.keys(normalised).length === normalised.length) {
            return normalised;
        }

        const out = {};
        normalised.forEach((item, index) => {
            out[index] = item;
        });

        return out;
    }

    if (value !== null && typeof value === 'object' && Object.getPrototypeOf(value) === Object.prototype) {
        Object.keys(value).forEach((key) => {
            value[key] = normaliseSerialisedValue(value[key]);
        });
    }

    return value;
}

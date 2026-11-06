/**
 * The inspector controls, by field type
 *
 * A field type is a PHP class (see Interfaces\Field) whose type name matches a definition registered here. The
 * editor registers its own types; another module or the app registers one by pushing a definition onto
 * `window.NAILS_CMS_WIDGET_EDITOR_FIELDS`, before or after the editor bundle loads:
 *
 *     (window.NAILS_CMS_WIDGET_EDITOR_FIELDS = window.NAILS_CMS_WIDGET_EDITOR_FIELDS || []).push({
 *         type: 'colour',
 *         component: ColourControl,
 *     });
 *
 * A later definition for a type replaces the earlier one.
 */

/**
 * @typedef {Object} FieldTypeDefinition
 * @property {string} type                     The PHP type name, e.g. `text`
 * @property {Object} component                A Vue 3 component. Props: field, value. Emits `update` with the new value.
 * @property {'label'|'block'} [layout]        `label` wraps the control in a <label>; `block` is for controls with
 *                                             several interactive parts. Default `label`.
 * @property {string} [className]              Added to the field's container, e.g. `checkbox`
 * @property {boolean} [wide]                  False when the control presents itself compactly, so `wide` on the
 *                                             field has no effect. Default true.
 * @property {function(unknown, Object): boolean} [isEmpty]    Whether a value counts as missing for `required`
 * @property {function(unknown, Object): unknown} [coerce]     Converts what the control reports into the stored value
 * @property {function(unknown, Object): string} [summary]     One line for a wide field in the inspector
 */

export const QUEUE = 'NAILS_CMS_WIDGET_EDITOR_FIELDS';

/** @type {Map<string, FieldTypeDefinition>} */
const types = new Map();

/**
 * @param {FieldTypeDefinition} definition
 * @throws {Error} When the definition has no type name or no component
 */
export function registerFieldType(definition) {
    if (!definition || typeof definition.type !== 'string' || definition.type === '') {
        throw new Error('A field type definition needs a type name');
    }
    if (!definition.component) {
        throw new Error('Field type "' + definition.type + '" needs a component');
    }

    types.set(definition.type, { layout: 'label', wide: true, ...definition });
}

/**
 * @param {string} type
 * @return {FieldTypeDefinition|null}
 */
export function fieldType(type) {
    return types.get(type) || null;
}

/** Forgets every type. For tests. */
export function clearFieldTypes() {
    types.clear();
}

/**
 * Registers what was queued before the editor loaded, then registers each later push straight away. A bad
 * definition is reported and skipped, so it cannot take the other types down with it.
 *
 * @param {Object} target Usually window
 */
export function installQueue(target) {
    const queued = Array.isArray(target[QUEUE]) ? target[QUEUE] : [];

    queued.forEach(registerSafely);
    target[QUEUE] = {
        push(...definitions) {
            definitions.forEach(registerSafely);
            return types.size;
        },
    };
}

/**
 * @param {FieldTypeDefinition} definition
 */
function registerSafely(definition) {
    try {
        registerFieldType(definition);
    } catch (error) {
        console.error('[widget editor] ' + error.message);
    }
}

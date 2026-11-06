/**
 * The rules of the field types module-cms ships (Nails\Cms\Cms\Fields), without their components, so they can be
 * tested without Vue. builtin-fields.js pairs them with the controls.
 */

import { plainText } from './fields.js';

/** @type {Object<string, Object>} Type name => definition, without `type` and `component` */
export const BUILTIN_RULES = {
    text: {},

    textarea: {},

    richtext: {
        layout: 'block',
        // The control is already a summary with a modal
        wide: false,
        summary: (value) => plainText(value) || 'Nothing yet',
    },

    select: {
        summary: (value, field) => (field.options && value in field.options ? field.options[value] : String(value ?? '')),
    },

    toggle: {
        className: 'checkbox',
        isEmpty: () => false,
        coerce: (raw) => Boolean(raw),
        summary: (value) => (value ? 'On' : 'Off'),
    },

    number: {
        // Nothing is clamped: a value outside min or max is kept, and the editor does not block it
        coerce: (raw) => {
            if (raw === '' || raw === null || raw === undefined) {
                return null;
            }
            const number = Number(raw);
            return Number.isFinite(number) ? number : null;
        },
        summary: (value) => (value === null || value === undefined ? 'Nothing yet' : String(value)),
    },

    repeater: {
        layout: 'block',
        summary: (value) => {
            const count = Array.isArray(value) ? value.length : 0;
            return count === 1 ? '1 item' : count + ' items';
        },
    },
};

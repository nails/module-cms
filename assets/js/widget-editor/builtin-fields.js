import { BUILTIN_RULES } from './builtin-rules.js';
import { registerFieldType } from './field-types.js';
import NumberControl from './controls/NumberControl.vue';
import RepeaterControl from './controls/RepeaterControl.vue';
import RichtextControl from './controls/RichtextControl.vue';
import SelectControl from './controls/SelectControl.vue';
import TableControl from './controls/TableControl.vue';
import TextareaControl from './controls/TextareaControl.vue';
import TextControl from './controls/TextControl.vue';
import ToggleControl from './controls/ToggleControl.vue';

const COMPONENTS = {
    text: TextControl,
    textarea: TextareaControl,
    richtext: RichtextControl,
    select: SelectControl,
    toggle: ToggleControl,
    number: NumberControl,
    repeater: RepeaterControl,
    table: TableControl,
};

/**
 * Registers the field types module-cms ships. They go through the same registry as any other module's types.
 */
export function registerBuiltinFields() {
    Object.keys(COMPONENTS).forEach((type) => {
        registerFieldType({ type, component: COMPONENTS[type], ...BUILTIN_RULES[type] });
    });
}

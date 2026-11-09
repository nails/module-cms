import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    QUEUE,
    clearFieldTypes,
    fieldType,
    installQueue,
    registerFieldType,
} from '../../assets/js/widget-editor/field-types.js';

const Control = { name: 'Control' };

beforeEach(() => {
    clearFieldTypes();
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('registerFieldType', () => {

    it('fills the layout and wide defaults', () => {
        registerFieldType({ type: 'colour', component: Control });

        expect(fieldType('colour')).toEqual({ type: 'colour', component: Control, layout: 'label', wide: true });
    });

    it('keeps the settings a definition gives', () => {
        registerFieldType({ type: 'colour', component: Control, layout: 'block', wide: false, className: 'x' });

        expect(fieldType('colour')).toMatchObject({ layout: 'block', wide: false, className: 'x' });
    });

    it('replaces an earlier definition of the same type', () => {
        const Other = { name: 'Other' };
        registerFieldType({ type: 'text', component: Control });
        registerFieldType({ type: 'text', component: Other });

        expect(fieldType('text').component).toBe(Other);
    });

    it('rejects a definition without a type name or a component', () => {
        expect(() => registerFieldType({ component: Control })).toThrow('type name');
        expect(() => registerFieldType({ type: 'colour' })).toThrow('needs a component');
    });

    it('returns null for a type nobody registered', () => {
        expect(fieldType('nope')).toBeNull();
    });
});

describe('installQueue', () => {

    it('registers what was queued before the editor loaded', () => {
        const target = { [QUEUE]: [{ type: 'colour', component: Control }] };

        installQueue(target);

        expect(fieldType('colour').component).toBe(Control);
    });

    it('registers a later push straight away', () => {
        const target = {};

        installQueue(target);
        target[QUEUE].push({ type: 'colour', component: Control }, { type: 'date', component: Control });

        expect(fieldType('colour')).not.toBeNull();
        expect(fieldType('date')).not.toBeNull();
    });

    it('reports and skips a bad definition without losing the others', () => {
        const error = vi.spyOn(console, 'error').mockImplementation(() => {});
        const target = { [QUEUE]: [{ type: 'broken' }, { type: 'colour', component: Control }] };

        installQueue(target);

        expect(fieldType('broken')).toBeNull();
        expect(fieldType('colour')).not.toBeNull();
        expect(error).toHaveBeenCalledOnce();
    });
});

import { describe, expect, it } from 'vitest';
import { serialiseFields } from '../../assets/js/widget-editor/serialise.js';

const field = (name, value, extra = {}) => ({
    name,
    type: 'text',
    value,
    checked: false,
    disabled: false,
    ...extra,
});

describe('serialiseFields', () => {

    it('serialises plain text inputs as strings', () => {
        expect(serialiseFields([field('title', 'Hello'), field('count', '3')])).toEqual({
            title: 'Hello',
            count: '3',
        });
    });

    it('skips disabled and unnamed fields', () => {
        expect(serialiseFields([
            field('a', 'kept'),
            field('b', 'skipped', { disabled: true }),
            field('', 'no name'),
        ])).toEqual({ a: 'kept' });
    });

    it('stores a single checkbox as a boolean, false when unchecked', () => {
        expect(serialiseFields([
            field('on', 'yes', { type: 'checkbox', checked: true }),
            field('off', 'no', { type: 'checkbox', checked: false }),
        ])).toEqual({ on: true, off: false });
    });

    it('stores a grouped checkbox as an array of checked values only', () => {
        expect(serialiseFields([
            field('tags[]', 'a', { type: 'checkbox', checked: true }),
            field('tags[]', 'b', { type: 'checkbox', checked: false }),
            field('tags[]', 'c', { type: 'checkbox', checked: true }),
        ])).toEqual({ tags: ['a', 'c'] });
    });

    it('stores an empty group when nothing is checked', () => {
        expect(serialiseFields([
            field('tags[]', 'a', { type: 'checkbox', checked: false }),
        ])).toEqual({ tags: [] });
    });

    it('stores a radio group as the checked value, or null when nothing is selected', () => {
        expect(serialiseFields([
            field('size', 'S', { type: 'radio', checked: false }),
            field('size', 'M', { type: 'radio', checked: true }),
            field('size', 'L', { type: 'radio', checked: false }),
        ])).toEqual({ size: 'M' });

        expect(serialiseFields([
            field('size', 'S', { type: 'radio', checked: false }),
            field('size', 'M', { type: 'radio', checked: false }),
        ])).toEqual({ size: null });
    });

    it('stores a multi-select as every selected value, or an empty array', () => {
        expect(serialiseFields([
            field('colours', ['red', 'blue'], { type: 'select-multiple' }),
        ])).toEqual({ colours: ['red', 'blue'] });

        expect(serialiseFields([
            field('colours', [], { type: 'select-multiple' }),
        ])).toEqual({ colours: [] });
    });

    it('expands bracket notation into nested objects and lists', () => {
        expect(serialiseFields([
            field('settings[layout][width]', '100'),
            field('settings[layout][height]', '50'),
        ])).toEqual({ settings: { layout: { width: '100', height: '50' } } });
    });

    it('builds a repeater as a list when rows are sequential', () => {
        expect(serialiseFields([
            field('items[0][label]', 'One'),
            field('items[1][label]', 'Two'),
        ])).toEqual({ items: [{ label: 'One' }, { label: 'Two' }] });
    });

    it('turns a repeater with gaps into an object keyed by index, not a padded list', () => {
        expect(serialiseFields([
            field('items[0][label]', 'One'),
            field('items[2][label]', 'Three'),
        ])).toEqual({ items: { 0: { label: 'One' }, 2: { label: 'Three' } } });
    });

    it('appends bare [] values to the end of the list', () => {
        expect(serialiseFields([
            field('items[][label]', 'One'),
            field('items[][label]', 'Two'),
        ])).toEqual({ items: [{ label: 'One' }, { label: 'Two' }] });
    });

    it('keeps an invalid bracket name as one literal key', () => {
        expect(serialiseFields([
            field('odd[name', 'x'),
        ])).toEqual({ 'odd[name': 'x' });
    });
});

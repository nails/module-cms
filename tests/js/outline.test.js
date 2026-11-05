import { describe, expect, it } from 'vitest';
import { dropTarget, pad, placeKey, rowDrop, snippet } from '../../assets/js/widget-editor/outline.js';

describe('snippet', () => {

    it('takes the first text field, without markup', () => {
        expect(snippet({ sTitle: '', body: '<p>Hello <strong>there</strong></p>' })).toBe('Hello there');
    });

    it('prefers a title-like field wherever it sits', () => {
        expect(snippet({ body: '<p>Body first</p>', sTitle: 'Hello canvas' })).toBe('Hello canvas');
    });

    it('skips values that read as settings rather than content', () => {
        expect(snippet({ size: 'LG', count: '3', title: 'Our people' })).toBe('Our people');
    });

    it('looks into repeater rows after the widget’s own fields', () => {
        expect(snippet({ items: [{ label: 'First card' }], style: 'light-grey' })).toBe('First card');
    });

    it('shortens long text', () => {
        const out = snippet({ body: 'a '.repeat(60) }, 10);
        expect(out.length).toBeLessThanOrEqual(10);
        expect(out.endsWith('…')).toBe(true);
    });

    it('decodes common entities', () => {
        expect(snippet({ body: 'Fish &amp; chips&nbsp;now' })).toBe('Fish & chips now');
    });

    it('is empty when there is no text', () => {
        expect(snippet({})).toBe('');
        expect(snippet(null)).toBe('');
        expect(snippet([])).toBe('');
    });
});

describe('dropTarget', () => {

    it('places before the row in its upper half', () => {
        expect(dropTarget(2, 3, 40)).toEqual({ index: 2, edge: 'before' });
    });

    it('places after the row in its lower half', () => {
        expect(dropTarget(2, 30, 40)).toEqual({ index: 3, edge: 'after' });
    });
});

describe('rowDrop', () => {

    const row = { uid: 'stack', index: 1, height: 48, parentUid: null, slot: null };

    it('places before the row in its upper half', () => {
        expect(rowDrop({ ...row, offsetY: 10, firstSlot: 'items', blocked: false })).toEqual({
            parentUid: null, slot: null, index: 1,
        });
    });

    it('places inside an open container from its lower half, not after the header', () => {
        expect(rowDrop({ ...row, offsetY: 30, firstSlot: 'items', blocked: false })).toEqual({
            parentUid: 'stack', slot: 'items', index: 0,
        });
    });

    it('places after a container that cannot take the drop, which is below its children', () => {
        expect(rowDrop({ ...row, offsetY: 30, firstSlot: 'items', blocked: true })).toEqual({
            parentUid: null, slot: null, index: 2,
        });
    });

    it('places after a collapsed container', () => {
        expect(rowDrop({ ...row, offsetY: 30, firstSlot: null, blocked: false })).toEqual({
            parentUid: null, slot: null, index: 2,
        });
    });
});

describe('placeKey', () => {

    it('distinguishes a nested list from the top level', () => {
        expect(placeKey(null, null, 0)).toBe('||0');
        expect(placeKey('stack', 'items', 0)).toBe('stack|items|0');
    });
});

describe('pad', () => {

    it('pads to two digits', () => {
        expect(pad(3)).toBe('03');
        expect(pad(12)).toBe('12');
    });
});

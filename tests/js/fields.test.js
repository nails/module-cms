import { describe, expect, it } from 'vitest';
import {
    addRow,
    canAddRow,
    coerce,
    isVisible,
    missingRequired,
    moveRow,
    newRow,
    plainText,
    removeRow,
    setRowValue,
    visibleFields,
} from '../../assets/js/widget-editor/fields.js';

const ROW = {
    key: 'aItems',
    type: 'repeater',
    label: 'Items',
    min: 0,
    max: 2,
    visibleWhen: {},
    default: [],
    fields: [
        { key: 'sText', type: 'text', label: 'Text', required: true, visibleWhen: {}, default: '' },
        { key: 'sTone', type: 'select', label: 'Tone', visibleWhen: {}, default: 'light', options: { light: 'Light' } },
    ],
};

const SCHEMA = [
    { key: 'bShowLink', type: 'toggle', label: 'Show link', visibleWhen: {}, default: false, required: false },
    { key: 'sLinkText', type: 'text', label: 'Link text', visibleWhen: { bShowLink: true }, default: '', required: true },
    { key: 'sHeading', type: 'text', label: 'Heading', visibleWhen: {}, default: '', required: true },
    ROW,
];

describe('isVisible and visibleFields', () => {

    it('shows a field with no conditions', () => {
        expect(isVisible(SCHEMA[2], {})).toBe(true);
    });

    it('shows a conditional field only while its condition holds', () => {
        expect(isVisible(SCHEMA[1], { bShowLink: false })).toBe(false);
        expect(isVisible(SCHEMA[1], { bShowLink: true })).toBe(true);
    });

    it('lists only the visible fields', () => {
        const keys = visibleFields(SCHEMA, { bShowLink: false }).map((field) => field.key);
        expect(keys).toEqual(['bShowLink', 'sHeading', 'aItems']);
    });
});

describe('missingRequired', () => {

    it('reports a required field that is empty', () => {
        expect(missingRequired(SCHEMA, { sHeading: '   ', aItems: [] })).toEqual([
            { path: 'sHeading', label: 'Heading' },
        ]);
    });

    it('does not report a conditional field while it is hidden', () => {
        const missing = missingRequired(SCHEMA, { bShowLink: false, sHeading: 'Hi', aItems: [] });
        expect(missing.map((item) => item.path)).not.toContain('sLinkText');
    });

    it('reports a conditional field once it is shown and empty', () => {
        const missing = missingRequired(SCHEMA, { bShowLink: true, sHeading: 'Hi', sLinkText: '', aItems: [] });
        expect(missing).toEqual([{ path: 'sLinkText', label: 'Link text' }]);
    });

    it('checks each repeater row by its own fields', () => {
        const missing = missingRequired(SCHEMA, {
            sHeading: 'Hi',
            aItems: [{ sText: 'ok', sTone: 'light' }, { sText: '', sTone: 'light' }],
        });
        expect(missing).toEqual([{ path: 'aItems[1].sText', label: 'Text' }]);
    });

    it('never treats a toggle as missing', () => {
        const toggle = { key: 'bOn', type: 'toggle', label: 'On', visibleWhen: {}, default: false, required: true };
        expect(missingRequired([toggle], { bOn: false })).toEqual([]);
    });
});

describe('repeater rows', () => {

    it('builds a new row from each child default', () => {
        expect(newRow(ROW)).toEqual({ sText: '', sTone: 'light' });
    });

    it('adds a row up to the maximum', () => {
        let rows = addRow(ROW, []);
        rows = addRow(ROW, rows);
        expect(rows).toHaveLength(2);
        expect(canAddRow(ROW, rows)).toBe(false);
        expect(addRow(ROW, rows)).toBe(rows);
    });

    it('removes a row down to the minimum', () => {
        const field = { ...ROW, min: 1 };
        const rows = [{ sText: 'a' }];
        expect(removeRow(field, rows, 0)).toBe(rows);
        expect(removeRow(ROW, [{ sText: 'a' }, { sText: 'b' }], 0)).toEqual([{ sText: 'b' }]);
    });

    it('moves a row up and down, and stays put at the ends', () => {
        const rows = [{ n: 1 }, { n: 2 }, { n: 3 }];
        expect(moveRow(rows, 0, 1).map((r) => r.n)).toEqual([2, 1, 3]);
        expect(moveRow(rows, 2, -1).map((r) => r.n)).toEqual([1, 3, 2]);
        expect(moveRow(rows, 0, -1)).toBe(rows);
        expect(moveRow(rows, 2, 1)).toBe(rows);
    });

    it('changes one child without touching the others', () => {
        const rows = [{ sText: 'a', sTone: 'light' }, { sText: 'b', sTone: 'light' }];
        const next = setRowValue(rows, 1, 'sText', 'changed');
        expect(next).toEqual([{ sText: 'a', sTone: 'light' }, { sText: 'changed', sTone: 'light' }]);
        expect(rows[1].sText).toBe('b');
    });
});

describe('coerce', () => {

    it('turns number input into a number, or null when empty', () => {
        const field = { type: 'number' };
        expect(coerce(field, '3')).toBe(3);
        expect(coerce(field, '')).toBeNull();
        expect(coerce(field, 'abc')).toBeNull();
    });

    it('keeps a number outside min and max, because nothing is blocked', () => {
        expect(coerce({ type: 'number', min: 0, max: 10 }, '42')).toBe(42);
    });

    it('turns a checkbox into a boolean', () => {
        expect(coerce({ type: 'toggle' }, true)).toBe(true);
        expect(coerce({ type: 'toggle' }, '')).toBe(false);
    });

    it('keeps a cdn object as a positive integer id', () => {
        expect(coerce({ type: 'cdn_object' }, '12')).toBe(12);
        expect(coerce({ type: 'cdn_object' }, '0')).toBeNull();
        expect(coerce({ type: 'cdn_object' }, '')).toBeNull();
    });

    it('leaves text as it is', () => {
        expect(coerce({ type: 'text' }, ' spaced ')).toBe(' spaced ');
    });
});

describe('plainText', () => {

    it('removes markup and collapses whitespace', () => {
        expect(plainText('<p>First <strong>rich</strong></p>\n<p>body</p>')).toBe('First rich body');
    });

    it('returns an empty string for no value', () => {
        expect(plainText(null)).toBe('');
    });
});

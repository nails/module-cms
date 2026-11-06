import { beforeAll, describe, expect, it } from 'vitest';
import { BUILTIN_RULES } from '../../assets/js/widget-editor/builtin-rules.js';
import { clearFieldTypes, registerFieldType } from '../../assets/js/widget-editor/field-types.js';
import {
    addRow,
    canAddRow,
    coerce,
    isEmpty,
    isVisible,
    missingRequired,
    moveRow,
    newRow,
    optionKeys,
    plainText,
    removeRow,
    rowTitle,
    setAt,
    setRowValue,
    summary,
    valueAt,
    visibleFields,
} from '../../assets/js/widget-editor/fields.js';

beforeAll(() => {
    clearFieldTypes();
    Object.keys(BUILTIN_RULES).forEach((type) => {
        registerFieldType({ type, component: {}, ...BUILTIN_RULES[type] });
    });
});

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

    it('names a row from its first field, using a select option label', () => {
        expect(rowTitle(ROW, { sText: '  Hello  ' }, 0)).toBe('Hello');
        expect(rowTitle(ROW, { sText: '' }, 2)).toBe('Item 3');
        const selectFirst = {
            ...ROW,
            fields: [
                { key: 'sTone', type: 'select', options: { dark: 'Dark' } },
                { key: 'sText', type: 'text' },
            ],
        };
        expect(rowTitle(selectFirst, { sTone: 'dark', sText: 'ignored' }, 0)).toBe('Dark');
    });

    it('reads and writes a repeater row without changing the other rows', () => {
        const data = { panels: [{ title: 'One', body: '' }, { title: 'Two', body: '<p>b</p>' }] };
        expect(valueAt(data, 'panels/1/body')).toBe('<p>b</p>');
        expect(valueAt(data, 'title')).toBeUndefined();

        const next = setAt(data, 'panels/0/title', 'Edited');
        expect(next.panels[0]).toEqual({ title: 'Edited', body: '' });
        expect(next.panels[1]).toBe(data.panels[1]);
        expect(data.panels[0].title).toBe('One');
        expect(setAt(data, 'label', 'Hi').label).toBe('Hi');
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

    it('leaves text as it is', () => {
        expect(coerce({ type: 'text' }, ' spaced ')).toBe(' spaced ');
    });

    it('stores what a type with no rule reports, including an unregistered type', () => {
        expect(coerce({ type: 'unknown' }, '7')).toBe('7');
    });
});

describe('isEmpty', () => {

    it('treats blank text, null and no rows as empty', () => {
        expect(isEmpty({ type: 'text' }, '  ')).toBe(true);
        expect(isEmpty({ type: 'number' }, null)).toBe(true);
        expect(isEmpty({ type: 'repeater' }, [])).toBe(true);
        expect(isEmpty({ type: 'number' }, 0)).toBe(false);
    });

    it('lets the type decide', () => {
        expect(isEmpty({ type: 'toggle' }, false)).toBe(false);
    });
});

describe('summary', () => {

    it('summarises each built-in type in one line', () => {
        expect(summary({ type: 'text' }, '')).toBe('Nothing yet');
        expect(summary({ type: 'textarea' }, 'Some notes')).toBe('Some notes');
        expect(summary({ type: 'richtext' }, '<p>Rich <b>body</b></p>')).toBe('Rich body');
        expect(summary({ type: 'select', options: { light: 'Light' } }, 'light')).toBe('Light');
        expect(summary({ type: 'toggle' }, true)).toBe('On');
        expect(summary({ type: 'number' }, 0)).toBe('0');
        expect(summary({ type: 'repeater' }, [{}, {}])).toBe('2 items');
        expect(summary({ type: 'repeater' }, [{}])).toBe('1 item');
    });
});

describe('optionKeys', () => {

    it('follows the order PHP gave, not the object order', () => {
        const field = { options: { 2: 'Two', 5: 'Five', '': 'Choose' }, order: ['', '5', '2'] };
        expect(optionKeys(field)).toEqual(['', '5', '2']);
    });

    it('falls back to the object order when order is missing or does not match', () => {
        expect(optionKeys({ options: { a: 'A', b: 'B' } })).toEqual(['a', 'b']);
        expect(optionKeys({ options: { a: 'A', b: 'B' }, order: ['a'] })).toEqual(['a', 'b']);
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

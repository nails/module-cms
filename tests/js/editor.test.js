import { describe, expect, it } from 'vitest';
import { createEditor } from '../../assets/js/widget-editor/editor.js';
import { organiserGroups, permits } from '../../assets/js/widget-editor/rules.js';
import { clearDraft, draftKey, loadDraft, saveDraft } from '../../assets/js/widget-editor/draft.js';

const heading = {
    slug: 'Heading',
    label: 'Heading',
    summary: 'A title',
    keywords: ['title'],
    groups: ['Text'],
    aliases: [],
    slots: {},
    defaults: { text: '' },
};

const note = {
    slug: 'Note',
    label: 'Note',
    summary: '',
    keywords: [],
    groups: [],
    aliases: ['legacy-note'],
    slots: {},
    defaults: {},
};

const columns = {
    slug: 'Columns',
    label: 'Columns',
    summary: '',
    keywords: [],
    groups: ['Layout'],
    aliases: [],
    slots: { left: { label: 'Left', max: null }, right: { label: 'Right', max: null } },
    defaults: {},
};

const catalogue = {
    widgets: { Heading: heading, Note: note, Columns: columns },
    groups: [
        { label: 'Text', slugs: ['Heading'] },
        { label: 'Layout', slugs: ['Columns'] },
        { label: 'Generic', slugs: ['Note'] },
    ],
    favourites: ['Note'],
    limits: { max_depth: 8, max_instances_per_area: 200 },
};

describe('permits', () => {

    it('allows everything when the area has no rules', () => {
        expect(permits(heading, {})).toBe(true);
    });

    it('applies allowed first, and treats an empty allowed list as nothing', () => {
        expect(permits(heading, { allowed: ['Heading'] })).toBe(true);
        expect(permits(note, { allowed: ['Heading'] })).toBe(false);
        expect(permits(heading, { allowed: [] })).toBe(false);
    });

    it('accepts an alias and a group, then removes disallowed matches', () => {
        expect(permits(note, { allowed: ['legacy-note'] })).toBe(true);
        expect(permits(heading, { allowed: ['group:Text'] })).toBe(true);
        expect(permits(note, { allowed: ['group:Generic'] })).toBe(true);
        expect(permits(heading, { allowed: ['group:Text'], disallowed: ['Heading'] })).toBe(false);
    });
});

describe('organiserGroups', () => {

    it('puts favourites first and filters by the area rules and the search', () => {
        const groups = organiserGroups(catalogue, { allowed: ['group:Text', 'Note'] }, 'title');
        expect(groups.map((group) => group.label)).toEqual(['Text']);
        expect(groups[0].slugs).toEqual(['Heading']);
    });

    it('does not offer hidden or deprecated widgets', () => {
        const withHidden = {
            ...catalogue,
            widgets: {
                ...catalogue.widgets,
                Heading: { ...heading, status: 'hidden' },
            },
        };
        const groups = organiserGroups(withHidden, {}, '');
        expect(groups.flatMap((group) => group.slugs)).not.toContain('Heading');
        expect(groups.flatMap((group) => group.slugs)).toContain('Note');
    });

    it('lists a favourite the area allows', () => {
        const groups = organiserGroups(catalogue, {}, '');
        expect(groups[0]).toEqual({ label: 'Favourites', slugs: ['Note'] });
    });
});

describe('draft', () => {

    it('round-trips a draft and ignores the order of the area names in the key', () => {
        const storage = new Map();
        const asStorage = {
            setItem: (key, value) => storage.set(key, value),
            getItem: (key) => storage.get(key) ?? null,
            removeItem: (key) => storage.delete(key),
        };

        expect(draftKey(['b', 'a'])).toBe(draftKey(['a', 'b']));
        saveDraft(asStorage, draftKey(['main']), { main: [{ slug: 'Heading', data: {} }] });
        expect(loadDraft(asStorage, draftKey(['main'])).areas.main).toHaveLength(1);
        clearDraft(asStorage, draftKey(['main']));
        expect(loadDraft(asStorage, draftKey(['main']))).toBeNull();
    });
});

describe('createEditor', () => {

    const areas = () => ([
        { slug: 'main', label: 'Main', rules: { allowed: ['Heading', 'Columns'] }, data: [{ slug: 'Heading', data: { text: 'Hi' } }] },
        { slug: 'side', label: 'Side', rules: {}, data: [] },
    ]);

    it('does not publish until something changes, and then publishes every area', () => {
        const seen = [];
        const source = areas();
        const editor = createEditor(source, catalogue, {
            onChange: (slug, data) => seen.push([slug, data]),
        });

        expect(seen).toEqual([]);
        expect(editor.stored('main')[0]).toBe(source[0].data[0]);

        editor.insert('Heading');
        expect(seen.map((entry) => entry[0])).toEqual(['main', 'side']);
        expect(editor.snapshot().nodes).toHaveLength(2);
    });

    it('refuses a widget the active area does not allow', () => {
        const editor = createEditor(areas(), catalogue);
        const result = editor.insert('Note');

        expect(result.ok).toBe(false);
        expect(editor.snapshot().nodes).toHaveLength(1);
        expect(editor.snapshot().message).toMatch(/not allowed/);
    });

    it('inserts into the first slot of the selected container', () => {
        const editor = createEditor([
            { slug: 'main', label: 'Main', data: [] },
        ], catalogue);

        const created = editor.insert('Columns');
        editor.select(created.uid);
        editor.insert('Heading');

        const columns = editor.stored('main')[0];
        expect(columns.data.left).toEqual([{ slug: 'Heading', data: { text: '' } }]);
        expect(columns.data.right).toEqual([]);
    });

    it('refuses a move into an area whose rules exclude the widget', () => {
        const editor = createEditor(areas(), catalogue);
        const heading = editor.snapshot().nodes[0];

        const result = editor.move(heading.uid, 'side', null, null, 0);
        expect(result.ok).toBe(true);

        editor.setActive('side');
        editor.insert('Note');
        const note = editor.snapshot().nodes.find((node) => node.slug === 'Note');
        const back = editor.move(note.uid, 'main', null, null, 0);

        expect(back.ok).toBe(false);
        expect(editor.stored('side').some((item) => item.slug === 'Note')).toBe(true);
    });

    it('undo restores the stored instances', () => {
        const source = areas();
        const editor = createEditor(source, catalogue);
        const before = JSON.stringify(editor.stored('main'));

        editor.insert('Heading');
        editor.undo();

        expect(JSON.stringify(editor.stored('main'))).toBe(before);
        expect(editor.snapshot().canUndo).toBe(false);
    });
});

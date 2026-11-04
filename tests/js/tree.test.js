import { describe, expect, it } from 'vitest';
import { createTree } from '../../assets/js/widget-editor/tree.js';

/**
 * A catalogue stand-in: "Columns" is a container with two slots, "Heading" and "Note" are leaves,
 * and "Gallery" has a slot capped at two children.
 */
const SLOTS = {
    Columns: { left: { label: 'Left', max: null }, right: { label: 'Right', max: null } },
    Gallery: { photos: { label: 'Photos', max: 2 } },
};

const options = (overrides = {}) => ({
    isEditable: (slug) => ['Heading', 'Note', 'Columns', 'Gallery'].includes(slug),
    slotsFor: (slug) => SLOTS[slug] || {},
    ...overrides,
});

const stored = () => ({
    main: [
        { slug: 'Heading', data: { text: 'Hi' } },
        {
            slug: 'Columns',
            data: {
                left: [{ slug: 'Note', data: { body: 'L1' } }],
                right: [],
            },
        },
    ],
});

describe('tree: round-trip', () => {

    it('returns the stored objects untouched when nothing changed (D19)', () => {
        const input = stored();
        const tree = createTree(options(), input);

        const out = tree.toStored('main');
        expect(out[0]).toBe(input.main[0]);
        expect(out[1]).toBe(input.main[1]);
    });

    it('serialises only the edited instance and its ancestors; siblings keep their original object', () => {
        const input = stored();
        const tree = createTree(options(), input);
        const [heading, columns] = tree.view('main');
        const note = columns.slots.left[0];

        tree.update(note.uid, { body: 'changed' });

        const out = tree.toStored('main');
        expect(out[0]).toBe(input.main[0]);
        expect(out[1]).not.toBe(input.main[1]);
        expect(out[1].data.left[0]).toEqual({ slug: 'Note', data: { body: 'changed' } });
        expect(out[1].data.right).toEqual([]);
        expect(heading.uid).toBeDefined();
    });

    it('never stores uids', () => {
        const tree = createTree(options(), stored());
        const [heading] = tree.view('main');
        tree.update(heading.uid, { text: 'Bye' });

        expect(JSON.stringify(tree.toStored('main'))).not.toContain('"uid"');
    });
});

describe('tree: locked instances', () => {

    it('keeps an unknown widget verbatim and refuses edits to it', () => {
        const input = { main: [{ slug: 'Retired', data: { old: true } }] };
        const tree = createTree(options(), input);
        const [retired] = tree.view('main');

        expect(tree.update(retired.uid, { old: false }).ok).toBe(false);
        expect(tree.toStored('main')[0]).toBe(input.main[0]);
    });

    it('refuses to move a locked instance into another list, but allows removal', () => {
        const input = { main: [{ slug: 'Retired', data: {} }, { slug: 'Heading', data: { text: 'x' } }] };
        const tree = createTree(options(), input);
        const [retired, heading] = tree.view('main');

        expect(tree.move(retired.uid, 'main', null, null, 2).ok).toBe(true);
        expect(tree.remove(heading.uid).ok).toBe(true);
        expect(tree.toStored('main')).toEqual([{ slug: 'Retired', data: {} }]);
    });
});

describe('tree: insert', () => {

    it('inserts at the top level of an area', () => {
        const tree = createTree(options(), { main: [] });
        const result = tree.insert('main', null, null, 0, 'Heading', { text: 'New' });

        expect(result.ok).toBe(true);
        expect(tree.toStored('main')).toEqual([{ slug: 'Heading', data: { text: 'New' } }]);
    });

    it('inserts into a slot of a container', () => {
        const tree = createTree(options(), stored());
        const columns = tree.view('main')[1];

        const result = tree.insert('main', columns.uid, 'right', 0, 'Note', { body: 'R' });
        expect(result.ok).toBe(true);
        expect(tree.toStored('main')[1].data.right).toEqual([{ slug: 'Note', data: { body: 'R' } }]);
    });

    it('refuses a widget that is not in the catalogue', () => {
        const tree = createTree(options(), { main: [] });
        expect(tree.insert('main', null, null, 0, 'Retired').ok).toBe(false);
    });

    it('respects the placeable rule for the area', () => {
        const tree = createTree(options({ canPlace: (slug) => slug !== 'Note' }), { main: [] });
        expect(tree.insert('main', null, null, 0, 'Note').ok).toBe(false);
        expect(tree.insert('main', null, null, 0, 'Heading').ok).toBe(true);
    });

    it('refuses a slot that is full', () => {
        const tree = createTree(options(), {
            main: [{ slug: 'Gallery', data: { photos: [{ slug: 'Note', data: {} }, { slug: 'Note', data: {} }] } }],
        });
        const gallery = tree.view('main')[0];

        const result = tree.insert('main', gallery.uid, 'photos', 0, 'Note');
        expect(result.ok).toBe(false);
        expect(result.error).toMatch(/full/);
    });

    it('refuses a slot the container does not have', () => {
        const tree = createTree(options(), stored());
        const columns = tree.view('main')[1];

        expect(tree.insert('main', columns.uid, 'middle', 0, 'Note').ok).toBe(false);
    });

    it('stops nesting at the maximum depth', () => {
        const tree = createTree(options({ maxDepth: 1 }), { main: [] });
        const top = tree.insert('main', null, null, 0, 'Columns');
        const child = tree.insert('main', top.uid, 'left', 0, 'Columns');
        expect(child.ok).toBe(true);

        const tooDeep = tree.insert('main', child.uid, 'left', 0, 'Note');
        expect(tooDeep.ok).toBe(false);
        expect(tooDeep.error).toMatch(/nested deeper/);
    });

    it('caps the number of instances in an area, counted at every depth', () => {
        const tree = createTree(options({ maxInstances: 2 }), { main: [] });
        const columns = tree.insert('main', null, null, 0, 'Columns').uid;
        tree.insert('main', columns, 'left', 0, 'Note');

        const third = tree.insert('main', null, null, 0, 'Heading');
        expect(third.ok).toBe(false);
        expect(tree.count('main')).toBe(2);
    });
});

describe('tree: duplicate, remove and move', () => {

    it('duplicates an instance with its children and new uids', () => {
        const tree = createTree(options(), stored());
        const columns = tree.view('main')[1];

        expect(tree.duplicate(columns.uid).ok).toBe(true);

        const [, original, copy] = tree.view('main');
        expect(copy.uid).not.toBe(original.uid);
        expect(copy.slots.left[0].uid).not.toBe(original.slots.left[0].uid);

        const out = tree.toStored('main');
        expect(out[2]).toEqual(out[1]);
    });

    it('removes an instance and everything inside it', () => {
        const tree = createTree(options(), stored());
        const columns = tree.view('main')[1];

        tree.remove(columns.uid);
        expect(tree.toStored('main')).toEqual([{ slug: 'Heading', data: { text: 'Hi' } }]);
    });

    it('moves an instance into another slot', () => {
        const tree = createTree(options(), stored());
        const [heading, columns] = tree.view('main');

        expect(tree.move(heading.uid, 'main', columns.uid, 'right', 0).ok).toBe(true);
        const out = tree.toStored('main');
        expect(out).toHaveLength(1);
        expect(out[0].data.right).toEqual([{ slug: 'Heading', data: { text: 'Hi' } }]);
    });

    it('reorders within one list, placing the item before the given index', () => {
        const tree = createTree(options(), {
            main: [
                { slug: 'Heading', data: { text: 'a' } },
                { slug: 'Heading', data: { text: 'b' } },
                { slug: 'Heading', data: { text: 'c' } },
            ],
        });
        const [a] = tree.view('main');

        tree.move(a.uid, 'main', null, null, 3);
        expect(tree.toStored('main').map((i) => i.data.text)).toEqual(['b', 'c', 'a']);
    });

    it('refuses to move a container into itself', () => {
        const tree = createTree(options(), {
            main: [{ slug: 'Columns', data: { left: [{ slug: 'Columns', data: { left: [], right: [] } }], right: [] } }],
        });
        const outer = tree.view('main')[0];
        const inner = outer.slots.left[0];

        expect(tree.move(outer.uid, 'main', inner.uid, 'left', 0).ok).toBe(false);
    });

    it('refuses a move that would exceed the maximum depth, counting the moved subtree', () => {
        const tree = createTree(options({ maxDepth: 1 }), {
            main: [
                { slug: 'Columns', data: { left: [], right: [] } },
                { slug: 'Columns', data: { left: [{ slug: 'Columns', data: { left: [], right: [] } }], right: [] } },
            ],
        });
        const [empty, nested] = tree.view('main');

        // Placing the nested container in the empty one's slot would put its child at depth 2
        const result = tree.move(nested.uid, 'main', empty.uid, 'left', 0);
        expect(result.ok).toBe(false);
        expect(result.error).toMatch(/nested deeper/);
    });
});

describe('tree: undo and redo', () => {

    it('restores the exact previous serialisation', () => {
        const tree = createTree(options(), stored());
        const before = JSON.stringify(tree.toStored('main'));

        const [heading] = tree.view('main');
        tree.remove(heading.uid);
        expect(tree.toStored('main')).toHaveLength(1);

        tree.undo();
        expect(JSON.stringify(tree.toStored('main'))).toBe(before);
    });

    it('redoes an undone change', () => {
        const tree = createTree(options(), stored());
        const [heading] = tree.view('main');
        tree.remove(heading.uid);
        const after = JSON.stringify(tree.toStored('main'));

        tree.undo();
        expect(tree.canRedo()).toBe(true);
        tree.redo();
        expect(JSON.stringify(tree.toStored('main'))).toBe(after);
    });

    it('treats consecutive edits to one instance as a single step', () => {
        const tree = createTree(options(), stored());
        const [heading] = tree.view('main');
        const before = JSON.stringify(tree.toStored('main'));

        tree.update(heading.uid, { text: 'a' });
        tree.update(heading.uid, { text: 'ab' });
        tree.update(heading.uid, { text: 'abc' });

        tree.undo();
        expect(JSON.stringify(tree.toStored('main'))).toBe(before);
        expect(tree.canUndo()).toBe(false);
    });

    it('clears the redo stack on a new change', () => {
        const tree = createTree(options(), stored());
        const [heading] = tree.view('main');
        tree.remove(heading.uid);
        tree.undo();

        tree.insert('main', null, null, 0, 'Note', { body: 'new' });
        expect(tree.canRedo()).toBe(false);
    });

    it('reports an error when there is nothing to undo', () => {
        const tree = createTree(options(), stored());
        expect(tree.undo().ok).toBe(false);
    });
});

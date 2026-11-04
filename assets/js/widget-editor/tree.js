/**
 * The WidgetEditor (v2) document: areas of widget instances, with containers nesting children in slots
 *
 * Pure JavaScript, no DOM and no Vue, so the rules can be tested on their own. The Vue shell wraps this.
 *
 * Stored form: each area is a list of {slug, data}; a container's children are inside its data, in named
 * slots. This module keeps the same shape while editing, plus a `uid` per instance that is never stored.
 *
 * Rules enforced here (the editor side, D25/D26; the server accepts what it is given):
 * - depth: a node may not sit deeper than `maxDepth` (top level is 0)
 * - total: an area holds at most `maxInstances` instances, counted at every depth
 * - slot max: a slot with `max` takes no more than that many children
 * - placeable: a widget is inserted or moved in only if `canPlace(slug)` allows it
 * - locked: an instance the editor cannot edit (unknown, disabled) is kept verbatim. It can be removed
 *   or reordered within its own list, but not edited or moved to another list
 *
 * Untouched instances serialise from their original object (D19), so saving an area that was only opened
 * gives back the same data.
 *
 * @typedef {Object} StoredInstance
 * @property {string} slug
 * @property {Object} data
 *
 * @typedef {Object} SlotDefinition
 * @property {string}      label
 * @property {number|null} max
 *
 * @typedef {Object} TreeOptions
 * @property {number}                              [maxDepth=8]
 * @property {number}                              [maxInstances=200]
 * @property {number}                              [historyLimit=100]
 * @property {(slug: string) => boolean}           [isEditable]  The widget is in the catalogue
 * @property {(slug: string) => boolean}           [canPlace]    The area rules allow it here
 * @property {(slug: string) => Object<string, SlotDefinition>} [slotsFor]
 */

const DEFAULTS = {
    maxDepth: 8,
    maxInstances: 200,
    historyLimit: 100,
    isEditable: () => true,
    canPlace: () => true,
    slotsFor: () => ({}),
};

/**
 * @param {TreeOptions} options
 * @param {Object<string, StoredInstance[]>} [areas] Stored areas to load
 */
export function createTree(options = {}, areas = {}) {

    const opts = { ...DEFAULTS, ...options };
    let uidCounter = 0;
    let state = {};
    let past = [];
    let future = [];
    let lastLabel = null;

    const newUid = () => 'n' + (++uidCounter);

    // ----------------------------------------------------------------------

    /**
     * Loads stored areas, replacing the document and clearing history
     * @param {Object<string, StoredInstance[]>} stored
     */
    function load(stored) {
        state = {};
        Object.keys(stored).forEach((areaSlug) => {
            state[areaSlug] = (stored[areaSlug] || []).map((instance) => fromStored(instance));
        });
        past = [];
        future = [];
        lastLabel = null;
        return api;
    }

    // ----------------------------------------------------------------------

    /**
     * Converts a stored instance into a node. The node keeps the stored object as `original`.
     * An editable container's slot children are pulled into `slots`; anything else stays in `data`.
     */
    function fromStored(instance) {

        const slug      = String(instance.slug);
        const editable  = opts.isEditable(slug);
        const data      = isPlainObject(instance.data) ? instance.data : {};
        const node      = {
            uid: newUid(),
            slug,
            data: { ...data },
            original: instance,
            locked: !editable,
            touched: false,
            inserted: false,
        };

        if (editable) {
            const slotNames = Object.keys(opts.slotsFor(slug));
            const slotCandidates = slotNames.filter((name) => Array.isArray(data[name]) && data[name].every(isStoredInstance));

            if (slotCandidates.length) {
                node.slots = {};
                slotCandidates.forEach((name) => {
                    node.slots[name] = data[name].map((child) => fromStored(child));
                    delete node.data[name];
                });
            }
        }

        return node;
    }

    // ----------------------------------------------------------------------

    /**
     * Builds a new node. Children of new nodes are only added by insert/duplicate.
     */
    function newNode(slug, data) {

        const node = {
            uid: newUid(),
            slug,
            data: { ...(isPlainObject(data) ? data : {}) },
            original: null,
            locked: false,
            touched: true,
            inserted: true,
        };

        const slots = Object.keys(opts.slotsFor(slug));
        if (slots.length) {
            node.slots = {};
            slots.forEach((name) => {
                node.slots[name] = [];
                delete node.data[name];
            });
        }

        return node;
    }

    /**
     * A copy of a subtree with fresh uids, marked as new
     */
    function copyNode(node) {

        const copy = {
            uid: newUid(),
            slug: node.slug,
            data: clone(node.data),
            original: null,
            locked: node.locked,
            touched: true,
            inserted: true,
        };

        if (node.slots) {
            copy.slots = {};
            Object.keys(node.slots).forEach((name) => {
                copy.slots[name] = node.slots[name].map((child) => copyNode(child));
            });
        }

        return copy;
    }

    // ----------------------------------------------------------------------

    /**
     * Finds a node by uid
     * @return {{node, list, index, parent, slot, area, depth}|null}
     */
    function locate(uid) {
        for (const area of Object.keys(state)) {
            const found = locateIn(state[area], uid, null, null, area, 0);
            if (found) {
                return found;
            }
        }
        return null;
    }

    function locateIn(list, uid, parent, slot, area, depth) {
        for (let index = 0; index < list.length; index++) {

            const node = list[index];
            if (node.uid === uid) {
                return { node, list, index, parent, slot, area, depth };
            }

            if (node.slots) {
                for (const name of Object.keys(node.slots)) {
                    const found = locateIn(node.slots[name], uid, node, name, area, depth + 1);
                    if (found) {
                        return found;
                    }
                }
            }
        }
        return null;
    }

    /** Number of instances in a subtree, including the root */
    function size(node) {
        let total = 1;
        if (node.slots) {
            Object.keys(node.slots).forEach((name) => {
                node.slots[name].forEach((child) => {
                    total += size(child);
                });
            });
        }
        return total;
    }

    /** Levels below a node: 0 for a leaf */
    function height(node) {
        let deepest = 0;
        if (node.slots) {
            Object.keys(node.slots).forEach((name) => {
                node.slots[name].forEach((child) => {
                    deepest = Math.max(deepest, 1 + height(child));
                });
            });
        }
        return deepest;
    }

    /** Instances in an area, at every depth */
    function areaCount(area) {
        return (state[area] || []).reduce((total, node) => total + size(node), 0);
    }

    function slotLimit(slug, slot) {
        const definition = opts.slotsFor(slug)[slot];
        return definition && definition.max !== undefined && definition.max !== null ? Number(definition.max) : null;
    }

    function contains(root, node) {
        if (root === node) {
            return true;
        }
        if (!root.slots) {
            return false;
        }
        return Object.keys(root.slots).some((name) => root.slots[name].some((child) => contains(child, node)));
    }

    // ----------------------------------------------------------------------

    /**
     * Runs a change: snapshots the document for undo, then applies the mutation.
     * Consecutive updates to one instance share a single undo step.
     */
    function commit(label, mutate, coalesce = false) {
        if (!(coalesce && label === lastLabel)) {
            past.push(clone(state));
            if (past.length > opts.historyLimit) {
                past.shift();
            }
        }
        future = [];
        lastLabel = label;
        mutate();
        return { ok: true };
    }

    function fail(error) {
        return { ok: false, error };
    }

    // ----------------------------------------------------------------------

    /**
     * Inserts a new widget
     * @return {{ok: boolean, uid?: string, error?: string}}
     */
    function insert(area, parentUid, slot, index, slug, data = {}) {

        if (!state[area]) {
            return fail('Unknown area');
        }
        if (!opts.isEditable(slug)) {
            return fail('"' + slug + '" cannot be edited here, so it cannot be added');
        }
        if (!opts.canPlace(slug)) {
            return fail('"' + slug + '" is not allowed in this area');
        }

        let list = state[area];
        let parent = null;
        let depth = 0;
        let limit = null;

        if (parentUid !== null && parentUid !== undefined) {
            const target = locate(parentUid);
            if (!target) {
                return fail('The container no longer exists');
            }
            if (!target.node.slots || !target.node.slots[slot]) {
                return fail('That container has no slot "' + slot + '"');
            }
            parent = target.node;
            list   = parent.slots[slot];
            depth  = target.depth + 1;
            limit  = slotLimit(parent.slug, slot);
        }

        if (depth > opts.maxDepth) {
            return fail('Widgets cannot be nested deeper than ' + opts.maxDepth + ' levels');
        }
        if (limit !== null && list.length >= limit) {
            return fail('This slot is full');
        }

        const node = newNode(slug, data);
        if (areaCount(area) + size(node) > opts.maxInstances) {
            return fail('This area holds at most ' + opts.maxInstances + ' widgets');
        }

        commit('insert', () => {
            list.splice(clampIndex(index, list.length), 0, node);
            if (parent) {
                parent.touched = true;
            }
        });

        return { ok: true, uid: node.uid };
    }

    /**
     * Removes an instance and everything inside it
     */
    function remove(uid) {

        const found = locate(uid);
        if (!found) {
            return fail('That widget no longer exists');
        }

        return commit('remove', () => {
            found.list.splice(found.index, 1);
            if (found.parent) {
                found.parent.touched = true;
            }
        });
    }

    /**
     * Copies an instance (and its children) to just after itself
     */
    function duplicate(uid) {

        const found = locate(uid);
        if (!found) {
            return fail('That widget no longer exists');
        }

        if (found.node.locked) {
            return fail('This widget cannot be edited here, so it cannot be duplicated');
        }

        const limit = found.slot ? slotLimit(found.parent.slug, found.slot) : null;
        if (limit !== null && found.list.length >= limit) {
            return fail('This slot is full');
        }

        const copy = copyNode(found.node);
        if (areaCount(found.area) + size(copy) > opts.maxInstances) {
            return fail('This area holds at most ' + opts.maxInstances + ' widgets');
        }

        return commit('duplicate', () => {
            found.list.splice(found.index + 1, 0, copy);
            if (found.parent) {
                found.parent.touched = true;
            }
        });
    }

    /**
     * Moves an instance to another position, possibly in another slot or area
     * @param {string}      uid
     * @param {string}      area       The destination area
     * @param {string|null} parentUid  The destination container, or null for the top level of the area
     * @param {string|null} slot       The destination slot, when parentUid is set
     * @param {number}      index      The position in the destination list
     */
    function move(uid, area, parentUid, slot, index) {

        const found = locate(uid);
        if (!found) {
            return fail('That widget no longer exists');
        }
        if (!state[area]) {
            return fail('Unknown area');
        }

        let list   = state[area];
        let parent = null;
        let depth  = 0;
        let limit  = null;

        if (parentUid !== null && parentUid !== undefined) {
            const target = locate(parentUid);
            if (!target) {
                return fail('The container no longer exists');
            }
            if (contains(found.node, target.node)) {
                return fail('A widget cannot be moved into itself');
            }
            if (!target.node.slots || !target.node.slots[slot]) {
                return fail('That container has no slot "' + slot + '"');
            }
            parent = target.node;
            list   = parent.slots[slot];
            depth  = target.depth + 1;
            limit  = slotLimit(parent.slug, slot);
        }

        const sameList = list === found.list;

        if (found.node.locked && !sameList) {
            return fail('This widget cannot be moved out of its place');
        }
        if (depth + height(found.node) > opts.maxDepth) {
            return fail('Widgets cannot be nested deeper than ' + opts.maxDepth + ' levels');
        }
        if (!sameList && limit !== null && list.length >= limit) {
            return fail('This slot is full');
        }
        if (found.area !== area && areaCount(area) + size(found.node) > opts.maxInstances) {
            return fail('This area holds at most ' + opts.maxInstances + ' widgets');
        }

        return commit('move', () => {
            found.list.splice(found.index, 1);

            let target = clampIndex(index, list.length);
            if (sameList && index > found.index) {
                target = clampIndex(index - 1, list.length);
            }

            list.splice(target, 0, found.node);

            if (found.parent) {
                found.parent.touched = true;
            }
            if (parent) {
                parent.touched = true;
            }
        });
    }

    /**
     * Changes an instance's own data. Slot children are not changed here.
     * @param {string} uid
     * @param {Object} patch
     */
    function update(uid, patch) {

        const found = locate(uid);
        if (!found) {
            return fail('That widget no longer exists');
        }
        if (found.node.locked) {
            return fail('This widget cannot be edited here');
        }

        const changes = {};
        Object.keys(patch || {}).forEach((key) => {
            if (!(found.node.slots && key in found.node.slots)) {
                changes[key] = patch[key];
            }
        });

        return commit('update:' + uid, () => {
            Object.assign(found.node.data, clone(changes));
            found.node.touched = true;
        }, true);
    }

    // ----------------------------------------------------------------------

    function undo() {
        if (!past.length) {
            return fail('Nothing to undo');
        }
        future.push(clone(state));
        state = past.pop();
        lastLabel = null;
        return { ok: true };
    }

    function redo() {
        if (!future.length) {
            return fail('Nothing to redo');
        }
        past.push(clone(state));
        state = future.pop();
        lastLabel = null;
        return { ok: true };
    }

    // ----------------------------------------------------------------------

    /**
     * The stored form of an area, without uids
     * @return {StoredInstance[]}
     */
    function toStored(area) {
        return (state[area] || []).map((node) => serialiseNode(node));
    }

    function serialiseNode(node) {

        if (!node.touched && !node.inserted && !hasChangedChild(node)) {
            return node.original;
        }

        const data = clone(node.data);
        if (node.slots) {
            Object.keys(node.slots).forEach((name) => {
                data[name] = node.slots[name].map((child) => serialiseNode(child));
            });
        }

        return { slug: node.slug, data };
    }

    function hasChangedChild(node) {
        if (!node.slots) {
            return false;
        }
        return Object.keys(node.slots).some((name) => node.slots[name].some(
            (child) => child.touched || child.inserted || hasChangedChild(child)
        ));
    }

    /**
     * A read-only copy of an area, with uids, for rendering
     */
    function view(area) {
        return clone(state[area] || []);
    }

    function areaSlugs() {
        return Object.keys(state);
    }

    const api = {
        load,
        insert,
        remove,
        duplicate,
        move,
        update,
        undo,
        redo,
        canUndo: () => past.length > 0,
        canRedo: () => future.length > 0,
        toStored,
        view,
        locate: (uid) => {
            const found = locate(uid);
            return found ? { node: clone(found.node), area: found.area, depth: found.depth } : null;
        },
        count: areaCount,
        areas: areaSlugs,
        maxDepth: () => opts.maxDepth,
        maxInstances: () => opts.maxInstances,
    };

    load(areas);
    return api;
}

// --------------------------------------------------------------------------

function clampIndex(index, length) {
    const n = Number.isInteger(index) ? index : length;
    return Math.max(0, Math.min(n, length));
}

function isPlainObject(value) {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function isStoredInstance(value) {
    return isPlainObject(value) && typeof value.slug === 'string' && 'data' in value;
}

function clone(value) {
    return value === undefined ? undefined : JSON.parse(JSON.stringify(value));
}

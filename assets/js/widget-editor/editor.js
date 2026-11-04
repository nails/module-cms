/**
 * The WidgetEditor session: one document, several areas, commands the shell calls
 *
 * Plain JavaScript. The Vue overlay renders `snapshot()` and calls the methods.
 */

import { createTree } from './tree.js';
import { permits } from './rules.js';

/**
 * @param {Array<{slug: string, label?: string, rules?: Object, data?: Array}>} areas
 * @param {Object} catalogue The catalogue payload
 * @param {{onChange?: Function}} [hooks]
 */
export function createEditor(areas, catalogue, hooks = {}) {

    const areaList = areas.map((area) => ({
        slug: area.slug,
        label: area.label || area.slug,
        rules: area.rules || {},
        data: Array.isArray(area.data) ? area.data : [],
    }));

    const stored = {};
    areaList.forEach((area) => {
        stored[area.slug] = area.data;
    });

    const limits = catalogue.limits || {};
    const tree = createTree({
        maxDepth: limits.max_depth,
        maxInstances: limits.max_instances_per_area,
        isEditable: (slug) => Boolean(catalogue.widgets[slug]),
        canPlace: () => true,
        slotsFor: (slug) => catalogue.widgets[slug]?.slots || {},
    }, stored);

    let active = areaList[0] ? areaList[0].slug : '';
    let selected = null;
    let message = '';

    function current() {
        return areaList.find((area) => area.slug === active) || null;
    }

    function placeable(slug) {
        const widget = catalogue.widgets[slug];
        const area = current();
        return Boolean(widget && area && permits(widget, area.rules));
    }

    function destination() {
        const fallback = {
            area: active,
            parentUid: null,
            slot: null,
            index: tree.view(active).length,
        };

        if (!selected) {
            return fallback;
        }

        const found = tree.locate(selected);
        if (!found || found.area !== active) {
            return fallback;
        }

        const slotNames = found.node.slots ? Object.keys(found.node.slots) : [];
        if (slotNames.length) {
            const slot = slotNames[0];
            return {
                area: found.area,
                parentUid: found.node.uid,
                slot,
                index: found.node.slots[slot].length,
            };
        }

        return {
            area: found.area,
            parentUid: found.parentUid,
            slot: found.slot,
            index: found.index + 1,
        };
    }

    function apply(result) {
        message = result.ok ? '' : (result.error || 'That change was not made');
        if (result.ok) {
            publish();
        }
        return result;
    }

    function publish() {
        if (typeof hooks.onChange !== 'function') {
            return;
        }
        tree.areas().forEach((slug) => {
            hooks.onChange(slug, tree.toStored(slug));
        });
    }

    function insert(slug) {
        const widget = catalogue.widgets[slug];
        if (!widget) {
            return apply({ ok: false, error: '"' + slug + '" cannot be edited here, so it cannot be added' });
        }
        if (!placeable(slug)) {
            return apply({ ok: false, error: '"' + (widget.label || slug) + '" is not allowed in this area' });
        }

        const dest = destination();
        const result = tree.insert(dest.area, dest.parentUid, dest.slot, dest.index, slug, widget.defaults || {});
        if (result.ok) {
            selected = result.uid;
        }
        return apply(result);
    }

    function remove(uid) {
        const result = tree.remove(uid || selected);
        if (result.ok && (uid || selected) === selected) {
            selected = null;
        }
        return apply(result);
    }

    function duplicate(uid) {
        const result = tree.duplicate(uid || selected);
        return apply(result);
    }

    function nudge(uid, delta) {
        return apply(tree.nudge(uid || selected, delta));
    }

    function move(uid, area, parentUid, slot, index) {
        const found = tree.locate(uid);
        const widget = found ? catalogue.widgets[found.node.slug] : null;
        const target = areaList.find((item) => item.slug === area);

        if (widget && target && !permits(widget, target.rules)) {
            return apply({ ok: false, error: '"' + widget.label + '" is not allowed in this area' });
        }

        return apply(tree.move(uid, area, parentUid, slot, index));
    }

    function update(uid, patch, replace = false) {
        return apply(tree.update(uid, patch, replace));
    }

    function undo() {
        const result = tree.undo();
        if (result.ok && selected && !tree.locate(selected)) {
            selected = null;
        }
        return apply(result);
    }

    function redo() {
        return apply(tree.redo());
    }

    function snapshot() {
        return {
            areas: areaList.map((area) => ({ slug: area.slug, label: area.label })),
            active,
            selected,
            nodes: tree.view(active),
            stored: active ? tree.toStored(active) : [],
            message,
            canUndo: tree.canUndo(),
            canRedo: tree.canRedo(),
            count: active ? tree.count(active) : 0,
            maxInstances: tree.maxInstances(),
        };
    }

    return {
        snapshot,
        placeable,
        setActive(slug) {
            if (areaList.some((area) => area.slug === slug)) {
                active = slug;
                selected = null;
                message = '';
            }
        },
        select(uid) {
            selected = uid;
        },
        insert,
        remove,
        duplicate,
        nudge,
        move,
        update,
        undo,
        redo,
        stored: (slug) => tree.toStored(slug),
        locate: (uid) => tree.locate(uid),
    };
}

/**
 * A short-lived local draft of the areas open in one editor session
 *
 * The textarea stays the source of truth. This copy is only for recovering after a crash or navigation.
 */

const PREFIX = 'cms-widget-editor:';

/**
 * Stable key for a set of areas, independent of tab order
 * @param {string[]} slugs
 * @return {string}
 */
export function draftKey(slugs) {
    return PREFIX + slugs.slice().sort().join('|');
}

/**
 * @param {Storage} storage
 * @param {string} key
 * @param {Object<string, Array|null>} areas
 */
export function saveDraft(storage, key, areas) {
    storage.setItem(key, JSON.stringify({
        savedAt: Date.now(),
        areas,
    }));
}

/**
 * @param {Storage} storage
 * @param {string} key
 * @return {{savedAt: number, areas: Object}|null}
 */
export function loadDraft(storage, key) {
    const raw = storage.getItem(key);
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed.areas !== 'object' || parsed.areas === null) {
            return null;
        }
        return parsed;
    } catch (e) {
        return null;
    }
}

/**
 * @param {Storage} storage
 * @param {string} key
 */
export function clearDraft(storage, key) {
    storage.removeItem(key);
}

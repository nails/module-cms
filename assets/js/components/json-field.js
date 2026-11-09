/**
 * Writes JSON into a hidden field without changing it when the value is the same
 *
 * MySQL returns JSON text with a space after each colon and comma, and JSON.stringify
 * does not add them. The page's unsaved-changes check compares the raw text, so
 * rewriting an unchanged field marks the form dirty. The first write keeps the text the
 * field started with, and a value equal to that original is written back as that text,
 * so an edit that is reverted leaves the field exactly as it was.
 */

const originals = new WeakMap();

/**
 * Whether two JSON strings encode the same value
 * @param {String} current
 * @param {String} next
 * @return {Boolean}
 */
export function sameJson(current, next) {

    if (current === next) {
        return true;
    }

    try {
        return JSON.stringify(JSON.parse(current)) === JSON.stringify(JSON.parse(next));
    } catch (e) {
        return false;
    }
}

/**
 * @param {{value: string, dispatchEvent: Function}} element
 * @param {*} data
 * @return {Boolean} Whether the field changed
 */
export function writeJsonField(element, data) {

    if (!element) {
        return false;
    }

    if (!originals.has(element)) {
        originals.set(element, element.value);
    }

    const original = originals.get(element);
    const next = JSON.stringify(data);
    const target = sameJson(original, next) ? original : next;

    if (element.value === target) {
        return false;
    }

    element.value = target;
    element.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
}

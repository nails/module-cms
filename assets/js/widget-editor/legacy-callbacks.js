/**
 * Runs a v1 widget's "dropped" or "removed" script for a v2 bridge (D13)
 *
 * In v1 the script is the body of a function that takes one argument, `domElement`: the widget's root as a jQuery
 * object. Across EZ's 109 dropped.js files the script uses only `domElement` (with jQuery) and
 * `window.NAILS.ADMIN.refreshUi`. None calls the editor through `this`, so `this` is an empty object here.
 *
 * A script that throws is reported, not rethrown, so one broken widget does not stop the editor.
 */

/**
 * @param {'dropped'|'removed'} name
 * @param {string} source The script, as stored by the server
 * @param {Element|jQuery} element The widget's form root
 * @return {string|null} The error message when the script threw; null when it ran, or there was nothing to run
 */
export function runLegacyCallback(name, source, element) {
    if (typeof source !== 'string' || source.trim() === '') {
        return null;
    }

    const $ = window.jQuery;
    if (typeof $ !== 'function') {
        return 'The ' + name + ' script needs jQuery';
    }

    try {
        const callback = new Function('domElement', source);
        callback.call({}, $(element));
        return null;
    } catch (error) {
        return error && error.message ? error.message : String(error);
    }
}

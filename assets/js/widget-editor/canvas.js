/**
 * The WidgetEditor canvas document
 *
 * The canvas is one same-origin iframe. It loads the site's stylesheets and each instance's server-rendered
 * preview. The iframe is sandboxed without allow-scripts (D20), so preview markup does not run script. This module
 * builds that document. It is pure so the rules can be tested without a DOM.
 */

/**
 * Keeps only stylesheet URLs that are http(s) or root-relative. Anything else is dropped, including
 * javascript: and data: URLs.
 * @param {unknown[]} urls
 * @return {string[]}
 */
export function canvasStylesheets(urls) {
    if (!Array.isArray(urls)) {
        return [];
    }

    return urls.filter((url) => {
        if (typeof url !== 'string') {
            return false;
        }
        const safe = /^(https?:\/\/|\/(?!\/))/i.test(url);
        return safe && /\.css(\?[^#]*)?(#.*)?$/i.test(url);
    });
}

/**
 * @param {string} value
 * @return {string}
 */
export function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * One canvas section: the preview HTML for a top-level instance, or its error
 *
 * @typedef {Object} CanvasSection
 * @property {string}      uid
 * @property {string}      html   Server-rendered preview. Trusted: the server produced it.
 * @property {string|null} error  Set when the preview failed
 * @property {boolean}     locked  The instance is kept as saved and has no preview
 * @property {boolean}     [pending] The preview has been requested and has not come back yet
 */

/**
 * @param {CanvasSection} section
 * @return {string}
 */
export function renderSection(section) {

    const uid = escapeHtml(section.uid);

    if (section.pending) {
        return '<div class="cms-canvas-item" data-uid="' + uid + '">'
            + '<div class="cms-canvas-placeholder">Loading preview…</div></div>';
    }

    if (section.error) {
        return '<div class="cms-canvas-item" data-uid="' + uid + '">'
            + '<div class="cms-canvas-error"><strong>This widget could not be previewed.</strong> '
            + escapeHtml(section.error) + '</div></div>';
    }

    if (section.locked) {
        return '<div class="cms-canvas-item" data-uid="' + uid + '">'
            + '<div class="cms-canvas-placeholder">Saved content. Edit it in the editor that created it.</div></div>';
    }

    if (!section.html) {
        return '<div class="cms-canvas-item" data-uid="' + uid + '">'
            + '<div class="cms-canvas-placeholder">Nothing to preview.</div></div>';
    }

    return '<div class="cms-canvas-item" data-uid="' + uid + '">' + section.html + '</div>';
}

const CANVAS_STYLE = 'body{margin:0;padding:24px 32px 48px;background:#fff;color:#222;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}'
    + '.cms-canvas-item{position:relative;margin:0;outline:1px dashed transparent}'
    + '.cms-canvas-item:hover,.cms-canvas-item:focus-within{outline-color:#9fb3c8}'
    + '.cms-we-insert{display:flex;align-items:center;gap:8px;width:100%;margin:6px 0;padding:0;border:0;background:transparent;color:#829ab1;cursor:pointer;font:inherit;font-size:12px}'
    + '.cms-we-insert:before,.cms-we-insert:after{content:"";flex:1;border-top:1px dashed #d9e2ec}'
    + '.cms-we-insert:hover{color:#1f2933}'
    + '.cms-we-insert:hover:before,.cms-we-insert:hover:after{border-top-color:#627d98}'
    + '.cms-canvas-error{padding:8px 12px;background:#fdecea;color:#611a15;border:1px solid #f5c2c0}'
    + '.cms-canvas-placeholder{padding:8px 12px;color:#627d98;border:1px dashed #bcccdc}'
    + '[data-cms-edit]{outline:1px dashed transparent;outline-offset:2px;cursor:text}'
    + '[data-cms-edit]:hover{outline-color:#9fb3c8}'
    + '[data-cms-edit="rich"]{cursor:pointer}'
    + '[data-cms-edit="text"]:focus{outline:2px solid #3b82f6;border-radius:2px}';

/**
 * The canvas document with no sections. The canvas loads this once and patches instances into it, so a
 * change does not reload the frame (and does not lose the caret while typing).
 *
 * @param {Object} options
 * @param {unknown[]} options.stylesheets Raw catalogue canvas_assets
 * @return {string}
 */
export function buildCanvasShell({ stylesheets }) {

    const links = canvasStylesheets(stylesheets)
        .map((url) => '<link rel="stylesheet" href="' + escapeHtml(url) + '">')
        .join('');

    return '<!doctype html><html><head><meta charset="utf-8">'
        + links
        + '<style>' + CANVAS_STYLE + '</style>'
        + '</head><body></body></html>';
}

/**
 * The whole canvas document, for use as an iframe srcdoc
 *
 * @param {Object} options
 * @param {unknown[]} options.stylesheets  Raw catalogue canvas_assets
 * @param {CanvasSection[]} options.sections
 * @return {string}
 */
export function buildCanvasDocument({ stylesheets, sections }) {

    const body = sections.map(renderSection).join('');

    return buildCanvasShell({ stylesheets }).replace('</body></html>', body + '</body></html>');
}

/**
 * Finds the node that owns an inline field in the canvas. The canvas reports a top-level instance and a path
 * within it (e.g. 'items/1' is the second child of the 'items' slot); this walks the tree to that node.
 *
 * @param {Array<{uid: string, slots?: Object}>} nodes Top-level nodes of the area
 * @param {string} topUid
 * @param {string} path Slash-separated path, '' for the top-level instance itself
 * @return {string|null} The node's uid, or null when the path no longer exists
 */
export function resolveNodeUid(nodes, topUid, path) {

    let node = (nodes || []).find((item) => item.uid === topUid) || null;
    const segments = path ? String(path).split('/') : [];

    for (let i = 0; node && i < segments.length; i += 2) {
        const children = node.slots && node.slots[segments[i]];
        node = Array.isArray(children) ? children[Number(segments[i + 1])] || null : null;
    }

    return node ? node.uid : null;
}

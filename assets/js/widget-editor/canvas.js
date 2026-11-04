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

/**
 * The whole canvas document, for use as an iframe srcdoc
 *
 * @param {Object} options
 * @param {unknown[]} options.stylesheets  Raw catalogue canvas_assets
 * @param {CanvasSection[]} options.sections
 * @return {string}
 */
export function buildCanvasDocument({ stylesheets, sections }) {

    const links = canvasStylesheets(stylesheets)
        .map((url) => '<link rel="stylesheet" href="' + escapeHtml(url) + '">')
        .join('');

    const body = sections.map(renderSection).join('');

    return '<!doctype html><html><head><meta charset="utf-8">'
        + links
        + '<style>'
        + 'body{margin:0;padding:16px;background:#fff;color:#222}'
        + '.cms-canvas-item{position:relative;margin:0 0 16px;outline:1px dashed transparent}'
        + '.cms-canvas-item:hover{outline-color:#9fb3c8}'
        + '.cms-canvas-error{padding:8px 12px;background:#fdecea;color:#611a15;border:1px solid #f5c2c0}'
        + '.cms-canvas-placeholder{padding:8px 12px;color:#627d98;border:1px dashed #bcccdc}'
        + '</style>'
        + '</head><body>' + body + '</body></html>';
}

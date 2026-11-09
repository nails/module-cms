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
 * @property {string}      [slug]  The stored slug, named in the locked warning
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
        const who = section.slug ? escapeHtml(section.slug) : 'This widget';
        return '<div class="cms-canvas-item" data-uid="' + uid + '">'
            + '<div class="cms-canvas-warning">'
            + '<strong><span class="cms-canvas-warning__mark" aria-hidden="true">⚠</span> Kept as saved</strong>'
            + '<span>' + who + ' cannot be edited here. It can be removed or reordered, '
            + 'and edited in the editor that created it.</span>'
            + '</div></div>';
    }

    if (!section.html) {
        return '<div class="cms-canvas-item" data-uid="' + uid + '">'
            + '<div class="cms-canvas-placeholder">Nothing to preview.</div></div>';
    }

    return '<div class="cms-canvas-item" data-uid="' + uid + '">' + section.html + '</div>';
}

/**
 * Instances whose cached preview is out of date.
 *
 * A first preview has no cache entry, so it is left out: the canvas shows its loading placeholder instead of
 * dimming an empty box. Locked instances are left out too.
 *
 * @param {Array<{uid: string, locked?: boolean}>} items
 * @param {Map<string, {key: ?string}>} cache
 * @param {(item: {uid: string}) => string} keyOf
 * @return {string[]}
 */
export function refreshingUids(items, cache, keyOf) {
    return (items || []).filter((item) => {
        if (!item || item.locked) {
            return false;
        }
        const entry = cache.get(item.uid);
        return Boolean(entry) && entry.key !== keyOf(item);
    }).map((item) => item.uid);
}

/*
 * The frame is as tall as its content and the editor page scrolls it, so the frame has no scrollbar of its own.
 * Events do not reach a frame that cannot run script, so hover, selection and editing are drawn by the editor
 * over the frame, and nothing here reacts to the pointer.
 */
const CANVAS_STYLE = 'html{overflow:hidden}'
    + 'body{display:flow-root;margin:0;padding:0;background:#fff;color:#222;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}'
    // :where() loses to any site rule, so these are only the defaults when the site does not set them.
    + ':where(body){line-height:1.45}'
    + ':where(h1,h2,h3,h4,h5,h6){line-height:1.3}'
    + ':where(blockquote){margin:1em 0;padding-left:1em;border-left:3px solid #cfc9c2}'
    + ':where(pre){margin:1em 0;padding:12px 14px;overflow-x:auto;background:#f6f4f1;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.92em;line-height:1.45;white-space:pre-wrap}'
    // Padding lives inside the widget's box, so the selection line stays on the canvas edge.
    + '.cms-canvas-item{display:flow-root;position:relative;margin:0;padding:12px 28px}'
    + '.cms-we-gap{height:24px}'
    + '.cms-we-hint{margin:0;color:#8a837d}'
    // The same inset and the same gap a widget has on the canvas. Columns keep the grid's own gutter.
    + '.cms-we-slot{display:flow-root;box-sizing:border-box;padding:24px 28px}'
    + '.cms-we-slot>*+*:not([class*="col-"]){margin-top:24px}'
    // An empty slot holds the place the editor draws its add button over
    + '.cms-we-slot-empty{box-sizing:border-box;min-height:64px;border:1px dashed #cfc9c2;border-radius:3px;background:rgba(250,248,246,.85)}'
    + '.cms-canvas-error{padding:10px 14px;background:#fdf2f2;color:#7a1f1f;border:1px solid #f1caca;font-size:13px}'
    + '.cms-canvas-warning{display:flex;flex-direction:column;gap:4px;padding:12px 14px;background:#fdf8e8;color:#6b5417;border:1px solid #ecd9a0;font-size:13px;line-height:1.45;text-align:left}'
    + '.cms-canvas-warning__mark{margin-right:6px}'
    + '.cms-canvas-placeholder{padding:18px 14px;color:#8a837d;border:1px dashed #d8d2cb;font-size:12px;letter-spacing:.06em;text-align:center;text-transform:uppercase}'
    // Accordion panels have no script here, so the open panel is the one carrying .in.
    // The editor writes that open state when the heading is clicked.
    + '.cms-widget-accordion .panel-group{margin:0}'
    + '.cms-widget-accordion .panel{margin:0 0 8px;border:1px solid #e4dfd8;border-radius:3px;background:#fff;box-shadow:none}'
    + '.cms-widget-accordion .panel:last-child{margin-bottom:0}'
    + '.cms-widget-accordion .panel-heading{padding:9px 12px;background:#faf8f6;border:0;border-radius:3px}'
    + '.cms-widget-accordion .panel:has(.panel-collapse.in)>.panel-heading{border-bottom:1px solid #e4dfd8;border-radius:3px 3px 0 0}'
    + '.cms-widget-accordion .panel-title{display:flex;align-items:center;gap:8px;margin:0;font-size:15px;font-weight:600;line-height:1.35}'
    + '.cms-widget-accordion .cms-accordion-title{flex:1;min-width:0}'
    + '.cms-widget-accordion .cms-accordion-title[data-cms-empty]{color:#8a837d;font-weight:400}'
    + '.cms-widget-accordion .cms-accordion-mark{display:flex;flex:none;align-items:center;justify-content:center;width:28px;height:28px;color:#6f675f}'
    + '.cms-widget-accordion .cms-accordion-mark::before{content:"";width:8px;height:8px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translateY(-2px)}'
    + '.cms-widget-accordion .panel:has(.panel-collapse.in) .cms-accordion-mark::before{transform:rotate(-135deg) translateY(-2px)}'
    + '.cms-widget-accordion .panel-body{padding:10px 12px}'
    // The padding is the inset. A paragraph's own margin would drop the text below the empty hint
    // the moment editing starts, and again when the first character replaces that hint.
    // An empty block quote is the same fields, with placeholders. A paragraph's margin would drop the
    // quotation below its placeholder the moment editing starts.
    + '.cms-widget-blockquote blockquote{margin:0}'
    + '.cms-widget-blockquote [data-cms-field=quote]>:first-child{margin-top:0}'
    + '.cms-widget-blockquote [data-cms-field=quote]>:last-child{margin-bottom:0}'
    + '.cms-widget-blockquote footer{margin-top:.65em;font-size:.92em;font-style:italic}'
    + '.cms-widget-blockquote footer a{color:#000}'
    + '.cms-widget-blockquote footer a[data-cms-empty]{text-decoration:none}'
    + '.cms-widget-blockquote [data-cms-empty]{color:#8a837d}'
    + '.cms-widget-accordion .panel-body>:first-child{margin-top:0}'
    + '.cms-widget-accordion .panel-body>:last-child{margin-bottom:0}'
    + '.cms-widget-accordion .panel-collapse{display:none}'
    + '.cms-widget-accordion .panel-collapse.in{display:block}'
    // An Area widget is not rendered on the canvas. The box names the area the page will render.
    + '.cms-we-area{display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px dotted #cfc9c2;border-radius:3px;background:#faf8f6}'
    + '.cms-we-area__icon{flex:none;color:#8a837d;font-size:18px;line-height:1}'
    + '.cms-we-area__copy{min-width:0}'
    + '.cms-we-area__name{margin:0;color:#242424;font-size:15px;font-weight:600;line-height:1.35}'
    + '.cms-we-area__note{margin:4px 0 0;color:#8a837d;font-size:13px;line-height:1.4}'
    // A table is drawn like the editor's sheet: full width, a light grid, and shaded heading rows. The selectors
    // name the rows and cells so they win over the site's own `.table` rules.
    + '.cms-widget-table{overflow-x:auto}'
    + '.cms-widget-table .table{width:100%;margin:0;border-collapse:collapse;border:1px solid #dcd7d1;background:#fff;font-size:.92em}'
    + '.cms-widget-table .table>thead>tr>th,.cms-widget-table .table>tbody>tr>td{height:2.2em;padding:6px 10px;border:1px solid #dcd7d1;vertical-align:top;text-align:left}'
    + '.cms-widget-table .table>thead>tr>th{background:#f4f2ef;color:#242424;font-weight:600}'
    // Tabs have no script here, so the pane carrying .active is the one on show. The editor moves that class
    // when a tab is clicked. These rules name the widget, so they win over a site's .nav-tabs rules.
    + '.nails-cms-widget-tabs .nav-tabs{display:flex;flex-wrap:wrap;margin:0;padding:0;list-style:none;border-bottom:1px solid #e4dfd8}'
    + '.nails-cms-widget-tabs .tab-item{float:none;margin:0}'
    + '.nails-cms-widget-tabs .tab-item>a{display:block;margin:0 2px -1px 0;padding:8px 14px;border:1px solid transparent;border-radius:3px 3px 0 0;background:transparent;color:#6f675f;font-weight:600;line-height:1.35;text-decoration:none}'
    + '.nails-cms-widget-tabs .tab-item.active>a{border-color:#e4dfd8;border-bottom-color:#fff;background:#fff;color:#242424}'
    + '.nails-cms-widget-tabs .cms-tab-title[data-cms-empty]{color:#8a837d;font-weight:400}'
    + '.nails-cms-widget-tabs .tab-content{padding:12px 2px 2px}'
    + '.nails-cms-widget-tabs .tab-pane{display:none}'
    + '.nails-cms-widget-tabs .tab-pane.active{display:block}'
    + '.nails-cms-widget-tabs .tab-pane>:first-child{margin-top:0}'
    + '.nails-cms-widget-tabs .tab-pane>:last-child{margin-bottom:0}'
    // An image is shown at its own size, and no wider than the canvas. It stays inline so the wrapper's
    // text-align (left, center or right) places it.
    + '.cms-widget-image img{display:inline-block;max-width:100%;height:auto;vertical-align:top}'
    + '.cms-widget-image a{display:inline-block;max-width:100%}';

/**
 * The canvas document with no sections. The canvas loads this once and patches instances into it, so a
 * change does not reload the frame (and does not lose the caret while typing).
 *
 * @param {Object} options
 * @param {unknown[]} options.stylesheets Raw catalogue canvas_assets
 * @return {string}
 */
export function buildCanvasShell({ stylesheets }) {

    // The same Font Awesome the admin loads, so a placeholder can use its widget's icon. The site stylesheets
    // do not include it.
    const fontAwesome = [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/fontawesome.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/solid.min.css',
    ];
    const links = fontAwesome.concat(canvasStylesheets(stylesheets))
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

/**
 * The canvas place of a node: the top-level instance that contains it, and the path within that instance.
 * An empty path is the top-level instance itself. This is the inverse of resolveNodeUid().
 *
 * @param {Array<{uid: string, slots?: Object<string, Array>}>} nodes
 * @param {?string} uid
 * @return {?{top: string, path: string}}
 */
export function placeOf(nodes, uid) {

    if (!uid) {
        return null;
    }

    /**
     * @param {Array} list
     * @param {?string} top
     * @param {string} path
     * @return {?{top: string, path: string}}
     */
    function walk(list, top, path) {
        for (let i = 0; i < list.length; i++) {
            const node = list[i];
            const nodeTop = top || node.uid;
            if (node.uid === uid) {
                return { top: nodeTop, path: path };
            }
            const slots = node.slots || {};
            const names = Object.keys(slots);
            for (let s = 0; s < names.length; s++) {
                const children = slots[names[s]] || [];
                for (let c = 0; c < children.length; c++) {
                    const childPath = (path ? path + '/' : '') + names[s] + '/' + c;
                    const found = walk([children[c]], nodeTop, childPath);
                    if (found) {
                        return found;
                    }
                }
            }
        }
        return null;
    }

    return walk(nodes || [], null, '');
}

/**
 * Reads a slot or child marker that the preview writes as a comment, e.g. `cms-we-slot content` or
 * `/cms-we-node content/0`. The path is URL-encoded so it cannot end the comment.
 *
 * @param {string} text The comment's text
 * @return {?{kind: 'slot'|'node', close: boolean, path: string}}
 */
export function parseMarker(text) {
    const match = /^(\/?)cms-we-(slot|node) (\S+)$/.exec(String(text || '').trim());
    if (!match) {
        return null;
    }
    try {
        return { kind: match[2], close: match[1] === '/', path: decodeURIComponent(match[3]) };
    } catch (e) {
        return null;
    }
}

/**
 * The path of the widget that holds a nested widget or a slot, one level up.
 * 'content/0/items/1' is held by 'content/0'; a top-level slot such as 'content' is held by ''.
 *
 * @param {string} path A child's path (slot/index pairs) or a slot's path (pairs then a slot name)
 * @return {?string} Null for the top-level widget itself
 */
export function ownerPath(path) {
    const segments = path ? String(path).split('/') : [];
    if (!segments.length) {
        return null;
    }
    const keep = segments.length % 2 === 0 ? segments.length - 2 : segments.length - 1;
    return segments.slice(0, keep).join('/');
}

/**
 * The stored widget at a path inside a top-level instance's stored data
 *
 * @param {{slug: string, data: Object}} item
 * @param {string} path
 * @return {?{slug: string, data: Object}}
 */
export function storedAt(item, path) {
    let node = item || null;
    const segments = path ? String(path).split('/') : [];
    for (let i = 0; node && i < segments.length; i += 2) {
        const children = node.data && node.data[segments[i]];
        node = Array.isArray(children) ? children[Number(segments[i + 1])] || null : null;
    }
    return node && typeof node.slug === 'string' ? node : null;
}

/**
 * Where the canvas offers to insert into a slot that has children: before the first, between each pair,
 * and after the last. Children side by side (columns) get an upright line between them, stacked children
 * a level one across the slot.
 *
 * @param {{top: number, left: number, width: number, height: number}} slot
 * @param {Array<{index: number, rect: {top: number, left: number, width: number, height: number}}>} children In order
 * @return {Array<{index: number, upright: boolean, x: number, y: number, length: number}>}
 */
export function slotInsertPoints(slot, children) {
    const list = (children || []).filter((child) => child && child.rect);
    if (!list.length) {
        return [];
    }

    const right = (rect) => rect.left + rect.width;
    const bottom = (rect) => rect.top + rect.height;
    const beside = (a, b) => b.left >= right(a) - 2 && b.top < bottom(a) - 2;
    const across = (y) => ({ upright: false, x: slot.left, y, length: slot.width });
    const upright = (x, a, b) => {
        const top = Math.min(a.top, b.top);
        return { upright: true, x, y: top, length: Math.max(bottom(a), bottom(b)) - top };
    };

    const first = list[0].rect;
    const last = list[list.length - 1].rect;
    const slotRight = slot.left + slot.width;
    const slotBottom = slot.top + slot.height;
    const row = list.length > 1 && beside(list[0].rect, list[1].rect);
    // The first and last lines sit in the slot's padding, so they do not cross the content
    const points = [{
        index: list[0].index,
        ...(row ? upright((slot.left + first.left) / 2, first, first) : across((slot.top + first.top) / 2)),
    }];

    for (let i = 1; i < list.length; i++) {
        const a = list[i - 1].rect;
        const b = list[i].rect;
        points.push({
            index: list[i].index,
            ...(beside(a, b) ? upright((right(a) + b.left) / 2, a, b) : across((bottom(a) + b.top) / 2)),
        });
    }

    const lastRow = list.length > 1 && beside(list[list.length - 2].rect, last);
    points.push({
        index: list[list.length - 1].index + 1,
        ...(lastRow ? upright((right(last) + slotRight) / 2, last, last) : across((bottom(last) + slotBottom) / 2)),
    });

    return points;
}

/**
 * Field keys edited on the canvas for one instance.
 *
 * The preview marks them with data-cms-field and data-cms-path. `path` is empty for the top-level
 * instance and a slot path for a nested one. A field of a nested instance is not included for its parent.
 *
 * @param {string} html The preview HTML
 * @param {string} [path]
 * @return {string[]}
 */
export function inlineFieldKeys(html, path) {
    const wanted = String(path || '');
    const keys = [];
    const tags = /<[^>]*>/g;
    let match = tags.exec(String(html || ''));

    while (match) {
        const field = /data-cms-field="([^"]*)"/.exec(match[0]);
        const fieldPath = /data-cms-path="([^"]*)"/.exec(match[0]);
        if (field && fieldPath && fieldPath[1] === wanted && keys.indexOf(field[1]) === -1) {
            keys.push(field[1]);
        }
        match = tags.exec(String(html || ''));
    }

    return keys;
}

/**
 * The @font-face rules in a stylesheet, so the canvas fonts can be used in the admin page.
 *
 * A root-relative or absolute url is left as it is. A relative url is resolved against the stylesheet,
 * because the rule is inserted into the admin document and would otherwise load from the wrong place.
 *
 * @param {string} css
 * @param {string} [baseUrl]
 * @return {string[]}
 */
export function fontFaceRules(css, baseUrl) {
    const rules = [];
    const pattern = /@font-face\s*\{/g;
    let match = pattern.exec(css);

    while (match) {
        let index = match.index + match[0].length;
        let depth = 1;
        while (index < css.length && depth > 0) {
            if (css[index] === '{') {
                depth += 1;
            } else if (css[index] === '}') {
                depth -= 1;
            }
            index += 1;
        }
        let block = css.slice(match.index, index);
        if (baseUrl) {
            block = block.replace(/url\(\s*(['"]?)([^'")]+)\1\s*\)/g, (full, quote, asset) => {
                if (/^(?:data:|https?:|\/\/)/i.test(asset) || asset.charAt(0) === '/') {
                    return full;
                }
                try {
                    return 'url(' + quote + new URL(asset, baseUrl).href + quote + ')';
                } catch (e) {
                    return full;
                }
            });
        }
        rules.push(block);
        match = pattern.exec(css);
    }

    return rules;
}

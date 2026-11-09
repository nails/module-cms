import { describe, expect, it } from 'vitest';
import {
    buildCanvasDocument,
    buildCanvasShell,
    canvasStylesheets,
    escapeHtml,
    renderSection,
    fontFaceRules,
    inlineFieldKeys,
    ownerPath,
    parseMarker,
    placeOf,
    refreshingUids,
    resolveNodeUid,
    slotInsertPoints,
    storedAt,
} from '../../assets/js/widget-editor/canvas.js';

describe('parseMarker', () => {

    it('reads the slot and child markers the preview writes', () => {
        expect(parseMarker('cms-we-slot content')).toEqual({ kind: 'slot', close: false, path: 'content' });
        expect(parseMarker('/cms-we-node content%2F0%2Fitems/2')).toEqual({ kind: 'node', close: true, path: 'content/0/items/2' });
    });

    it('ignores other comments', () => {
        expect(parseMarker(' a note ')).toBeNull();
        expect(parseMarker('cms-we-slot')).toBeNull();
        expect(parseMarker('cms-we-slot %E0%A4%A')).toBeNull();
    });
});

describe('ownerPath', () => {

    it('is the widget one level up from a child or a slot', () => {
        expect(ownerPath('content/0/items/1')).toBe('content/0');
        expect(ownerPath('content/0')).toBe('');
        expect(ownerPath('content/0/items')).toBe('content/0');
        expect(ownerPath('content')).toBe('');
        expect(ownerPath('')).toBeNull();
    });
});

describe('storedAt', () => {

    const item = {
        slug: 'outer',
        data: { content: [{ slug: 'inner', data: { content: [{ slug: 'leaf', data: {} }] } }] },
    };

    it('walks the stored data to a nested widget', () => {
        expect(storedAt(item, '').slug).toBe('outer');
        expect(storedAt(item, 'content/0').slug).toBe('inner');
        expect(storedAt(item, 'content/0/content/0').slug).toBe('leaf');
    });

    it('is null when the path has gone', () => {
        expect(storedAt(item, 'content/3')).toBeNull();
        expect(storedAt(item, 'other/0')).toBeNull();
        expect(storedAt(null, '')).toBeNull();
    });
});

describe('slotInsertPoints', () => {

    const rect = (top, left, width, height) => ({ top, left, width, height });

    it('puts a level line before, between and after stacked children', () => {
        const slot = rect(0, 10, 200, 100);
        const points = slotInsertPoints(slot, [
            { index: 0, rect: rect(0, 10, 200, 40) },
            { index: 1, rect: rect(50, 10, 200, 50) },
        ]);

        expect(points.map((point) => [point.index, point.upright, point.y])).toEqual([
            [0, false, 0],
            [1, false, 45],
            [2, false, 100],
        ]);
        expect(points[0]).toMatchObject({ x: 10, length: 200 });
    });

    it('puts an upright line between children side by side', () => {
        const slot = rect(0, 0, 300, 80);
        const points = slotInsertPoints(slot, [
            { index: 0, rect: rect(0, 0, 100, 80) },
            { index: 1, rect: rect(0, 110, 100, 60) },
        ]);

        expect(points.map((point) => [point.index, point.upright, point.x])).toEqual([
            [0, true, 0],
            [1, true, 105],
            [2, true, 255],
        ]);
        expect(points[1]).toMatchObject({ y: 0, length: 80 });
    });

    it('puts the end lines in the padding around the children', () => {
        const points = slotInsertPoints(rect(0, 0, 200, 100), [{ index: 0, rect: rect(24, 28, 144, 52) }]);
        expect(points.map((point) => [point.index, point.y])).toEqual([[0, 12], [1, 88]]);
    });

    it('offers one place on each side of a single child', () => {
        const points = slotInsertPoints(rect(0, 0, 100, 20), [{ index: 0, rect: rect(0, 0, 100, 20) }]);
        expect(points.map((point) => [point.index, point.y])).toEqual([[0, 0], [1, 20]]);
    });

    it('has nothing to offer for an empty slot', () => {
        expect(slotInsertPoints(rect(0, 0, 10, 10), [])).toEqual([]);
    });
});

describe('buildCanvasShell', () => {

    it('has an empty body, so instances are patched in rather than reloading the frame', () => {
        const doc = buildCanvasShell({ stylesheets: ['/a.css'] });
        expect(doc).toContain('<body></body></html>');
        expect(doc).toContain('<link rel="stylesheet" href="/a.css">');
    });

    it('has no scrollbar of its own, as the editor sizes the frame to its content', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('html{overflow:hidden}');
        expect(doc).toContain('display:flow-root');
    });

    it('insets a widget inside its box, so its line stays on the canvas edge', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('body{display:flow-root;margin:0;padding:0;');
        expect(doc).toContain(':where(body){line-height:1.45}');
        expect(doc).toContain(':where(h1,h2,h3,h4,h5,h6){line-height:1.3}');
        expect(doc).toContain('.cms-canvas-item{display:flow-root;position:relative;margin:0;padding:12px 28px}');
    });

    it('keeps an accordion paragraph on the same line as its empty hint', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('.cms-widget-accordion .panel-body{padding:10px 12px}');
        expect(doc).toContain('.cms-widget-accordion .panel-body>:first-child{margin-top:0}');
        expect(doc).toContain('.cms-widget-accordion .panel-body>:last-child{margin-bottom:0}');
    });

    it('keeps a block quote paragraph on the same line as its empty placeholder', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('.cms-widget-blockquote [data-cms-field=quote]>:first-child{margin-top:0}');
        expect(doc).toContain('.cms-widget-blockquote [data-cms-field=quote]>:last-child{margin-bottom:0}');
        expect(doc).toContain('.cms-widget-blockquote [data-cms-empty]{color:#8a837d}');
    });

    it('sets the citation in italics, with a black link and no underline on its placeholder', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('.cms-widget-blockquote footer{margin-top:.65em;font-size:.92em;font-style:italic}');
        expect(doc).toContain('.cms-widget-blockquote footer a{color:#000}');
        expect(doc).toContain('.cms-widget-blockquote footer a[data-cms-empty]{text-decoration:none}');
    });

    it('draws a chosen CMS Area as a dotted box instead of its contents', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('.cms-we-area{display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px dotted #cfc9c2;');
        expect(doc).toContain('.cms-we-area__icon{flex:none;color:#8a837d;font-size:18px;line-height:1}');
        expect(doc).toContain('font-awesome/6.4.0/css/solid.min.css');
    });

    it('draws tabs and shows only the pane carrying active, over the site rules', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('.nails-cms-widget-tabs .nav-tabs{display:flex;');
        expect(doc).toContain('.nails-cms-widget-tabs .tab-pane{display:none}');
        expect(doc).toContain('.nails-cms-widget-tabs .tab-pane.active{display:block}');
        expect(doc).toContain('.nails-cms-widget-tabs .tab-pane>:first-child{margin-top:0}');
        expect(doc).toContain('.nails-cms-widget-tabs .tab-pane>:last-child{margin-bottom:0}');
    });

    it('keeps an image inside the canvas and lets its alignment place it', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('.cms-widget-image img{display:inline-block;max-width:100%;height:auto;vertical-align:top}');
        expect(doc).toContain('.cms-widget-image a{display:inline-block;max-width:100%}');
    });

    it('draws a table full width with a grid and shaded headings, over the site rules', () => {
        const doc = buildCanvasShell({ stylesheets: [] });
        expect(doc).toContain('.cms-widget-table .table{width:100%;');
        expect(doc).toContain('.cms-widget-table .table>thead>tr>th,.cms-widget-table .table>tbody>tr>td{');
        expect(doc).toContain('.cms-widget-table .table>thead>tr>th{background:#f4f2ef;');
    });
});

describe('buildCanvasDocument with inline fields', () => {

    it('places sections inside the body', () => {
        const doc = buildCanvasDocument({
            stylesheets: [],
            sections: [{ uid: 'n1', html: '<h2 data-cms-field="sTitle">Hi</h2>', error: null, locked: false, pending: false }],
        });
        expect(doc).toContain('<body><div class="cms-canvas-item" data-uid="n1"><h2 data-cms-field="sTitle">Hi</h2></div></body>');
    });
});

describe('resolveNodeUid', () => {

    const nodes = [
        { uid: 'n1', slots: {} },
        {
            uid: 'n2',
            slots: {
                items: [
                    { uid: 'n3', slots: {} },
                    { uid: 'n4', slots: { inner: [{ uid: 'n5', slots: {} }] } },
                ],
            },
        },
    ];

    it('returns the top-level instance for an empty path', () => {
        expect(resolveNodeUid(nodes, 'n2', '')).toBe('n2');
    });

    it('walks slots and indexes', () => {
        expect(resolveNodeUid(nodes, 'n2', 'items/0')).toBe('n3');
        expect(resolveNodeUid(nodes, 'n2', 'items/1')).toBe('n4');
    });

    it('walks deeper nesting', () => {
        expect(resolveNodeUid(nodes, 'n2', 'items/1/inner/0')).toBe('n5');
    });

    it('returns null when the path no longer exists', () => {
        expect(resolveNodeUid(nodes, 'n2', 'items/9')).toBeNull();
        expect(resolveNodeUid(nodes, 'n2', 'missing/0')).toBeNull();
        expect(resolveNodeUid(nodes, 'gone', '')).toBeNull();
    });
});

describe('placeOf', () => {

    const nodes = [
        { uid: 'n1', slots: {} },
        {
            uid: 'n2',
            slots: {
                items: [
                    { uid: 'n3', slots: {} },
                    { uid: 'n4', slots: { inner: [{ uid: 'n5', slots: {} }] } },
                ],
            },
        },
    ];

    it('is the inverse of resolveNodeUid', () => {
        expect(placeOf(nodes, 'n1')).toEqual({ top: 'n1', path: '' });
        expect(placeOf(nodes, 'n2')).toEqual({ top: 'n2', path: '' });
        expect(placeOf(nodes, 'n3')).toEqual({ top: 'n2', path: 'items/0' });
        expect(placeOf(nodes, 'n5')).toEqual({ top: 'n2', path: 'items/1/inner/0' });
        expect(resolveNodeUid(nodes, 'n2', placeOf(nodes, 'n5').path)).toBe('n5');
    });

    it('returns null for a missing widget', () => {
        expect(placeOf(nodes, null)).toBeNull();
        expect(placeOf(nodes, 'gone')).toBeNull();
    });
});

describe('canvasStylesheets', () => {

    it('keeps http(s) and root-relative stylesheets', () => {
        expect(canvasStylesheets([
            'https://example.com/site.css',
            '/assets/site.css?v=3',
            'http://example.com/a.css#x',
        ])).toEqual([
            'https://example.com/site.css',
            '/assets/site.css?v=3',
            'http://example.com/a.css#x',
        ]);
    });

    it('drops script, data, protocol-relative and non-css URLs', () => {
        expect(canvasStylesheets([
            'javascript:alert(1)',
            'data:text/css,body{}',
            '//cdn.example.com/site.css',
            '/assets/site.js',
            42,
            null,
        ])).toEqual([]);
    });

    it('returns an empty list for anything that is not a list', () => {
        expect(canvasStylesheets(undefined)).toEqual([]);
        expect(canvasStylesheets('/a.css')).toEqual([]);
    });
});

describe('escapeHtml', () => {

    it('escapes the characters that matter in text and attributes', () => {
        expect(escapeHtml('<a href="x">\'&</a>')).toBe('&lt;a href=&quot;x&quot;&gt;&#39;&amp;&lt;/a&gt;');
    });
});

describe('renderSection', () => {

    it('wraps trusted preview HTML in the item container', () => {
        expect(renderSection({ uid: 'n1', html: '<h2>Hi</h2>', error: null, locked: false }))
            .toBe('<div class="cms-canvas-item" data-uid="n1"><h2>Hi</h2></div>');
    });

    it('shows an error card, with the message escaped', () => {
        const out = renderSection({ uid: 'n1', html: '', error: '<img onerror=x>', locked: false });
        expect(out).toContain('cms-canvas-error');
        expect(out).toContain('&lt;img onerror=x&gt;');
        expect(out).not.toContain('<img');
    });

    it('shows a warning for a locked instance, naming its slug and hiding the preview', () => {
        const out = renderSection({
            uid: 'n1',
            html: '<p>secret</p>',
            error: null,
            locked: true,
            slug: 'App.Cms.Widgets.<Wev>',
        });
        expect(out).toContain('cms-canvas-warning');
        expect(out).toContain('App.Cms.Widgets.&lt;Wev&gt;');
        expect(out).not.toContain('secret');
        expect(out).not.toContain('<Wev>');
    });

    it('shows a loading placeholder while a preview is pending', () => {
        expect(renderSection({ uid: 'n1', html: '', error: null, locked: false, pending: true }))
            .toContain('Loading preview');
    });

    it('shows a placeholder when there is no HTML and no error', () => {
        expect(renderSection({ uid: 'n1', html: '', error: null, locked: false })).toContain('Nothing to preview');
    });
});

describe('refreshingUids', () => {

    const keyOf = (item) => JSON.stringify(item.data);

    it('dims an instance whose cached preview is out of date, and not a first load or a locked one', () => {
        const cache = new Map([
            ['a', { key: '"old"' }],
            ['b', { key: '"same"' }],
        ]);
        const items = [
            { uid: 'a', data: 'new' },
            { uid: 'b', data: 'same' },
            { uid: 'c', data: 'first' },
            { uid: 'd', data: 'locked', locked: true },
        ];

        expect(refreshingUids(items, cache, keyOf)).toEqual(['a']);
    });
});

describe('buildCanvasDocument', () => {

    it('links only safe stylesheets and never adds a script tag', () => {
        const doc = buildCanvasDocument({
            stylesheets: ['https://site.test/a.css', 'javascript:bad()'],
            sections: [{ uid: 'n1', html: '<p>ok</p>', error: null, locked: false }],
        });

        expect(doc).toContain('<link rel="stylesheet" href="https://site.test/a.css">');
        expect(doc).not.toContain('javascript:');
        expect(doc).not.toContain('<script');
        expect(doc).toContain('<p>ok</p>');
    });

    it('renders every section in order, including failures', () => {
        const doc = buildCanvasDocument({
            stylesheets: [],
            sections: [
                { uid: 'n1', html: '<b>one</b>', error: null, locked: false },
                { uid: 'n2', html: '', error: 'boom', locked: false },
                { uid: 'n3', html: '<i>three</i>', error: null, locked: false },
            ],
        });

        const first = doc.indexOf('one');
        const second = doc.indexOf('boom');
        const third = doc.indexOf('three');
        expect(first).toBeGreaterThan(0);
        expect(second).toBeGreaterThan(first);
        expect(third).toBeGreaterThan(second);
    });
});

describe('inlineFieldKeys', () => {

    const html = [
        '<div class="cms-widget" data-cms-field="body" data-cms-edit="rich" data-cms-path="">',
        '<h2 data-cms-path="items/0" data-cms-field="title" data-cms-edit="text">Hi</h2>',
        '<p data-cms-field="body" data-cms-edit="rich" data-cms-path="items/0">There</p>',
        '<p data-cms-field="body" data-cms-edit="rich" data-cms-path="">Again</p>',
        '</div>',
    ].join('');

    it('keeps the fields of the top-level instance', () => {
        expect(inlineFieldKeys(html, '')).toEqual(['body']);
    });

    it('keeps the fields of one nested instance', () => {
        expect(inlineFieldKeys(html, 'items/0')).toEqual(['title', 'body']);
    });

    it('returns nothing for a path the preview does not mark', () => {
        expect(inlineFieldKeys(html, 'items/1')).toEqual([]);
        expect(inlineFieldKeys('', '')).toEqual([]);
    });
});

describe('fontFaceRules', () => {

    it('keeps each face and resolves a relative url against the stylesheet', () => {
        const css = [
            'body { color: red; }',
            '@font-face { font-family: "One"; src: url("../fonts/one.woff2") format("woff2"); }',
            '@font-face { font-family: "Two"; src: url("/assets/fonts/two.woff2"); }',
            'h1 { font-family: One; }',
        ].join('\n');

        const rules = fontFaceRules(css, 'https://site.test/assets/css/app.css');

        expect(rules).toHaveLength(2);
        expect(rules[0]).toContain('font-family: "One"');
        expect(rules[0]).toContain('url("https://site.test/assets/fonts/one.woff2")');
        expect(rules[1]).toContain('url("/assets/fonts/two.woff2")');
        expect(rules.join('\n')).not.toContain('color: red');
    });

    it('leaves an absolute or root-relative url as it is', () => {
        const css = '@font-face { src: url("https://cdn.test/a.woff2"), url(//cdn.test/b.woff2); }';
        const rules = fontFaceRules(css, 'https://site.test/app.css');

        expect(rules[0]).toContain('url("https://cdn.test/a.woff2")');
        expect(rules[0]).toContain('url(//cdn.test/b.woff2)');
    });
});

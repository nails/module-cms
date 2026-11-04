import { describe, expect, it } from 'vitest';
import {
    buildCanvasDocument,
    canvasStylesheets,
    escapeHtml,
    renderSection,
} from '../../assets/js/widget-editor/canvas.js';

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

    it('shows a placeholder for a locked instance, with no preview', () => {
        const out = renderSection({ uid: 'n1', html: '<p>secret</p>', error: null, locked: true });
        expect(out).toContain('cms-canvas-placeholder');
        expect(out).not.toContain('secret');
    });

    it('shows a loading placeholder while a preview is pending', () => {
        expect(renderSection({ uid: 'n1', html: '', error: null, locked: false, pending: true }))
            .toContain('Loading preview');
    });

    it('shows a placeholder when there is no HTML and no error', () => {
        expect(renderSection({ uid: 'n1', html: '', error: null, locked: false })).toContain('Nothing to preview');
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

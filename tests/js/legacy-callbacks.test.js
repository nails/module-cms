import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { runLegacyCallback } from '../../assets/js/widget-editor/legacy-callbacks.js';

/** A jQuery stand-in that records what it was given */
function stubJquery() {
    const calls = [];
    const $ = (element) => {
        calls.push(element);
        return { wrapped: element, find: (selector) => ({ selector, within: element }) };
    };
    return { $, calls };
}

describe('runLegacyCallback', () => {

    let stub;

    beforeEach(() => {
        stub = stubJquery();
        globalThis.window = { jQuery: stub.$ };
    });

    afterEach(() => {
        delete globalThis.window;
        vi.restoreAllMocks();
    });

    it('does nothing for an empty script', () => {
        expect(runLegacyCallback('dropped', '', {})).toBeNull();
        expect(runLegacyCallback('dropped', '   ', {})).toBeNull();
        expect(runLegacyCallback('dropped', undefined, {})).toBeNull();
        expect(stub.calls).toHaveLength(0);
    });

    it('passes domElement as a jQuery object, as v1 does', () => {
        const root = { id: 'form-root' };
        const result = runLegacyCallback('dropped', 'globalThis.seen = domElement.find(".x");', root);

        expect(result).toBeNull();
        expect(stub.calls).toEqual([root]);
        expect(globalThis.seen).toEqual({ selector: '.x', within: root });
        delete globalThis.seen;
    });

    it('runs the script with an empty this, so it cannot reach the editor', () => {
        runLegacyCallback('dropped', 'globalThis.thisValue = this;', {});
        expect(globalThis.thisValue).toEqual({});
        delete globalThis.thisValue;
    });

    it('reports a script that throws, without rethrowing', () => {
        const result = runLegacyCallback('removed', 'throw new Error("boom");', {});
        expect(result).toBe('boom');
    });

    it('reports a syntax error as a message', () => {
        const result = runLegacyCallback('dropped', 'this is not javascript', {});
        expect(typeof result).toBe('string');
        expect(result.length).toBeGreaterThan(0);
    });

    it('explains that jQuery is needed when it is missing', () => {
        globalThis.window = {};
        expect(runLegacyCallback('dropped', 'domElement;', {})).toBe('The dropped script needs jQuery');
    });
});

import { describe, expect, it, vi } from 'vitest';
import { sameJson, writeJsonField } from '../../assets/js/components/json-field.js';

function field(value) {
    return { value, dispatchEvent: vi.fn() };
}

describe('sameJson', () => {
    it('treats spacing as irrelevant', () => {
        expect(sameJson('[{"a": 1, "b": [2]}]', '[{"a":1,"b":[2]}]')).toBe(true);
    });

    it('detects a different value', () => {
        expect(sameJson('[{"a": 1}]', '[{"a":2}]')).toBe(false);
    });

    it('treats unparseable text as different', () => {
        expect(sameJson('', '[]')).toBe(false);
    });
});

describe('writeJsonField', () => {
    it('leaves the field alone when the value has not changed', () => {
        const el = field('[{"slug": "A", "data": {}}]');
        const changed = writeJsonField(el, [{ slug: 'A', data: {} }]);

        expect(changed).toBe(false);
        expect(el.value).toBe('[{"slug": "A", "data": {}}]');
        expect(el.dispatchEvent).not.toHaveBeenCalled();
    });

    it('writes a real change and fires change', () => {
        const el = field('[{"slug": "A", "data": {}}]');
        const changed = writeJsonField(el, [{ slug: 'A', data: { text: 'x' } }]);

        expect(changed).toBe(true);
        expect(JSON.parse(el.value)).toEqual([{ slug: 'A', data: { text: 'x' } }]);
        expect(el.dispatchEvent).toHaveBeenCalledTimes(1);
    });

    it('restores the original text when an edit is reverted', () => {
        const original = '[{"slug": "A", "data": {"text": "Hello"}}]';
        const el = field(original);

        writeJsonField(el, [{ slug: 'A', data: { text: 'Hello there' } }]);
        expect(el.value).not.toBe(original);

        writeJsonField(el, [{ slug: 'A', data: { text: 'Hello' } }]);
        expect(el.value).toBe(original);
        expect(el.dispatchEvent).toHaveBeenCalledTimes(2);
    });

    it('does nothing for a missing element', () => {
        expect(writeJsonField(null, [])).toBe(false);
    });
});

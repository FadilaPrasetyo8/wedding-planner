import { describe, expect, it } from 'vitest';
import { parseCommitAmount, swapTermUuids } from './payment-term-actions';

describe('payment term ordering', () => {
    it('swaps a term with its neighbor', () => {
        const uuids = ['a', 'b', 'c'];

        expect(swapTermUuids(uuids, 1, -1)).toEqual(['b', 'a', 'c']);
        expect(swapTermUuids(uuids, 1, 1)).toEqual(['a', 'c', 'b']);
    });

    it('rejects moves past the ends of the list', () => {
        const uuids = ['a', 'b', 'c'];

        expect(swapTermUuids(uuids, 0, -1)).toBeNull();
        expect(swapTermUuids(uuids, 2, 1)).toBeNull();
    });

    it('never mutates the original order', () => {
        const uuids = ['a', 'b', 'c'];

        swapTermUuids(uuids, 0, 1);

        expect(uuids).toEqual(['a', 'b', 'c']);
    });
});

describe('commit amount parsing', () => {
    it('accepts plain and separator-formatted rupiah amounts', () => {
        expect(parseCommitAmount('5000000')).toBe(5_000_000);
        expect(parseCommitAmount('Rp 5.000.000')).toBe(5_000_000);
    });

    it('rejects non-numeric, negative, and empty input', () => {
        expect(parseCommitAmount('')).toBeNull();
        expect(parseCommitAmount('abc')).toBeNull();
        expect(parseCommitAmount('-500000')).toBeNull();
    });
});

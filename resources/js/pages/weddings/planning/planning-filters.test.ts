import { describe, expect, it } from 'vitest';
import { queryString } from './planning-filters';

describe('planning filter query', () => {
    it('preserves active list filters for timeline navigation', () => {
        const query = queryString({
            search: 'Gedung',
            assignee: 12,
            overdue: true,
            date_from: '2027-01-01',
            direction: 'desc',
            category: '',
            unused: false,
        });
        const params = new URLSearchParams(query);

        expect(params.get('search')).toBe('Gedung');
        expect(params.get('assignee')).toBe('12');
        expect(params.get('overdue')).toBe('1');
        expect(params.get('date_from')).toBe('2027-01-01');
        expect(params.get('direction')).toBe('desc');
        expect(params.has('category')).toBe(false);
        expect(params.has('unused')).toBe(false);
    });
});

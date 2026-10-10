import { describe, expect, it } from 'vitest';
import { exportUrl } from './export-url';

describe('exportUrl', () => {
    it('preserves active filters and omits empty values and pagination', () => {
        expect(
            exportUrl('/weddings/w-1/exports/guests', {
                search: 'Keluarga Wijaya',
                side: 'side-1',
                group: 'all',
                rsvp: '',
                page: 3,
                overdue: false,
            }),
        ).toBe(
            '/weddings/w-1/exports/guests?search=Keluarga+Wijaya&side=side-1',
        );
    });

    it('encodes checklist type and selected event values', () => {
        expect(
            exportUrl('/weddings/w-1/exports/rundown', {
                event: 'event/1',
            }),
        ).toBe('/weddings/w-1/exports/rundown?event=event%2F1');
    });
});

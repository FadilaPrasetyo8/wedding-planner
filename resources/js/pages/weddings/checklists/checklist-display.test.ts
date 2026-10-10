import { describe, expect, it } from 'vitest';
import { checklistProgressLabel, checklistRowTone } from './checklist-display';

describe('checklist progress label', () => {
    it('reports completed count over total without overdue noise', () => {
        expect(
            checklistProgressLabel({
                estimatedTotal: 0,
                actualTotal: 0,
                itemCount: 6,
                completedCount: 2,
                overdueCount: 0,
            }),
        ).toBe('2/6 selesai');
    });

    it('appends the overdue count in words when present', () => {
        expect(
            checklistProgressLabel({
                estimatedTotal: 0,
                actualTotal: 0,
                itemCount: 6,
                completedCount: 2,
                overdueCount: 3,
            }),
        ).toBe('2/6 selesai · 3 terlambat');
    });
});

describe('checklist row tone', () => {
    it('marks open items as open', () => {
        expect(checklistRowTone({ isCompleted: false, isOverdue: false })).toBe(
            'open',
        );
    });

    it('marks completed items as completed', () => {
        expect(checklistRowTone({ isCompleted: true, isOverdue: false })).toBe(
            'completed',
        );
    });

    it('prefers overdue over completed', () => {
        expect(checklistRowTone({ isCompleted: true, isOverdue: true })).toBe(
            'overdue',
        );
    });
});

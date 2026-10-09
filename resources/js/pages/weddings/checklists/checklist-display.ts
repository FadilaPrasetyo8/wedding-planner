/**
 * Pure presentation logic for the seserahan/administration checklist pages.
 * Server-side summary stays the source of truth; these helpers only drive
 * row-level display decisions (D-022).
 */

export type ChecklistRow = {
    uuid: string;
    name: string;
    quantity: number;
    estimatedCost: number | null;
    actualCost: number | null;
    estimatedTotal: number;
    actualTotal: number;
    deadline: string | null;
    isOverdue: boolean;
    isCompleted: boolean;
    status: string | null;
};

export type ChecklistSummary = {
    estimatedTotal: number;
    actualTotal: number;
    itemCount: number;
    completedCount: number;
    overdueCount: number;
};

/**
 * The progress label shown next to the summary. Completed and overdue counts
 * always include the words, never color alone.
 */
export const checklistProgressLabel = (summary: ChecklistSummary): string => {
    const parts = [`${summary.completedCount}/${summary.itemCount} selesai`];

    if (summary.overdueCount > 0) {
        parts.push(`${summary.overdueCount} terlambat`);
    }

    return parts.join(' · ');
};

/**
 * Status pill tone for an item. Overdue wins over completed because a stale
 * completed flag should not hide urgency from the couple.
 */
export const checklistRowTone = (
    row: Pick<ChecklistRow, 'isCompleted' | 'isOverdue'>,
): 'completed' | 'overdue' | 'open' => {
    if (row.isOverdue) {
        return 'overdue';
    }

    return row.isCompleted ? 'completed' : 'open';
};

/**
 * Pure helpers for the budget-item payment-term workflow. The page components
 * wrap these with confirmation dialogs and Inertia router calls; the logic
 * itself stays unit-testable without a DOM.
 */

/** Swap the uuid at `index` with its neighbor; null when the move is out of range. */
export function swapTermUuids(
    uuids: readonly string[],
    index: number,
    direction: -1 | 1,
): string[] | null {
    const target = index + direction;

    if (target < 0 || target >= uuids.length) {
        return null;
    }

    const reordered = [...uuids];
    [reordered[index], reordered[target]] = [
        reordered[target],
        reordered[index],
    ];

    return reordered;
}

/**
 * Parse a user-entered rupiah amount for committing a budget item. Accepts
 * digit input with separators; returns null for anything that is not a
 * non-negative integer amount, including input with a leading minus sign.
 */
export function parseCommitAmount(input: string): number | null {
    const trimmed = input.trim();

    if (trimmed.startsWith('-')) {
        return null;
    }

    const parsed = Number.parseInt(trimmed.replace(/\D/g, ''), 10);

    if (Number.isNaN(parsed) || parsed < 0) {
        return null;
    }

    return parsed;
}

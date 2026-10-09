/**
 * Pure presentation logic for the guest list page. Server-side aggregates
 * stay the source of truth; these helpers only drive display decisions
 * (D-024).
 */

export type GuestRow = {
    uuid: string;
    name: string;
    phone: string | null;
    invitationCount: number;
    paxCount: number;
    invitationStatus: string;
    rsvpStatus: string | null;
    notes: string | null;
    side: string | null;
    sideUuid: string | null;
    group: string | null;
    groupUuid: string | null;
};

export type GuestAggregates = {
    guestCount: number;
    invitationCount: number;
    paxCount: number;
    attendingCount: number;
};

/**
 * One-line summary under the page title. Every number is spelled out with
 * its word so the totals never rely on position alone.
 */
export const guestSummaryLabel = (aggregates: GuestAggregates): string => {
    const parts = [
        `${aggregates.guestCount} tamu`,
        `${aggregates.invitationCount} undangan`,
        `${aggregates.paxCount} pax`,
    ];

    if (aggregates.attendingCount > 0) {
        parts.push(`${aggregates.attendingCount} pax hadir`);
    }

    return parts.join(' · ');
};

/** Pill tone for the invitation status of a guest row. */
export const invitationTone = (
    status: string,
): 'notSent' | 'sent' | 'failed' => {
    if (status === 'sent' || status === 'delivered') {
        return 'sent';
    }

    return status === 'failed' ? 'failed' : 'notSent';
};

/** Pill tone for the RSVP status of a guest row, null when unset. */
export const rsvpTone = (
    status: string | null,
): 'attending' | 'declined' | 'pending' | null => {
    if (status === null) {
        return null;
    }

    if (status === 'attending') {
        return 'attending';
    }

    if (status === 'not_attending') {
        return 'declined';
    }

    return 'pending';
};

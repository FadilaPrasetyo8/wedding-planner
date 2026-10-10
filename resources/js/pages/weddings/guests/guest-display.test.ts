import { describe, expect, it } from 'vitest';
import { guestSummaryLabel, invitationTone, rsvpTone } from './guest-display';

describe('guest summary label', () => {
    it('lists counts with words and skips attending when zero', () => {
        expect(
            guestSummaryLabel({
                guestCount: 12,
                invitationCount: 12,
                paxCount: 30,
                attendingCount: 0,
            }),
        ).toBe('12 tamu · 12 undangan · 30 pax');
    });

    it('appends attending pax when present', () => {
        expect(
            guestSummaryLabel({
                guestCount: 12,
                invitationCount: 12,
                paxCount: 30,
                attendingCount: 18,
            }),
        ).toBe('12 tamu · 12 undangan · 30 pax · 18 pax hadir');
    });
});

describe('invitation tone', () => {
    it('groups sent and delivered as sent', () => {
        expect(invitationTone('sent')).toBe('sent');
        expect(invitationTone('delivered')).toBe('sent');
    });

    it('marks failed separately and everything else not sent', () => {
        expect(invitationTone('failed')).toBe('failed');
        expect(invitationTone('not_sent')).toBe('notSent');
    });
});

describe('rsvp tone', () => {
    it('returns null when rsvp is unset', () => {
        expect(rsvpTone(null)).toBeNull();
    });

    it('maps each rsvp state', () => {
        expect(rsvpTone('attending')).toBe('attending');
        expect(rsvpTone('not_attending')).toBe('declined');
        expect(rsvpTone('pending')).toBe('pending');
        expect(rsvpTone('tentative')).toBe('pending');
    });
});

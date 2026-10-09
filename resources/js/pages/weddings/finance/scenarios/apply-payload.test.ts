import { describe, expect, it } from 'vitest';
import { applyScenarioPayload } from './apply-payload';

describe('scenario apply payload', () => {
    it('posts without a confirm flag on first application', () => {
        expect(applyScenarioPayload(false)).toBeUndefined();
    });

    it('requires the explicit confirm flag on repeated application', () => {
        expect(applyScenarioPayload(true)).toEqual({ confirm: true });
    });
});

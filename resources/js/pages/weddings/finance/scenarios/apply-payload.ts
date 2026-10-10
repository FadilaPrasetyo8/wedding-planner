/**
 * Pure helper for the scenario apply flow: the first application posts an
 * empty payload, while any repeat application must carry the explicit
 * `confirm` flag required by the backend (D-020).
 */
export function applyScenarioPayload(
    hasBeenApplied: boolean,
): { confirm: true } | undefined {
    return hasBeenApplied ? { confirm: true } : undefined;
}

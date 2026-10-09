# Release runbook

1. Review the current diff and migration plan.
2. Create and verify a PostgreSQL backup.
3. Run `scripts/release.sh` in a disposable environment.
4. Apply migrations using the approved deployment platform procedure.
5. Run `scripts/smoke-test.sh https://planner.example.test`.
6. Confirm `/up`, `/ready`, the public home page, and the unauthenticated dashboard boundary.

This repository does not deploy automatically from these scripts. Production approval, deployment, and rollback remain external release-gate actions.

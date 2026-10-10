# Internal V1 release checklist

- [ ] Automated backend quality gates pass on disposable PostgreSQL.
- [ ] Frontend check, typecheck, tests, and production build pass.
- [ ] Shell syntax and operational artifact contract tests pass.
- [ ] `/up` and `/ready` are verified in staging.
- [ ] Export authorization, formula safety, and wedding isolation are verified.
- [ ] Accessibility/responsive review has no unresolved blocker.
- [ ] UAT evidence is recorded by a real tester.
- [ ] Encrypted off-host backup and a disposable restore drill are verified.
- [ ] Migration compatibility and rollback/recovery plan are reviewed.
- [ ] Monitoring, incident contacts, and recovery runbook are available.

This repository does not claim full release readiness until manual UAT, controlled staging restore, monitoring, and deployment rehearsal are signed off externally.

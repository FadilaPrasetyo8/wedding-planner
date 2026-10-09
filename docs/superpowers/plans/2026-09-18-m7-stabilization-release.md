# M7 Stabilization and Release Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the internal V1 release gate with wedding-scoped spreadsheet-compatible exports, operational safeguards, repeatable release checks, a critical browser journey, and documented UAT/recovery procedures.

**Architecture:** Keep the application as one Laravel + Inertia deployment. Export endpoints reuse the authoritative wedding-scoped list queries and stream safe UTF-8 CSV files that Excel opens; no spreadsheet dependency is added. Operational readiness is repository-only: health/readiness code, testable scripts, CI configuration, and runbooks are implemented without deploying or modifying production infrastructure.

**Tech Stack:** Laravel 13 / PHP 8.3, PostgreSQL, Inertia 3, React 19, TypeScript, Tailwind CSS 4, PHPUnit 12, Vitest 4, Playwright, POSIX shell scripts for deployment operations.

**Spec:** `docs/implementation-plan.md` M7 (lines 61–67), `docs/PRD_Wedding_Planner.md` FR-17 and Internal V1 Release Gate, and `docs/architecture.md` Search/Export, Security Boundaries, Observability, and Deployment Considerations.

## Global Constraints

- Work on branch `m7-stabilization-release`; M3–M6 are uncommitted and therefore implementation must remain in the live checkout rather than a clean linked worktree.
- Do not commit, push, deploy, provision resources, use production credentials, or modify real production infrastructure.
- Preserve every unrelated working-tree change; never reset, clean, or discard files.
- Owner and editor may export; viewer and non-members may not export.
- Every export starts from a route-scoped `Wedding` relationship, reuses validated active filters, and never accepts browser-provided rows or totals.
- Export money as integer whole rupiah, never floating-point or formatted currency strings.
- Export files must not include attachment paths, signed URLs, secrets, private credentials, or records from another wedding.
- CSV output uses UTF-8 BOM, RFC-compatible quoting through `fputcsv`, a `.csv` extension, and neutralizes spreadsheet formulas beginning with `=`, `+`, `-`, or `@` after leading whitespace.
- No vendor custom fields, billing, subscriptions, payment gateways, vendor portal, or marketplace functionality.
- V1 deployment remains one Laravel/Inertia application on a Docker-compatible Linux VPS baseline.
- Production templates contain placeholders only; `APP_DEBUG=false`, HTTPS cookies, server-only storage credentials, and no `VITE_` secret variables.
- `/up` remains liveness; `/ready` reports dependency readiness without returning exception messages, configuration details, wedding data, bucket names, or paths.
- Backup/restore scripts must fail closed, avoid printing connection secrets, use restrictive file permissions, and require explicit target confirmation for restore.
- Playwright is limited to the critical early-access journey and must not use production credentials.
- This plan contains no commit steps because the user explicitly prohibited commits.

---

### Task 1: Shared Export Infrastructure and Authorization

**Files:**
- Create: `app/Support/Exports/CsvExportResponse.php`
- Create: `app/Http/Controllers/Exports/WeddingExportController.php`
- Create: `app/Http/Requests/Exports/BudgetExportRequest.php`
- Create: `app/Http/Requests/Exports/GuestExportRequest.php`
- Create: `app/Http/Requests/Exports/ChecklistExportRequest.php`
- Create: `app/Http/Requests/Exports/RundownExportRequest.php`
- Modify: `app/Policies/WeddingPolicy.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/Exports/CsvExportResponseTest.php`
- Test: `tests/Feature/Exports/WeddingExportAuthorizationTest.php`

**Interfaces:**
- Produces: `WeddingPolicy::export(User $user, Wedding $wedding): bool`.
- Produces: `CsvExportResponse::stream(string $filename, array $headers, iterable $rows): StreamedResponse`.
- Produces named routes `weddings.exports.budget`, `weddings.exports.guests`, `weddings.exports.planning`, `weddings.exports.checklist`, and `weddings.exports.rundown`.
- Consumes: `PlanningIndexRequest::filters()` and `TaskListQuery::apply()` for planning export.

- [ ] **Step 1: Add failing authorization and CSV safety tests.**
  - Assert owner/editor export authorization is true and viewer is false.
  - Assert streamed output starts with `\xEF\xBB\xBF`, has `text/csv; charset=UTF-8`, and uses a `.csv` download filename.
  - Assert cells whose trimmed value starts with `=`, `+`, `-`, or `@` are prefixed with a single quote while ordinary text and numeric integers are unchanged.

- [ ] **Step 2: Run focused tests and verify they fail.**
  - Run `php artisan test tests/Unit/Exports/CsvExportResponseTest.php tests/Feature/Exports/WeddingExportAuthorizationTest.php`.
  - Expected failure: missing policy method, response helper, controller, and routes.

- [ ] **Step 3: Implement the CSV response helper.**
  - Return `response()->streamDownload(...)` with an explicit UTF-8 content type.
  - Write BOM once, then headers and rows with `fputcsv`.
  - Sanitize only strings with `/^\s*[=+\-@]/u`; do not coerce integer/float/bool/null values to prefixed strings.
  - Reject path separators and CR/LF in the generated filename or normalize it internally from trusted server strings.

- [ ] **Step 4: Add export policy and typed requests.**
  - `WeddingPolicy::export()` returns true only for active owner/editor membership.
  - Each request authorizes `export` and validates its module filters against the route wedding.
  - `RundownExportRequest` validates `event` through a `Rule::exists('wedding_events', 'uuid')->where('wedding_id', $wedding->id)` constraint.
  - `ChecklistExportRequest` validates `type` through `ChecklistType`/route constraint plus same-wedding status/category UUIDs.

- [ ] **Step 5: Add explicit export routes and controller method skeletons.**
  - Add five GET routes under existing `auth` + `wedding.member` + scoped-binding groups.
  - Use explicit controller methods rather than a generic module parameter.
  - Return empty but correctly headed CSVs first so authorization and response tests can pass.

- [ ] **Step 6: Run the focused tests until green.**

### Task 2: Filter-Preserving Wedding-Scoped Export Data

**Files:**
- Create: `app/Support/Exports/BudgetExport.php`
- Create: `app/Support/Exports/GuestExport.php`
- Create: `app/Support/Exports/ChecklistExport.php`
- Create: `app/Support/Exports/RundownExport.php`
- Modify: `app/Http/Controllers/Exports/WeddingExportController.php`
- Modify: `app/Http/Controllers/Finance/BudgetItemController.php` only if extracting a shared query method is necessary.
- Modify: `app/Http/Controllers/Logistics/GuestController.php` only if extracting a shared query method is necessary.
- Modify: `app/Http/Controllers/Logistics/ChecklistController.php` only if extracting a shared query method is necessary.
- Modify: `app/Http/Controllers/EventDay/RundownController.php` only if extracting event selection/query helpers is necessary.
- Test: `tests/Feature/Exports/WeddingExportTest.php`

**Interfaces:**
- Each export class exposes `headers(): array` and `rows(Wedding $wedding, array $filters): iterable`.
- Planning export remains in `WeddingExportController` and consumes `TaskListQuery::apply($wedding, $request->filters())->get()`.
- Controller passes only server-built filenames: `{wedding-slug}-{module}-{Y-m-d}.csv`.

- [ ] **Step 1: Write failing isolation/filter/shape tests for all five exports.**
  - Create at least two weddings, matching and nonmatching rows in both, and owner/editor/viewer users.
  - Verify active filters include matching same-wedding rows and exclude nonmatching or foreign-wedding rows.
  - Verify viewer receives 403 and outsider is rejected by `wedding.member`.
  - Verify budget money columns contain raw integer rupiah and derived paid/outstanding amounts come from payment terms.
  - Verify planning preserves allowlisted sort direction and filter semantics from `TaskListQuery`.
  - Verify checklist route type cannot leak the other checklist type.
  - Verify rundown rows follow stored `sort_order` for the selected same-wedding event.
  - Verify formula-like user strings are neutralized.

- [ ] **Step 2: Run `php artisan test tests/Feature/Exports/WeddingExportTest.php` and verify failure.**

- [ ] **Step 3: Implement budget and guest exporters.**
  - Budget filters: `search`, `status`, `category`; order by `name`, then `id`.
  - Budget columns: item, category, event, vendor, status, estimated amount, final amount, paid total, outstanding, payment-term count, notes.
  - Guest filters: `search`, `side`, `group`, `invitation`, `rsvp`; order by `name`, then `id`.
  - Guest columns: name, side, group, phone, invitation count, pax, invitation status, RSVP, notes.
  - Eager-load every relationship used in row mapping and aggregate payment terms without per-row queries.

- [ ] **Step 4: Implement planning, checklist, and rundown exporters.**
  - Planning columns: task, category, status, priority, assignee, event, start date, due date, completed at, notes/description.
  - Checklist filters: `search`, `status`, `category`; order by deadline then `id`; columns include type, group/category/status, assignee, quantity, unit/total costs, dates, purchase URL, notes.
  - Rundown selects the requested event, or the primary/first event when omitted, and exports event, order, times, title, PIC, status, highlights, and notes.

- [ ] **Step 5: Include active custom fields as deterministic dynamic columns.**
  - For budget, guest, task, and checklist modules, append active definitions ordered by `sort_order`, then `id`.
  - Batch-load values through `SubjectCustomFields::valuesForSubjects()`; never issue one query per row.
  - Serialize checkbox as `Ya`/`Tidak`, multi-select as a comma-separated value, scalar values as display-safe strings, and currency/number as raw numeric values.
  - Do not export attachments, internal storage paths, or inactive definitions.

- [ ] **Step 6: Run export tests, PHPStan, and Pint check.**
  - `php artisan test tests/Feature/Exports tests/Unit/Exports`
  - `composer types:check`
  - `composer lint:check`

### Task 3: Export Controls in the Five Host Pages

**Files:**
- Modify: `resources/js/pages/weddings/finance/budget/index.tsx`
- Modify: `resources/js/pages/weddings/guests/index.tsx`
- Modify: `resources/js/pages/weddings/planning/index.tsx`
- Modify: `resources/js/pages/weddings/planning/timeline.tsx`
- Modify: `resources/js/pages/weddings/checklists/index.tsx`
- Modify: `resources/js/pages/weddings/event-day/rundown.tsx`
- Modify generated Wayfinder files by running the approved generator; do not hand-edit generated files.
- Test: `resources/js/pages/weddings/exports.test.tsx` or focused helper tests colocated with a new `resources/js/lib/export-url.ts`.

**Interfaces:**
- Consumes named export routes from Task 1.
- Produces one reusable `exportUrl(baseUrl: string, filters: Record<string, unknown>): string` helper if needed to preserve nonempty query parameters without `page`.

- [ ] **Step 1: Regenerate Wayfinder and verify export helpers exist.**
  - Run the repository’s established Wayfinder generation command with `--with-form` if required by current route generation.

- [ ] **Step 2: Add a failing frontend test for filter-preserving URL construction.**
  - Assert empty/all/default values are omitted, active filters are encoded, and `page` is omitted.

- [ ] **Step 3: Implement the URL helper and render `Ekspor CSV (Excel)` controls.**
  - Show controls only when backend passes `canExport`.
  - Pass `canExport` from each controller using `can('export', $wedding)`; do not infer from `canEdit` in React.
  - Use ordinary anchor navigation so the browser receives the streamed download.
  - Preserve currently active filters and selected checklist type/rundown event.
  - Keep button label and icon accessible; viewers see no export control but remain protected by backend authorization.

- [ ] **Step 4: Run frontend type, test, formatting, and build gates.**
  - `npm run types:check`
  - `npm run test`
  - `npm run check:fix`
  - `npm run check`
  - `npm run build`

### Task 4: Health, Correlation IDs, and Logging Safety

**Files:**
- Create: `app/Http/Controllers/Health/ReadinessController.php`
- Create: `app/Http/Middleware/RequestCorrelation.php`
- Create: `app/Logging/RedactSensitiveContext.php` only if the current logging stack supports a processor/tap cleanly; otherwise configure Laravel exception-context redaction and document the boundary.
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php` or `routes/console.php` as appropriate.
- Modify: `config/logging.php`
- Test: `tests/Feature/Operations/HealthTest.php`
- Test: `tests/Feature/Operations/LoggingSecurityTest.php`

**Interfaces:**
- Produces public `GET /ready` returning only `{"status":"ok"}` with 200 or `{"status":"unavailable"}` with 503.
- Produces response header `X-Request-ID` and scoped log context key `request_id`.

- [ ] **Step 1: Write failing health and correlation tests.**
  - `/up` stays 200.
  - `/ready` returns 200 when PostgreSQL and configured attachment disk are reachable.
  - Simulated database/storage exceptions return 503 without exception text, DSN, bucket, path, or credentials.
  - A valid UUID `X-Request-ID` is echoed; malformed input is replaced with a server UUID.

- [ ] **Step 2: Implement readiness with bounded, non-destructive checks.**
  - Database: `select 1`.
  - Storage: invoke an adapter capability that does not create/delete/list private wedding objects; if the driver cannot support a safe remote check, validate disk resolution and document that storage availability is monitored separately rather than exposing a listing.
  - Catch dependency exceptions, log a category-safe warning with `request_id`, and return the generic 503 body.

- [ ] **Step 3: Implement request correlation and sensitive context redaction.**
  - Register middleware in the web stack.
  - Add the ID to `Log::withContext()` and every response.
  - Redact recursive keys matching password, token, authorization, cookie, secret, signature, and storage credentials before structured logging.

- [ ] **Step 4: Run focused operation tests and static checks.**

### Task 5: Repository-Only Backup, Restore, Release, and Smoke Procedures

**Files:**
- Create: `scripts/backup-database.sh`
- Create: `scripts/restore-database.sh`
- Create: `scripts/release.sh`
- Create: `scripts/smoke-test.sh`
- Create: `deploy/env.production.example`
- Create: `docs/operations/environment.md`
- Create: `docs/operations/backup-restore.md`
- Create: `docs/operations/release-runbook.md`
- Create: `docs/operations/monitoring-recovery.md`
- Test: `tests/Feature/Operations/OperationalArtifactsTest.php`

**Interfaces:**
- `backup-database.sh BACKUP_DIR` consumes standard `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`; writes a custom-format dump plus `.sha256` under `umask 077`.
- `restore-database.sh DUMP_FILE --confirm-database=NAME [--acknowledge-production]` verifies checksum and archive before restore.
- `smoke-test.sh BASE_URL` checks public and auth-boundary contracts without credentials.

- [ ] **Step 1: Write failing artifact-contract tests.**
  - Assert production env template contains placeholders, `APP_DEBUG=false`, secure cookies, server-only attachment variables, and no secret value or `VITE_` storage credential.
  - Assert scripts contain strict mode, never echo `PGPASSWORD`, and restore requires exact database confirmation.
  - Assert smoke script checks `/up`, `/ready`, `/`, and unauthenticated `/dashboard` redirect.

- [ ] **Step 2: Implement backup and restore scripts.**
  - Backup: custom format, timestamped sanitized filename, SHA-256 checksum, explicit destination, restrictive permissions, optional integer retention days limited to that destination.
  - Restore: verify `.sha256`, run `pg_restore --list`, require exact target database string, reject a production-like environment unless the second acknowledgement is present, and never construct shell commands with unquoted user input.

- [ ] **Step 3: Implement release and smoke scripts without running them against production.**
  - Release performs preflight, dependency install, frontend build, Laravel optimization, controlled `migrate --force`, optional queue restart, smoke test, and maintenance recovery via trap.
  - Document that backup and compatibility review happen before migration and that application rollback never pretends to reverse an unsafe database migration automatically.

- [ ] **Step 4: Write operations documentation.**
  - Environment variable contract and secret ownership.
  - RPO/RTO, retention, encrypted off-host copy, restore drill, and `attachments:reconcile` after database restoration.
  - Monitoring matrix for HTTP, 5xx, database, storage, email, queue, disk, TLS, and backup freshness.
  - Incident severity, rollback/recovery sequence, evidence retention, and prohibition against logging signed URLs/private content.

- [ ] **Step 5: Validate shell syntax and focused tests.**
  - `bash -n scripts/backup-database.sh scripts/restore-database.sh scripts/release.sh scripts/smoke-test.sh`
  - `php artisan test tests/Feature/Operations/OperationalArtifactsTest.php`

### Task 6: CI Hardening and Disposable Backup/Restore Drill

**Files:**
- Modify: `.github/workflows/tests.yml`
- Create: `scripts/verify-restored-database.sh`
- Modify: `phpunit.xml` only if CI-specific PostgreSQL settings need an explicit safe test database.

**Interfaces:**
- CI triggers on pull requests and pushes to both `master` and `main` during branch-name transition.
- CI uses a PostgreSQL service/database created only for tests.

- [ ] **Step 1: Add PostgreSQL service and full quality gates to CI.**
  - Install dependencies with `composer install --no-interaction --prefer-dist` and `npm ci` rather than `composer setup`.
  - Copy `.env.example`, generate a disposable key, configure only test PostgreSQL credentials, run migrations, then run `composer ci:check`, `npm run test`, and `npm run build`.
  - Retain SHA-pinned actions and `contents: read` permissions.

- [ ] **Step 2: Add operational script validation and backup/restore drill.**
  - Validate shell syntax.
  - Seed the disposable source database, back it up, create a second disposable database, restore into it, and run a verification script that checks expected schema and representative seeded rows.
  - Drop only CI-created disposable databases.

- [ ] **Step 3: Validate workflow syntax locally where available and run repository gates.**
  - Do not invoke GitHub Actions or external deployment from this session.

### Task 7: Playwright Critical Journey

**Files:**
- Modify: `package.json`
- Modify: `package-lock.json`
- Create: `playwright.config.ts`
- Create: `tests/e2e/critical-journey.spec.ts`
- Create: `tests/e2e/helpers.ts` if needed.
- Modify: `.github/workflows/tests.yml`

**Interfaces:**
- Produces scripts `test:e2e` and `test:e2e:install`.
- Uses Laravel’s local test environment and a dedicated disposable database; never production credentials.

- [ ] **Step 1: Add the approved Playwright development dependency and configuration.**
  - Configure Chromium, base URL, screenshots/traces only on failure, and a web server command that starts Laravel/Vite in test mode.
  - Use stable roles/labels; add narrow `data-testid` only where accessible selectors cannot be made reliable.

- [ ] **Step 2: Implement one serial critical journey.**
  - Register first user, verify through a test-only captured verification URL or an approved test-environment helper, create wedding, add a task, create budget item, add ten flexible payment terms, invite a second user, accept invitation, and switch workspaces.
  - Verify viewer cannot mutate/export and an outsider cannot open another wedding URL or obtain an attachment download.
  - Keep email/storage faked locally; do not bypass production authorization code.

- [ ] **Step 3: Add CI E2E job.**
  - Install Chromium with dependencies, prepare the disposable PostgreSQL database, run the journey, and upload trace/screenshot artifacts only on failure.

- [ ] **Step 4: Run the critical journey locally.**
  - `npm run test:e2e:install`
  - `npm run test:e2e`
  - If the environment cannot install browsers, record the exact blocker and keep unit/feature coverage green without claiming E2E passed.

### Task 8: Performance, Accessibility, Responsive, and Security Regression Pass

**Files:**
- Create: `tests/Feature/Stabilization/QueryBudgetTest.php`
- Create: `tests/Feature/Stabilization/ReleaseScopeTest.php`
- Create: `docs/operations/accessibility-responsive-review.md`
- Modify specific controllers/components only when tests or review identify concrete defects.

**Interfaces:**
- Query tests establish documented upper bounds for representative populated index pages and export requests, not brittle exact counts.
- Release-scope test prevents routes/models/config for billing, subscriptions, payment gateways, vendor auth/portal, or public marketplace.

- [ ] **Step 1: Add representative query-budget tests.**
  - Seed enough rows to expose N+1 behavior for dashboard, planning, budget, guests, checklist, vendor, rundown, and each export.
  - Compare small and larger datasets and assert bounded query growth; use generous limits tied to relationship shape.

- [ ] **Step 2: Fix only measured N+1 or index gaps.**
  - Add eager loads, aggregate subqueries, or database indexes only when a test demonstrates the need.
  - Never add speculative caches; any future cache key must include wedding and permission context.

- [ ] **Step 3: Add release-scope/security regression tests.**
  - Reassert viewer export/mutation denial, cross-wedding export/file denial, verified upload boundary, 410 missing-object response, and absence of forbidden V1 route/model surfaces.
  - Run `php artisan route:list` review for all export/file/custom-field routes.

- [ ] **Step 4: Perform and document responsive/accessibility review.**
  - Review representative auth, dashboard, planning, budget/payment terms, guests, checklist, vendor, rundown, custom-fields, and attachment flows at ~400 px and desktop width.
  - Verify keyboard focus, labels, error/loading/empty states, destructive confirmations, non-color status text, table overflow containment, and touch target usability.
  - Use Playwright screenshots/accessibility snapshots where available; record each defect and fix it before marking the review complete.

- [ ] **Step 5: Run stabilization tests and frontend gates.**

### Task 9: Early-Access UAT and M7 Exit Gate

**Files:**
- Create: `docs/operations/uat-checklist.md`
- Create: `docs/operations/release-checklist.md`
- Modify: `README.md`
- Modify: `docs/progress.md`
- Modify: `docs/decision-log.md` only if M7 introduces a material accepted decision not already covered by architecture.

**Interfaces:**
- UAT evidence template records tester, date, branch/SHA or build identifier, browser/device, scenario result, severity, screenshots/log references, and sign-off.

- [ ] **Step 1: Write UAT and release checklists.**
  - Cover registration/authentication, verification/reset, multi-workspace creation/switching, membership roles/invitation/ownership, task/timeline, budget with ten terms, savings, both checklists, private vendors, guests, rundown/print, playlist, custom fields, private attachments, exports, isolation, mobile/desktop, keyboard/accessibility, backup/restore/reconciliation, and recovery drill.
  - Define blocker severity: release-blocking for data leak, unauthorized mutation/download/export, financial corruption, failed restore, broken registration, or unusable primary mobile flow.
  - Do not mark manual UAT signed off without a real tester result.

- [ ] **Step 2: Update README with local verification and operations entry points.**
  - Document exact setup, backend/frontend/E2E commands, health endpoints, export format, and links to operations runbooks.

- [ ] **Step 3: Run fresh full gates.**
  - `php artisan migrate:fresh --seed`
  - `composer ci:check`
  - `npm run test`
  - `npm run build`
  - `npm run test:e2e` if browser installation succeeded.
  - `bash -n scripts/*.sh`
  - Run focused export, operation, security, and query tests if they are not already included.

- [ ] **Step 4: Review the complete diff.**
  - Check authorization, cross-wedding isolation, formula injection, private path/secret exposure, unrelated scope, and forbidden V1 features.
  - Search React/browser output for `SUPABASE`, `service_role`, storage paths, or secrets.

- [ ] **Step 5: Update progress truthfully.**
  - Record implemented M7 work and exact automated gate counts/results.
  - If manual UAT, real production backup/restore drill, or real monitoring/deployment remains unperformed, state that M7 is implementation-complete but the Internal V1 release gate is pending those external/manual sign-offs; do not claim full release readiness.
  - Identify the exact next action: early-access UAT and controlled staging restore/deployment rehearsal.

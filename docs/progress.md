# Wedding Planner Progress

## Current State

- **Phase:** M2 — Dashboard and Planning complete
- **Implementation:** M0 Tasks 1–7, all eight M1 vertical slices, and all seven M2 vertical slices complete; all M2 exit gates pass
- **Current milestone:** M2 — Dashboard and Planning
- **Last verified update:** 7 September 2026

The product owner approved the planning baseline plus the M0, M1, and M2 execution designs. M0 technical decisions are recorded in D-013 through D-015; M1 retention, invitation, and event-only template decisions are recorded in D-016 through D-018; the M2 template timeline anchor is accepted in D-012. The application now adds wedding-scoped event and task planning, configurable task references, a filterable planning list, a bounded timeline, and a server-authoritative wedding dashboard to the verified multi-wedding foundation. No deployment, Supabase configuration, production infrastructure, or M3+ operational functionality has begun.

## Planning Deliverables

| Deliverable | State | Notes |
|---|---|---|
| `CLAUDE.md` | Complete | Repository operating rules and implementation invariants |
| `docs/PRD_Wedding_Planner.md` | Complete | True UTF-8 Markdown source of truth |
| `docs/architecture.md` | Review-ready | Architecture sections completed and cross-checked |
| `docs/database-erd.md` | Review-ready | Entity responsibilities, isolation, integrity, and deferred scope |
| `docs/implementation-plan.md` | Review-ready | Sequential M0–M7 goals, gates, and test categories |
| `docs/decision-log.md` | Complete | Accepted product and architecture decisions through M2 |
| `docs/progress.md` | Complete | Current status and handoff instructions |

## Completed Decisions

- Many public accounts and multiple wedding workspaces per account
- Exactly one owner with explicit transactional ownership transfer
- Owner/editor/viewer permissions and viewer read-only behavior
- Laravel + React TypeScript + Inertia + Tailwind + PostgreSQL
- Laravel Auth only; private Supabase Storage behind Laravel
- Private wedding-entered vendor contacts without vendor-facing features
- Unlimited repeatable payment terms rather than fixed installment columns
- `BIGINT` whole-rupiah financial persistence
- Guest-first custom fields, then task, checklist item, and budget item
- Excel-first export; PDF later
- No subscription, billing, or payment gateway in V1

## M0 Decisions Recorded

- Laravel 13 with PHP 8.3 as the minimum runtime.
- Official React starter with Inertia 3, React 19, Tailwind CSS 4, and its shadcn/ui baseline.
- PHPUnit for backend tests; Vitest and React Testing Library only for focused frontend behavior tests.
- Separate PostgreSQL databases for local development and automated tests.
- Database-backed deployed sessions, synchronous local queue, and a Docker-compatible Linux VPS deployment baseline.
- No Teams, WorkOS, social login, billing, passkeys, two-factor authentication, or other optional authentication features in M0.

## Remaining Decisions

- **Before brand work:** final product name and visual identity. This does not block the verified application milestones.
- **When operationally required:** email and observability providers. These do not block the approved local implementation.

## Verified M0 Work

### Task 1 — Technical decisions

- **Status:** Complete
- **Verification:** Decision records D-013 through D-015 were checked against the architecture and ERD; previously unresolved M0 technology, authentication starter, test runner, environment, session/queue, and deployment choices are now consistent across the source documents.
- **Scope check:** Documentation only. No application code, dependency, database, deployment, Supabase, or M1+ change was made.

### Task 2 — Laravel React foundation

- **Status:** Complete
- **Verification:** The official Laravel 13 React starter boots from the repository root with PHP 8.3, Inertia 3, React 19, Tailwind CSS 4, the generated shadcn/ui baseline, and PHPUnit. Passkey and two-factor routes, configuration, model integration, migrations, feature-only UI, and direct npm dependencies were removed while standard registration, session authentication, password reset, email verification, profile, and password-management flows were preserved.
- **Quality gates:** The product owner ran Wayfinder generation with form variants, TypeScript type checking, frontend checks, the production build, and `composer test` sequentially from the repository root; all passed. Generated route and controller actions were then inspected and confirmed to expose `.form()` variants.
- **Scope check:** `CLAUDE.md` and all planning documents were preserved. No Teams, WorkOS, social login, billing, deployment, Supabase configuration, production infrastructure, or M1+ work was added.

### Task 3 — PostgreSQL environments

- **Status:** Complete
- **Verification:** Local development targets PostgreSQL database `wedding_planner`, while PHPUnit targets the distinct `wedding_planner_test` database. The test database was rebuilt from scratch and seeded successfully with all foundation migrations.
- **Runtime conventions:** PostgreSQL is the default database driver, the local queue is synchronous, deployed-style database sessions remain documented, and real credentials remain in ignored environment files. A regression test asserts the automated test environment cannot target the development database.
- **Quality gates:** `composer test` passed with PHP formatting, static analysis, and 37 backend tests containing 107 assertions.

### Task 4 — Authentication hardening

- **Status:** Complete
- **Verification:** The user model implements Laravel's email-verification contract. Public registration creates an unverified account and sends the framework verification notification. Unverified users may log in and use the ordinary authenticated dashboard/account shell; verification remains available and will gate invitation, private-upload, and other sensitive endpoints individually. Login/logout, password reset, password confirmation, email verification, duplicate-email validation, and password-confirmation validation are covered.
- **Security controls:** Login, registration, password-reset-link, and verification-notification requests are rate-limited. Authentication and sensitive settings mutations were checked for the web middleware and CSRF protection baseline. Optional passkey and two-factor features remain disabled.
- **Quality gates:** All 32 authentication feature tests passed, followed by `composer test` with PHP formatting, static analysis, and 46 backend tests containing 169 assertions.

### Task 5 — Profile and email reverification security

- **Status:** Complete
- **Verification:** Profile routes require authentication; changing an email clears its verification timestamp and sends a fresh Laravel verification notification without blocking the ordinary authenticated dashboard/account shell. Keeping the same email preserves verification and sends no notification.
- **Security controls:** Unverified users retain ordinary authenticated account access. Password confirmation remains required for the security page, and password validation remains required for password changes and account deletion. Future invitation, private-upload, and other sensitive routes must enforce verification at their own backend boundaries.
- **Quality gates:** All 13 settings feature tests passed, followed by `composer test` with PHP formatting, static analysis, and 50 backend tests containing 192 assertions.

### Task 6 — Application shells and M0 UI baseline

- **Status:** Complete
- **Verification:** The public welcome page and authenticated application shell render through Inertia with guest, verified-user, and protected-route coverage. The starter placeholder presentation was replaced with a responsive, accessible, warm wedding-planning foundation without adding workspace or wedding-domain behavior.
- **UI baseline:** Shared theme tokens, branding, navigation, buttons, form controls, empty states, feedback primitives, light/dark appearance support, and visible focus behavior build on the generated shadcn/ui baseline. A focused Vitest and React Testing Library test verifies the accessible password-visibility interaction.
- **Quality gates:** The frontend test passed; frontend formatting and lint reported no issues across 65 formatted and 148 linted files; TypeScript passed; the production build completed; and `composer test` passed with PHP formatting, static analysis, and 53 backend tests containing 214 assertions.

### Task 7 — M0 exit gate

- **Status:** Complete
- **Functional verification:** Public registration, Laravel session-protected routes, email verification, email-change reverification, authentication throttling, CSRF middleware, profile security, public/authenticated Inertia shells, and focused frontend behavior are covered by passing automated tests.
- **Database verification:** `php artisan migrate:fresh --seed --force` completed against the dedicated PostgreSQL `wedding_planner_test` database. Regression coverage confirms automated tests do not target the development database.
- **Quality gates:** After the final verification-access adjustment, the focused authentication/profile run passed 9 tests with 39 assertions. `composer test` passed Pint, PHPStan with zero errors, and PHPUnit with 55 tests and 231 assertions. `npm test` passed 1 Vitest test. `npm run check` reported 65 files correctly formatted and no warnings or lint errors across 148 files. `npm run types:check` passed. `npm run build` completed successfully with 2,274 modules transformed.
- **Secret-exposure checks:** No database, PostgreSQL URL, Supabase key, or service-role identifier was found in React/application resources or the final browser bundle, and `.env.example` contains no sensitive `VITE_*` variable names. Inertia shared-prop tests also reject server credential namespaces.
- **Scope check:** No wedding-domain tables, workspace flows, M1 features, deployment, commit, Supabase configuration, or production infrastructure changes were made.

## Verified M1 Work

### Workspace schema and integrity

- **Status:** Complete
- **Verification:** PostgreSQL migrations create weddings, retained memberships, invitations, application templates, typed template sections/items, initial wedding events, and activity logs with foreign keys, checks, indexes, UUIDs, and rollback behavior.
- **Integrity controls:** `(wedding_id, user_id)` is unique, inactive memberships retain `left_at`, and a PostgreSQL partial unique index permits at most one active owner per wedding.

### Onboarding and template cloning

- **Status:** Complete
- **Verification:** An authenticated user can create multiple wedding workspaces. Each creation atomically creates the wedding, one owner membership, a primary event, supported event-only template clones, and an activity log. Clone failure rolls back every record, and cloned events remain independent between weddings and from the application template.
- **Scope check:** Template payloads are validated and only `event` items are supported. No M2–M5 operational records are created.

### Active wedding context and isolation

- **Status:** Complete
- **Verification:** Canonical `/weddings/{wedding:uuid}/...` routes require active membership. The session stores only the last accessible wedding UUID for dashboard redirects. Non-members and inactive members receive `403`; soft-deleted weddings receive `404`; shared wedding props contain only the signed-in user's active memberships.
- **Authorization:** Policies and backend middleware enforce owner, editor, and viewer capabilities independently of the UI. Nested member and invitation routes use scoped bindings where applicable.

### Secure invitations and retained membership reactivation

- **Status:** Complete
- **Verification:** A verified owner can invite any normalized valid email as editor or viewer. Random plaintext tokens are sent once while only SHA-256 hashes are stored; invitations expire after seven days and terminal tokens are single-use. Acceptance and decline require an authenticated, verified account whose normalized email matches the invitation.
- **Security controls:** Invalid tokens return `404`, expired or consumed tokens return `410`, wrong recipients receive `403`, and invitation creation, cancellation, acceptance, and decline are the only M1 routes gated by email verification. Acceptance reactivates the retained membership row rather than creating a duplicate.

### Membership, ownership, and settings

- **Status:** Complete
- **Verification:** Owners can manage editor/viewer roles and remove active non-owner members; non-owners may leave. An owner cannot leave or be removed before transfer. Ownership transfer locks and updates memberships transactionally, demoting the former owner to editor and preserving exactly one active owner.
- **Settings:** Only owners can update wedding settings or soft-delete a wedding. Deletion requires exact wedding-name confirmation and clears a matching redirect preference.

### Wedding-specific interface

- **Status:** Complete
- **Verification:** The responsive Inertia shell uses Indonesian copy and the temporary `Wedding Planner` identity. It exposes wedding switching, onboarding, overview, member/invitation management, owner settings, explicit `Pemilik`/`Editor`/`Hanya lihat` states, and capability-aware navigation without treating UI visibility as authorization.
- **Visual direction:** The interface uses the approved warm, calm, premium wedding-planning direction rather than a generic administration dashboard.

### M1 exit gate

- **Status:** Complete
- **Database:** `php artisan migrate:fresh --seed --force` completed against the dedicated PostgreSQL test database, including `TemplateSeeder`.
- **Backend quality:** `composer test` passed Pint, PHPStan with zero errors, and PHPUnit with 84 tests and 405 assertions. The focused M1 workspace suite passed 29 tests with 171 assertions.
- **Frontend quality:** `npm test` passed 1 Vitest test. `npm run check` reported 72 files correctly formatted and no warnings or lint errors across 155 files. `npm run types:check` passed. `npm run build` completed with 2,289 modules transformed.
- **Route review:** Wedding routes use canonical UUID binding plus `auth` and `wedding.member`; invitation-sensitive routes additionally use `verified`; cross-wedding member and invitation resources use scoped bindings.
- **Secret review:** No Supabase service role, database password, secret key, invitation token hash, or storage path identifier was found in React sources.
- **Scope review:** Backend and wedding UI scans found no M2 task/timeline, finance-payment, logistics, event-day, file, or custom-field implementation. The existing optional target-budget wedding setting is the only finance-related M1 field and does not implement M3 behavior.

## Verified M2 Work

### Planning schema, defaults, and templates

- **Status:** Complete
- **Verification:** PostgreSQL now stores wedding-scoped task categories, immutable-behavior task statuses, and tasks with direct `wedding_id`, UUID route keys, controlled enum values, indexes, rollback behavior, and composite foreign keys that reject cross-wedding category, status, assignee membership, and event relationships.
- **Defaults and templates:** Existing and newly created weddings receive independent Indonesian category and status defaults. Validated `task` template items clone atomically after events, retain relative offsets, and use the D-012 primary-event anchor with a wedding-date fallback.

### Wedding events and relative dates

- **Status:** Complete
- **Verification:** Active members can read wedding-scoped event lists and details; owners and editors can create, update, and delete events with validated type, date, optional time, venue, notes, and primary state. Database and action safeguards maintain one primary event per active wedding.
- **Date integrity:** Primary-event changes use transactions and row locks, require explicit confirmation when relative tasks exist, and recalculate only `template_relative` task dates from stored offsets. Manual task dates remain unchanged.

### Task configuration and lifecycle

- **Status:** Complete
- **Verification:** Owners and editors can manage wedding-scoped categories and statuses; viewers receive an explicit read-only interface. Labels, colors, order, and active state are editable, while status behavior remains immutable and uniquely controlled per wedding.
- **Task lifecycle:** Task CRUD validates category, status, active assignee membership, and optional event against the route wedding. Manual and template-relative date modes are persisted consistently, and completed status behavior controls `completed_at` on the server.

### Planning list and timeline

- **Status:** Complete
- **Verification:** The planning list provides server-side search, status, category, priority, assignee, event, overdue, and date-range filters, plus allowlisted deterministic sorting and pagination with URL query state. Desktop uses a dense table and mobile uses compact rows.
- **Timeline:** The bounded dependency-free timeline reuses the validated planning query and date semantics, preserves active filters between views, renders task ranges and event markers with textual status and overdue meaning, and keeps ordinary edit forms as the accessible non-drag mutation path.

### Wedding dashboard

- **Status:** Complete
- **Verification:** The canonical wedding overview now provides a wedding-timezone primary-event countdown, task totals and completion percentage, overdue and upcoming lists, upcoming events, and status/category grouping from wedding-scoped server queries.
- **Aggregate rules:** Cancelled tasks are excluded from the completion denominator and overdue count. Dashboard lists are bounded and contain no finance, savings, vendor, guest, logistics, file, or event-day metrics.

### M2 exit gate

- **Status:** Complete
- **Database:** `php artisan migrate:fresh --seed --force` completed successfully, including `TemplateSeeder`.
- **Backend quality:** `composer test` passed Pint, PHPStan with zero errors, and PHPUnit with 118 tests and 791 assertions.
- **Frontend quality:** `npm test` passed 2 Vitest test files with 2 tests. `npm run check` reported 86 files correctly formatted and no warnings or lint errors across 169 files. `npm run types:check` passed. `npm run build` completed with 2,309 modules transformed; only the existing optional `fontaine` warning was emitted.
- **Route and authorization review:** All 35 wedding routes use the expected authenticated application boundary; M2 routes additionally use `wedding.member`, invitation mutations retain `verified`, and nested event, task, category, and status routes enforce scoped bindings. Policies preserve owner/editor mutations and viewer read-only access.
- **Isolation and query review:** Three-wedding tests cover dashboard and planning isolation. Operational queries originate from authorized wedding relationships, use eager loading or aggregate queries, and no global `Task`, `TaskCategory`, `TaskStatus`, or `WeddingEvent` lookup pattern was found.
- **Secret and scope review:** No database or Supabase secret marker was found in React sources. M2 application scans found no finance, savings, vendor, guest, logistics, file, custom-field, or event-day implementation. `git diff --check` passed.

## Next Authorized Work

M2 is complete. The exact next recommended milestone is **M3 — Finance**, but it has not begun and requires explicit product-owner authorization before implementation.

## M0 Exit Gate

**Passed on 4 September 2026.** Public registration works, ordinary protected routes require Laravel session authentication without globally requiring verified email, and verification remains implemented for future sensitive endpoints such as wedding invitations and private uploads. Changed email resets verification and triggers a new verification notification without blocking the dashboard/account shell. No database or Supabase secret reaches React or the production browser bundle, and migration, backend test, frontend test, lint, typecheck, and production build commands are documented and passing.

## M1 Exit Gate

**Passed on 7 September 2026.** Users can create or join multiple isolated wedding workspaces; each active wedding has exactly one active owner; ownership transfer, member lifecycle, verified sensitive invitation actions, active-wedding redirects, onboarding, event-only template cloning, and soft deletion are implemented and covered by passing tests. Complete backend and frontend quality gates pass, and route, authorization, isolation, secret-exposure, and M2+ scope reviews found no unresolved M1 defect.

## M2 Exit Gate

**Passed on 7 September 2026.** Wedding-scoped events, configurable task references, task lifecycle, planning list, timeline, and dashboard are implemented with canonical UUID routes, active-membership middleware, scoped nested bindings, Policies, Form Requests, direct tenant keys, and composite relationship constraints. The accepted D-012 primary-event anchor moves only template-relative task dates after explicit confirmation. Fresh database, backend, frontend, formatting, static-analysis, type-check, build, route, isolation, query, secret-exposure, and M3+ exclusion checks passed. M3 has not begun.

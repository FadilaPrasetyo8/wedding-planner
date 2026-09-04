# Wedding Planner Progress

## Current State

- **Phase:** M0 — Foundation complete
- **Implementation:** M0 Tasks 1–7 complete; all M0 exit gates pass
- **Current milestone:** M0 — Foundation
- **Last verified update:** 4 September 2026

The product owner approved the planning baseline and M0 execution design. M0 technical decisions are recorded in D-013 through D-015. The Laravel 13, Inertia 3, React 19, Tailwind CSS 4, PostgreSQL, authentication, profile-security, application-shell, and UI-testing foundations are implemented and verified. No deployment, Supabase configuration, production infrastructure, or M1+ work has begun.

## Planning Deliverables

| Deliverable | State | Notes |
|---|---|---|
| `CLAUDE.md` | Complete | Repository operating rules and implementation invariants |
| `docs/PRD_Wedding_Planner.md` | Complete | True UTF-8 Markdown source of truth |
| `docs/architecture.md` | Review-ready | Architecture sections completed and cross-checked |
| `docs/database-erd.md` | Review-ready | Entity responsibilities, isolation, integrity, and deferred scope |
| `docs/implementation-plan.md` | Review-ready | Sequential M0–M7 goals, gates, and test categories |
| `docs/decision-log.md` | Complete | Accepted decisions and one milestone-gated pending decision |
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

- **Before M2:** template timeline anchor event, recorded as D-012.
- **Before brand work:** final product name and visual identity. This does not block M0 backend and authentication foundations.
- **When operationally required:** email and observability providers. These do not block the approved local M0 implementation.

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

## Next Authorized Work

M0 is complete. The exact next recommended milestone is **M1 — Workspace and membership**, beginning with its approved requirements and first vertical slice only after explicit authorization.

## M0 Exit Gate

**Passed on 4 September 2026.** Public registration works, ordinary protected routes require Laravel session authentication without globally requiring verified email, and verification remains implemented for future sensitive endpoints such as wedding invitations and private uploads. Changed email resets verification and triggers a new verification notification without blocking the dashboard/account shell. No database or Supabase secret reaches React or the production browser bundle, and migration, backend test, frontend test, lint, typecheck, and production build commands are documented and passing.

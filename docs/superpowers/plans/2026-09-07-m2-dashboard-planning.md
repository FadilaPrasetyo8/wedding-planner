# M2 Dashboard and Planning Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver isolated wedding events, task configuration and lifecycle, searchable planning, an accessible timeline, and a server-authoritative planning dashboard.

**Architecture:** Wedding-scoped PostgreSQL records use direct tenancy keys and composite foreign keys. Canonical UUID routes pass through active-membership middleware, resource Policies, Form Requests, and focused transactional Actions. A shared validated task query drives list and timeline, while dedicated aggregate queries drive the dashboard.

**Tech Stack:** Laravel 13, PHP 8.3, PostgreSQL, PHPUnit, Inertia 3, React 19 TypeScript, Tailwind CSS 4, shadcn/ui, Vitest/RTL.

**Spec:** `docs/superpowers/specs/2026-09-07-m2-dashboard-planning.md`

## Global Constraints

- Implement M2 only; do not add budgets, savings, vendors, guests, checklists, rundown, playlist, files, exports, custom fields, deployment, Supabase, or billing.
- Preserve completed M0/M1 behavior and tests.
- Use canonical `/weddings/{wedding:uuid}/...` routes and direct `wedding_id` on M2 operational records.
- Owner/editor may mutate operational planning; viewer is read-only.
- Use Indonesian copy and the existing warm ivory/burgundy/muted rose/clay visual system.
- Do not add a large Gantt dependency or any major dependency.
- Do not commit, deploy, configure Supabase, or use subagents.
- Follow TDD per slice; run focused tests during implementation and the full quality gate only after all slices are complete.

---

### Task 1: M2 schema, models, defaults, and accepted anchor

**Files:**
- Create `database/migrations/2026_09_07_000000_create_planning_tables.php`.
- Create `app/Enums/TaskBehavior.php`, `TaskPriority.php`, and `TaskDateMode.php`.
- Create `app/Models/TaskCategory.php`, `TaskStatus.php`, and `Task.php`.
- Modify `app/Models/Wedding.php`, `WeddingEvent.php`, `WeddingMember.php`, and `TemplateItem.php`.
- Create planning factories and `app/Actions/Weddings/SeedWeddingPlanningDefaultsAction.php`.
- Modify onboarding and template seed/cloning Actions.
- Test `tests/Feature/Planning/PlanningSchemaTest.php` and extend `CreateWeddingTest.php`.

**Interfaces:** Produces wedding relationships `taskCategories()`, `taskStatuses()`, and `tasks()`; event fields `type`, `starts_at`, `ends_at`, `venue`, `notes`; task date modes and status behaviors; independent defaults and validated template tasks.

- [ ] Write failing tests proving the partial one-primary index, composite same-wedding foreign keys, date checks, immutable status behavior contract, Indonesian defaults, and M2 task-template rollback/independence.
- [ ] Run only those tests and confirm failure because M2 schema does not exist.
- [ ] Implement the migration, models, enums, relationships, factories, defaults Action, and M2 template payload validation.
- [ ] Integrate defaults and task cloning into the existing wedding-creation transaction, choosing Akad as default primary when present and otherwise retaining the explicit/main-date event.
- [ ] Run the focused schema and onboarding tests until green.

### Task 2: Event CRUD and primary-event invariants

**Files:**
- Create event Store/Update Form Requests, `WeddingEventPolicy`, `WeddingEventController`, and `ChangePrimaryWeddingEventAction`.
- Create `resources/js/pages/weddings/events/index.tsx`, `create.tsx`, and `edit.tsx`.
- Add event routes and navigation.
- Test `tests/Feature/Planning/WeddingEventTest.php`.

**Interfaces:** Event mutations resolve through `$wedding->events()`; primary changes accept `confirm_recalculate_relative_tasks`; the Action locks events and updates only `template_relative` task dates from retained offsets.

- [ ] Write failing tests for owner/editor CRUD, viewer denial, time validation, one-primary protection, required replacement, explicit recalculation confirmation, manual-date preservation, and cross-wedding nested 404.
- [ ] Run the event tests and verify the missing routes/behavior failures.
- [ ] Implement Policies, Requests, controller coordination, transaction Action, routes, and Indonesian responsive pages.
- [ ] Run the focused event tests until green.

### Task 3: Task configuration and task lifecycle

**Files:**
- Create category/status Policies, Requests, controllers, and routes.
- Create task Store/Update Requests, `TaskPolicy`, `TaskController`, and `PersistTaskAction`.
- Create `resources/js/pages/weddings/planning/create.tsx`, `edit.tsx`, and `configuration.tsx`.
- Test `tests/Feature/Planning/TaskConfigurationTest.php` and `TaskManagementTest.php`.

**Interfaces:** `PersistTaskAction` computes template-relative dates from the current primary-event/main-date anchor, sets or clears `completed_at` from immutable status behavior, and stores only route-wedding relationships validated by the Requests.

- [ ] Write failing tests for independent defaults, editable labels/colors, immutable behavior, used-reference protection, owner/editor mutations, viewer denial, active same-wedding assignment, event/category/status isolation, date-mode rules, completed lifecycle, and cross-wedding nested 404.
- [ ] Run only configuration/task tests and confirm expected failures.
- [ ] Implement the Requests, Policies, Actions, controllers, routes, and behavior-focused forms.
- [ ] Run focused configuration/task tests until green.

### Task 4: Planning list query and responsive list

**Files:**
- Create `app/Http/Requests/Weddings/PlanningIndexRequest.php` and `app/Support/Planning/TaskListQuery.php`.
- Add planning index to `TaskController`.
- Create `resources/js/pages/weddings/planning/index.tsx` and typed planning props.
- Add a focused Vitest test for filter URL submission/reset if interaction complexity warrants it.
- Test `tests/Feature/Planning/PlanningListTest.php`.

**Interfaces:** `TaskListQuery::apply(Wedding $wedding, array $filters): Builder` is the sole list/timeline filter implementation; it begins from `$wedding->tasks()`, validates allowlisted sorts, eager-loads display relations, and orders by task ID as final tie-breaker.

- [ ] Write failing feature tests for search; each requested filter; overdue and date-range semantics; allowlisted deterministic sorting; pagination query retention; and three-wedding read isolation.
- [ ] Run the planning-list tests and confirm expected failures.
- [ ] Implement validated filters, query service, pagination props, desktop table, mobile compact rows, empty states, and viewer read-only messaging.
- [ ] Run focused backend tests and only behavior-relevant frontend tests until green.

### Task 5: Dependency-free timeline/Gantt

**Files:**
- Create `app/Http/Controllers/Weddings/WeddingTimelineController.php`.
- Create `resources/js/pages/weddings/planning/timeline.tsx` and focused timeline components.
- Add timeline route/navigation.
- Test `tests/Feature/Planning/WeddingTimelineTest.php` and focused range-rendering unit/component tests when behavior is nontrivial.

**Interfaces:** Timeline invokes the same `TaskListQuery` filters as list view and serializes bounded start/due dates plus event markers. Date mutation remains through the accessible task edit route.

- [ ] Write failing tests proving list/timeline filter and date consistency, event marker isolation, overdue state, and bounded result behavior.
- [ ] Run timeline tests and confirm missing route/props failure.
- [ ] Implement the timeline controller and a horizontally contained CSS timeline with duration bars, text status, event milestones, overdue labels, keyboard links, and reduced-motion-safe styling.
- [ ] Run focused timeline tests until green.

### Task 6: Server-authoritative wedding dashboard

**Files:**
- Create `app/Support/Planning/WeddingDashboardData.php` or equivalent focused query service.
- Modify `WeddingOverviewController.php` and `resources/js/pages/weddings/show.tsx`.
- Test `tests/Feature/Planning/WeddingDashboardTest.php`.

**Interfaces:** Dashboard service accepts one authorized Wedding and returns primary event/countdown, task totals, completion percentage, overdue/upcoming collections, and grouped counts using wedding-scoped aggregate queries.

- [ ] Write failing tests for timezone-aware countdown, active/completed denominator, overdue semantics, upcoming tasks/events, grouped counts, query isolation across three weddings, and viewer read access.
- [ ] Run the dashboard tests and confirm missing aggregate props.
- [ ] Implement bounded aggregate/eager-loaded queries and replace the M1 summary with dense Indonesian planning information only.
- [ ] Run focused dashboard tests until green and inspect query count for obvious N+1 behavior.

### Task 7: M2 exit gate and verified documentation

**Files:**
- Modify `docs/decision-log.md` to mark D-012 accepted with the approved primary-event/Akad/main-date behavior.
- Modify `docs/progress.md` only after all verification passes.

- [ ] Run `php artisan migrate:fresh --seed --force` against the dedicated test database.
- [ ] Run focused frontend tests, then complete `composer test` once; reproduce any failures with focused commands before rerunning the complete backend gate.
- [ ] Run `npm run check`, `npm run types:check`, and `npm run build` sequentially.
- [ ] Inspect route middleware/scoped bindings and all M2 Policies.
- [ ] Review query construction for wedding-first access, aggregate scoping, deterministic pagination, bounded timeline payloads, and N+1 prevention.
- [ ] Run three-wedding isolation, secret-exposure, and M3+ scope scans; inspect `git diff --check` and changed files without discarding unrelated work.
- [ ] Record D-012 and M2 completion/results in documentation only after every gate passes.

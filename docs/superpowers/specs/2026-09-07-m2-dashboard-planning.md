# M2 Dashboard and Planning Specification

**Status:** Accepted — approved for implementation

**Date:** 7 September 2026

**Scope:** M2 only. M0 and M1 remain complete and are not repeated. This specification adds wedding events, task configuration and lifecycle, planning list, timeline/Gantt, and planning-only dashboard data. Finance, savings, logistics, event-day operations, files, exports, custom fields, deployment, and Supabase configuration remain excluded.

## 1. Outcomes

M2 turns each wedding workspace into a useful planning system. Owners and editors can maintain events and tasks; viewers receive an explicit read-only experience. Every record, relationship, list query, timeline row, and dashboard aggregate is scoped from the route wedding and protected by active-membership middleware plus Policies.

## 2. Timeline anchor decision

D-012 is accepted with this behavior:

1. Template-relative task dates anchor to the wedding's user-selected primary event.
2. When a default primary event must be selected, an event typed or named `Akad` is preferred.
3. If no usable primary event exists, relative-date calculation falls back to `weddings.wedding_date`.
4. A primary-event change recalculates only tasks whose date mode is `template_relative` and only after explicit confirmation. Manual task dates never move.
5. Relative tasks retain integer start and due offsets, so recalculation is deterministic and does not compound prior date changes.

## 3. Data model and integrity

### Wedding events

`wedding_events` retains direct `wedding_id` and adds controlled `type` (`engagement`, `akad`, `reception`, `custom`), nullable start/end times, venue, and notes. Event date is required. End time cannot precede start time when both are supplied. Custom events use `custom` while preserving a user-defined name.

A PostgreSQL partial unique index allows at most one primary event per wedding. Event Actions run transactions and row locks to maintain at least one primary event for an active wedding. A primary event cannot be demoted or deleted without selecting a replacement. Changing the primary event with relative tasks requires the confirmation field `confirm_recalculate_relative_tasks`.

### Task categories

`task_categories` contains UUID, direct `wedding_id`, label, color, sort order, active state, and timestamps. Names are unique case-insensitively per wedding. Defaults are cloned independently for every wedding: `Persiapan Utama`, `Dokumen & Administrasi`, `Vendor & Lokasi`, and `Koordinasi Keluarga`.

### Task statuses

`task_statuses` contains UUID, direct `wedding_id`, editable label and color, immutable behavioral key, sort order, active state, and timestamps. Behavioral keys are `todo`, `in_progress`, `completed`, and `cancelled`. They determine lifecycle and aggregates; changing a label never changes behavior. Custom statuses select one behavior at creation, and update routes never accept a behavior change. Defaults are `Belum Mulai`, `Proses`, `Selesai`, and `Dibatalkan`.

### Tasks

`tasks` contains UUID, direct `wedding_id`, title, nullable description, category, status, priority (`low`, `medium`, `high`, `urgent`), nullable active wedding-membership assignee, nullable event, date mode (`manual`, `template_relative`), start/due dates, nullable relative start/due offsets, nullable `completed_at`, notes, and timestamps.

Tenant-sensitive relationships use composite foreign keys containing `wedding_id`. Due date cannot precede start date. Manual tasks have null offsets. Template-relative tasks retain both offsets and server-calculated dates. A completed-behavior status sets `completed_at`; moving to any other behavior clears it. Assignments accept only active memberships from the same wedding.

## 4. Defaults and template tasks

Wedding creation clones planning defaults in the existing onboarding transaction. M2 expands controlled template item types to include `task`. Task payloads contain title, optional description, category label, status behavior, priority, start offset, due offset, optional event name, and notes. Payloads are validated before persistence. Unsupported or invalid payloads roll back wedding creation.

Existing weddings receive independent default categories and statuses during the M2 migration. Master templates receive a small Indonesian planning starter set; no M3+ payload is stored or cloned.

## 5. Authorization and HTTP boundaries

Canonical routes remain under `/weddings/{wedding:uuid}/...` and use `auth` plus `wedding.member`.

- All active members may view dashboard, events, planning list, task detail, timeline, and planning configuration.
- Owners and editors may create, update, and delete events, categories, statuses, and tasks.
- Viewers cannot mutate.
- Event and task Policies authorize reads and mutations.
- Form Requests authorize through Policies and validate all related identifiers against the route wedding.
- Nested records are resolved from wedding relationships or scoped bindings; application code never performs a global operational lookup followed only by a wedding comparison.

## 6. Event management

The event index is a compact chronological list with event type, date/time, venue, and primary marker. Owners/editors receive create/edit forms and destructive confirmations; viewers receive a clearly labeled read-only state. Event mutation Actions lock the wedding event set, enforce one primary, and recalculate relative tasks only when explicitly confirmed.

## 7. Task management

Task create/edit forms support manual dates or primary-event-relative offsets. Categories, statuses, assignee memberships, and events are server-provided wedding-scoped options. Validation errors are Indonesian and field-specific. Task deletion uses explicit confirmation. Status transitions update `completed_at` on the server.

Category and status configuration is compact and secondary to task work. Used references cannot be deleted; they may be renamed, recolored, reordered, or deactivated. Status behavior is shown with a text label and cannot be edited after creation.

## 8. Planning list

`GET /weddings/{wedding:uuid}/planning` owns list state through validated query parameters:

- `search`
- `status`
- `category`
- `priority`
- `assignee`
- `event`
- `overdue`
- `date_from`
- `date_to`
- `sort`
- `direction`
- `page`

Sort fields are allowlisted: title, start date, due date, priority, status, category, and updated time. Every sort adds `tasks.id` as a deterministic tie-breaker. Search and filters begin from `$wedding->tasks()`, eager-load display relationships, and paginate with the query string retained.

Desktop uses a dense table; mobile uses compact task rows/cards. Empty filtered and empty workspace states are distinct. Viewer mode states that planning is read-only.

## 9. Timeline and Gantt

The timeline reuses the same validated filter/query service as the planning list, ensuring identical date ranges and filters. It is dependency-free and server-driven: CSS grid/positioning renders a bounded date scale, task duration bars, status text, event milestone markers, and overdue labels. Color is never the only status signal.

There is no arbitrary drag mutation. Every task links to an accessible edit form where keyboard and touch users can change dates under normal server validation. Wide timeline content scrolls within its own container. Reduced-motion users receive no nonessential transitions.

## 10. Wedding dashboard

The canonical wedding overview becomes the M2 dashboard. All values come from direct wedding-scoped server queries:

- Primary-event countdown in the wedding timezone
- Active task total, completed total, and completion percentage
- Overdue task count and compact overdue list
- Upcoming tasks and events
- Counts grouped by status and category where useful

Cancelled tasks are excluded from active completion denominator and overdue counts. A task is overdue when its due date is before the wedding-local current date and its status behavior is neither completed nor cancelled. No budget, savings, vendor, guest, file, or later-milestone metric appears.

## 11. UX and accessibility

All copy is Indonesian. Existing ivory, burgundy, muted rose, and clay tokens remain authoritative. Screens are information-dense, with small summary bands, compact tables, restrained borders, and no generic dashboard card wall.

Important controls are at least 44px on touch layouts. Forms use visible labels and nearby errors. Focus remains visible. Keyboard users can reach all actions. Status and overdue state include text/icon meaning. Loading, empty, error, success, disabled, confirmation, and viewer read-only states are represented where behaviorally relevant. Motion is subtle and disabled under `prefers-reduced-motion`.

## 12. Performance and verification

List and dashboard controllers use bounded selects, aggregate queries, eager loading, and server pagination. Relevant indexes begin with `wedding_id`. No per-row relationship queries are permitted.

Focused tests cover schema constraints, defaults, role authorization, event primary invariants, confirmed relative-date recalculation, status lifecycle, same-wedding assignments and references, list filter/sort/pagination persistence, timeline/list consistency, dashboard aggregates, and three-wedding isolation. Focused frontend tests cover only meaningful filter URL behavior and viewer mutation state. Final verification runs fresh migrations/seeds, complete backend and frontend suites, formatting/lint, PHP and TypeScript static analysis, production build, route/Policy review, N+1/aggregate review, isolation review, secret scan, and M3+ exclusion review before `docs/progress.md` is updated.

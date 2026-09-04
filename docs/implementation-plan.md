# Wedding Planner Implementation Plan

This plan sequences the Wedding Planner V1 baseline as coherent vertical slices. No application implementation begins until the planning suite is approved, and every milestone must satisfy its exit criteria before the next begins unless explicitly reprioritized.

## M0 — Foundation

- **Goal:** Establish the supported Laravel, React, TypeScript, Inertia, Tailwind, and PostgreSQL application baseline with secure Laravel authentication and repeatable quality gates.
- **Dependencies:** Approved architecture and ERD; M0 decisions for exact framework versions, authentication starter, test runner, environment strategy, and development tooling.
- **Main deliverables:** Project setup; environment and secret conventions; database connection; public and authenticated app shells; registration, login, logout, password reset, email verification, and profile management; baseline UI primitives; factories and test infrastructure.
- **Exit criteria:** Public registration works without an allowlist; protected routes require Laravel session authentication; changed email requires reverification; no Supabase or database secret reaches React; migration, test, lint, typecheck, and build commands are documented and passing.
- **Required test categories:** Authentication feature tests; profile and verification tests; CSRF and rate-limit coverage; frontend shell and form-state tests; configuration and secret-exposure checks.

## M1 — Workspace and Membership

- **Goal:** Deliver secure multi-wedding workspaces, exactly-one-owner membership, invitations, workspace switching, onboarding, and atomic template cloning.
- **Dependencies:** M0 complete; ownership constraints and transfer behavior approved; template payload and wedding deletion conventions resolved.
- **Main deliverables:** Weddings, memberships, invitations, owner/editor/viewer Policies, active-wedding context, switcher, member management, ownership transfer, onboarding, initial events, template selection, and independent template cloning.
- **Exit criteria:** A user can create or join multiple weddings; creation assigns exactly one owner; invitations grant only editor or viewer; ownership transfer is atomic; owners cannot leave before transfer; cloned records are independent; unauthorized wedding access is forbidden and cross-wedding nested records are not exposed.
- **Required test categories:** Workspace and invitation feature tests; role and Policy matrix tests; ownership concurrency and transaction tests; template-cloning rollback tests; three-wedding isolation tests; onboarding frontend tests.

## M2 — Dashboard and Planning

- **Goal:** Provide wedding-scoped events, tasks, configurable planning references, dashboard summaries, countdown, list workflows, and Gantt/timeline views.
- **Dependencies:** M1 complete; task priority, status behavioral flags, and template date-anchor behavior approved.
- **Main deliverables:** Wedding events; task categories and statuses; task CRUD and assignment to active same-wedding members; search, filters, sorting, pagination, completion lifecycle, dashboard planning summaries, upcoming items, countdown, and Gantt view.
- **Exit criteria:** Task dates and completion behavior are valid; list and Gantt dates agree; URL filters persist; dashboard planning aggregates use only the active wedding; owner/editor mutations and viewer read-only behavior are enforced; important CRUD remains usable on mobile.
- **Required test categories:** Planning feature and validation tests; Policy and relationship-isolation tests; date-offset, progress, and countdown unit tests; filter and Gantt frontend tests; aggregate query and N+1 checks.

## M3 — Finance

- **Goal:** Deliver reliable wedding-scoped budget scenarios, committed budget items, repeatable payment terms, and savings with server-authoritative whole-rupiah calculations.
- **Dependencies:** M2 complete; financial status rules, valid-paid behavior, contributor linking, and audit requirements approved.
- **Main deliverables:** Budget categories and scenarios; independent scenario copying and application; budget items linked only to same-wedding events and private vendor contacts; ordered payment-term child records; paid, outstanding, and overpayment behavior; savings goals, contributors, contributions, dashboard finance summaries, and activity logs for important financial changes.
- **Exit criteria:** No fixed payment columns or floating-point money exist; one budget item supports at least ten terms; paid and outstanding totals derive from valid records; overpayment warns without rewriting the final amount; scenario application preserves existing actual data; financial mutations are atomic and wedding-isolated.
- **Required test categories:** Financial feature and Policy tests; calculation and overpayment unit tests; transaction and concurrency tests; ten-payment-term coverage; three-wedding aggregate isolation tests; payment editor and validation-state frontend tests.

## M4 — Logistics

- **Goal:** Deliver usable seserahan, administration, private vendor-contact, and guest workflows without introducing vendor-facing functionality.
- **Dependencies:** M3 complete; checklist structure, private vendor contact shape, guest status strategy, and import scope approved.
- **Main deliverables:** Checklist groups, categories, statuses, and items for seserahan and administration; costs, deadlines, completion, assignees, and summaries; private wedding-entered vendor categories, contacts, statuses, budget relationships, and internal notes; guest sides, groups, invitation and RSVP states, guest CRUD, pax summaries, search, filters, sorting, and pagination.
- **Exit criteria:** All records and relationships are wedding-scoped; checklist totals and overdue states are correct; selected vendors use status and relationships rather than a duplicate table; no vendor account, listing, marketplace, portal, or vendor custom field exists; guest counts and labels remain isolated; viewers cannot mutate.
- **Required test categories:** Module feature, validation, and Policy tests; checklist and guest aggregate unit tests; cross-wedding relationship tests; list/filter/pagination frontend tests; vendor non-scope and read-only checks.

## M5 — Event Day

- **Goal:** Deliver ordered wedding-event rundowns and playlists with practical event-day and print-ready workflows.
- **Dependencies:** M4 complete; reliable ordering strategy approved; required template event-day content available.
- **Main deliverables:** Rundown CRUD, event assignment, times, PIC details, notes, highlights, status, reordering, overlap warnings, and print-friendly view; playlist CRUD, event and moment filtering, links, requests, notes, and reordering; independent template data for both modules.
- **Exit criteria:** Reordering is deterministic and contained within the active wedding and event; invalid time ranges are rejected; overlaps warn without unnecessary blocking; playlist order persists; template records remain independently editable; print output matches stored rundown order.
- **Required test categories:** Event-day feature and Policy tests; time-range, overlap, and ordering unit tests; concurrency and cross-wedding relationship tests; reordering and print-view frontend tests; template independence tests.

## M6 — Flexibility and Files

- **Goal:** Add controlled customization and private attachment flows without weakening typed domain rules, authorization, or wedding isolation.
- **Dependencies:** M5 complete; custom-field value strategy, attachment associations, lifecycle states, retention behavior, and supported storage adapter approved.
- **Main deliverables:** Configurable wedding-scoped references where not already delivered; controlled custom-field definitions and values for guests first, then tasks, checklist items, and budget items; private Supabase Storage adapter; PostgreSQL attachment metadata; verified-email uploads; short-lived signed access; lifecycle, deletion, cleanup, and reconciliation flows.
- **Exit criteria:** Custom fields never replace core financial, ownership, authorization, relational, or indexed date fields; no vendor custom fields exist; definitions and values cannot cross weddings; file type and size rules are server-enforced; unauthorized users cannot obtain signed URLs; failed upload or deletion does not leave false active metadata or unmanaged state.
- **Required test categories:** Custom-field feature, validation, and renderer tests; module and wedding-isolation tests; storage-adapter integration tests; upload, download, deletion, compensation, and reconciliation tests; file progress and failure-state frontend tests.

## M7 — Stabilization and Release

- **Goal:** Verify the complete early-access product for security, reliability, usability, scoped export, deployment, and operational readiness.
- **Dependencies:** M0–M6 exit criteria complete; hosting, queue, email, observability, backup, and release procedures selected and documented.
- **Main deliverables:** Excel-first selected exports for budget, guests, timeline/tasks, checklists, and rundown; print-friendly rundown; measured performance and query tuning; responsive and accessibility pass; audit and logging review; deployment automation; backup and restore procedure; smoke tests; critical Playwright journey; early-access UAT.
- **Exit criteria:** All quality gates pass; exports reproduce validated active-wedding filters and exclude other weddings; critical authorization and file scenarios pass; primary pages are usable within the approved target; backups and restore are tested; monitoring and recovery procedures exist; no V1 billing, subscription, or vendor-facing functionality is present.
- **Required test categories:** Full Laravel and frontend regression suites; export isolation tests; security and authorization regression; performance and N+1 checks; accessibility and responsive review; Playwright critical flow; deployment smoke, backup, restore, and failure-recovery tests.

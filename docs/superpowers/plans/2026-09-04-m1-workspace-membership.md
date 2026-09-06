# M1 Workspace and Membership Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver secure, isolated multi-wedding creation, membership, invitations, ownership transfer, active context, event-only template onboarding, and Indonesian role-aware UI.

**Architecture:** Canonical UUID wedding routes pass through active-membership middleware and Policies. PostgreSQL constraints plus transactional Actions protect ownership, invitations, retained memberships, and template cloning. Inertia receives minimal active-workspace summaries and renders an M1-only wedding shell.

**Tech Stack:** Laravel 13, PHP 8.3, PostgreSQL, PHPUnit, Inertia 3, React 19 TypeScript, Tailwind CSS 4, shadcn/ui, Vitest/RTL.

**Spec:** `docs/superpowers/specs/2026-09-04-m1-workspace-membership.md`

## Global Constraints

- Implement M1 only; do not add M2 dashboard metrics, tasks, finance, or later-domain records.
- Use canonical `/weddings/{wedding:uuid}/...` URLs; session context is redirect preference only.
- Keep ordinary access available to unverified users; gate only sensitive invitation actions.
- Store money as nullable non-negative PostgreSQL BIGINT whole rupiah.
- Keep all UI copy Indonesian and temporary identity `Wedding Planner`.
- Do not add major dependencies, deploy, configure Supabase, commit, or use subagents.
- Follow TDD for each behavior and run commands sequentially.

---

### Task 1: Workspace schema, models, and integrity

**Files:**
- Create migrations for weddings, wedding_members, wedding_events, templates, template_sections, template_items, wedding_invitations, and activity_logs.
- Create enums in `app/Enums/` and models in `app/Models/`.
- Modify `app/Models/User.php` relationships.
- Create factories and update `database/seeders/DatabaseSeeder.php`.
- Test `tests/Feature/Workspace/WorkspaceSchemaTest.php`.

**Interfaces:** Produces `Wedding`, `WeddingMember`, `WeddingEvent`, `Template`, `TemplateSection`, `TemplateItem`, `WeddingInvitation`, `ActivityLog`, role/status enums, active membership scopes, UUID binding, and PostgreSQL one-owner index.

- [ ] Write tests for constraints, casts, UUID route key, active membership uniqueness, one active owner, and valid event-only seeded templates.
- [ ] Run focused tests and confirm failure because schema/models are absent.
- [ ] Implement migrations, enums, models, relationships, factories, and event-only seed data.
- [ ] Run fresh migrations/seeds and focused tests; inspect rollback behavior.

### Task 2: Atomic wedding creation and onboarding

**Files:**
- Create `app/Actions/Weddings/CreateWeddingAction.php` and `CloneWeddingTemplateAction.php`.
- Create onboarding Form Request/controller/routes.
- Create `resources/js/pages/onboarding/create.tsx` and wedding types.
- Test `tests/Feature/Workspace/CreateWeddingTest.php`, `TemplateCloningTest.php`, and focused onboarding component test.

**Interfaces:** `CreateWeddingAction::execute(User $user, array $data): Wedding`; cloner accepts a Wedding and optional Template and creates validated independent events in the caller transaction.

- [ ] Write failing tests for owner creation, multiple weddings, validation, independent cloning, blank setup, and forced rollback.
- [ ] Implement transaction, validation, controller, canonical redirect, and Indonesian multi-step onboarding UI.
- [ ] Run focused backend and frontend tests until green.

### Task 3: Active wedding context, policies, and isolation

**Files:**
- Create `ActiveWeddingContext`, `EnsureWeddingMembership`, `WeddingPolicy`, middleware alias/provider registration, dashboard redirect/controller, wedding overview controller/routes.
- Modify shared Inertia props and TypeScript global types.
- Test `tests/Feature/Workspace/ActiveWeddingContextTest.php`, `WeddingPolicyTest.php`, and `WeddingIsolationTest.php`.

**Interfaces:** Request-scoped context exposes `wedding()` and `membership()`; middleware sets session preference only after successful authorization; `/dashboard` safely resolves preference/first/onboarding.

- [ ] Write failing guest, inactive-member, non-member 403, soft-deleted, redirect, and three-wedding isolation tests.
- [ ] Implement context, middleware, policies, scoped routes, and minimal props.
- [ ] Run focused tests and confirm canonical routes and response semantics.

### Task 4: Secure invitations

**Files:**
- Create notification, requests, controllers, `AcceptWeddingInvitationAction`, and invitation routes/pages.
- Extend invitation Policy and member page props.
- Test `tests/Feature/Workspace/WeddingInvitationTest.php`.

**Interfaces:** Invitation factory method returns plaintext token once while persisting SHA-256 hash; invitation resolution hashes the route token; acceptance creates or reactivates one membership row.

- [ ] Write failing tests for any-email delivery, verified-owner gate, editor/viewer restriction, hash/expiry/single-use, matching verified account, decline/cancel, wrong email, reactivation, and deleted wedding.
- [ ] Implement notification and transactional lifecycle with stable Indonesian errors.
- [ ] Run focused invitation tests until green.

### Task 5: Member management and ownership transfer

**Files:**
- Create membership requests/controllers and `TransferWeddingOwnershipAction`, `DeactivateWeddingMembershipAction`.
- Extend `WeddingPolicy`/membership Policy and member routes.
- Test `tests/Feature/Workspace/MembershipManagementTest.php` and `OwnershipTransferTest.php`.

**Interfaces:** Transfer locks wedding and memberships, demotes current owner to editor, promotes active target; deactivation rejects active owner and timestamps non-owner membership.

- [ ] Write failing Policy-matrix, role-update, removal, leave, owner safeguard, transfer, and rollback/concurrency tests.
- [ ] Implement owner-only management and self-leave rules.
- [ ] Run focused tests; verify exactly one active owner after every successful mutation.

### Task 6: Wedding settings and soft deletion

**Files:**
- Create wedding update/delete requests/controllers/actions and settings routes/page.
- Test `tests/Feature/Workspace/WeddingSettingsTest.php`.

**Interfaces:** Owner-only validated update; delete action confirms name, locks owner membership, soft-deletes wedding, clears active session preference, and records activity.

- [ ] Write failing owner/editor/viewer, validation, explicit confirmation, and deleted-access tests.
- [ ] Implement settings and soft deletion without restoration UI.
- [ ] Run focused tests until green.

### Task 7: Indonesian role-aware application shell

**Files:**
- Modify app sidebar/logo/header/layout/dashboard.
- Create wedding switcher, role badge, verification notice, members/settings/index pages.
- Add focused Vitest tests for switcher/onboarding/read-only behavior.

**Interfaces:** Shared props provide accessible weddings, active wedding, active membership, and capabilities; UI never calculates permissions from role alone.

- [ ] Write failing component tests for switching links and explicit `Hanya lihat` state.
- [ ] Implement warm ivory/burgundy wedding-specific shell, M1-only nav, responsive sheet behavior, visible focus, loading/error/empty/confirmation states, and Indonesian copy.
- [ ] Run Vitest, frontend check, and TypeScript check until green.

### Task 8: M1 exit gate and documentation

**Files:**
- Update `docs/decision-log.md` with approved M1 retention, invitation, context, and template decisions.
- Update `docs/progress.md` only after all gates pass.

- [ ] Run fresh PostgreSQL migrations and seeds.
- [ ] Run all PHPUnit tests once; fix failures with focused reproduction before rerunning the full gate.
- [ ] Run all Vitest tests, frontend formatting/lint, TypeScript, and production build sequentially.
- [ ] Inspect routes for auth, verification, membership, and owner boundaries.
- [ ] Run authorization, three-wedding isolation, secret-exposure, generated-bundle, and M2+ scope scans.
- [ ] Review changed files for unrelated work, then record only verified results in `docs/progress.md`.

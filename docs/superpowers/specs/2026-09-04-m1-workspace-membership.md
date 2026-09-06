# M1 Workspace and Membership Specification

**Status:** Accepted — approved for implementation

**Date:** 4 September 2026

**Scope:** M1 only. This specification implements the M1 requirements already approved in the PRD, architecture, decision log, ERD, and implementation plan. It does not introduce M2 planning behavior or later operational modules.

## 1. Outcomes

M1 delivers a secure multi-wedding foundation in which an authenticated account can create or join multiple isolated wedding workspaces. Each active wedding has exactly one active owner, editor and viewer roles are enforced on the server, invitations are secure and email-bound, ownership transfer is explicit and atomic, template cloning creates independent initial wedding events, and every wedding screen resolves a canonical UUID-scoped context.

## 2. Scope boundaries

Included:

- Wedding creation and onboarding
- Multiple weddings per account
- Exactly-one-owner membership with retained inactive member history
- Editor and viewer memberships
- Secure invitation creation, delivery, acceptance, decline, and cancellation
- Explicit ownership transfer, member role changes, member removal, and member leave rules
- Canonical `/weddings/{wedding:uuid}/...` routes
- A last-accessible-wedding session preference used only for redirects
- Initial wedding events and M1-supported template cloning
- Owner/editor/viewer Policies, middleware, and UI states
- Indonesian wedding index, onboarding, workspace overview, invitation, member, and settings interfaces
- Responsive wedding-specific application navigation and switcher
- Minimal wedding-scoped activity records for sensitive M1 actions

Excluded:

- Tasks, timeline, Gantt, countdown, dashboard aggregates, planning metrics, budgets, savings, checklists, vendors, guests, rundown, playlist, files, custom fields, exports, billing, deployment, and Supabase configuration
- M2–M5 template payloads or placeholder operational records
- A final product name or final brand identity
- Resolution of D-012, the M2 timeline-anchor decision

## 3. Canonical context and navigation

Wedding pages use canonical UUID URLs:

- `/weddings/{wedding:uuid}/dashboard`
- `/weddings/{wedding:uuid}/members`
- `/weddings/{wedding:uuid}/settings`

`/dashboard` is an authenticated redirect endpoint. It chooses the session's last wedding only when the user still has an active membership, otherwise the first accessible wedding, otherwise `/onboarding`. The session value is a convenience and never authorizes access. Every canonical request resolves the route wedding, checks active membership, then applies a Policy.

An authenticated non-member receives `403 Forbidden`. Nested records are loaded from the active wedding relation so a cross-wedding nested identifier receives `404 Not Found`. Soft-deleted weddings are not route-bindable in ordinary application routes.

## 4. Data model

### Weddings

`weddings` stores an internal bigint ID, immutable unique UUID, workspace name, both partner names, main wedding date, nullable planning start date, IANA timezone, `IDR` currency, nullable non-negative whole-rupiah target budget, controlled status, timestamps, and soft deletion. Planning start cannot follow the wedding date. M1 creates weddings in `planning` status. Deletion is owner-only, explicitly confirmed, and soft-deletes the wedding; restoration is outside M1.

### Memberships

`wedding_members` stores wedding and user foreign keys, role (`owner`, `editor`, or `viewer`), `joined_at`, nullable `left_at`, and timestamps. Null `left_at` means active. `(wedding_id, user_id)` is unique.

PostgreSQL has a partial unique index on `wedding_id` where `role = 'owner' and left_at is null`, enforcing at most one active owner. Creation and ownership transfer enforce at least one active owner transactionally. Rejoining restores the existing row by setting its invited role, resetting `joined_at`, and clearing `left_at`.

The owner cannot leave, remove themselves, or be removed. Ownership must first be transferred to an existing active editor or viewer. Transfer promotes the target and demotes the previous owner to editor in one transaction while locking the wedding and relevant memberships.

### Invitations

`wedding_invitations` stores a public UUID, wedding, normalized lowercase email, role restricted to editor/viewer, SHA-256 token hash, inviting owner, expiry, terminal timestamps, and normal timestamps.

An owner may invite any valid email, registered or not. Only a verified owner may create or cancel an invitation. Acceptance or decline requires an authenticated, verified account whose normalized current email matches. Tokens are cryptographically random, expire after seven days, and are single-use. Terminal or expired invitations cannot be reused. A replacement atomically cancels an expired pending invitation for the same wedding and email before insertion.

Acceptance creates a membership or reactivates the retained inactive row. It rejects an active member and never grants ownership.

### Templates and initial events

`templates`, `template_sections`, and `template_items` are application-owned masters. Items use a controlled `type` and validated JSONB payload. M1 supports only `event` items containing `name`, `date_offset_days`, `is_primary`, and `sort_order`.

M1 creates `wedding_events` with UUID, direct wedding ownership, name, date, primary flag, order, and timestamps. Cloned events are independent rows and do not retain a source-of-truth template dependency.

Selections are `Bismillah Nikah`, a smaller `Inti Acara`, or blank. All payloads pass the same cloner validation used by onboarding. No M2–M5 payload is created or stored. Event offsets use the entered main wedding date and do not resolve D-012.

## 5. Atomic onboarding

Onboarding collects workspace name, both partner names, wedding date, optional planning start, timezone, optional target budget, an initial event name, and a template or blank selection.

`CreateWeddingAction` runs one transaction: create wedding and UUID; create owner membership; create explicit initial event; clone supported template events without duplicating the explicit event name/date; record creation activity. Any failure rolls back every record.

## 6. Authorization and roles

Owner can view the wedding, update/delete settings, manage editor/viewer invitations and memberships, transfer ownership, and leave only after transfer. Editor can view M1 pages and leave but cannot administer workspace access or settings. Viewer can view M1 pages in an explicit read-only state and leave but cannot mutate.

Policies and Form Request authorization are authoritative. Hidden or disabled controls are usability only. Future operational mutation rights are not implemented in M1.

## 7. HTTP and active context

Controllers are thin; Form Requests validate and authorize; focused Actions own transactions. `SetActiveWedding` verifies active membership, sets request-scoped wedding/member context, and writes the UUID to session only after access succeeds.

Invitation notifications use Laravel notifications and the synchronous queue convention. Email provider selection remains deferred. Tokens, hashes, and private invitation state are excluded from shared props and logs.

## 8. Inertia application shell

Shared authenticated props contain the current user; accessible wedding summaries; active wedding; active role and server-derived capabilities; flash; and sidebar state. No unrestricted models or unrelated wedding data are serialized.

All UI copy is Indonesian. The temporary identity remains `Wedding Planner`. The keyboard-accessible switcher and M1 navigation contain `Ruang pernikahan`, `Anggota`, and `Pengaturan`. The overview shows wedding identity, date, current role, verification guidance, and honest setup status—never M2 countdowns, task metrics, financial aggregates, or charts.

Owner pages expose access management. Editor/viewer pages explain limitations with labels and icons; viewer state says `Hanya lihat`. Unverified users can access ordinary account and wedding pages, while invitation controls explain verification requirements. Desktop uses a compact wedding-aware sidebar; mobile uses accessible sheet navigation and 44px controls. Focus is visible, status is not color-only, motion is restrained, and destructive actions require confirmation.

## 9. Error and concurrency behavior

Validation errors use field-specific Indonesian messages. Unauthorized access is 403; cross-wedding nested lookup is 404. Transfer and invitation acceptance lock relevant rows. Database uniqueness prevents concurrent active owners. Failed onboarding leaves no residue. Expired, terminal, mismatched-email, or deleted-wedding invitations cannot change membership. Leave/removal sets `left_at` and preserves the row.

## 10. Verification

Coverage must prove multiple weddings, owner assignment, one-owner enforcement, safe membership reactivation, role Policy matrix, three-wedding isolation, ordinary unverified wedding access, verified invitation actions, any-email delivery, email-bound acceptance/decline, hash/expiry/single-use rules, owner safeguards and atomic transfer, template independence and rollback, soft deletion, and safe session redirects.

Focused frontend tests cover onboarding/template state, switcher behavior, and explicit read-only roles. Final gates include fresh PostgreSQL migrations/seeds, all PHPUnit and Vitest tests, formatting/lint, TypeScript, production build, route inspection, authorization/isolation review, secret scan, and M2+ scope scan. `docs/progress.md` changes only after all gates pass.

# Wedding Planner Decision Log

This log records product and architecture decisions that implementation must not silently change. Status `Accepted` means the decision is part of the V1 baseline; `Pending` means implementation must stop at the stated milestone gate until the product owner decides.

## D-001 — Open Multi-Account Product Foundation

- **Status:** Accepted
- **Decision:** Registration is open to many accounts. A user may create or join multiple isolated wedding workspaces.
- **Consequence:** No two-account allowlist or hardcoded user pairing. Billing and entitlements remain outside V1.

## D-002 — Laravel Modular Monolith

- **Status:** Accepted
- **Decision:** Build one Laravel application with React, TypeScript, Inertia.js, Tailwind CSS, and PostgreSQL.
- **Consequence:** Do not create separate frontend and public API repositories. Exact supported versions are selected and recorded during M0.

## D-003 — Laravel Authentication and Private Supabase Storage

- **Status:** Accepted
- **Decision:** Laravel session authentication is the only identity system. PostgreSQL is the application source of truth; Supabase provides private object storage only through Laravel.
- **Consequence:** Do not use Supabase Auth or expose database/storage secrets to React. Laravel authorizes each signed file access.

## D-004 — Exactly One Owner per Wedding

- **Status:** Accepted
- **Decision:** Every active wedding has exactly one owner. Other memberships are editor or viewer. Ownership transfer to an existing active member is required before the owner can leave or be removed.
- **Consequence:** Transfer is transactional and protected by database uniqueness and last-owner safeguards.

## D-005 — Private Vendor Contacts, Not a Vendor Platform

- **Status:** Accepted
- **Decision:** V1 stores private vendor candidates and contact records entered by wedding members for their own planning.
- **Consequence:** No vendor registration, authentication, public profile, listing, marketplace, vendor portal, vendor-owned data, or vendor custom field is included.

## D-006 — Repeatable Payment Terms

- **Status:** Accepted
- **Decision:** A budget item has an ordered zero-to-many collection of payment-term child records with user-defined labels.
- **Consequence:** Fixed `DP`, `Termin 1`, or `Termin 2` columns are prohibited. At least ten terms must work without a schema change.

## D-007 — Controlled Custom Fields and Rollout

- **Status:** Accepted
- **Decision:** Custom fields are limited to guests first, followed by tasks, checklist items, and budget items.
- **Consequence:** Core finance, ownership, authorization, relationships, and important indexed dates remain typed. Vendor custom fields and a generic form builder are outside V1.

## D-008 — Whole-Rupiah Money

- **Status:** Accepted
- **Decision:** Monetary values use PostgreSQL `BIGINT` representing whole Indonesian Rupiah.
- **Consequence:** Floating-point money and formatted currency strings are prohibited in persistence. Server-side records determine aggregates.

## D-009 — Excel-First Export

- **Status:** Accepted
- **Decision:** Implement Excel export after core CRUD stabilizes; add PDF later for print-oriented output.
- **Consequence:** M7 first covers selected Excel exports for budget, guests, tasks/timeline, checklists, and rundown. Viewers cannot export in V1.

## D-010 — V1 Private Upload Policy

- **Status:** Accepted
- **Decision:** Uploads accept PDF, JPG/JPEG, PNG, and WebP up to 10 MB per file. Verified email is required for upload and invitation, but not for creating a wedding.
- **Consequence:** Laravel validates size, extension, detected MIME type, target authorization, and wedding ownership before private storage.

## D-011 — No V1 Monetization

- **Status:** Accepted
- **Decision:** V1 is open early access without subscription, payment gateway, SaaS invoicing, trials, or paid feature limits.
- **Consequence:** Commercial entities, routes, UI, and speculative entitlement abstractions are deferred until product validation.

## D-012 — Template Timeline Anchor

- **Status:** Accepted
- **Decision:** Template-relative tasks anchor to the wedding's user-selected primary event. When a default primary event must be chosen, prefer an Akad event; if no usable primary event exists, fall back to `weddings.wedding_date`.
- **Consequence:** Changing the primary event recalculates only `template_relative` task dates from their stored offsets and requires explicit confirmation when relative tasks exist. Manual task dates never move.

## D-013 — M0 Supported Application Baseline

- **Status:** Accepted
- **Decision:** M0 uses Laravel 13 with PHP 8.3 as the minimum runtime and the official Laravel React starter generated with Inertia 3, React 19, Tailwind CSS 4, and its shadcn/ui baseline. Backend tests use PHPUnit. Vitest and React Testing Library may be added only for focused frontend behavior tests.
- **Consequence:** Exact patch versions are pinned by the generated Composer and npm lock files. The application does not enable Teams, WorkOS, social login, billing, passkeys, two-factor authentication, or other optional authentication features during M0.

## D-014 — M0 Environment and Runtime Conventions

- **Status:** Accepted
- **Decision:** Local development uses the installed PHP, Node.js, and PostgreSQL toolchain with separate PostgreSQL databases for development and automated tests. Laravel session authentication is authoritative; deployed environments use database-backed sessions, while the local queue remains synchronous until an approved asynchronous workload exists.
- **Consequence:** Real credentials stay in uncommitted environment files. `.env.example` documents PostgreSQL configuration without secrets, test configuration targets a distinct database, and neither database nor future Supabase credentials may be exposed through Vite or Inertia props.

## D-015 — Docker VPS Deployment Baseline

- **Status:** Accepted
- **Decision:** The supported deployment direction is a container-compatible Linux VPS running the single Laravel and Inertia application with PostgreSQL and HTTPS dependencies supplied per environment.
- **Consequence:** M0 records compatible environment assumptions only. Deployment automation, production infrastructure changes, Supabase configuration, and operational release procedures remain deferred to their approved milestones.

## D-016 — M1 Wedding and Membership Retention

- **Status:** Accepted
- **Decision:** Weddings use soft deletion. Membership rows are retained with a nullable `left_at`; leaving or removal deactivates the existing row, and rejoining safely reactivates that row instead of inserting a duplicate `(wedding_id, user_id)` pair.
- **Consequence:** Each active wedding maintains exactly one active owner. Ownership must be transferred before the owner can leave or be removed, and historical membership identity is preserved without adding a separate history table.

## D-017 — M1 Open-Email Invitations

- **Status:** Accepted
- **Decision:** A verified owner may invite an editor or viewer by email even when no account currently uses that address. The recipient must register or log in, verify the matching normalized email, and then accept or decline using a random, hashed, expiring, single-use token.
- **Consequence:** Email verification does not block ordinary authenticated or wedding access. It is enforced only on invitation creation, cancellation, acceptance, and decline in M1; invitation tokens never grant ownership.

## D-018 — M1 Typed Event-Only Template Cloning

- **Status:** Accepted
- **Decision:** M1 uses application-owned templates, sections, and typed template items, but validates and clones only supported initial wedding events. It does not create or store unvalidated M2–M5 operational data.
- **Consequence:** Wedding creation, owner membership, initial event creation, and supported template cloning are atomic and independent. M2 expands this controlled template model only after applying the accepted timeline-anchor behavior in D-012.

## D-019 — M3 Finance Lifecycle and Payment Semantics

- **Status:** Accepted
- **Decision:** Budget items use `draft`, `committed`, and `cancelled`; committing requires a final amount. Payment terms use server-derived `planned`, `partially_paid`, and `paid` states, plus explicit `cancelled`. Only non-cancelled actual amounts on committed, non-cancelled items count toward paid totals.
- **Consequence:** Positive actual amounts require a paid timestamp. Overpayment remains visible as negative outstanding and never rewrites the committed amount. Items with payment or commitment history are cancelled rather than deleted.

## D-020 — M3 Scenario Copy and Application

- **Status:** Accepted
- **Decision:** Copied scenarios create independent child rows with no linkage to original items. Applying a scenario transactionally appends an independent batch of draft budget items, never merges or deletes existing budget data, and repeated application requires explicit confirmation.
- **Consequence:** New weddings receive six independent curated scenario allocations from Rp10m through Rp100m. Application provenance uses immutable name snapshots and a batch UUID rather than a dependency on source scenario items.

## D-021 — M3 Milestone Boundaries and Savings Identity

- **Status:** Accepted
- **Decision:** M3 omits vendor relationships until M4 and payment-proof attachments until M6. Savings contributors use either a same-wedding membership link or a custom label, with a stored display-name snapshot retained if membership ends.
- **Consequence:** M3 introduces no temporary vendor-name or proof-metadata source of truth. Financial audit logs cover monetary and state changes with controlled before/after values.

## D-022 — M4 Unified Typed Checklist Structure

- **Status:** Accepted
- **Decision:** Seserahan and administration share one checklist schema (`checklist_groups`, `checklist_categories`, `checklist_statuses`, `checklist_items`) differentiated by a `type` column with `(wedding_id, type, name)` uniqueness. Statuses are controlled wedding-scoped rows carrying an `is_completed` flag; moving an item to a completed status stamps `completed_at` server-side.
- **Consequence:** One migration family, policy set, controller set, and UI serve both modules; module-specific fields (purchase URL for seserahan, completion tracking for administration) are nullable columns validated per type. Labels of the two modules never leak into each other.

## D-023 — M4 Vendor Contacts as Child Records and Budget Link

- **Status:** Accepted
- **Decision:** Vendor PIC details are repeatable `vendor_contacts` child rows (name, phone, email, role, is_primary) rather than columns on `vendors`. Selected vendors link to budget items through a nullable composite-FK `vendor_id` on `budget_items` added in M4; status and relationships are the source of truth with no duplicate selected-vendor table.
- **Consequence:** Vendor statuses use a fixed application enum (candidate, contacted, negotiating, selected, rejected, done) since the PRD list is not user-customizable. Phone stays text to preserve formatting and leading zeros. No vendor account, public listing, marketplace, portal, or vendor custom field exists.

## D-024 — M4 Guest Sides and Groups as Controlled Rows, Invitation and RSVP as Enums

- **Status:** Accepted
- **Decision:** `guest_sides` and `guest_groups` are wedding-scoped controlled rows (customizable labels with PRD defaults). Invitation status (`not_sent, sent, delivered, failed`) and optional RSVP status (`pending, attending, not_attending, tentative`) are application enums because the PRD does not make them customizable.
- **Consequence:** Guest counts, pax, and invitation counts are non-negative integers aggregated server-side per side and group, consistent with the active filter. Side labels of one wedding never affect another wedding.

## D-025 — Warm Wedding Visual Redesign

- **Status:** Accepted
- **Decision:** The application-wide visual refresh adopts a refined Blush & Rose direction: Fraunces serif display type for headings via Bunny Fonts, softer blush-ivory/dusty-rose/gold palette in `resources/css/app.css`, warm charcoal dark mode, and serif wordmark — a presentation-layer change only.
- **Consequence:** No layout, information-density, business-rule, or component-structure changes. Semantic state colors remain independent of brand color, status keeps text labels, and CLAUDE.md §9 exclusions (no glassmorphism, decorative gradients, or excessive motion) continue to apply.

## D-026 — UX Restructure and Warm Sunset Palette

- **Status:** Accepted
- **Decision:** User feedback drove two changes: (1) the sidebar's flat 10-item list becomes four labeled groups (Utama, Keuangan, Persiapan, Ruang kerja) and all finance pages share one `SectionTabs` underline-tab navigation (Ringkasan/Anggaran/Skenario/Tabungan plus Target/Penabung/Riwayat sub-tabs) instead of per-page button mixes; (2) the D-025 maroon-leaning palette is replaced by "Warm Sunset" (user-selected from three visual-companion mockups): golden-cream surfaces, peach-terracotta primary `#e2795b`, champagne-peach accents, and a warm brown-charcoal dark mode with bright peach `#ec8f6f` accents. Welcome page copy is fully Indonesian.
- **Consequence:** Palette values live only in CSS tokens in `resources/css/app.css`; no component hardcodes brand hexes. `NavMain` now takes `NavGroup[]` and marks the active route via `useCurrentUrl`. Semantic state colors stay independent of brand color and status remains text-labeled. Frontend gates (vp check, tsc, vitest, vite build) pass; no backend, schema, or business-rule change.


## D-027 — M6 Custom Fields as Typed JSONB Values with Module Enum

- **Status:** Accepted
- **Decision:** Custom fields use two wedding-scoped tables: `custom_field_definitions` (module enum restricted to guest, task, checklist_item, budget_item; field_type enum of the ten PRD types; label, generated unique field_key, is_required, options jsonb, sort_order, is_active) and `custom_field_values` (composite-FK definition link, module enum, subject_id validated per-module with wedding-scoped existence rules, single JSONB value column storing `{"v": <typed-value>}`). Type changes are rejected while a definition has values; definitions with values cannot be deleted, only deactivated; deactivation preserves historical values and stops required-validation. Currency values are whole-rupiah integers per D-008; file_reference values must reference a same-wedding attachment.
- **Consequence:** No vendor custom fields exist (D-005) and no generic form builder; required-ness is enforced server-side on subject create/update against active definitions only. The single JSONB shape avoids per-type migrations while typed validation against the definition keeps values trustworthy.

## D-028 — M6 Attachments with Typed Nullable Foreign Keys, Not Polymorphism

- **Status:** Accepted
- **Decision:** One `attachments` table links to each supported subject (budget item, payment term, checklist item, vendor, guest, task) through separate nullable composite-FK `(wedding_id, *_id)` columns with a CHECK enforcing exactly one non-null subject per row, following the M4 `budget_items.vendor_id` pattern. Storage paths embed the immutable wedding UUID and module namespace; the original filename is never the storage key. Metadata is created only after the object is stored, with compensating object deletion on metadata failure, and deletion removes the object first so no active metadata ever points at a missing object. No attachment-operation record is added; reconciliation is a metadata-versus-storage-listing command.
- **Consequence:** Cross-wedding subject references are impossible at the database level, subject deletion detaches attachments without orphaning metadata, and audit/retention remain queryable per module. Adding a new attachable module later is an additive migration.

## D-029 — M6 Storage Adapter: S3-Compatible Supabase Disk with Local Private Fallback

- **Status:** Accepted
- **Decision:** Supabase Storage is mounted as a Laravel `supabase` disk through the official `league/flysystem-aws-s3-v3` adapter (the only new PHP dependency of M6), pointing at the S3-compatible endpoint with a private bucket; development and automated tests use the same `local` private disk so no Supabase connection or service key exists outside the server. Every file access goes through Laravel authorization followed by a short-lived (10-minute) temporary URL; permanent public URLs are never used. Upload routes require email verification in addition to membership and policy checks, and extension (pdf/jpg/jpeg/png/webp), detected MIME type, and 10 MB size limits are enforced server-side.
- **Consequence:** React never receives storage credentials or direct URLs; unverified accounts cannot upload; failed uploads leave no active metadata; and the adapter boundary keeps the storage backend swappable via configuration.

## D-030 — Vintage Analog Visual Direction at the Theme Token Layer

- **Status:** Accepted (palette amended 2026-09-26: oxblood/brass intermediate rejected by the user in favor of the brief's reference palette)
- **Decision:** The presentation layer adopts a Vintage Analog / Retro Film direction with the brief's reference palette: paper cream `#f5e6c8` background, `#fbf3df` cards, muted teal primary (rendered `#3e6667` so button text clears 4.5:1; raw `#4a7b7c` reserved for charts and ambient gradients), warm beige `#d4a574` secondary, dusty pink `#e8b4b8` accent, ink `#171512` foreground, darkroom mode on green-ink `#1e211e` with light teal `#7fb0b1`. Typography: Fraunces editorial display + Instrument Sans UI + IBM Plex Mono metadata. Paper-style shadows (`shadow-sm = 0 2px 0 ink/0.08`, `shadow-md/lg` for overlays) with the old maroon ink rgb replaced by `rgb(23 21 18)` across all components. Editorial radius `0.28rem` with derived `--radius-xl/2xl/3xl` so legacy `rounded-{xl,2xl,3xl}` utilities collapse into the film scale. Ambient texture: a 5%-opacity inline SVG fractal-noise grain overlay (fixed, `pointer-events: none`, disabled under `prefers-reduced-transparency`); the body background is solid paper with no additional light-leak gradients (the user asked for a clean `#f5e6c8` background). Shared primitives (button, input, badge, label, dialog, alert, select, checkbox, card) are redesigned on this system; app shell, sidebar, heading, wedding switcher, auth layouts, welcome, and dashboard carry it through. Group labels in the sidebar become mono-uppercase archival captions and survive icon collapse via `sr-only`.
- **Consequence:** Theme tokens are the single source of truth — every Tailwind radius/shadow utility on existing pages inherits the film tokens without per-file edits, so 95 legacy `rounded-{xl,2xl,3xl}`/`shadow-{sm,md,lg}` usages across 42 files now render on the editorial paper scale. No backend, route, authorization, schema, money-type, or file-storage behavior changes; semantic state colors remain independent of brand color, status keeps text labels, focus-visible outlines, reduced-motion, and 44px touch-target budgets are preserved.

## D-031 — Modal-Based Create and Edit Flows

- **Status:** Accepted (user instruction 2026-09-30; supersedes the "avoid modals for every interaction" note in CLAUDE.md §9 for create/edit forms)
- **Decision:** At the user's explicit request, all "add data" and concise edit flows move from separate pages to in-page modals, starting with a reusable `FormDialog` pattern built on the existing Radix dialog primitive, applied first to Events (then Tasks, then other modules). Old create/edit GET pages are removed and their controller actions become 302 redirects to the module index so legacy URLs stay alive. Modal quality is guarded by acceptance criteria: focus trap, Escape-to-close, scroll lock, retained input on validation errors, loading/disabled submit state, and ARIA semantics. Detail (show) pages, destructive confirmations outside edit, and cross-page navigation remain full pages.
- **Consequence:** Backend routes, verbs, Form Requests, policies, and wedding isolation are unchanged; only presentation UX and the two GET controller actions for create/edit change per module. Events' success feedback is unified to the app convention (`Inertia::flash('toast')` + sonner) instead of the legacy `->with('success')`. Rollout is phased (Events+pattern first, validated by QA and review, before other modules adopt it).

## D-032 — M01 Modal Scope Assumptions (Non-Interactive Decisions)

- **Status:** Accepted (orchestrator assumptions recorded because the user asked to proceed without further questions)
- **Decision:** For M01 (reusable FormDialog + Events): (a) the event delete action with its two-step confirmation moves inside the edit modal (close to its edit context) and stays a row-independent server-validated action; (b) the "Edit acara" button on the event detail page opens the same edit modal since the event data is already in that page's props; (c) the unsaved-changes guard (dirty-state close confirmation) is deferred — not built in M01, noted as a future enhancement; (d) a focused RTL test may cover FormDialog close/submit behavior, with manual accessibility checks as the primary verification.
- **Consequence:** No data-loss protection when closing a dirty modal in M01 (documented risk). Feature tests for event deletion and relative-task recalculation must pass unchanged. These assumptions are visible to the user in the decision log and can be revised at any time.

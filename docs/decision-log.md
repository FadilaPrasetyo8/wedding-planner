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

- **Status:** Pending; required before M2
- **Decision needed:** Choose whether relative template tasks anchor to the akad date or to a user-selected primary wedding event.
- **Consequence:** M0 and M1 may proceed, but M2 date-offset behavior must not be implemented until this is accepted and recorded.

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
- **Consequence:** Wedding creation, owner membership, initial event creation, and supported template cloning are atomic and independent. The timeline-anchor decision in D-012 remains pending for M2.


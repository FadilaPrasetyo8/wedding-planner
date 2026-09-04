# Wedding Planner — Claude Code Instructions

## 1. Purpose

This repository contains a multi-user wedding planning application. It starts as an internal/public early-access product without billing, but its architecture must be ready to grow into a commercial SaaS.

The application is not limited to two accounts. Anyone may register, create a wedding workspace, and optionally invite other registered users. Every wedding has independent planning data, settings, categories, payment terms, files, and custom fields.

Do not reduce the product to a static spreadsheet clone. Preserve the useful structure from the reference workbook while turning repeated columns and configurable values into proper relational data.

## 2. Sources of Truth

Before planning or implementing a task, read the relevant files completely:

1. `CLAUDE.md`
2. `docs/PRD_Wedding_Planner.md`
3. `docs/architecture.md`
4. `docs/database-erd.md`
5. `docs/implementation-plan.md`
6. `docs/decision-log.md`
7. `docs/progress.md`

If one of the supporting documents does not exist yet, do not invent its decisions. Create or request the missing design during the planning phase.

Priority when instructions conflict:

1. The user's latest explicit instruction
2. The approved PRD
3. Approved architecture and decision records
4. This file
5. Existing implementation patterns

Do not silently change an approved product or architecture decision. Record material decisions in `docs/decision-log.md`.

## 3. Product Scope

### V1 includes

- Public registration, login, logout, password reset, and email verification
- User profile management
- Multiple wedding workspaces per user
- Wedding membership and invitation
- Owner, editor, and viewer roles
- Onboarding and template cloning
- Dashboard and wedding countdown
- Events, timeline, tasks, and Gantt/timeline view
- Budget scenarios
- Wedding budget and flexible payment terms
- Saving goals and contributions
- Seserahan checklist
- Wedding administration checklist
- Private wedding-entered vendor contact management
- Guest management
- Event rundown
- Wedding playlist
- Private file attachments
- Configurable categories, statuses, labels, and limited custom fields
- Search, filtering, sorting, pagination, and selected exports

### V1 does not include

- Subscription plans
- Payment gateway integration
- Usage-based billing
- Feature entitlements based on payment
- Wedding Organizer multi-client administration
- White-label support
- A fully generic form builder
- Arbitrary formulas, scripts, or conditional workflow builders
- Native mobile applications
- Vendor registration, authentication, public listings, marketplace, or vendor-facing portal
- Vendor custom fields

Do not implement a non-goal unless the user explicitly changes the approved scope.

## 4. Required Technology Stack

- Backend/application: Laravel stable release
- Frontend: React with TypeScript
- Server-driven navigation: Inertia.js
- Styling: Tailwind CSS and approved reusable UI primitives
- Database: PostgreSQL
- Authentication: Laravel authentication only
- File storage: private Supabase Storage bucket through Laravel
- Testing: Laravel feature/unit tests and frontend tests where appropriate
- End-to-end testing: Playwright after critical flows are stable

This is one Laravel + Inertia application. Do not create a separate REST API and separate frontend repository unless an approved architecture decision requires it.

React must never communicate directly with PostgreSQL or use Supabase service credentials. Supabase is not the authentication provider for this application.

## 5. Core Product Invariants

These rules are mandatory.

### 5.1 Wedding workspace isolation

- A wedding workspace is the primary data boundary.
- Every operational record must either contain `wedding_id` directly or be unambiguously traceable to a wedding parent.
- Every query, aggregate, search, export, mutation, and file operation must be scoped to the active wedding.
- Route model binding is not sufficient authorization.
- Use Laravel Policies or an equivalent approved authorization layer.
- A user without membership must not read, mutate, export, or download another wedding's data.
- Never trust a `wedding_id`, role, price, total, or permission supplied by the browser without server-side validation.

### 5.2 Accounts and memberships

- Registration must support many accounts and must not use a hardcoded allowlist.
- A user may create or join more than one wedding.
- The wedding creator becomes its owner.
- Membership roles are `owner`, `editor`, and `viewer` unless the PRD is amended.
- Owner manages settings, members, invitations, and destructive workspace actions.
- Editor manages operational planning data but cannot perform owner-only actions.
- Viewer has read-only access and cannot export in V1.
- Enforce permissions on the backend even when the UI hides an action.

### 5.3 Flexible payment terms

- Never create fixed database columns named `dp`, `termin_1`, `termin_2`, or similar installments.
- A budget item has zero or more payment-term child records.
- Payment terms can be named, ordered, added, removed, and updated independently.
- One budget item must support at least ten payment terms without a schema change.
- Store planned amount, due date, payment state, actual paid amount, paid date, notes, and proof metadata as approved by the ERD.
- Calculate paid and outstanding amounts from valid payment records; do not trust frontend totals.
- Warn about overpayment; do not silently rewrite the committed final amount.

### 5.4 Customization model

Use a hybrid customization strategy:

- Core domain data uses normal typed columns and relations.
- Configurable references use wedding-scoped tables, such as events, categories, statuses, guest groups, and side labels.
- Repeatable values use one-to-many child tables, such as payment terms, savings contributions, and attachments.
- Truly optional attributes may use controlled custom-field definitions and values.

V1 custom fields are enabled for guests first, followed by tasks, checklist items, and budget items. Do not enable vendor custom fields.

Custom fields must not replace:

- Financial source-of-truth fields
- Ownership and membership fields
- Authorization fields
- Important indexed dates
- Required relational keys

Do not build nested form builders, executable formulas, arbitrary scripts, or conditional workflow engines in V1.

### 5.5 Financial data

- Never store money in floating-point types.
- Use PostgreSQL `BIGINT` whole-rupiah values for money.
- Store raw numeric values, not formatted currency strings.
- Format Indonesian Rupiah only at the presentation boundary.
- Use database transactions for multi-record financial mutations.
- Financial aggregates must be derived from wedding-scoped source records.
- Important financial changes must produce an auditable actor and timestamp record where required by the PRD.

### 5.6 Files and Supabase Storage

- File metadata belongs in PostgreSQL.
- File objects belong in a private Supabase Storage bucket.
- The browser must never receive the Supabase service role key or storage secret.
- Laravel authorizes access before creating a short-lived signed URL.
- Validate extension, MIME type, size, and filename on the backend.
- V1 accepts PDF, JPG/JPEG, PNG, and WebP up to 10 MB per file.
- Do not use the original filename as the storage key.
- Storage paths must include an immutable wedding identifier and module namespace.
- File deletion must handle database and storage failure consistently.
- Documents, contracts, invoices, and payment proofs must never use permanent public URLs.

## 6. High-Level Domain Model

Use the approved ERD as the final reference. Expected domain areas include:

- Identity: users, weddings, wedding_members, wedding_invitations
- Setup: templates and template items
- Planning: wedding_events, tasks, task_categories, task_statuses
- Finance: budget_scenarios, scenario_items, budget_items, payment_terms
- Savings: saving_goals, saving_contributors, saving_contributions
- Checklists: seserahan/checklist items, groups, categories, and statuses
- Private vendor contacts: vendor categories, vendors, contacts, and related attachments; no vendor accounts or public listings
- Guests: guest sides, guest groups, guests, invitation and RSVP states
- Event day: rundown_items and playlist_items
- Files: attachments
- Customization: custom_field_definitions and custom_field_values
- Audit: activity_logs

Do not create duplicate source-of-truth tables for concepts such as selected vendors. Prefer a status plus relationship when that accurately represents the domain.

## 7. Backend Engineering Rules

- Keep controllers thin.
- Use Form Requests for server-side validation.
- Use Policies for authorization.
- Use Actions or Services for multi-step business operations.
- Use database transactions for template cloning and other atomic multi-record operations.
- Use Eloquent relationships, local scopes, eager loading, and pagination intentionally.
- Prevent obvious N+1 query patterns.
- Add foreign keys, indexes, and unique constraints that include `wedding_id` where uniqueness is wedding-specific.
- Use factories and seeders for realistic development and test data.
- Prefer enums or controlled status records according to the approved customization design.
- Use soft deletion only where recovery or audit value justifies it.
- Never place business-critical authorization or calculations only in React.
- Do not expose stack traces, secrets, or internal storage paths to users.

## 8. Inertia and React Rules

- Use TypeScript for all new React code.
- Do not use `any` to bypass modeling unless a narrow exception is documented.
- Keep page props explicit and serializable.
- Keep server-owned data on the server and pass only the data needed by the page.
- Prefer Inertia form patterns for application mutations.
- Do not create client-side API abstractions without a real need.
- Extract reusable components after a meaningful repeated pattern exists.
- Keep domain-specific forms understandable; do not force every form through one generic schema renderer.
- Use URL query parameters for meaningful filters, sorting, search, and pagination state.
- Keep UI totals consistent with authoritative server calculations.
- Provide loading, empty, error, success, disabled, and destructive-confirmation states.
- Preserve keyboard navigation and visible focus states.
- Status must not be communicated by color alone.

## 9. UI and UX Direction

The interface should feel like an elegant and trustworthy wedding planning workspace, not a generic admin template.

Desired qualities:

- Warm, modern, calm, and premium
- Clear hierarchy and readable data tables
- Practical for long planning sessions
- Consistent spacing, typography, and interaction patterns
- Subtle motion that explains state changes
- Desktop-first for dense planning views, while important CRUD flows remain usable on mobile

Avoid:

- Generic AI-generated dashboard styling
- Excessive gradients, glassmorphism, and decorative blur
- Neon or cyberpunk styling
- Unnecessary animations
- Huge cards that reduce information density
- Using modals for every interaction
- Hiding important financial detail behind visual decoration

Before expanding to every screen, establish the design direction using representative screens: authentication, app shell, dashboard, timeline, budget/payment terms, and guest list.

## 10. Skill Routing

Do not invoke every skill for every task. Use the smallest relevant set.

### Superpowers

Use for:

- Requirement analysis
- Implementation planning
- Breaking milestones into small tasks
- Test-driven feature implementation
- Systematic debugging
- Verification before claiming completion
- Code review and refactoring plans

### UI UX Pro Max

Use for:

- Information architecture
- User flows
- Design-system decisions
- Page hierarchy
- Form and table usability
- Responsive behavior
- Accessibility review
- UX evaluation of complex flows

Do not use it to override approved business rules or database constraints.

### Frontend Design

Use for:

- Implementing the approved visual direction
- React component composition
- Tailwind styling
- Responsive layout
- Empty, loading, and error states
- Purposeful motion and micro-interactions

Do not use it as a substitute for backend authorization, validation, or domain modeling.

### Laravel Boost or official Laravel documentation tools

Use when available for:

- Version-compatible Laravel guidance
- Framework conventions
- Package and API documentation
- Inspecting application routes, configuration, and models

Prefer official or project-compatible documentation. Do not upgrade framework versions or install packages merely because a newer example exists.

### Context7 or equivalent documentation retrieval

Use only when current official documentation is needed for Laravel, Inertia, React, Tailwind, PostgreSQL, Supabase Storage, or Playwright. Prefer primary documentation over blog posts.

### Playwright

Use after the relevant flow works at feature-test level. Prioritize critical end-to-end flows rather than creating brittle coverage for every visual detail.

All skill output remains subordinate to the PRD, approved architecture, and this repository's existing conventions.

## 11. Implementation Workflow

For each task:

1. Read the relevant source-of-truth documents.
2. Inspect the existing implementation before proposing changes.
3. State assumptions and identify unresolved decisions.
4. Create a scoped plan for the requested milestone or feature.
5. Implement the smallest coherent vertical slice.
6. Add or update tests with the implementation.
7. Run the relevant verification commands.
8. Review the diff for unrelated changes, security issues, and accidental scope expansion.
9. Update `docs/progress.md` when a milestone or meaningful feature is completed.
10. Update `docs/decision-log.md` when a material decision is approved.

Do not mark work as complete only because code was generated.

## 12. Milestone Discipline

Implement one approved milestone at a time:

- M0 — Foundation
- M1 — Workspace and membership
- M2 — Dashboard and planning
- M3 — Finance
- M4 — Logistics
- M5 — Event day
- M6 — Flexibility and files
- M7 — Stabilization and release

Do not begin the next milestone until the current milestone's exit criteria pass or the user explicitly reprioritizes the work.

Within a milestone, prefer vertical slices that include database, backend, UI, and tests. Do not implement all frontend screens followed by all backend code.

## 13. Testing Requirements

Discover the repository's actual scripts before running commands. Typical quality gates include:

```bash
php artisan migrate:fresh --seed
php artisan test
npm run lint
npm run typecheck
npm run build
```

Run frontend unit tests and Playwright only when they are configured and relevant.

Required high-risk test coverage includes:

- Registration and authentication
- Wedding creation and membership
- Owner/editor/viewer authorization
- User A cannot access Wedding B without membership
- Dashboard aggregates include only the active wedding
- Template cloning creates independent records
- A budget item supports at least ten payment terms
- Paid and outstanding calculations are correct
- Financial mutations are validated and atomic where needed
- Unauthorized users cannot obtain signed file URLs
- File upload failures do not leave active false metadata
- Search, filters, pagination, and exports remain wedding-scoped
- Custom fields from one wedding do not appear in another wedding

Never weaken or delete a failing test merely to make the suite green unless the test is proven obsolete and the behavioral change is approved.

## 14. Definition of Done

A feature is done only when:

- It satisfies the relevant PRD requirement and acceptance criteria.
- Database changes include constraints and rollback behavior.
- Backend authorization and validation are implemented.
- The UI includes relevant loading, empty, validation, error, and success states.
- Automated tests cover the main path and important failure paths.
- Wedding isolation has been explicitly verified.
- Relevant quality gates pass.
- No secret is exposed to the browser or committed to the repository.
- Documentation and progress tracking are updated when needed.
- The final summary lists changed files, tests run, results, and any remaining risks.

## 15. Safety and Repository Hygiene

- Preserve unrelated user changes.
- Do not run destructive Git or filesystem commands without explicit approval.
- Do not reset, discard, or overwrite work merely to simplify implementation.
- Do not commit, push, deploy, or modify production infrastructure unless explicitly requested.
- Never use production credentials for development or tests.
- Never commit `.env`, secrets, service keys, database dumps, or private wedding documents.
- Ask before adding a major dependency, changing the stack, or performing an irreversible migration.
- Prefer small, reviewable changes over large speculative rewrites.

## 16. Completion Report Format

When finishing a task, report:

1. Outcome
2. Main files changed
3. Tests and verification run
4. Remaining risks or decisions
5. Exact next recommended milestone

Do not claim tests passed if they were not run. Clearly distinguish implementation from recommendation or unverified assumptions.

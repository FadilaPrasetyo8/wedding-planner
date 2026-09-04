# Wedding Planner Database ERD

## 1. Purpose and Conventions

- **Intended entities:** All V1 entities documented in the sections below.
- **Responsibility:** Define the planned PostgreSQL relational model in which every operational entity is directly wedding-scoped, money uses whole-rupiah integers, and configurable or repeatable data remains relational rather than becoming fixed columns.
- **Important unresolved decision:** Exact PostgreSQL and Laravel-compatible identifier, enum, soft-deletion, and timestamp conventions will be confirmed during M0.

## 2. Identity and Authentication

- **Intended entities:** `users` and Laravel-compatible session, password-reset, and email-verification support records.
- **Responsibility:** Store application identities and Laravel authentication state without using Supabase Auth.
- **Accepted M0 convention:** Use the official Laravel 13 React starter with Laravel session authentication. Development and automated tests use separate PostgreSQL databases, and deployed environments use database-backed sessions.

## 3. Weddings, Memberships, and Invitations

- **Intended entities:** `weddings`, `wedding_members`, and `wedding_invitations`.
- **Responsibility:** Establish each wedding as the primary data boundary, allow users to join multiple weddings, and enforce exactly one owner per active wedding with editor and viewer memberships.
- **Important unresolved decision:** The detailed soft-deletion and retention policy for weddings and former memberships requires approval before migrations are implemented.

## 4. Templates and Onboarding

- **Intended entities:** `templates`, `template_sections`, and `template_items`.
- **Responsibility:** Represent reusable onboarding presets that are cloned atomically into independent wedding-owned operational records.
- **Important unresolved decision:** The final typed payload strategy for heterogeneous template items will be decided alongside the first template-cloning vertical slice.

## 5. Events, Tasks, and Timeline

- **Intended entities:** `wedding_events`, `task_categories`, `task_statuses`, and `tasks`.
- **Responsibility:** Store wedding-scoped events and configurable task planning data used by list, dashboard, and Gantt views.
- **Important unresolved decision:** The approved representation for task priority and status behavioral flags will be finalized before the planning migrations.

## 6. Budget Scenarios, Budget Items, and Payment Terms

- **Intended entities:** `budget_scenarios`, `scenario_items`, `budget_categories`, `budget_items`, and `payment_terms`.
- **Responsibility:** Separate exploratory scenarios from committed wedding budgets and model every installment as an ordered child `payment_terms` record rather than fixed DP or termin columns.
- **Important unresolved decision:** The precise controlled states for budget items and valid paid payment terms will be approved before financial calculations are implemented.

## 7. Savings

- **Intended entities:** `saving_goals`, `saving_contributors`, and `saving_contributions`.
- **Responsibility:** Track wedding-scoped savings targets and repeatable contributions independently from vendor payments and budget expenditure.
- **Important unresolved decision:** The model for linking a contributor optionally to an active wedding membership while preserving custom contributor labels requires finalization.

## 8. Seserahan and Administration Checklists

- **Intended entities:** `checklist_groups`, `checklist_categories`, `checklist_statuses`, and `checklist_items`.
- **Responsibility:** Support wedding-scoped seserahan and administration workflows through typed checklist records, configurable references, costs, deadlines, and completion state.
- **Important unresolved decision:** Whether both checklist modules share one typed structure or use specialized subtype tables will be decided before M4 migrations.

## 9. Private Vendor Contacts

- **Intended entities:** `vendor_categories`, `vendors`, and `vendor_contacts`.
- **Responsibility:** Store private wedding-entered vendor candidates, contacts, internal status, and relationships to budget items without vendor accounts, public listings, marketplace behavior, or vendor custom fields.
- **Important unresolved decision:** Whether simple PIC details remain on `vendors` or use repeatable `vendor_contacts` records will be settled before the vendor vertical slice.

## 10. Guests

- **Intended entities:** `guest_sides`, `guest_groups`, `guest_invitation_statuses`, `guest_rsvp_statuses`, and `guests`.
- **Responsibility:** Store wedding-scoped guest identities, configurable grouping labels, invitation counts, pax counts, contact details, and invitation or RSVP state.
- **Important unresolved decision:** The final division between controlled status tables and application enums will be decided before guest migrations.

## 11. Rundown and Playlist

- **Intended entities:** `rundown_items` and `playlist_items`.
- **Responsibility:** Store ordered, wedding-scoped event-day activities and song selections linked to the appropriate wedding event.
- **Important unresolved decision:** The ordering-key strategy for reliable concurrent reordering requires confirmation before implementation.

## 12. Attachments

- **Intended entities:** `attachments` and, only if reconciliation requires it, an attachment-operation record.
- **Responsibility:** Persist wedding-scoped private-file metadata and lifecycle state while file objects remain in private Supabase Storage behind Laravel authorization.
- **Important unresolved decision:** The final attachment association strategy and lifecycle-state detail will be decided before the file-storage migration.

## 13. Custom Fields

- **Intended entities:** `custom_field_definitions` and `custom_field_values`.
- **Responsibility:** Provide controlled wedding-scoped optional fields only for guests, tasks, checklist items, and budget items without replacing core financial, relational, date, ownership, or authorization data.
- **Important unresolved decision:** The validated storage shape for each supported custom-field type and safe type-change behavior requires detailed design before M6.

## 14. Activity Logs

- **Intended entities:** `activity_logs`.
- **Responsibility:** Record wedding-scoped actor, action, subject, timestamp, and controlled change evidence for sensitive financial, membership, ownership, and file operations.
- **Important unresolved decision:** The retention period and exact audited action set will be approved before audit implementation.

## 15. Constraints and Indexes

- **Intended entities:** Constraints and indexes across every entity above rather than a separate domain table.
- **Responsibility:** Enforce referential integrity, exactly one owner, same-wedding relationships, wedding-specific uniqueness, valid values, and efficient wedding-first query patterns.
- **Integrity rules:**
  - Every operational, configurable-reference, repeatable-child, attachment, custom-field-value, and activity-log record has a direct `wedding_id`; master template and Laravel authentication records are the explicit non-wedding-scoped exceptions.
  - Tenant-sensitive parent-child references use composite foreign keys containing both the parent identifier and `wedding_id`, backed by matching parent uniqueness, so relationships cannot cross weddings.
  - `wedding_members` uniquely constrains `(wedding_id, user_id)` and uses a PostgreSQL partial unique index for at most one `owner` per active wedding; creation and ownership transfer use a transaction with row locks to guarantee at least one owner.
  - Ownership transfer atomically promotes an existing same-wedding active member and demotes the previous owner; deleting or removing the current owner is restricted until transfer completes.
  - `payment_terms` are ordered child records of `budget_items`, carry the same `wedding_id`, and have no fixed DP or numbered-termin columns.
  - All monetary values use whole-rupiah `BIGINT` columns with applicable non-negative checks; floating-point money columns are prohibited.
  - Wedding-specific natural keys and ordering keys include `wedding_id`; indexes for operational access begin with `wedding_id` and add validated filter, sort, parent, or status columns.
  - Deletion cascades only to true dependent rows, restricts deletion while protected references remain, and uses controlled soft deletion where recovery or audit requires it; attachment-object cleanup is coordinated separately from relational deletion.
  - Attachment and custom-field subject links must use tenant-enforced composite references or typed linking tables, not an unenforced polymorphic identifier capable of crossing weddings.
- **Important unresolved decision:** Exact per-table cascade, restrict, and soft-deletion choices will be finalized with the complete column and foreign-key design before migrations.

## 16. Relationship Summary

- **Intended entities:** All parent-child, membership, configurable-reference, attachment, custom-field, and audit relationships defined by this ERD.
- **Responsibility:** Make each operational record directly attributable to one wedding and prevent cross-wedding relationships while preserving independent template clones and repeatable child data.
- **Relationship rule:** Every operational relationship is either directly anchored to `weddings` or protected by a composite parent-and-`wedding_id` foreign key; application authorization supplements but never replaces this database integrity.
- **Important unresolved decision:** A complete cardinality diagram will be added only after entity columns and foreign-key strategies are approved.

## 17. Deferred Entities

- **Intended entities:** Future plans, subscriptions or purchases, transactions, entitlements, organizations, vendor accounts, public vendor profiles, marketplace records, public invitations, guest-facing RSVP, QR check-in, and white-label configuration.
- **Responsibility:** Keep post-V1 commercial, organizer, vendor-facing, and public guest functionality outside the initial schema while preserving the existing user, wedding, and membership foundation.
- **Important unresolved decision:** Deferred entities receive no schema design until their product scope and architecture are separately approved.

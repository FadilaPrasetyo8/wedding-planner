# Wedding Planner

Wedding Planner is a Laravel 13, Inertia 3, React 19, TypeScript, Tailwind CSS 4, and PostgreSQL application. Product scope and engineering rules are defined in `CLAUDE.md` and the documents under `docs/`.

## Local prerequisites

- PHP 8.3 or newer with the PostgreSQL PDO extension
- Composer
- Node.js and npm
- PostgreSQL

## Environment setup

1. Copy `.env.example` to `.env` without committing the resulting file.
2. Create separate PostgreSQL databases for development and automated tests:
    - `wedding_planner`
    - `wedding_planner_test`
3. Set local PostgreSQL credentials only in `.env`. The committed example contains no password.
4. Generate an application key with `php artisan key:generate` when needed.
5. Install dependencies with `composer install` and `npm install`.
6. Initialize the development database with `php artisan migrate:fresh --seed`.

The local queue uses the synchronous driver. Automated tests use `wedding_planner_test` through `phpunit.xml`; never point that file at the development database. Deployed environments use database-backed sessions, while real credentials remain outside source control.

## Quality gates

Run these commands from the repository root:

```bash
php artisan migrate:fresh --seed
composer test
npm run types:check
npm run check
npm run build
```

`composer test` clears cached configuration, checks PHP formatting and static analysis, then runs PHPUnit against the dedicated PostgreSQL test database.

Do not add database credentials, Supabase secrets, or other server-only values to variables prefixed with `VITE_` or to Inertia page props.

## Operations and release checks

- Liveness: `GET /up`
- Dependency readiness: `GET /ready`
- Excel-compatible exports are safe UTF-8 BOM CSV downloads; no spreadsheet dependency is required.
- Backup: `scripts/backup-database.sh ./backups`
- Restore: `scripts/restore-database.sh DUMP_FILE --confirm-database=NAME`
- Smoke checks: `scripts/smoke-test.sh BASE_URL`
- Full repository release verification: `scripts/release.sh`

See [`docs/operations/environment.md`](docs/operations/environment.md), [`backup-restore.md`](docs/operations/backup-restore.md), [`release-runbook.md`](docs/operations/release-runbook.md), [`monitoring-recovery.md`](docs/operations/monitoring-recovery.md), [`uat-checklist.md`](docs/operations/uat-checklist.md), and [`release-checklist.md`](docs/operations/release-checklist.md).

Manual UAT, controlled staging restore, monitoring, and deployment rehearsal remain external release-gate actions.

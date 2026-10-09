# Production environment

Use `deploy/env.production.example` as a server-side template. Copy values into the deployment secret manager; do not commit a populated environment file.

Set `APP_DEBUG=false`, use a generated `APP_KEY`, enforce secure HTTP-only session cookies, and provide PostgreSQL and private Supabase Storage credentials only to Laravel. Storage credentials must never use a `VITE_` prefix or reach the browser.

Run migrations with the release procedure only after reviewing the database backup and rollback plan.

## Email verification

Email verification is enabled by default and must remain enabled in production. The
`REQUIRE_EMAIL_VERIFICATION` environment variable controls only the verification
middleware gate used by invitation and private attachment mutations; it does not
remove authentication, wedding membership checks, role policies, private storage,
or signed download URLs.

For local development or automated tests that do not send verification mail, set
`REQUIRE_EMAIL_VERIFICATION=false`. Never copy that value into a production
secret manager. Restore `true` before a release and verify the effective value with
application configuration review.

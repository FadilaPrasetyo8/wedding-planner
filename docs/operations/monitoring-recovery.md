# Monitoring and recovery

Monitor HTTP liveness at `/up` and dependency readiness at `/ready`. Readiness exposes only a generic status; request identifiers are returned in `X-Request-ID` and included in scoped logs.

On an alert, inspect the correlated server logs without exposing passwords, tokens, signed URLs, storage credentials, or private wedding data. If readiness is unavailable, check PostgreSQL connectivity and attachment storage configuration, then follow the backup/restore and release runbooks. Record the incident, impact, remediation, and verification.

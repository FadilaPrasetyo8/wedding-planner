#!/usr/bin/env bash
set -Eeuo pipefail

: "${PGDATABASE:?PGDATABASE is required}"
php artisan migrate:status --no-ansi >/dev/null
psql --no-psqlrc --tuples-only --command='select count(*) from users' "$PGDATABASE" | tr -d '[:space:]' | grep -Eq '^[0-9]+$'
printf 'Restored database verification passed.\n'

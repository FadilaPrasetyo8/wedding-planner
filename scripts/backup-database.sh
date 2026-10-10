#!/usr/bin/env bash
set -Eeuo pipefail

backup_dir="${1:?Usage: $0 BACKUP_DIR}"
mkdir -p "$backup_dir"
umask 077
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
archive="$backup_dir/wedding-planner-${timestamp}.dump"

: "${PGHOST:?PGHOST is required}"
: "${PGDATABASE:?PGDATABASE is required}"
: "${PGUSER:?PGUSER is required}"
: "${PGPASSWORD:?PGPASSWORD is required}"

pg_dump --format=custom --file="$archive" "$PGDATABASE"
sha256sum "$archive" > "${archive}.sha256"
printf 'Backup written to %s\n' "$archive"

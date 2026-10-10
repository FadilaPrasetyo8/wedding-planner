#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    printf 'Usage: %s DUMP_FILE --confirm-database=NAME [--acknowledge-production]\n' "$0" >&2
    exit 2
}

dump_file="${1:-}"
[[ -n "$dump_file" ]] || usage
shift
confirm_database=""
aknowledge_production=false
for argument in "$@"; do
    case "$argument" in
        --confirm-database=*) confirm_database="${argument#*=}" ;;
        --acknowledge-production) acknowledge_production=true ;;
        *) usage ;;
    esac
done
[[ -n "$confirm_database" ]] || usage
[[ -f "$dump_file" && -f "${dump_file}.sha256" ]] || { printf 'Dump or checksum is missing.\n' >&2; exit 1; }
: "${PGDATABASE:?PGDATABASE is required}"
[[ "$confirm_database" == "$PGDATABASE" ]] || { printf 'Database confirmation does not match PGDATABASE.\n' >&2; exit 1; }
if [[ "${APP_ENV:-local}" == "production" && "$acknowledge_production" != true ]]; then
    printf 'Production restore requires --acknowledge-production.\n' >&2
    exit 1
fi
sha256sum --check "${dump_file}.sha256"
pg_restore --clean --if-exists --no-owner --dbname="$PGDATABASE" "$dump_file"
printf 'Restore completed for the confirmed database.\n'

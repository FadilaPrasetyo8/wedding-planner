#!/usr/bin/env bash
set -Eeuo pipefail

base_url="${1:?Usage: $0 BASE_URL}"
check_status() {
    local path="$1"
    local expected="$2"
    local actual
    actual="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' --location --max-redirs 0 "${base_url%/}${path}")"
    [[ "$actual" == "$expected" ]] || { printf '%s returned %s, expected %s\n' "$path" "$actual" "$expected" >&2; exit 1; }
}
check_status /up 200
check_status /ready 200
check_status / 200
check_status /dashboard 302
printf 'Smoke checks passed.\n'

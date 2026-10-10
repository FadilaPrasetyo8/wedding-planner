#!/usr/bin/env bash
set -Eeuo pipefail

composer install --no-interaction --prefer-dist --no-progress
npm ci
php artisan config:clear
composer lint:check
composer types:check
php artisan test
npm run check
npm run types:check
npm run test
npm run build
printf 'Release verification completed; no deployment was performed.\n'

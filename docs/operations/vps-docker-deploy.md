# VPS Docker deployment

Baseline: D-015 (container-compatible Linux VPS, one Laravel and Inertia application, PostgreSQL and HTTPS supplied per environment).

This setup runs the app container and a PostgreSQL 16 container on one VPS. The app listens only on `127.0.0.1:8080`; put a reverse proxy with HTTPS (Caddy or Nginx + certbot) in front of it.

## 1. Prepare the server

Install Docker Engine and the Compose plugin, then create the secret directory outside the repository:

```bash
sudo mkdir -p /etc/wedding-planner
sudo chmod 700 /etc/wedding-planner
```

## 2. Create the database password

```bash
openssl rand -base64 32 | sudo tee /etc/wedding-planner/db_password.txt >/dev/null
sudo chmod 600 /etc/wedding-planner/db_password.txt
```

## 3. Create the application environment

Create `/etc/wedding-planner/app.env` from `deploy/env.production.example`. Set these values to match the database container:

```
DB_CONNECTION=pgsql
DB_HOST=database
DB_PORT=5432
DB_DATABASE=wedding_planner
DB_USERNAME=wedding_planner
DB_PASSWORD=<same value as /etc/wedding-planner/db_password.txt>
```

Generate `APP_KEY` once, locally or on the server, and paste it into `app.env`:

```bash
php artisan key:generate --show
```

Keep `APP_DEBUG=false`, `REQUIRE_EMAIL_VERIFICATION=true`, and `APP_URL` set to the public HTTPS URL. Supabase credentials stay server-side only. Never copy `.env` into the image.

```bash
sudo chmod 600 /etc/wedding-planner/app.env
```

## 4. Build and start

From the repository directory on the VPS:

```bash
docker compose build
docker compose up -d database
docker compose up -d app
```

The first start does not run migrations. Review the database backup plan (`docs/operations/backup-restore.md`), then run the release procedure:

```bash
RUN_MIGRATIONS=true docker compose up -d --force-recreate app
```

Set `RUN_MIGRATIONS` back to `false` (or unset it) for later starts unless a release requires migrations.

## 5. Verify

```bash
docker compose ps
curl -fsS http://127.0.0.1:8080/up
curl -fsS http://127.0.0.1:8080/ready
```

`scripts/smoke-test.sh` can be pointed at the public HTTPS URL after the reverse proxy is in place.

## 6. Update

```bash
git pull
docker compose build app
RUN_MIGRATIONS=true docker compose up -d app
```

## Backups

Database backups use `scripts/backup-database.sh` against the `database` container. Verify restores with `scripts/restore-database.sh` and `scripts/verify-restored-database.sh` before relying on them.

## Notes

- Uploaded files go to Supabase Storage (private bucket) through Laravel, not to the container filesystem.
- Sessions use the database driver, so restarting the app container does not log users out.
- Queue work is synchronous (`QUEUE_CONNECTION=sync`); no worker container is needed until an approved async workload exists.
- The reverse proxy must forward `X-Forwarded-Proto` so secure cookies and generated URLs use HTTPS.

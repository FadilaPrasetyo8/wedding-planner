# Backup and restore

Set standard PostgreSQL environment variables and run:

```sh
scripts/backup-database.sh ./backups
```

The script writes a private custom-format archive and a SHA-256 sidecar. Restore only after confirming the exact target database:

```sh
scripts/restore-database.sh ./backups/example.dump --confirm-database=wedding_planner
```

Production restores additionally require `--acknowledge-production`. Test restores against a disposable database before any release activity. Never print or commit `PGPASSWORD`.

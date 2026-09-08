# ReadyRide — Update Guide

How to move an **existing** ReadyRide install to a newer version **without losing data**.
New installs don't need this — they just run the web installer.

---

## What is preserved vs replaced

| Preserved (your data) | Replaced (new code) |
|---|---|
| `.env` (all your config) | `app/`, `routes/`, `config/`, `resources/` |
| `storage/` — uploads, `firebase.json`, install lock, logs | `vendor/` (if shipped), `public/build` (assets) |
| `public/storage` (symlink) & user uploads | `database/migrations` (new migrations only) |

Because updates are **migration-based**, your existing rows stay intact — new versions
only *add* tables/columns, never wipe data.

---

## Option A — Automatic (recommended)

On your server, in your live project folder:

```bash
cd /var/www/your-project

# 1. download & extract the new release next to it
unzip ~/readyride-vX.Y.Z-*.zip -d ~/readyride-new

# 2. run the updater, pointing at the extracted folder
./update.sh ~/readyride-new/readyride
```

The script will:
1. back up your MySQL database to `storage/backups/` (best effort),
2. put the site in maintenance mode,
3. copy new code over yours (keeping `.env` + `storage/`),
4. run `php artisan migrate --force`,
5. rebuild caches, restart the queue, and bring the site back up.

After it finishes, restart your workers and FPM:
```bash
sudo supervisorctl restart all
sudo systemctl reload php8.3-fpm   # your PHP version
```

---

## Option B — Manual

1. **Back up first** — database + the whole project folder.
2. Upload the new files over the old ones, but **do NOT overwrite**:
   - `.env`
   - `storage/` (especially `storage/app/…` uploads, `storage/app/firebase/firebase.json`, `storage/installed`)
   - `public/storage`
3. Then run:
   ```bash
   php artisan down
   composer install --no-dev --optimize-autoloader   # only if vendor isn't shipped
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize:clear
   php artisan config:cache && php artisan view:cache
   php artisan queue:restart
   php artisan up
   sudo supervisorctl restart all
   ```
4. Compare your `.env` with the new `.env.example` and add any **new keys**:
   ```bash
   diff <(sort .env) <(sort .env.example) | grep '>'
   ```

---

## Rollback

If something goes wrong:
```bash
# restore the code folder from your backup, then:
mysql -u USER -p DB_NAME < storage/backups/db-<old-version>-<timestamp>.sql
php artisan up
```

---

## Notes for new releases (seller checklist)

- Bump `VERSION` before packaging.
- Keep every migration **additive & idempotent** (guard with `Schema::hasColumn/hasTable`) so it's safe on already-populated databases.
- Never ship `.env`, `storage/app/firebase/firebase.json`, or `storage/installed` — `build-release.sh` strips them automatically.

#!/usr/bin/env bash
#
# ReadyRide — safe in-place updater for EXISTING installs
# -----------------------------------------------------------------------------
# For buyers who already installed an older version and want the new one WITHOUT
# losing their data. It copies the new release OVER the current install while
# PRESERVING everything that must survive an upgrade:
#     .env  •  storage/ (uploads, firebase.json, install lock, logs)  •  public/storage
# then runs the new migrations and clears caches.
#
# Run this FROM your live project root:
#     cd /var/www/your-project
#     ./update.sh /path/to/new-release-folder      # extracted new version
#     ./update.sh /path/to/new-release-folder --yes # skip the confirm prompt
#
set -euo pipefail

SRC="${1:-}"
ASSUME_YES=0
[[ "${2:-}" == "--yes" ]] && ASSUME_YES=1

APP_ROOT="$(pwd)"
PHP="$(command -v php || true)"

say()  { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
die()  { printf '\033[1;31mERROR: %s\033[0m\n' "$1" >&2; exit 1; }

# ---- 0. sanity checks ------------------------------------------------------
[[ -n "$SRC" ]]            || die "Usage: ./update.sh /path/to/new-release-folder [--yes]"
[[ -d "$SRC" ]]           || die "New release folder not found: $SRC"
[[ -f "$SRC/artisan" ]]   || die "$SRC doesn't look like a ReadyRide release (no artisan)."
[[ -f "$APP_ROOT/artisan" ]] || die "Run this from your live project root (no artisan here)."
[[ -f "$APP_ROOT/.env" ]] || die "No .env in the current install — is this the right folder?"
[[ -n "$PHP" ]]           || die "php not found in PATH."

OLD_VER="$(cat "$APP_ROOT/VERSION" 2>/dev/null || echo '?')"
NEW_VER="$(cat "$SRC/VERSION" 2>/dev/null || echo '?')"
echo "Current install : $APP_ROOT  (v$OLD_VER)"
echo "New release      : $SRC  (v$NEW_VER)"

if [[ "$ASSUME_YES" != "1" ]]; then
  read -r -p $'\nThis will overwrite code files (data/.env preserved) and run migrations. Continue? [y/N] ' ans
  [[ "$ans" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 0; }
fi

# ---- 1. best-effort database backup ---------------------------------------
say "Backing up database (best effort)…"
mkdir -p "$APP_ROOT/storage/backups"
envval() { grep -E "^$1=" "$APP_ROOT/.env" | head -1 | cut -d= -f2- | tr -d '"'"'"'\r'; }
DB_CONN="$(envval DB_CONNECTION)"; DB_DB="$(envval DB_DATABASE)"
DB_USER="$(envval DB_USERNAME)"; DB_PASS="$(envval DB_PASSWORD)"
DB_HOST="$(envval DB_HOST)"; DB_PORT="$(envval DB_PORT)"
BACKUP="$APP_ROOT/storage/backups/db-${OLD_VER}-$(date +%Y%m%d-%H%M%S).sql"
if [[ "$DB_CONN" == "mysql" ]] && command -v mysqldump >/dev/null 2>&1 && [[ -n "$DB_DB" ]]; then
  if MYSQL_PWD="$DB_PASS" mysqldump -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" \
       -u "$DB_USER" --single-transaction --quick "$DB_DB" > "$BACKUP" 2>/dev/null; then
    echo "  saved: $BACKUP"
  else
    echo "  ⚠️  mysqldump failed — PLEASE back up your database manually before continuing."
    [[ "$ASSUME_YES" == "1" ]] || { read -r -p "  Continue anyway? [y/N] " a; [[ "$a" =~ ^[Yy]$ ]] || exit 1; }
  fi
else
  echo "  ⚠️  Skipped (not mysql / mysqldump unavailable). Back up your DB manually!"
fi

# ---- 2. maintenance mode ---------------------------------------------------
say "Enabling maintenance mode…"
"$PHP" artisan down --render="errors::503" >/dev/null 2>&1 || "$PHP" artisan down || true

# ---- 3. copy new code, preserving data/config ------------------------------
say "Copying new code (preserving .env, storage/, public/storage)…"
rsync -a --delete \
  --exclude '.env' \
  --exclude '.env.backup' \
  --exclude 'storage/' \
  --exclude 'public/storage' \
  --exclude 'public/uploads' \
  --exclude '.git' \
  "$SRC"/ "$APP_ROOT"/
# Note: --delete removes files no longer in the release (clean upgrade), but the
# excludes above keep all your data, uploads and configuration untouched.

cd "$APP_ROOT"

# ---- 4. dependencies (only if vendor not shipped) --------------------------
if [[ ! -d vendor ]] && command -v composer >/dev/null 2>&1; then
  say "vendor missing → composer install (production)…"
  composer install --no-dev --optimize-autoloader --no-interaction
fi

# ---- 5. migrate + finalize -------------------------------------------------
say "Running migrations…"
"$PHP" artisan migrate --force

say "Refreshing storage link & caches…"
"$PHP" artisan storage:link 2>/dev/null || true
"$PHP" artisan optimize:clear
# Rebuild caches for production speed (safe: config/routes/views only).
"$PHP" artisan config:cache
"$PHP" artisan route:cache  2>/dev/null || true
"$PHP" artisan view:cache

say "Restarting queue workers…"
"$PHP" artisan queue:restart

# ---- 6. done ---------------------------------------------------------------
say "Bringing the site back up…"
"$PHP" artisan up

cat <<EOF

✅ Update complete:  v$OLD_VER  →  v$NEW_VER

Next (do these yourself if you use them):
  • Restart Supervisor workers so they load new code/config:
        sudo supervisorctl restart all
  • If you use PHP-FPM/OPcache, reload it:
        sudo systemctl reload php8.3-fpm
  • Compare .env with the new .env.example for any NEW keys:
        diff <(sort .env) <(sort .env.example) | grep '>'

DB backup (if taken): storage/backups/
EOF

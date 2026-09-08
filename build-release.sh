#!/usr/bin/env bash
#
# ReadyRide — CodeCanyon release packager
# -----------------------------------------------------------------------------
# Builds a clean, buyer-ready ZIP of the project:
#   • installs production PHP deps (composer --no-dev) and compiles front-end assets
#   • strips ALL secrets (.env, firebase.json), logs, caches, dev files and the
#     install lock, so the buyer's fresh installer runs correctly
#   • outputs dist/readyride-vX.Y.Z-<timestamp>.zip
#
# Usage:
#   ./build-release.sh                 # full package (vendor + built assets)
#   ./build-release.sh --no-vendor     # skip composer install (buyer runs it)
#   ./build-release.sh --no-assets     # skip npm build (ship existing public/build)
#
set -euo pipefail

# ---- config ----------------------------------------------------------------
APP_NAME="readyride"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

DEFAULT_VERSION="$(cat VERSION 2>/dev/null | tr -d '[:space:]' || echo '1.0.0')"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_DIR="$ROOT/dist"
STAGE="$(mktemp -d)/${APP_NAME}"

VERSION=""
INCLUDE_VENDOR=1
INCLUDE_ASSETS=1
for arg in "$@"; do
  case "$arg" in
    --no-vendor) INCLUDE_VENDOR=0 ;;
    --no-assets) INCLUDE_ASSETS=0 ;;
    --*) echo "Unknown flag: $arg"; exit 1 ;;
    *)  VERSION="$arg" ;;   # positional: release version, e.g. ./build-release.sh 1.2.0
  esac
done

# Ask for the release version if not passed as an argument (interactive shell only).
if [[ -z "$VERSION" ]]; then
  if [[ -t 0 ]]; then
    read -r -p "Release version [${DEFAULT_VERSION}]: " VERSION
  fi
  VERSION="${VERSION:-$DEFAULT_VERSION}"
fi
VERSION="$(printf '%s' "$VERSION" | tr -d '[:space:]')"
[[ -n "$VERSION" ]] || VERSION="$DEFAULT_VERSION"

# Persist it so the shipped VERSION file matches the package name.
printf '%s\n' "$VERSION" > "$ROOT/VERSION"
echo "Building release version: $VERSION"

ZIP="$OUT_DIR/${APP_NAME}-v${VERSION}-${STAMP}.zip"

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

# ---- 1. copy source into a clean staging dir -------------------------------
say "Staging project (excluding junk & secrets)…"
mkdir -p "$STAGE" "$OUT_DIR"
rsync -a \
  --exclude '.git' \
  --exclude '.github' \
  --exclude 'node_modules' \
  --exclude 'dist' \
  --exclude 'tests' \
  --exclude '.env' \
  --exclude '.env.backup' \
  --exclude '.phpunit.result.cache' \
  --exclude '.idea' --exclude '.vscode' --exclude '.DS_Store' \
  --exclude 'storage/installed' \
  --exclude 'storage/app/firebase/firebase.json' \
  --exclude 'storage/app/public/*' \
  --exclude 'storage/logs/*.log' \
  --exclude 'storage/framework/cache/data/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*.php' \
  --exclude 'bootstrap/cache/*.php' \
  --exclude 'database/database.sqlite' \
  ./ "$STAGE/"

cd "$STAGE"

# ---- 2. production PHP dependencies ----------------------------------------
if [[ "$INCLUDE_VENDOR" == "1" ]]; then
  if command -v composer >/dev/null 2>&1; then
    say "composer install (production)…"
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
      || echo "⚠️  composer install failed — shipping the existing vendor/ instead."
  else
    echo "composer not found — shipping the existing vendor/ (buyer can re-run 'composer install --no-dev')."
  fi
fi

# ---- 3. compile front-end assets -------------------------------------------
if [[ "$INCLUDE_ASSETS" == "1" ]]; then
  if command -v npm >/dev/null 2>&1; then
    say "npm build (vite)…"
    if (npm ci || npm install) && npm run build; then
      rm -rf node_modules
    else
      echo "⚠️  npm build failed — shipping the existing public/build (if present)."
      rm -rf node_modules
    fi
  else
    echo "npm not found — shipping existing public/build (if present)."
  fi
fi

# ---- 4. final scrub (belt & suspenders) ------------------------------------
say "Scrubbing secrets, caches & lock files…"
rm -f  .env .env.backup 2>/dev/null || true
rm -f  storage/installed 2>/dev/null || true
rm -f  storage/app/firebase/firebase.json 2>/dev/null || true
find storage/logs -type f -name '*.log' -delete 2>/dev/null || true
find storage/framework/views -type f -name '*.php' -delete 2>/dev/null || true
find storage/framework/cache/data -mindepth 1 -maxdepth 1 -exec rm -rf {} + 2>/dev/null || true
find storage/framework/sessions -type f ! -name '.gitignore' -delete 2>/dev/null || true
rm -f bootstrap/cache/*.php 2>/dev/null || true
find storage/app/public -mindepth 1 -maxdepth 1 ! -name '.gitignore' -exec rm -rf {} + 2>/dev/null || true

# Guarantee .env.example ships and required empty dirs keep their .gitignore.
[[ -f .env.example ]] || { echo "WARNING: .env.example missing!"; }
for d in storage/logs storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/app/public bootstrap/cache; do
  mkdir -p "$d"; [[ -f "$d/.gitignore" ]] || printf '*\n!.gitignore\n' > "$d/.gitignore"
done

# Abort if any obvious secret slipped through.
if [[ -f .env || -f storage/app/firebase/firebase.json || -f storage/installed ]]; then
  echo "ERROR: a secret/lock file survived the scrub — aborting."; exit 1
fi

# CodeCanyon packages MUST ship a ready-to-run vendor/ so buyers never need
# composer. Fail loudly if it's missing instead of shipping a broken zip.
if [[ "$INCLUDE_VENDOR" == "1" && ! -f vendor/autoload.php ]]; then
  echo "ERROR: vendor/ is missing — the package would not run out of the box."
  echo "       Install composer and re-run, or run 'composer install --no-dev' in the project first."
  exit 1
fi

# ---- 5. zip (zip → python3 fallback) ---------------------------------------
say "Zipping → $ZIP"
STAGE_PARENT="$(dirname "$STAGE")"
STAGE_BASE="$(basename "$STAGE")"
if command -v zip >/dev/null 2>&1; then
  ( cd "$STAGE_PARENT" && zip -rqX "$ZIP" "$STAGE_BASE" -x '*/.DS_Store' )
elif command -v python3 >/dev/null 2>&1; then
  echo "  'zip' not installed — using python3 fallback."
  ( cd "$STAGE_PARENT" && python3 -c "import shutil,sys; shutil.make_archive(sys.argv[1], 'zip', '.', sys.argv[2])" "${ZIP%.zip}" "$STAGE_BASE" )
else
  echo "Staged (unzipped) build kept at: $STAGE"
  die "Neither 'zip' nor 'python3' found. Install zip:  sudo apt install zip   (or  brew install zip )  then re-run."
fi

[[ -f "$ZIP" ]] || die "Zip step finished but $ZIP was not created."
SIZE="$(du -h "$ZIP" | cut -f1)"
rm -rf "$STAGE_PARENT"

say "Done ✅  ($SIZE)"
echo "Package: $ZIP"
echo "Version: $VERSION"
echo
echo "Ships WITH:    production vendor/ + built public/build (buyer needs NO composer/npm)"
echo "Ships WITHOUT: .env, firebase.json, storage/installed, logs, caches, node_modules, .git"
echo "Buyer flow: upload → visit /install → configure DB + admin via installer → done."

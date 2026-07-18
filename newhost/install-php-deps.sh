#!/usr/bin/env bash
#
# install-php-deps.sh - Ensure Composer is installed and PHP deps (vendor/) are present.
#
# Run once on the golden box (baked into the snapshot) and/or per app clone.
# Idempotent. Two things:
#   1. Install the composer binary to /usr/local/bin if missing.
#   2. Run `composer install --no-dev --optimize-autoloader` in the app root
#      so vendor/ (tcpdf today; endroid/qr-code once QR ships) is materialized.
#
# vendor/ is gitignored and MUST be provisioned this way, not committed. This is
# what makes SWUDeck/CreateImage.php's `require .../vendor/autoload.php` resolve
# instead of fataling (a missing vendor/ is why deck-image copy currently fails).
#
# Usage:
#   sudo ./install-php-deps.sh
#
# Config (override via environment):
#   APP_ROOT      app root containing composer.json (default: parent of this script)
#   COMPOSER_BIN  where to install composer          (default: /usr/local/bin/composer)
#   PHP_BIN       php to run composer with            (default: php on PATH)
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_ROOT="${APP_ROOT:-$(cd "$SCRIPT_DIR/.." && pwd)}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"
PHP_BIN="${PHP_BIN:-php}"

log()  { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ok\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

# -h/--help
case "${1:-}" in
  -h|--help) grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
esac

command -v "$PHP_BIN" >/dev/null 2>&1 || die "php not found (set PHP_BIN=...)."
[ -f "$APP_ROOT/composer.json" ] || die "no composer.json at $APP_ROOT (set APP_ROOT=...)."

log "Provisioning PHP deps for $APP_ROOT"

# ---------------------------------------------------------------------------
# 1. Composer binary
# ---------------------------------------------------------------------------
if command -v composer >/dev/null 2>&1; then
  COMPOSER_BIN="$(command -v composer)"
  ok "composer already installed ($("$COMPOSER_BIN" --version 2>/dev/null | head -1))"
elif [ -x "$COMPOSER_BIN" ]; then
  ok "composer present at $COMPOSER_BIN"
else
  log "Installing composer -> $COMPOSER_BIN"
  "$PHP_BIN" -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
  "$PHP_BIN" /tmp/composer-setup.php --install-dir="$(dirname "$COMPOSER_BIN")" --filename="$(basename "$COMPOSER_BIN")"
  rm -f /tmp/composer-setup.php
  [ -x "$COMPOSER_BIN" ] || die "composer install failed."
  ok "installed $("$COMPOSER_BIN" --version 2>/dev/null | head -1)"
fi

# ---------------------------------------------------------------------------
# 2. composer install (materialize vendor/)
# ---------------------------------------------------------------------------
log "Running composer install in $APP_ROOT"
( cd "$APP_ROOT" && "$COMPOSER_BIN" install --no-dev --optimize-autoloader )
[ -f "$APP_ROOT/vendor/autoload.php" ] || die "composer install did not produce vendor/autoload.php"
ok "vendor/ provisioned at $APP_ROOT/vendor"

cat <<SUMMARY

PHP deps provisioned.
Verify:
  $PHP_BIN -r 'require "$APP_ROOT/vendor/autoload.php"; echo "autoload OK\n";'
  # (once QR ships, also: class_exists("Endroid\\\\QrCode\\\\Builder\\\\Builder"))
SUMMARY

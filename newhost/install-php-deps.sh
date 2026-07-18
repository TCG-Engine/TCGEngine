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
#   LAMPP_ROOT    LAMPP install                      (default: /opt/lampp)
#   PHP_BIN       php to run composer with           (default: LAMPP's php if present, else `php`)
#
# IMPORTANT: composer MUST run under the SAME PHP that serves the site (LAMPP's
# PHP 8.2), NOT the system `php` (often a different version, e.g. 7.4, with a
# different extension set) — otherwise vendor/ is resolved for the wrong runtime
# and composer's platform check fails on extensions the CLI lacks but Apache has.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_ROOT="${APP_ROOT:-$(cd "$SCRIPT_DIR/.." && pwd)}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"
LAMPP_ROOT="${LAMPP_ROOT:-/opt/lampp}"
# Default PHP_BIN to LAMPP's php (the web runtime), not the system php.
if [ -z "${PHP_BIN:-}" ]; then
  if [ -x "$LAMPP_ROOT/bin/php" ]; then PHP_BIN="$LAMPP_ROOT/bin/php"; else PHP_BIN="php"; fi
fi

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
ok "using PHP: $PHP_BIN ($("$PHP_BIN" -v 2>/dev/null | head -1))"

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
log "Running composer install in $APP_ROOT (under $PHP_BIN)"
# Invoke composer UNDER $PHP_BIN (not composer's own shebang, which finds the system php).
# --ignore-platform-reqs: LAMPP's CLI extension set differs from its web SAPI (e.g. curl/
# mysqli are present for Apache/mod_php but not the CLI), so the platform check would wrongly
# reject packages that run fine under the site's runtime. vendor code executes under mod_php.
( cd "$APP_ROOT" && "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --ignore-platform-reqs )
[ -f "$APP_ROOT/vendor/autoload.php" ] || die "composer install did not produce vendor/autoload.php"
ok "vendor/ provisioned at $APP_ROOT/vendor"

cat <<SUMMARY

PHP deps provisioned.
Verify:
  $PHP_BIN -r 'require "$APP_ROOT/vendor/autoload.php"; echo "autoload OK\n";'
  # (once QR ships, also: class_exists("Endroid\\\\QrCode\\\\Builder\\\\Builder"))
SUMMARY

#!/usr/bin/env bash
#
# harden-webp.sh - give XAMPP's PHP working WebP support via the `imagick` extension.
#
# Why: these XAMPP 8.2.12 builds ship a GD compiled WITHOUT webp (gd_info WebP
# Support => empty, imagewebp() undefined). The app converts card art to WebP through
# Imagick instead (Custom image pipeline / zzImageConverter.php). A freshly restored
# box has no imagick extension, so conversion fatals. This installs it.
#
# Approach (repeatable, always-latest): install system ImageMagick + its dev headers
# and a build toolchain, then BUILD the latest stable imagick extension from source
# against XAMPP's own PHP (its bundled phpize/php-config), so the .so is ABI-correct
# by construction. No hand-copied imagick.so, no ABI-twin-box dependency. Safe to
# re-run: it rebuilds from the newest PECL stable each time.
#
# Assumes: XAMPP at /opt/lampp (with bundled phpize/php-config), Debian/Ubuntu (apt),
# run as root, network access to apt + pecl.php.net.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LAMPP_ROOT="${LAMPP_ROOT:-/opt/lampp}"
PHP_BIN="${PHP_BIN:-$LAMPP_ROOT/bin/php}"
PHP_INI="${PHP_INI:-$LAMPP_ROOT/etc/php.ini}"
PHPIZE="${PHPIZE:-$LAMPP_ROOT/bin/phpize}"
PHP_CONFIG="${PHP_CONFIG:-$LAMPP_ROOT/bin/php-config}"
# Latest stable imagick source; this URL always redirects to the newest release tarball.
IMAGICK_URL="${IMAGICK_URL:-https://pecl.php.net/get/imagick}"
# Legacy imagewebp() polyfill from the old drop-in flow — retired by this script if present.
POLYFILL_PATH="${POLYFILL_PATH:-$LAMPP_ROOT/etc/imagewebp-polyfill.php}"

SKIP_APT=0; ASSUME_YES=0
for arg in "$@"; do
  case "$arg" in
    --skip-apt) SKIP_APT=1 ;;
    --yes|-y)   ASSUME_YES=1 ;;
    -h|--help)  grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

TS="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$SCRIPT_DIR/newhost-backups-$TS"
BUILD_DIR=""
cleanup() { [ -n "$BUILD_DIR" ] && [ -d "$BUILD_DIR" ] && rm -rf "$BUILD_DIR" || true; }
trap cleanup EXIT

log()  { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ok\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

backup() {
  local f="$1"; [ -f "$f" ] || return 0
  mkdir -p "$BACKUP_DIR"
  local dest="$BACKUP_DIR/$(echo "$f" | sed 's#^/##; s#/#_#g')"
  [ -f "$dest" ] || cp -p "$f" "$dest"
}

# Ensure `extension=<ext>` is active without clobbering other extension= lines.
php_ext_enable() {
  local ext="$1"
  local off="^[[:space:]]*;[[:space:]]*extension[[:space:]]*=[[:space:]]*${ext}(\.so)?[[:space:]]*\$"
  local on="^[[:space:]]*extension[[:space:]]*=[[:space:]]*${ext}(\.so)?[[:space:]]*\$"
  if grep -Eq "$on" "$PHP_INI"; then :  # already enabled
  elif grep -Eq "$off" "$PHP_INI"; then sed -ri "s#$off#extension=${ext}#" "$PHP_INI"
  else printf 'extension=%s\n' "$ext" >> "$PHP_INI"; fi
}

# Retire the legacy imagewebp() polyfill: remove its auto_prepend_file line (only if it
# points at OUR polyfill, never a user's own) and delete the file. No-op if never installed.
retire_polyfill() {
  local esc
  esc="$(printf '%s' "$POLYFILL_PATH" | sed 's/[.[\*^$/]/\\&/g')"
  if grep -Eq "^[[:space:]]*auto_prepend_file[[:space:]]*=[[:space:]]*${esc}[[:space:]]*$" "$PHP_INI"; then
    backup "$PHP_INI"
    sed -ri "/^[[:space:]]*auto_prepend_file[[:space:]]*=[[:space:]]*${esc}[[:space:]]*$/d" "$PHP_INI"
    ok "removed legacy imagewebp() polyfill auto_prepend_file"
  fi
  if [ -f "$POLYFILL_PATH" ]; then
    backup "$POLYFILL_PATH"; rm -f "$POLYFILL_PATH"
    ok "deleted legacy polyfill $POLYFILL_PATH"
  fi
}

# --------------------------------------------------------------------------- #
[ "$(id -u)" -eq 0 ] || die "must run as root (sudo)."
[ -d "$LAMPP_ROOT" ] || die "LAMPP not found at $LAMPP_ROOT."
[ -x "$PHP_BIN" ] || die "php not found at $PHP_BIN."
[ -x "$PHPIZE" ] || die "phpize not found at $PHPIZE — this XAMPP lacks the dev tools needed to build extensions."
[ -x "$PHP_CONFIG" ] || die "php-config not found at $PHP_CONFIG — cannot build against XAMPP's PHP."
log "Hardening WebP (build imagick from source) for $LAMPP_ROOT  (backups -> $BACKUP_DIR)"

# 1. System ImageMagick + dev headers + build toolchain ---------------------- #
if [ "$SKIP_APT" -eq 0 ]; then
  log "Installing ImageMagick, dev headers, and build toolchain (apt)"
  command -v apt-get >/dev/null 2>&1 || die "apt-get not found; install the deps manually or use --skip-apt."
  DEBIAN_FRONTEND=noninteractive apt-get update -y || warn "apt-get update had errors; continuing"
  # imagemagick             -> the `convert` binary + delegates (incl. webp)
  # libmagickwand-dev       -> MagickWand headers/pkg-config the extension compiles against
  # build-essential/autoconf/pkg-config -> phpize build chain
  # libwebp-dev/curl        -> webp delegate + tarball fetch
  DEBIAN_FRONTEND=noninteractive apt-get install -y \
      imagemagick libmagickwand-dev build-essential autoconf pkg-config libwebp-dev curl \
    || die "could not install build deps via apt — check the box's apt sources / network."
  if convert -list format 2>/dev/null | grep -qi 'webp'; then
    ok "ImageMagick has a WebP delegate ($(convert --version 2>/dev/null | head -1))"
  else
    warn "ImageMagick installed but no WebP delegate detected — webp writes may fail."
  fi
else
  warn "skipping apt (--skip-apt) — assuming ImageMagick dev libs + build toolchain are present"
  command -v curl >/dev/null 2>&1 || die "curl not found and --skip-apt set; install curl or drop --skip-apt."
fi

# 2. Fetch latest stable imagick source ------------------------------------- #
BUILD_DIR="$(mktemp -d)"
log "Downloading latest stable imagick ($IMAGICK_URL)"
curl -fSL "$IMAGICK_URL" -o "$BUILD_DIR/imagick.tgz" \
  || die "failed to download imagick source from $IMAGICK_URL"
tar xzf "$BUILD_DIR/imagick.tgz" -C "$BUILD_DIR" \
  || die "failed to extract imagick tarball."
BUILD_SRC="$(find "$BUILD_DIR" -maxdepth 1 -type d -name 'imagick-*' | head -1)"
[ -n "$BUILD_SRC" ] && [ -d "$BUILD_SRC" ] || die "could not locate extracted imagick-* source dir."
ok "source: $(basename "$BUILD_SRC")"

# 3. Build + install against XAMPP's PHP ------------------------------------ #
log "Building imagick against XAMPP PHP ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"
(
  cd "$BUILD_SRC"
  "$PHPIZE"
  ./configure --with-php-config="$PHP_CONFIG"
  make -j"$(nproc 2>/dev/null || echo 1)"
  make install
) || die "imagick build/install failed — see the compiler output above."
ok "imagick.so built and installed"

# Verify the freshly-built .so resolves its libs (catches a missing IM runtime early).
EXT_DIR="$("$PHP_BIN" -r 'echo ini_get("extension_dir");')"
if [ -n "$EXT_DIR" ] && [ -f "$EXT_DIR/imagick.so" ]; then
  missing="$(ldd "$EXT_DIR/imagick.so" 2>/dev/null | awk '/not found/{print "    "$1}')"
  [ -n "$missing" ] && { warn "imagick.so has UNRESOLVED libraries:"; printf '%s\n' "$missing"; }
fi

# 4. Enable extension=imagick + retire the old polyfill --------------------- #
log "Enabling extension=imagick in $PHP_INI"
backup "$PHP_INI"
if "$PHP_BIN" -m 2>/dev/null | grep -qi '^imagick$'; then
  ok "imagick already loaded"
else
  php_ext_enable "imagick"
  ok "extension=imagick set"
fi
retire_polyfill

# 5. Apply + verify --------------------------------------------------------- #
log "Restarting LAMPP"
"$LAMPP_ROOT/lampp" restart || warn "LAMPP restart failed; restart it manually."

echo
if "$PHP_BIN" -r 'exit(class_exists("Imagick")?0:1);' 2>/dev/null; then
  webp_ok="$("$PHP_BIN" -r '$i=new Imagick(); echo in_array("WEBP",array_map("strtoupper",$i->queryFormats()))?"yes":"no";' 2>/dev/null)"
  ok "Imagick loads (WebP supported: ${webp_ok:-unknown})"
  [ "${webp_ok:-no}" = "yes" ] || warn "Imagick loaded but WebP not in queryFormats — check the ImageMagick webp delegate."
else
  die "Imagick did NOT load after build — check the unresolved-libs note above and 'php -m'."
fi

cat <<SUMMARY

WebP hardening done.  Backups: $BACKUP_DIR

Verify:
  $PHP_BIN -m | grep -i imagick
  $PHP_BIN -r '\$i=new Imagick(); var_dump(in_array("WEBP",\$i->queryFormats()));'

imagick was BUILT from the latest PECL stable against XAMPP's PHP, so re-running this
script safely rebuilds it current. No app-code deploy or hand-copied .so needed.
SUMMARY

#!/usr/bin/env bash
#
# harden-host.sh - ONE-TIME host hardening for a native XAMPP/LAMPP box.
#
# Run this ONCE on the golden host, then snapshot the box. Everything here is
# host-wide and app-agnostic, so every future app cloned from the snapshot
# inherits it. Per-app setup (DB name env vars + clearing the DB) is a separate,
# re-runnable script: newhost/provision-app.sh.
#
# Does three things:
#   1. Enable OPcache in php.ini
#   2. Remove phpMyAdmin entirely (so it can't be reached from the internet)
#   3. Install + configure fail2ban (ip rate-limit / ip jail)
#
# Assumes: XAMPP at /opt/lampp, Debian/Ubuntu (apt), Apache via mod_php, run as root.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Config (override via environment, e.g. LAMPP_ROOT=/opt/lampp7 ./harden-host.sh)
# ---------------------------------------------------------------------------
LAMPP_ROOT="${LAMPP_ROOT:-/opt/lampp}"
PHP_INI="${PHP_INI:-$LAMPP_ROOT/etc/php.ini}"
XAMPP_CONF="${XAMPP_CONF:-$LAMPP_ROOT/etc/extra/httpd-xampp.conf}"
PMA_DIR="${PMA_DIR:-$LAMPP_ROOT/phpmyadmin}"
ACCESS_LOG="${ACCESS_LOG:-$LAMPP_ROOT/logs/access_log}"
ERROR_LOG="${ERROR_LOG:-$LAMPP_ROOT/logs/error_log}"

# fail2ban DoS jail tunables: ban an IP that makes > MAXRETRY requests within
# FINDTIME seconds, for BANTIME seconds. Defaults are deliberately generous so a
# normal browsing session (many asset requests) is never caught.
BANTIME="${BANTIME:-3600}"     # 1 hour
FINDTIME="${FINDTIME:-60}"     # per 60s window
MAXRETRY="${MAXRETRY:-300}"    # 300 requests / minute from one IP

# ---------------------------------------------------------------------------
# Flags
# ---------------------------------------------------------------------------
SKIP_OPCACHE=0; SKIP_PMA=0; SKIP_F2B=0; ASSUME_YES=0
for arg in "$@"; do
  case "$arg" in
    --skip-opcache)    SKIP_OPCACHE=1 ;;
    --skip-phpmyadmin) SKIP_PMA=1 ;;
    --skip-fail2ban)   SKIP_F2B=1 ;;
    --yes|-y)          ASSUME_YES=1 ;;
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

TS="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$(cd "$(dirname "$0")" && pwd)/newhost-backups-$TS"

log()  { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ok\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

backup() {
  # backup <file> - copy a file into the timestamped backup dir once
  local f="$1"
  [ -f "$f" ] || return 0
  mkdir -p "$BACKUP_DIR"
  local dest="$BACKUP_DIR/$(echo "$f" | sed 's#^/##; s#/#_#g')"
  [ -f "$dest" ] || cp -p "$f" "$dest"
}

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
[ "$(id -u)" -eq 0 ] || die "must run as root (sudo)."
[ -d "$LAMPP_ROOT" ] || die "LAMPP not found at $LAMPP_ROOT (set LAMPP_ROOT=...)."
log "Hardening host at $LAMPP_ROOT  (backups -> $BACKUP_DIR)"

# ---------------------------------------------------------------------------
# 1. PHP config (OPcache + required extensions)
# ---------------------------------------------------------------------------
# php_ini_set <key> <value> - upsert a php.ini directive, uncommenting if needed.
php_ini_set() {
  local key="$1" val="$2" line="$1=$2"
  # match optional leading ';' + whitespace, the key, optional spaces, '='
  local re="^[[:space:]]*;?[[:space:]]*$(printf '%s' "$key" | sed 's/[.[\*^$]/\\&/g')[[:space:]]*="
  if grep -Eq "$re" "$PHP_INI"; then
    sed -ri "s#$re.*#$line#" "$PHP_INI"
  else
    printf '%s\n' "$line" >> "$PHP_INI"
  fi
}

# php_ext_enable <ext> - ensure `extension=<ext>` is active. Unlike php_ini_set this
# matches the WHOLE `extension=<ext>` line (there are many extension= lines, so we must
# not clobber a different one). Uncomments `;extension=<ext>` or appends if absent.
php_ext_enable() {
  local ext="$1"
  local on="^[[:space:]]*extension[[:space:]]*=[[:space:]]*${ext}(\.so)?[[:space:]]*\$"
  local off="^[[:space:]]*;[[:space:]]*extension[[:space:]]*=[[:space:]]*${ext}(\.so)?[[:space:]]*\$"
  if grep -Eq "$on" "$PHP_INI"; then
    :  # already enabled
  elif grep -Eq "$off" "$PHP_INI"; then
    sed -ri "s#$off#extension=${ext}#" "$PHP_INI"
  else
    printf 'extension=%s\n' "$ext" >> "$PHP_INI"
  fi
}

if [ "$SKIP_OPCACHE" -eq 0 ]; then
  log "Configuring PHP (OPcache + extensions) in $PHP_INI"
  [ -f "$PHP_INI" ] || die "php.ini not found at $PHP_INI"
  backup "$PHP_INI"
  # Only declare the opcache zend_extension if it isn't already loaded (XAMPP often
  # ships it pre-enabled; adding it again -> "Cannot load Zend OPcache - already loaded").
  PHP_BIN="$LAMPP_ROOT/bin/php"
  if "$PHP_BIN" -m 2>/dev/null | grep -qi 'Zend OPcache'; then
    ok "OPcache already loaded; not re-declaring zend_extension"
  elif grep -Eq '^[[:space:]]*zend_extension[[:space:]]*=[[:space:]]*(.*/)?opcache(\.so)?[[:space:]]*$' "$PHP_INI"; then
    ok "zend_extension=opcache already present in php.ini"
  else
    php_ini_set "zend_extension" "opcache"
  fi
  php_ini_set "opcache.enable" "1"
  php_ini_set "opcache.enable_cli" "0"
  php_ini_set "opcache.memory_consumption" "128"
  php_ini_set "opcache.interned_strings_buffer" "16"
  php_ini_set "opcache.max_accelerated_files" "10000"
  php_ini_set "opcache.revalidate_freq" "2"
  php_ini_set "opcache.validate_timestamps" "1"
  ok "OPcache directives written"
  # GD provides imagewebp() used by zzImageConverter.php / the png-to-webp flow.
  # Only add extension=gd if GD isn't already present (XAMPP often builds it in
  # statically -> no extension= line, and adding one would try to double-load it).
  if "$PHP_BIN" -m 2>/dev/null | grep -qi '^gd$'; then
    ok "GD already loaded; not adding extension=gd"
  else
    php_ext_enable "gd"
    ok "extension=gd enabled"
  fi
else
  warn "skipping PHP config (--skip-opcache)"
fi

# ---------------------------------------------------------------------------
# 2. Remove phpMyAdmin entirely
# ---------------------------------------------------------------------------
if [ "$SKIP_PMA" -eq 0 ]; then
  log "Removing phpMyAdmin"
  if [ -f "$XAMPP_CONF" ]; then
    backup "$XAMPP_CONF"
    if grep -Eq '^[[:space:]]*Alias[[:space:]]+/phpmyadmin' "$XAMPP_CONF"; then
      # Comment the phpMyAdmin Alias line and its matching <Directory> block.
      awk '
        BEGIN { inblk=0 }
        /^[[:space:]]*Alias[[:space:]]+\/phpmyadmin/ { print "#REMOVED# " $0; next }
        /^[[:space:]]*<Directory[[:space:]]+".*phpmyadmin"[[:space:]]*>/ { inblk=1; print "#REMOVED# " $0; next }
        inblk==1 {
          print "#REMOVED# " $0
          if ($0 ~ /<\/Directory>/) inblk=0
          next
        }
        { print }
      ' "$XAMPP_CONF" > "$XAMPP_CONF.tmp" && mv "$XAMPP_CONF.tmp" "$XAMPP_CONF"
      ok "commented phpMyAdmin Alias/Directory in httpd-xampp.conf"
    else
      warn "no active phpMyAdmin Alias in $XAMPP_CONF (already removed?)"
    fi
  else
    warn "$XAMPP_CONF not found; skipping conf edit"
  fi
  if [ -d "$PMA_DIR" ]; then
    rm -rf "$PMA_DIR"
    ok "deleted $PMA_DIR"
  else
    warn "$PMA_DIR already gone"
  fi
else
  warn "skipping phpMyAdmin removal (--skip-phpmyadmin)"
fi

# ---------------------------------------------------------------------------
# 3. fail2ban
# ---------------------------------------------------------------------------
if [ "$SKIP_F2B" -eq 0 ]; then
  log "Installing + configuring fail2ban"
  command -v apt-get >/dev/null 2>&1 || die "apt-get not found; install fail2ban manually or use --skip-fail2ban."
  if ! command -v fail2ban-client >/dev/null 2>&1; then
    DEBIAN_FRONTEND=noninteractive apt-get update -y
    DEBIAN_FRONTEND=noninteractive apt-get install -y fail2ban
  else
    ok "fail2ban already installed"
  fi

  # Custom DoS filter: every request line from a host counts as one hit.
  backup /etc/fail2ban/filter.d/xampp-dos.conf
  cat > /etc/fail2ban/filter.d/xampp-dos.conf <<'FILTER'
# xampp-dos - counts every HTTP request per client IP (combined access log).
[Definition]
failregex = ^<HOST> -.*"(GET|POST|HEAD|PUT|DELETE|OPTIONS|PATCH).*"
ignoreregex =
FILTER

  # jail.local: the DoS rate-limit jail + standard Apache jails, all pointed at
  # XAMPP's real log paths (NOT /var/log/apache2).
  backup /etc/fail2ban/jail.local
  cat > /etc/fail2ban/jail.local <<JAIL
# Managed by newhost/harden-host.sh
[DEFAULT]
bantime  = $BANTIME
findtime = $FINDTIME
maxretry = $MAXRETRY
backend  = auto
banaction = iptables-multiport

[xampp-dos]
enabled  = true
port     = http,https
filter   = xampp-dos
logpath  = $ACCESS_LOG
maxretry = $MAXRETRY
findtime = $FINDTIME
bantime  = $BANTIME

[apache-auth]
enabled  = true
port     = http,https
logpath  = $ERROR_LOG

[apache-badbots]
enabled  = true
port     = http,https
logpath  = $ACCESS_LOG

[apache-overflows]
enabled  = true
port     = http,https
logpath  = $ERROR_LOG

[recidive]
enabled  = true
JAIL
  ok "wrote xampp-dos filter + jail.local"

  systemctl enable fail2ban >/dev/null 2>&1 || true
  if systemctl restart fail2ban 2>/dev/null; then
    ok "fail2ban restarted"
  else
    service fail2ban restart 2>/dev/null || warn "could not restart fail2ban via systemctl/service; start it manually"
  fi
else
  warn "skipping fail2ban (--skip-fail2ban)"
fi

# ---------------------------------------------------------------------------
# Apply + summary
# ---------------------------------------------------------------------------
log "Restarting LAMPP to apply OPcache / phpMyAdmin changes"
"$LAMPP_ROOT/lampp" restart || warn "LAMPP restart failed; restart it manually."

cat <<SUMMARY

Host hardening complete.  Backups: $BACKUP_DIR

Verify:
  OPcache     : $LAMPP_ROOT/bin/php -r 'var_dump(opcache_get_status()!==false);'
  GD/WebP     : $LAMPP_ROOT/bin/php -r 'var_dump(function_exists("imagewebp"));'   # expect true
  phpMyAdmin  : curl -s -o /dev/null -w '%{http_code}\n' http://localhost/phpmyadmin   # expect 404
  fail2ban    : fail2ban-client status && fail2ban-client status xampp-dos

Next: snapshot this box, then run  ./provision-app.sh <app> <db>  on each new clone.
SUMMARY

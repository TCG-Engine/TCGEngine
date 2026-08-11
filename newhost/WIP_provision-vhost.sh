#!/usr/bin/env bash
#
# provision-vhost.sh <APP> [DB_NAME] --server-name <fqdn> - MULTI-SITE setup on one box.
#
# Use this INSTEAD OF provision-app.sh when a single box serves more than one site.
# provision-app.sh puts SetEnv MYSQL_DATABASE_NAME at SERVER level, which is why one
# box = one DB. This script puts the same SetEnv inside a name-based <VirtualHost>, so
# the DB is chosen per REQUEST from the Host header. Nothing in PHP changes:
# Database/ConnectionManager.php still calls getenv(), and SharedUI/ActiveSite.php still
# resolves the rendered site FROM the db name — so "the site and the connected DB can
# never disagree" survives intact.
#
# Every vhost may share ONE DocumentRoot (one checkout, one deploy). Sites are separated
# by hostname, NOT by URL path, because ~450 hardcoded "/TCGEngine/..." absolute paths in
# the app would escape a path prefix and land in the wrong site's env. Hostname separation
# also gives per-site session cookies for free (browsers scope cookies by host), which is
# what keeps a login on one sim from resolving to a different person's row in another
# sim's `users` table.
#
# SAFE TO RE-RUN. It only ever writes Apache conf — it NEVER touches the database.
# (Need to create a DB? Use provision-app.sh --reset-db on a dedicated box, or create it
# by hand; this script only refuses to proceed if the DB is missing.)
#
# Running it for an app that is currently provisioned the old way MIGRATES that app:
# the server-level httpd-<APP>-env.conf is retired and its Include removed.
#
# Usage:
#   sudo DB_PASS=... ./provision-vhost.sh swusim   --server-name swusim.example.com
#   sudo DB_PASS=... ./provision-vhost.sh hellbreaksim --server-name hellbreak.example.com
#   sudo DB_PASS=... ./provision-vhost.sh grandarchivesim --server-name ga.example.com \
#        --ports "80 443" --ssl-cert /path/fullchain.pem --ssl-key /path/privkey.pem
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Positional args + flags
# ---------------------------------------------------------------------------
APP=""; DB_NAME=""; SERVER_NAME=""; SERVER_ALIAS=""
PORTS=""; SSL_CERT=""; SSL_KEY=""; SKIP_RESTART=0; DEFAULT_SITE=0
want_val=""
for arg in "$@"; do
  if [ -n "$want_val" ]; then
    case "$want_val" in
      server-name)  SERVER_NAME="$arg" ;;
      server-alias) SERVER_ALIAS="$arg" ;;
      ports)        PORTS="$arg" ;;
      ssl-cert)     SSL_CERT="$arg" ;;
      ssl-key)      SSL_KEY="$arg" ;;
    esac
    want_val=""; continue
  fi
  case "$arg" in
    --server-name|--server-alias|--ports|--ssl-cert|--ssl-key)
                     want_val="${arg#--}" ;;
    --default-site)  DEFAULT_SITE=1 ;;
    --skip-restart)  SKIP_RESTART=1 ;;
    -h|--help)       grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    --*)             echo "Unknown option: $arg" >&2; exit 2 ;;
    *)               if [ -z "$APP" ]; then APP="$arg"; elif [ -z "$DB_NAME" ]; then DB_NAME="$arg"; fi ;;
  esac
done
[ -z "$want_val" ] || { echo "Option --$want_val needs a value" >&2; exit 2; }
# Same convention as provision-app.sh: app == db unless a 2nd positional overrides it.
DB_NAME="${DB_NAME:-$APP}"
PORTS="${PORTS:-80}"

# ---------------------------------------------------------------------------
# Config (override via environment)
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LAMPP_ROOT="${LAMPP_ROOT:-/opt/lampp}"
HTTPD_CONF="${HTTPD_CONF:-$LAMPP_ROOT/etc/httpd.conf}"
MYSQL_BIN="${MYSQL_BIN:-$LAMPP_ROOT/bin/mysql}"
DOCROOT="${DOCROOT:-$LAMPP_ROOT/htdocs}"

# All vhost confs live here. "000-default" is the catch-all and MUST load first (Apache
# uses the FIRST vhost on a port as the fallback for any unmatched Host header).
EXTRA_DIR="$LAMPP_ROOT/etc/extra"
DEFAULT_CONF="$EXTRA_DIR/httpd-vhost-000-default.conf"
# --default-site: this app's vhost is ALSO the fallback for any unmatched Host, so it must sort FIRST
# (Apache serves the first vhost on a port to unmatched requests). That keeps a single-site box behaving
# EXACTLY as it does with no vhosts at all — the bare IP, www., monitoring and any other hostname pointed
# here all keep resolving. The deny-everything catch-all is what you want once a SECOND site exists, not
# before: introducing it during the env migration changes two things at once.
if [ "$DEFAULT_SITE" -eq 1 ]; then
  VHOST_CONF="$EXTRA_DIR/httpd-vhost-000-$APP.conf"
else
  VHOST_CONF="$EXTRA_DIR/httpd-vhost-100-$APP.conf"
fi
OLD_ENV_CONF="$EXTRA_DIR/httpd-$APP-env.conf"   # what provision-app.sh writes

MYSQL_HOST="${MYSQL_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"          # REQUIRED — enforced in preflight (no passwordless DB)
REDIS_HOST="${REDIS_HOST:-127.0.0.1}"
REDIS_PORT="${REDIS_PORT:-6379}"

TS="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$SCRIPT_DIR/newhost-backups-$TS"

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

mysql_cli() {
  if [ -n "$DB_PASS" ]; then
    "$MYSQL_BIN" -h "$MYSQL_HOST" -u "$DB_USER" -p"$DB_PASS" "$@"
  else
    "$MYSQL_BIN" -h "$MYSQL_HOST" -u "$DB_USER" "$@"
  fi
}

db_exists() { mysql_cli -N -e "SHOW DATABASES LIKE '$1';" | grep -Fxq "$1"; }

# Is a conf file actually loaded? (present on disk AND Included from httpd.conf, either
# by its own line or by our "1*" glob).
conf_is_included() {
  local base; base="$(basename "$1")"
  [ -f "$HTTPD_CONF" ] || return 1
  grep -Fq "$base" "$HTTPD_CONF" && return 0
  case "$base" in
    httpd-vhost-1*.conf) grep -Fq 'etc/extra/httpd-vhost-1*.conf' "$HTTPD_CONF" && return 0 ;;
  esac
  return 1
}

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
[ "$(id -u)" -eq 0 ] || die "must run as root (sudo)."
[ -d "$LAMPP_ROOT" ] || die "LAMPP not found at $LAMPP_ROOT."
[ -n "$APP" ]        || die "APP is required (1st positional)."
[ -n "$SERVER_NAME" ] || die "--server-name <fqdn> is required — this script separates sites by HOSTNAME."
[ -n "$DB_PASS" ]    || die "DB_PASS is required — pass DB_PASS=... (a passwordless DB is not allowed)."
[ -d "$DOCROOT" ]    || die "DocumentRoot '$DOCROOT' does not exist (set DOCROOT=...)."

echo "$APP" | grep -Eq '^[A-Za-z0-9_-]+$' \
  || die "APP '$APP' must be [A-Za-z0-9_-] (it becomes a filename)."
echo "$SERVER_NAME" | grep -Eq '^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?$' \
  || die "--server-name '$SERVER_NAME' is not a valid hostname."
for p in $PORTS; do
  echo "$p" | grep -Eq '^[0-9]{1,5}$' || die "--ports must be numeric (got '$p')."
done

log "Provisioning vhost '$SERVER_NAME' -> app '$APP' (db '$DB_NAME') at $LAMPP_ROOT  (backups -> $BACKUP_DIR)"

# DB connectivity + existence. This script never creates a DB, so it must already exist —
# fail here rather than as a site-wide 500 after the restart.
[ -x "$MYSQL_BIN" ] || die "mysql client not found at $MYSQL_BIN."
mysql_cli -e "SELECT 1;" >/dev/null 2>&1 \
  || die "cannot connect to MySQL as '$DB_USER'@'$MYSQL_HOST' — check DB_USER / DB_PASS."
db_exists "$DB_NAME" \
  || die "database '$DB_NAME' does not exist. This script never creates one — create it first (provision-app.sh --reset-db on a dedicated box, or by hand)."
ok "MySQL reachable as '$DB_USER'@'$MYSQL_HOST'; database '$DB_NAME' exists"

# TLS: if 443 is requested the cert paths are mandatory, otherwise Apache starts and then
# serves the wrong thing (or refuses) in a way that is annoying to diagnose.
if echo "$PORTS" | grep -qw 443; then
  [ -n "$SSL_CERT" ] && [ -n "$SSL_KEY" ] \
    || die "--ports includes 443 but --ssl-cert / --ssl-key were not given."
  [ -f "$SSL_CERT" ] || die "--ssl-cert '$SSL_CERT' not found."
  [ -f "$SSL_KEY" ]  || die "--ssl-key '$SSL_KEY' not found."
fi

# ---- Guard 1: exactly ONE mechanism may decide the DB on this box. -----------
# A leftover SERVER-LEVEL SetEnv from provision-app.sh silently wins for every request
# whose Host matches no vhost, and it is invisible in the vhost files — the same
# "wrong site served silently" footgun provision-app.sh's own conflict check exists to
# prevent. This app's own old conf is MIGRATED below; any OTHER app's must be converted
# by running this script for it too.
stragglers=""
for f in "$EXTRA_DIR"/httpd-*-env.conf; do
  [ -e "$f" ] || continue
  [ "$f" = "$OLD_ENV_CONF" ] && continue
  grep -Eq '^[[:space:]]*SetEnv[[:space:]]+MYSQL_DATABASE_NAME[[:space:]]+' "$f" || continue
  conf_is_included "$f" || continue
  other="$(grep -E '^[[:space:]]*SetEnv[[:space:]]+MYSQL_DATABASE_NAME[[:space:]]+' "$f" | awk '{print $3}' | head -1)"
  stragglers+="  ${f}  (MYSQL_DATABASE_NAME=${other})"$'\n'
done
if [ -n "$stragglers" ]; then
  warn "these apps still set MYSQL_DATABASE_NAME at SERVER level (they would win for any unmatched Host):"
  printf '%s' "$stragglers" >&2
  die "convert each one first:  sudo DB_PASS=... $0 <app> --server-name <fqdn>"
fi

# ---- Guard 2: one hostname, one app. ----------------------------------------
for f in "$EXTRA_DIR"/httpd-vhost-1*.conf; do
  [ -e "$f" ] || continue
  [ "$f" = "$VHOST_CONF" ] && continue
  if grep -Eq "^[[:space:]]*ServerName[[:space:]]+$SERVER_NAME[[:space:]]*$" "$f"; then
    die "ServerName '$SERVER_NAME' is already claimed by $f — pick another hostname or remove that vhost."
  fi
done

# ---------------------------------------------------------------------------
# 1. Catch-all vhost (must be FIRST on every port)
# ---------------------------------------------------------------------------
# Apache serves the first-listed vhost on a port to any request whose Host matches none.
# Without this, adding a second site would silently make the alphabetically-first app the
# default for unknown hostnames — connecting some random Host header to a real database.
# This one denies before PHP ever runs, so an unmatched host can never reach a DB.
if [ "$DEFAULT_SITE" -eq 1 ]; then
  ok "--default-site: skipping the deny catch-all; '$SERVER_NAME' is the fallback for unmatched hosts"
else
log "Writing catch-all vhost -> $DEFAULT_CONF"
backup "$DEFAULT_CONF"
{
  printf '# Managed by newhost/provision-vhost.sh — DO NOT hand-edit.\n'
  printf '# Catch-all: any request whose Host matches no site below lands here and is denied\n'
  printf '# BEFORE PHP runs, so it can never connect to a database. Must load first.\n'
  for p in $PORTS; do
    printf '\n<VirtualHost *:%s>\n' "$p"
    printf '    ServerName   unmatched-host.invalid\n'
    printf '    DocumentRoot "%s"\n' "$DOCROOT"
    printf '    <Location />\n'
    printf '        Require all denied\n'
    printf '    </Location>\n'
    printf '</VirtualHost>\n'
  done
} > "$DEFAULT_CONF"
ok "wrote $DEFAULT_CONF ($(echo "$PORTS" | wc -w | tr -d ' ') port block(s))"
fi

# ---------------------------------------------------------------------------
# 2. This app's vhost
# ---------------------------------------------------------------------------
log "Writing vhost -> $VHOST_CONF"
backup "$VHOST_CONF"
{
  printf '# Managed by newhost/provision-vhost.sh for app: %s  (db: %s)\n' "$APP" "$DB_NAME"
  printf '# Apache (mod_php) exposes SetEnv values to PHP getenv(). The db name also decides\n'
  printf '# which site renders — see SharedUI/ActiveSite.php.\n'
  for p in $PORTS; do
    printf '\n<VirtualHost *:%s>\n' "$p"
    printf '    ServerName   %s\n' "$SERVER_NAME"
    [ -n "$SERVER_ALIAS" ] && printf '    ServerAlias  %s\n' "$SERVER_ALIAS"
    printf '    DocumentRoot "%s"\n' "$DOCROOT"
    printf '\n'
    printf '    SetEnv MYSQL_DATABASE_NAME    %s\n' "$DB_NAME"
    printf '    SetEnv MYSQL_SERVER_NAME      %s\n' "$MYSQL_HOST"
    printf '    SetEnv MYSQL_SERVER_USER_NAME %s\n' "$DB_USER"
    printf '    SetEnv MYSQL_ROOT_PASSWORD    %s\n' "$DB_PASS"
    printf '    SetEnv REDIS_HOST             %s\n' "$REDIS_HOST"
    printf '    SetEnv REDIS_PORT             %s\n' "$REDIS_PORT"
    if [ "$p" = "443" ]; then
      printf '\n    SSLEngine on\n'
      printf '    SSLCertificateFile    "%s"\n' "$SSL_CERT"
      printf '    SSLCertificateKeyFile "%s"\n' "$SSL_KEY"
    fi
    printf '\n    ErrorLog  "logs/%s-error_log"\n' "$APP"
    printf '    CustomLog "logs/%s-access_log" combined\n' "$APP"
    printf '</VirtualHost>\n'
  done
} > "$VHOST_CONF"
# Only chmod what exists: --default-site writes no catch-all, and `set -e` would abort the run
# half-configured (vhost written but not yet Included, old env conf not yet retired).
chmod 600 "$VHOST_CONF"                                  # SetEnv carries the DB password
[ -f "$DEFAULT_CONF" ] && chmod 600 "$DEFAULT_CONF"
ok "wrote $VHOST_CONF (ServerName $SERVER_NAME, db $DB_NAME)"

# ---------------------------------------------------------------------------
# 3. Include the vhost confs (catch-all explicitly first, then the rest)
# ---------------------------------------------------------------------------
# Two explicit lines rather than one glob: the catch-all's position is load-bearing, and
# an explicit ordering does not depend on how a given Apache build sorts wildcard matches.
inc_default="Include etc/extra/httpd-vhost-000-default.conf"
inc_sites="Include etc/extra/httpd-vhost-1*.conf"
if [ "$DEFAULT_SITE" -eq 1 ]; then
  # Glob covers httpd-vhost-000-<app>.conf; no separate catch-all line yet.
  inc_default="Include etc/extra/httpd-vhost-0*.conf"
fi
if [ -f "$HTTPD_CONF" ]; then
  if grep -Fq "$inc_sites" "$HTTPD_CONF"; then
    ok "httpd.conf already includes the vhost confs"
  else
    backup "$HTTPD_CONF"
    printf '\n# vhosts (newhost/provision-vhost.sh) — the 0* line MUST stay first: whatever it matches is\n# the fallback for an unmatched Host (the deny catch-all, or the --default-site app).\n%s\n%s\n' \
      "$inc_default" "$inc_sites" >> "$HTTPD_CONF"
    ok "added vhost Includes to httpd.conf"
  fi
else
  warn "$HTTPD_CONF not found; add these manually, in this order:"
  warn "  $inc_default"
  warn "  $inc_sites"
fi

# ---------------------------------------------------------------------------
# 4. Retire this app's server-level env conf (the migration step)
# ---------------------------------------------------------------------------
# Leaving it in place would keep a server-level SetEnv that overrides nothing for matched
# hosts but silently supplies a DB for unmatched ones — see Guard 1.
if [ -f "$OLD_ENV_CONF" ]; then
  log "Retiring server-level env conf -> $OLD_ENV_CONF"
  backup "$OLD_ENV_CONF"
  if [ -f "$HTTPD_CONF" ] && grep -Fq "httpd-$APP-env.conf" "$HTTPD_CONF"; then
    backup "$HTTPD_CONF"
    sed -i.bak "\#Include etc/extra/httpd-$APP-env.conf#d" "$HTTPD_CONF"
    rm -f "$HTTPD_CONF.bak"
    ok "removed 'Include etc/extra/httpd-$APP-env.conf' from httpd.conf"
  fi
  mv "$OLD_ENV_CONF" "$OLD_ENV_CONF.retired"
  ok "moved to $OLD_ENV_CONF.retired (backed up in $BACKUP_DIR)"
else
  ok "no server-level env conf for '$APP' to retire"
fi

# ---------------------------------------------------------------------------
# 5. Validate + apply
# ---------------------------------------------------------------------------
if [ -x "$LAMPP_ROOT/bin/httpd" ]; then
  if "$LAMPP_ROOT/bin/httpd" -t >/dev/null 2>&1; then
    ok "apache config test passed"
  else
    warn "apache config test FAILED — output follows; NOT restarting:"
    "$LAMPP_ROOT/bin/httpd" -t || true
    die "fix the config above (originals are in $BACKUP_DIR), then re-run."
  fi
fi

if [ "$SKIP_RESTART" -eq 0 ]; then
  log "Restarting LAMPP to apply vhost changes"
  "$LAMPP_ROOT/lampp" restart || warn "LAMPP restart failed; restart it manually."
else
  warn "skipping restart (--skip-restart) — changes are NOT live yet"
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
sites=""
for f in "$EXTRA_DIR"/httpd-vhost-1*.conf; do
  [ -e "$f" ] || continue
  sn="$(grep -E '^[[:space:]]*ServerName[[:space:]]+' "$f" | awk '{print $2}' | head -1)"
  db="$(grep -E '^[[:space:]]*SetEnv[[:space:]]+MYSQL_DATABASE_NAME[[:space:]]+' "$f" | awk '{print $3}' | head -1)"
  sites+="  $sn  ->  $db"$'\n'
done

cat <<SUMMARY

Vhost '$SERVER_NAME' provisioned for app '$APP'.  Backups: $BACKUP_DIR

Sites now on this box:
$sites
Verify:
  Right DB   : curl -s -H 'Host: $SERVER_NAME' http://127.0.0.1/TCGEngine/SharedUI/MainMenu.php | head
               (should render the site mapped to '$DB_NAME' in SharedUI/ActiveSite.php)
  Isolation  : log in on one hostname, then load the other — you must NOT be logged in there.
               (Browsers scope session cookies by host; that is what keeps the two
               \`users\` tables from being confused for each other.)
  Catch-all  : curl -s -o /dev/null -w '%{http_code}\\n' -H 'Host: nope.invalid' http://127.0.0.1/
               (expect 403 — an unmatched Host must never reach PHP)

Note: this box is now MULTI-SITE. Do not run provision-app.sh here — it writes a
server-level SetEnv that would become the silent default for unmatched hosts.
SUMMARY

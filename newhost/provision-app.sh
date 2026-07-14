#!/usr/bin/env bash
#
# provision-app.sh <APP> <DB_NAME> - PER-APP setup on a native XAMPP/LAMPP box.
#
# Run this once for each app cloned from the hardened snapshot (see harden-host.sh).
# It is re-runnable / idempotent. Two things:
#   1. Env vars  - Apache SetEnv so the app's getenv() resolves the right DB name.
#   2. Set up DB - DROP the leftover template db (STALE_DB, default "soulmastersdb"),
#                  then (re)create <DB_NAME> fresh and load the canonical schema
#                  (SCHEMA_SQL, default ../Database/database.sql). Ends empty + clean.
#
# The app reads DB config via getenv() in Database/ConnectionManager.php and Redis
# via Core/NetworkingLibraries.php. Apache (mod_php) exposes SetEnv values to getenv().
#
# Usage:
#   sudo ./provision-app.sh                 # defaults: app=swusim db=swusim
#   sudo ./provision-app.sh myapp myapp_db
#   sudo ./provision-app.sh swusim swusim --yes --skip-db
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Positional args + flags
# ---------------------------------------------------------------------------
APP=""; DB_NAME=""
SKIP_ENV=0; SKIP_DB=0; ASSUME_YES=0
for arg in "$@"; do
  case "$arg" in
    --skip-env) SKIP_ENV=1 ;;
    --skip-db)  SKIP_DB=1 ;;
    --yes|-y)   ASSUME_YES=1 ;;
    -h|--help)  grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    --*)        echo "Unknown option: $arg" >&2; exit 2 ;;
    *)          if [ -z "$APP" ]; then APP="$arg"; elif [ -z "$DB_NAME" ]; then DB_NAME="$arg"; fi ;;
  esac
done
APP="${APP:-swusim}"
DB_NAME="${DB_NAME:-swusim}"

# ---------------------------------------------------------------------------
# Config (override via environment)
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LAMPP_ROOT="${LAMPP_ROOT:-/opt/lampp}"
HTTPD_CONF="${HTTPD_CONF:-$LAMPP_ROOT/etc/httpd.conf}"
MYSQL_BIN="${MYSQL_BIN:-$LAMPP_ROOT/bin/mysql}"
ENV_CONF="$LAMPP_ROOT/etc/extra/httpd-$APP-env.conf"

MYSQL_HOST="${MYSQL_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"          # XAMPP root is passwordless by default
STALE_DB="${STALE_DB:-soulmastersdb}"          # leftover db from the restore, to be DROPPED
SCHEMA_SQL="${SCHEMA_SQL:-$SCRIPT_DIR/../Database/database.sql}"  # canonical schema to load
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

# mysql CLI arg builder (omit -p entirely when password is blank)
mysql_cli() {
  if [ -n "$DB_PASS" ]; then
    "$MYSQL_BIN" -h "$MYSQL_HOST" -u "$DB_USER" -p"$DB_PASS" "$@"
  else
    "$MYSQL_BIN" -h "$MYSQL_HOST" -u "$DB_USER" "$@"
  fi
}

db_exists() { mysql_cli -N -e "SHOW DATABASES LIKE '$1';" | grep -Fxq "$1"; }

# ---------------------------------------------------------------------------
# Preflight
# ---------------------------------------------------------------------------
[ "$(id -u)" -eq 0 ] || die "must run as root (sudo)."
[ -d "$LAMPP_ROOT" ] || die "LAMPP not found at $LAMPP_ROOT."
log "Provisioning app '$APP' (db '$DB_NAME') at $LAMPP_ROOT  (backups -> $BACKUP_DIR)"

# ---------------------------------------------------------------------------
# 1. Env vars (Apache SetEnv)
# ---------------------------------------------------------------------------
if [ "$SKIP_ENV" -eq 0 ]; then
  log "Writing env SetEnv conf -> $ENV_CONF"
  backup "$ENV_CONF"
  cat > "$ENV_CONF" <<CONF
# Managed by newhost/provision-app.sh for app: $APP
# Apache (mod_php) exposes these to PHP getenv().
SetEnv MYSQL_DATABASE_NAME    $DB_NAME
SetEnv MYSQL_SERVER_NAME      $MYSQL_HOST
SetEnv MYSQL_SERVER_USER_NAME $DB_USER
SetEnv MYSQL_ROOT_PASSWORD    $DB_PASS
SetEnv REDIS_HOST             $REDIS_HOST
SetEnv REDIS_PORT             $REDIS_PORT
CONF
  ok "wrote $ENV_CONF"

  # Ensure httpd.conf Includes it (guarded so re-runs don't duplicate).
  local_include="Include etc/extra/httpd-$APP-env.conf"
  if [ -f "$HTTPD_CONF" ]; then
    if grep -Fq "$local_include" "$HTTPD_CONF"; then
      ok "httpd.conf already includes the env conf"
    else
      backup "$HTTPD_CONF"
      printf '\n# app env vars (newhost/provision-app.sh)\n%s\n' "$local_include" >> "$HTTPD_CONF"
      ok "added Include to httpd.conf"
    fi
  else
    warn "$HTTPD_CONF not found; add '$local_include' to your Apache config manually"
  fi
else
  warn "skipping env vars (--skip-env)"
fi

# ---------------------------------------------------------------------------
# 2. Set up DB (drop stale, recreate target, load canonical schema)
# ---------------------------------------------------------------------------
if [ "$SKIP_DB" -eq 0 ]; then
  log "Setting up database '$DB_NAME' fresh from $SCHEMA_SQL"
  [ -x "$MYSQL_BIN" ] || die "mysql client not found at $MYSQL_BIN."
  [ -f "$SCHEMA_SQL" ] || die "schema file not found at $SCHEMA_SQL (set SCHEMA_SQL=...)."

  # Work out what we're about to destroy (for the confirmation prompt).
  drop_stale=0; drop_target=0
  if [ "$STALE_DB" != "$DB_NAME" ] && db_exists "$STALE_DB"; then drop_stale=1; fi
  if db_exists "$DB_NAME"; then drop_target=1; fi

  if [ "$ASSUME_YES" -eq 0 ]; then
    printf '\033[1;33mThis will:\033[0m\n'
    [ "$drop_stale" -eq 1 ]  && printf '  - DROP the leftover database "%s"\n' "$STALE_DB"
    [ "$drop_target" -eq 1 ] && printf '  - DROP the existing database "%s"\n' "$DB_NAME"
    printf '  - CREATE "%s" fresh and load %s\n' "$DB_NAME" "$SCHEMA_SQL"
    printf 'Type the app db name (%s) to confirm: ' "$DB_NAME"
    read -r reply
    [ "$reply" = "$DB_NAME" ] || die "confirmation mismatch; aborted."
  fi

  if [ "$drop_stale" -eq 1 ]; then
    mysql_cli -e "DROP DATABASE \`$STALE_DB\`;"
    ok "dropped leftover database '$STALE_DB'"
  fi
  mysql_cli -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4;"
  mysql_cli "$DB_NAME" < "$SCHEMA_SQL"
  tcount="$(mysql_cli -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_type='BASE TABLE';")"
  ok "created '$DB_NAME' and loaded schema ($tcount tables)"
else
  warn "skipping DB setup (--skip-db)"
fi

# ---------------------------------------------------------------------------
# Apply + summary
# ---------------------------------------------------------------------------
log "Restarting LAMPP to apply env changes"
"$LAMPP_ROOT/lampp" restart || warn "LAMPP restart failed; restart it manually."

cat <<SUMMARY

App '$APP' provisioned.  Backups: $BACKUP_DIR

Verify:
  DB name   : hit an app page and confirm it connects to '$DB_NAME'
  Schema    : $MYSQL_BIN -u $DB_USER ${DB_PASS:+-p} -e "SHOW TABLES FROM \`$DB_NAME\`;"
  No stale  : $MYSQL_BIN -u $DB_USER ${DB_PASS:+-p} -e "SHOW DATABASES LIKE '$STALE_DB';"   # expect empty
SUMMARY

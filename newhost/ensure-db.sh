#!/usr/bin/env bash
#
# ensure-db.sh — create app databases that don't exist yet. NEVER destructive.
#
# This is the SAFE counterpart to `provision-app.sh --reset-db`, which does
# `DROP DATABASE IF EXISTS ... CREATE DATABASE ...` and would WIPE a live database.
# Point that at an app that is already serving traffic and you lose the data.
#
# This script cannot do that. There is no DROP statement anywhere in it:
#   - database exists  -> reports its table count and LEAVES IT COMPLETELY ALONE
#   - database missing -> CREATE DATABASE + load Database/database.sql
#
# That makes it safe to name a live database alongside a new one, which is the whole
# point — preparing a box for a second sim means naming both, and you must not have to
# think hard about whether that is safe.
#
# DRY-RUN BY DEFAULT (same convention as the SET_NNN migration tooling). It prints the
# plan and changes nothing until you pass --apply.
#
# A fresh load from Database/database.sql is COMPLETE — it already contains the final
# definitions, including `users.discordID` + its unique key (migration 11) and
# `matchhistory` (migration 09). Database/migrations/*.sql are for upgrading EXISTING
# databases; do not apply them to a database this script just created.
#
# Usage:
#   sudo DB_PASS=... ./ensure-db.sh grandarchivesim hellbreaksim            # dry run
#   sudo DB_PASS=... ./ensure-db.sh grandarchivesim hellbreaksim --apply    # do it
#   sudo DB_PASS=... ./ensure-db.sh hellbreaksim --apply --schema /path/to/other.sql
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Args
# ---------------------------------------------------------------------------
DBS=(); APPLY=0; want_val=""
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SCHEMA_SQL="${SCHEMA_SQL:-$SCRIPT_DIR/../Database/database.sql}"

for arg in "$@"; do
  if [ -n "$want_val" ]; then
    case "$want_val" in schema) SCHEMA_SQL="$arg" ;; esac
    want_val=""; continue
  fi
  case "$arg" in
    --schema)  want_val="schema" ;;
    --apply)   APPLY=1 ;;
    -h|--help) grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    --*)       echo "Unknown option: $arg" >&2; exit 2 ;;
    *)         DBS+=("$arg") ;;
  esac
done
[ -z "$want_val" ] || { echo "Option --$want_val needs a value" >&2; exit 2; }
[ "${#DBS[@]}" -gt 0 ] || { echo "Usage: sudo DB_PASS=... $0 <db> [<db> ...] [--apply]" >&2; exit 2; }

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
LAMPP_ROOT="${LAMPP_ROOT:-/opt/lampp}"
MYSQL_BIN="${MYSQL_BIN:-$LAMPP_ROOT/bin/mysql}"
MYSQL_HOST="${MYSQL_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
ACTIVE_SITE="${ACTIVE_SITE:-$SCRIPT_DIR/../SharedUI/ActiveSite.php}"

log()  { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ok\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !!\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

mysql_cli() {
  if [ -n "$DB_PASS" ]; then
    "$MYSQL_BIN" -h "$MYSQL_HOST" -u "$DB_USER" -p"$DB_PASS" "$@"
  else
    "$MYSQL_BIN" -h "$MYSQL_HOST" -u "$DB_USER" "$@"
  fi
}
db_exists()    { mysql_cli -N -e "SHOW DATABASES LIKE '$1';" | grep -Fxq "$1"; }
table_count()  { mysql_cli -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$1' AND table_type='BASE TABLE';"; }

# ---------------------------------------------------------------------------
# Preflight — fail before touching anything
# ---------------------------------------------------------------------------
[ "$(id -u)" -eq 0 ] || die "must run as root (sudo)."
[ -x "$MYSQL_BIN" ]  || die "mysql client not found at $MYSQL_BIN."
[ -n "$DB_PASS" ]    || die "DB_PASS is required — pass DB_PASS=... (a passwordless DB is not allowed)."
[ -f "$SCHEMA_SQL" ] || die "schema file not found at $SCHEMA_SQL (set --schema/SCHEMA_SQL)."
mysql_cli -e "SELECT 1;" >/dev/null 2>&1 || die "cannot connect to MySQL as '$DB_USER' — check DB_PASS."

# Reject anything that isn't a plain identifier. These names are interpolated into SQL,
# and a typo'd/quoted name is far more likely than an attack — either way, refuse it.
for db in "${DBS[@]}"; do
  echo "$db" | grep -Eq '^[A-Za-z0-9_]+$' \
    || die "database name '$db' is not a plain identifier ([A-Za-z0-9_]+) — refusing."
done

# The app resolves BOTH the DB connection and the rendered site from MYSQL_DATABASE_NAME
# (SharedUI/ActiveSite.php). A database whose name is not in that map throws at runtime
# rather than falling back — so a typo here surfaces as a site-wide 500, not a default page.
# Warn now, when it costs nothing to fix.
if [ -f "$ACTIVE_SITE" ]; then
  for db in "${DBS[@]}"; do
    grep -Eq "^[[:space:]]*'$db'[[:space:]]*=>" "$ACTIVE_SITE" \
      || warn "'$db' is not in the \$dbToSite map in SharedUI/ActiveSite.php — ActiveSite will THROW for it. Add it before serving that hostname."
  done
else
  warn "could not read $ACTIVE_SITE — skipping the site-map check."
fi

[ "$APPLY" -eq 1 ] || log "DRY RUN — nothing will be changed. Re-run with --apply to execute."
log "Schema for new databases: $SCHEMA_SQL"

# ---------------------------------------------------------------------------
# Per-database: create only if absent
# ---------------------------------------------------------------------------
created=(); skipped=()
for db in "${DBS[@]}"; do
  if db_exists "$db"; then
    # Existing database: report and move on. Nothing below this line touches it.
    ok "'$db' already exists ($(table_count "$db") tables) — leaving it untouched."
    skipped+=("$db")
    continue
  fi

  if [ "$APPLY" -eq 0 ]; then
    log "WOULD CREATE '$db' (utf8mb4) and load $(basename "$SCHEMA_SQL")"
    created+=("$db")
    continue
  fi

  log "Creating '$db' (utf8mb4)"
  mysql_cli -e "CREATE DATABASE \`$db\` CHARACTER SET utf8mb4;"
  mysql_cli "$db" < "$SCHEMA_SQL"
  n="$(table_count "$db")"
  [ "$n" -gt 0 ] || die "'$db' was created but the schema load produced 0 tables — check $SCHEMA_SQL."
  ok "created '$db' and loaded schema ($n tables)"
  created+=("$db")
done

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo
if [ "$APPLY" -eq 0 ]; then
  printf 'DRY RUN. Would create: %s\n' "${created[*]:-(none)}"
  printf 'Already present, untouched: %s\n' "${skipped[*]:-(none)}"
  printf '\nRe-run with --apply to execute.\n'
else
  printf 'Created: %s\n' "${created[*]:-(none)}"
  printf 'Already present, untouched: %s\n' "${skipped[*]:-(none)}"
  cat <<SUMMARY

Verify:
  $MYSQL_BIN -u $DB_USER -p -e "SHOW DATABASES;"
  $MYSQL_BIN -u $DB_USER -p -e "SHOW TABLES FROM \`${DBS[0]}\`;"

Note: creating a database does NOT make a site serve it. The hostname -> DB binding is the
vhost's SetEnv MYSQL_DATABASE_NAME (see provision-vhost.sh / VHOST-migration-runbook.md).
SUMMARY
fi

#!/usr/bin/env bash
#
# backup-db.sh - nightly backup of a TCGEngine box: every site database + the secrets the app
# cannot be rebuilt without, rotated locally and (optionally) copied OFF the box.
#
# Why off the box: on 2026-10-21 the host locked the production server with no notice, and the
# only copy of every account, rating and stat was on it. A backup on the same disk shares the
# same fate; set BACKUP_RCLONE_REMOTE.
#
# Each run writes  $BACKUP_DIR/<UTC timestamp>/
#   <db>.sql.gz     mysqldump --single-transaction (consistent, no table locks on InnoDB)
#   secrets.tgz     APIKeys/APIKeys.php, SWUDeck/SimulationConfig.local.php, the site vhosts
#                   (they carry the DB password and per-site SetEnv secrets)
#   SHA256SUMS
# then deletes local runs older than BACKUP_KEEP_DAYS and, with a remote set, `rclone copy`s the
# run to <remote>/<hostname>/<timestamp>/. Exit status is non-zero if ANY part failed, so cron
# mail / the log shows it.
#
# Config: /etc/tcgengine/backup.conf (written by setup-box.sh), overridable by environment:
#   BACKUP_DBS="swusim swudeck"   BACKUP_DIR=/var/backups/tcgengine   BACKUP_KEEP_DAYS=14
#   BACKUP_RCLONE_REMOTE="b2:bucket/tcgengine"   APP_ROOT=/var/www/html/TCGEngine
#
# Usage:
#   sudo ./backup-db.sh                        # uses /etc/tcgengine/backup.conf
#   sudo ./backup-db.sh --config /path/to.conf
#
# Restore one database (the dump carries CREATE DATABASE / USE, so it recreates it by name):
#   gunzip -c <run>/<db>.sql.gz | mysql
# or let setup-box.sh do it: point RESTORE_DIR at the run directory (it restores only EMPTY dbs).
#
set -euo pipefail

CONFIG="/etc/tcgengine/backup.conf"
want=""
for arg in "$@"; do
  if [ -n "$want" ]; then CONFIG="$arg"; want=""; continue; fi
  case "$arg" in
    --config)  want=config ;;
    -h|--help) grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed '/^!/d'; exit 0 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done
[ -z "$want" ] || { echo "--config needs a path" >&2; exit 2; }

# Environment wins over the file: remember what was set before sourcing it.
_env_dbs="${BACKUP_DBS:-}"; _env_dir="${BACKUP_DIR:-}"; _env_keep="${BACKUP_KEEP_DAYS:-}"
_env_remote="${BACKUP_RCLONE_REMOTE:-}"; _env_root="${APP_ROOT:-}"
# shellcheck disable=SC1090
[ -f "$CONFIG" ] && . "$CONFIG"
BACKUP_DBS="${_env_dbs:-${BACKUP_DBS:-}}"
BACKUP_DIR="${_env_dir:-${BACKUP_DIR:-/var/backups/tcgengine}}"
BACKUP_KEEP_DAYS="${_env_keep:-${BACKUP_KEEP_DAYS:-14}}"
BACKUP_RCLONE_REMOTE="${_env_remote:-${BACKUP_RCLONE_REMOTE:-}}"
APP_ROOT="${_env_root:-${APP_ROOT:-/var/www/html/TCGEngine}}"

log()  { printf '%s  %s\n' "$(date -u +%FT%TZ)" "$*"; }
fail() { log "FAIL: $*"; FAILED=$((FAILED+1)); }
FAILED=0

[ "$(id -u)" -eq 0 ] || { echo "must run as root" >&2; exit 1; }
[ -n "$BACKUP_DBS" ] || { echo "BACKUP_DBS is empty (set it in $CONFIG)" >&2; exit 2; }
echo "$BACKUP_KEEP_DAYS" | grep -Eq '^[0-9]+$' || { echo "BACKUP_KEEP_DAYS must be a number" >&2; exit 2; }

DUMP="$(command -v mariadb-dump || command -v mysqldump || true)"
[ -n "$DUMP" ] || { echo "neither mariadb-dump nor mysqldump found" >&2; exit 1; }

umask 077
TS="$(date -u +%Y%m%d-%H%M%S)"
DEST="$BACKUP_DIR/$TS"
mkdir -p "$DEST"
log "backup $TS -> $DEST"

# 1. Databases. root over the unix socket (no password on the command line or in a file).
for db in $BACKUP_DBS; do
  echo "$db" | grep -Eq '^[A-Za-z0-9_]+$' || { fail "'$db' is not a plain identifier"; continue; }
  out="$DEST/$db.sql.gz"
  # pipefail makes the pipeline fail when the DUMP fails, not just gzip. stderr is kept for the
  # message only: a deprecation warning there is not a failure.
  if "$DUMP" --single-transaction --quick --routines --triggers --events \
       --default-character-set=utf8mb4 --databases "$db" 2>"$DEST/$db.err" | gzip -6 > "$out" \
     && gzip -t "$out" 2>/dev/null && gzip -dc "$out" | grep -c 'CREATE TABLE' >/dev/null; then
    rm -f "$DEST/$db.err"
    log "  ok  $db  ($(du -h "$out" | cut -f1))"
  else
    fail "$db dump failed or has no tables: $(head -c 400 "$DEST/$db.err" 2>/dev/null)"
  fi
done

# 2. Secrets: the files a fresh clone cannot recreate.
secret_files=()
for rel in APIKeys/APIKeys.php SWUDeck/SimulationConfig.local.php; do
  [ -f "$APP_ROOT/$rel" ] && secret_files+=("$APP_ROOT/$rel")
done
for f in /etc/apache2/sites-available/1*-*.conf /etc/tcgengine/*.conf; do
  [ -f "$f" ] && secret_files+=("$f")
done
if [ "${#secret_files[@]}" -gt 0 ]; then
  if tar -czf "$DEST/secrets.tgz" "${secret_files[@]}" 2>/dev/null; then
    log "  ok  secrets.tgz  (${#secret_files[@]} files)"
  else
    fail "secrets.tgz"
  fi
else
  log "  --  no secret files found to archive"
fi

# 2b. Petranaki's own bug reports (LocalBugReportLib.php) live on disk first; the database only indexes them.
if [ -d "$APP_ROOT/SWUSim/BugReports" ]; then
  if tar -czf "$DEST/bugreports.tgz" -C "$APP_ROOT/SWUSim" BugReports 2>/dev/null; then
    log "  ok  bugreports.tgz  ($(du -h "$DEST/bugreports.tgz" | cut -f1))"
  else
    fail "bugreports.tgz"
  fi
fi

( cd "$DEST" && sha256sum -- * > SHA256SUMS )

# 3. Rotate local runs (only our own timestamped dirs).
find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -name '20[0-9][0-9][01][0-9][0-3][0-9]-*' \
     -mtime "+$BACKUP_KEEP_DAYS" -print -exec rm -rf {} + | sed 's/^/  rotated /' || true

# 4. Off-box copy.
if [ -n "$BACKUP_RCLONE_REMOTE" ]; then
  if ! command -v rclone >/dev/null 2>&1; then
    fail "BACKUP_RCLONE_REMOTE is set but rclone is not installed"
  elif rclone copy "$DEST" "$BACKUP_RCLONE_REMOTE/$(hostname -s)/$TS" --transfers 2 --retries 3 2>&1 | sed 's/^/  rclone: /'; then
    log "  ok  copied off-box -> $BACKUP_RCLONE_REMOTE/$(hostname -s)/$TS"
  else
    fail "rclone copy to $BACKUP_RCLONE_REMOTE"   # pipefail: rclone's own exit status
  fi
else
  log "  !!  no BACKUP_RCLONE_REMOTE: this backup exists ONLY on this box"
fi

if [ "$FAILED" -gt 0 ]; then
  log "backup $TS finished with $FAILED failure(s)"
  exit 1
fi
log "backup $TS ok"

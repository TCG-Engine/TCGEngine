#!/usr/bin/env bash
#
# Backup the local SWUDeck MySQL database from its Docker container.
#
# The `swudeck` database runs inside the swustats-mysql-server container
# (see docker-compose.yml). This dumps it to a timestamped, gzipped .sql
# file under DevTools/local/_backups/ (gitignored).
#
# Usage:
#   ./DevTools/backup-swudeck-db.sh [output-dir]
#
# Env overrides:
#   CONTAINER  container name        (default: otmtcge-swustats-mysql-server-1)
#   DB         database name         (default: swudeck)
#   DB_USER    mysql user            (default: root)
#   DB_PASS    mysql password        (default: secret)
#   KEEP       backups to retain     (default: 10, set 0 to keep all)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

CONTAINER="${CONTAINER:-otmtcge-swustats-mysql-server-1}"
DB="${DB:-swudeck}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-secret}"
KEEP="${KEEP:-10}"

# Default to the gitignored local backups dir (DevTools/local/_backups/).
OUT_DIR="${1:-$SCRIPT_DIR/_backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_FILE="${OUT_DIR}/swudeck-${STAMP}.sql.gz"

mkdir -p "$OUT_DIR"

# Confirm the container is running before we try to exec into it.
if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
  echo "ERROR: container '$CONTAINER' is not running." >&2
  echo "Running mysql containers:" >&2
  docker ps --format '{{.Names}}' | grep -i mysql >&2 || true
  exit 1
fi

echo "Dumping '$DB' from '$CONTAINER' -> $OUT_FILE"

# --single-transaction: consistent snapshot without locking (InnoDB)
# --routines --triggers --events: include stored programs
# MYSQL_PWD avoids the password showing up in the container's process list.
docker exec -e MYSQL_PWD="$DB_PASS" "$CONTAINER" \
  mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --databases "$DB" \
    -u "$DB_USER" \
  | gzip > "$OUT_FILE"

# mysqldump writes to stdout; if it failed, pipefail aborts before this line.
SIZE="$(du -h "$OUT_FILE" | cut -f1)"
echo "Done: $OUT_FILE ($SIZE)"

# Prune old backups, keeping the newest $KEEP.
if [ "$KEEP" -gt 0 ]; then
  mapfile -t OLD < <(ls -1t "${OUT_DIR}"/swudeck-*.sql.gz 2>/dev/null | tail -n +"$((KEEP + 1))")
  if [ "${#OLD[@]}" -gt 0 ]; then
    echo "Pruning ${#OLD[@]} old backup(s) (keeping newest $KEEP):"
    for f in "${OLD[@]}"; do
      echo "  rm $f"
      rm -f "$f"
    done
  fi
fi

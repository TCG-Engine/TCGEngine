#!/usr/bin/env bash
#
# Restore the local SWUDeck MySQL database into its Docker container.
#
# Partner to backup-swudeck-db.sh. Feeds a gzipped dump produced by that
# script back into the swustats-mysql-server container. The dumps use
# `--databases swudeck`, so they carry their own CREATE DATABASE / USE
# statements — no target DB needs to be specified here.
#
# THIS IS DESTRUCTIVE: it overwrites the current `swudeck` database.
#
# Usage:
#   ./DevTools/local/restore-swudeck-db.sh [backup-file]
#
#   With no argument, restores the newest swudeck-*.sql.gz in _backups/.
#   Accepts either a .sql.gz or a plain .sql file.
#
# Env overrides:
#   CONTAINER  container name   (default: otmtcge-swustats-mysql-server-1)
#   DB_USER    mysql user       (default: root)
#   DB_PASS    mysql password   (default: secret)
#   FORCE      skip confirmation prompt when set to 1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

CONTAINER="${CONTAINER:-otmtcge-swustats-mysql-server-1}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-secret}"
BACKUP_DIR="${SCRIPT_DIR}/_backups"

# Resolve the backup file: explicit arg, else newest in _backups/.
BACKUP_FILE="${1:-}"
if [ -z "$BACKUP_FILE" ]; then
  BACKUP_FILE="$(ls -1t "${BACKUP_DIR}"/swudeck-*.sql.gz 2>/dev/null | head -n1 || true)"
  if [ -z "$BACKUP_FILE" ]; then
    echo "ERROR: no backup given and none found in ${BACKUP_DIR}/" >&2
    exit 1
  fi
fi

if [ ! -f "$BACKUP_FILE" ]; then
  echo "ERROR: backup file not found: $BACKUP_FILE" >&2
  exit 1
fi

# Confirm the container is running before we try to exec into it.
if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
  echo "ERROR: container '$CONTAINER' is not running." >&2
  echo "Running mysql containers:" >&2
  docker ps --format '{{.Names}}' | grep -i mysql >&2 || true
  exit 1
fi

SIZE="$(du -h "$BACKUP_FILE" | cut -f1)"
echo "About to restore into '$CONTAINER':"
echo "  from: $BACKUP_FILE ($SIZE)"
echo "  This OVERWRITES the current 'swudeck' database."

if [ "${FORCE:-}" != "1" ]; then
  read -r -p "Type 'yes' to proceed: " REPLY
  if [ "$REPLY" != "yes" ]; then
    echo "Aborted."
    exit 1
  fi
fi

# Decompress .gz on the fly; pass plain .sql straight through.
case "$BACKUP_FILE" in
  *.gz) DECOMP=(gzip -dc) ;;
  *)    DECOMP=(cat) ;;
esac

echo "Restoring..."
# MYSQL_PWD avoids the password showing up in the container's process list.
"${DECOMP[@]}" "$BACKUP_FILE" \
  | docker exec -i -e MYSQL_PWD="$DB_PASS" "$CONTAINER" \
      mysql -u "$DB_USER"

echo "Done: restored '$CONTAINER' from $(basename "$BACKUP_FILE")"

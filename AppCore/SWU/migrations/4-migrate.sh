#!/bin/bash
# SET_NNN identity migration — backup, then migrate.
#
#   ./4-migrate.sh --db=swudeck --backup-dir=/var/backups/swustats           # dry run
#   ./4-migrate.sh --db=swudeck --backup-dir=/var/backups/swustats --apply   # for real
#
# DRY RUN IS THE DEFAULT. It takes the backup, builds every _new table, and runs every assertion —
# on real data, with real timings — but never swaps, and drops the _new tables at the end. That is a
# genuine rehearsal, not a syntax check, and it is the measurement the window is sized from.
#
# --apply additionally runs 03_swap.sql. Nothing else differs, so a clean dry run is real evidence.
#
# What this does NOT do, deliberately:
#   * rewrite deck gamestate files (spec §5) — separate tool, separate step, separate rollback
#   * drop any *_old table — that is next weekend's job, and it is what makes rollback possible
#   * touch prod unless you point --db at it
#
# Rollback while the *_old tables exist:  RENAME TABLE x TO x_new, x_old TO x;   (per table)
# Rollback after they are dropped:        restore the dump this script took.
#
# Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §6, §9

set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

DB=""
BACKUP_DIR=""
APPLY=0
SKIP_BACKUP=0
MYSQL_BIN="${MYSQL_BIN:-${MYSQL:-mysql}}"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-${MYSQLDUMP:-mysqldump}}"

for arg in "$@"; do
  case "$arg" in
    --db=*)         DB="${arg#--db=}" ;;
    --backup-dir=*) BACKUP_DIR="${arg#--backup-dir=}" ;;
    --apply)        APPLY=1 ;;
    --skip-backup)  SKIP_BACKUP=1 ;;
    -h|--help)      sed -n '2,25p' "${BASH_SOURCE[0]}"; exit 0 ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done

[ -n "$DB" ] || { echo "FATAL: --db=<database> is required." >&2; exit 2; }
if [ "$SKIP_BACKUP" -eq 0 ] && [ -z "$BACKUP_DIR" ]; then
  echo "FATAL: --backup-dir=<dir> is required (or pass --skip-backup, which you should not)." >&2
  exit 2
fi

# Credentials come from a defaults file, never the command line — an inline -p exposes the password
# in `ps` to every user on the box. MYCNF is exported by the runbook's §0 environment block.
MYCNF="${MYCNF:-}"
MY=("$MYSQL_BIN")
MYDUMP=("$MYSQLDUMP_BIN")
if [ -n "$MYCNF" ]; then MY+=("$MYCNF"); MYDUMP+=("$MYCNF"); fi

say()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
ok()   { printf '   \033[32mok\033[0m   %s\n' "$*"; }
warn() { printf '   \033[33mwarn\033[0m %s\n' "$*"; }
die()  { printf '\n\033[31mFATAL\033[0m %s\n\n' "$*" >&2; exit 1; }

sql()      { "${MY[@]}" -N -B "$DB" -e "$1"; }
sql_file() { "${MY[@]}" "$DB" < "$1"; }

# ─────────────────────────────────────────────────────────────────────────────
say "0. Preflight"

"${MY[@]}" -N -B -e "SELECT 1" >/dev/null 2>&1 || die "cannot connect. Is MYCNF set? (see the runbook §0)"
ok "database reachable"

EXISTS=$(sql "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$DB';" 2>/dev/null)
[ "${EXISTS:-0}" = "1" ] || die "database '$DB' does not exist"
ok "database '$DB' exists"

for f in 01_id_map.sql 02_rekey_stats.sql 03_swap.sql; do
  [ -s "$HERE/$f" ] || die "$HERE/$f is missing or empty.
  01_id_map.sql      <- php tools/materialize-id-map.php > 01_id_map.sql
  02/03              <- php tools/build-rekey-sql.php --out=$HERE
Both must be regenerated on THIS box after any card-dictionary or schema change."
done
ok "migration files present"

# The id map is generated from the card dictionaries. If it is stale relative to them, the migration
# maps cards to identities the dictionaries no longer agree with — silently.
DICT="$HERE/../../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php"
if [ -f "$DICT" ] && [ "$DICT" -nt "$HERE/01_id_map.sql" ]; then
  die "01_id_map.sql is OLDER than the card dictionary it was built from.
Regenerate it:  php $HERE/tools/materialize-id-map.php > $HERE/01_id_map.sql"
fi
ok "id map is not older than the card dictionary"

DB_MB=$(sql "SELECT ROUND(SUM(DATA_LENGTH+INDEX_LENGTH)/1024/1024) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB';")
ok "database size: ${DB_MB} MB"

# The rebuild holds the old and new copies of a table at once, and the dump lands beside them.
# Create the directory HERE, not in step 1: df on a path that does not exist yet returns nothing,
# and the check would silently pass by printing "? MB free" — which it did.
if [ "$SKIP_BACKUP" -eq 0 ]; then
  mkdir -p "$BACKUP_DIR" || die "cannot create $BACKUP_DIR"
  AVAIL_MB=$(df -Pm "$BACKUP_DIR" 2>/dev/null | awk 'NR==2{print $4+0}')
  [ -n "${AVAIL_MB:-}" ] && [ "$AVAIL_MB" -gt 0 ] \
    || die "cannot determine free space on $BACKUP_DIR. Check it by hand before proceeding."
  if [ "$AVAIL_MB" -lt "$DB_MB" ]; then
    die "backup dir has ${AVAIL_MB} MB free, database is ${DB_MB} MB. Free space or pick another dir."
  fi
  ok "backup dir has ${AVAIL_MB} MB free (database is ${DB_MB} MB)"
fi

# ─────────────────────────────────────────────────────────────────────────────
say "1. Backup"

if [ "$SKIP_BACKUP" -eq 1 ]; then
  warn "SKIPPED by --skip-backup. There is no rollback path if this run goes wrong."
else
  STAMP=$(date +%Y%m%d-%H%M%S)
  DUMP="$BACKUP_DIR/$DB-$STAMP.sql.gz"

  echo "   dumping to $DUMP ..."
  # --single-transaction keeps InnoDB consistent without locking writers out.
  if ! "${MYDUMP[@]}" --single-transaction --routines --triggers "$DB" | gzip > "$DUMP"; then
    die "mysqldump failed. Do not proceed."
  fi

  # A dump that silently failed is still a file — verify it, do not just check it exists.
  [ -s "$DUMP" ] || die "$DUMP is empty"
  gunzip -t "$DUMP" 2>/dev/null || die "$DUMP is not a valid gzip — the dump is corrupt"
  ok "gzip integrity verified"

  # ...and verify it actually contains the tables we are about to rewrite. An empty-but-valid dump
  # of the wrong database passes every check above.
  MISSING=""
  for t in $(sql "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME IN ('carddeckstats','cardmetastats','deckmetastats','deckmetamatchupstats','opponentdeckstats','opponentnamedbasestats','completedgame');"); do
    if ! zgrep -q "CREATE TABLE \`$t\`" "$DUMP"; then MISSING="$MISSING $t"; fi
  done
  [ -z "$MISSING" ] || die "the dump is missing CREATE TABLE for:$MISSING"
  ok "dump contains every target table"
  ok "backup complete: $(du -h "$DUMP" | cut -f1)  $DUMP"
fi

# ─────────────────────────────────────────────────────────────────────────────
say "2. Load the id map"

START=$(date +%s)
sql_file "$HERE/01_id_map.sql" || die "01_id_map.sql failed"
MAP_ROWS=$(sql "SELECT COUNT(*) FROM swu_id_map;")
MAP_TOK=$(sql "SELECT COUNT(*) FROM swu_id_map WHERE oldID REGEXP '^[A-Z0-9]{2,5}_T[0-9]{2}\$';")
ok "swu_id_map loaded: $MAP_ROWS rows, $MAP_TOK token id(s)"

# Tokens are 94% of class 3 on prod. A map without them means ~54k rows are about to be dropped as
# unresolvable — the exact outcome the token-inclusion change exists to prevent.
if [ "${MAP_TOK:-0}" -lt 10 ]; then
  die "only ${MAP_TOK} token id(s) in the map.
Tokens are 94% of unresolvable stat rows on prod (13 ids, 54,312 rows). Migrating now would DROP
them. Regenerate SWUDeck's dictionary with withPreview=1 (a live re-fetch — the cached card array
predates token inclusion), then rebuild 01_id_map.sql."
fi

# ─────────────────────────────────────────────────────────────────────────────
say "3. Build and verify the new tables"
echo "   02_rekey_stats.sql aborts on schema drift and on any counter mismatch."
echo "   The live tables are NOT modified by this step."

if ! sql_file "$HERE/02_rekey_stats.sql"; then
  die "02_rekey_stats.sql FAILED — see the error above.
The live tables are untouched. The *_new tables are left in place for inspection; drop them when done:
  ${MY[*]} $DB -e \"SHOW TABLES LIKE '%_new';\""
fi
ok "all tables rebuilt and every assertion passed"

# ─────────────────────────────────────────────────────────────────────────────
if [ "$APPLY" -eq 0 ]; then
  say "4. Dry run — rolling back"
  for t in $(sql "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME LIKE '%\\_new';"); do
    sql "DROP TABLE \`$t\`;" && echo "   dropped $t"
  done
  sql "DROP TABLE IF EXISTS swu_id_map;"
  ELAPSED=$(( $(date +%s) - START ))
  say "DRY RUN COMPLETE in ${ELAPSED}s"
  echo "   Nothing was changed. That elapsed time is what the maintenance window must accommodate,"
  echo "   plus the deck-file rewrite, which this script does not run."
  echo "   Re-run with --apply to migrate for real."
  exit 0
fi

say "4. Swap"
if ! sql_file "$HERE/03_swap.sql"; then
  die "03_swap.sql FAILED. Some tables may have swapped and others not — check:
  ${MY[*]} $DB -e \"SHOW TABLES LIKE '%\\_old';\"
Roll back each swapped table with:  RENAME TABLE x TO x_new, x_old TO x;"
fi
ELAPSED=$(( $(date +%s) - START ))
ok "swap complete"

say "MIGRATION APPLIED in ${ELAPSED}s"
sql "SELECT TABLE_NAME AS kept_for_rollback FROM information_schema.TABLES
      WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME LIKE '%\\_old' ORDER BY TABLE_NAME;"
echo
echo "   Still to do, in order:"
echo "     1. rewrite the deck gamestate files (spec §5) — NOT run by this script"
echo "     2. smoke-test per the runbook §2.8"
echo "     3. restore service"
echo "     4. keep every *_old table and the dump until NEXT weekend, then drop them"
echo
echo "   Rollback until then:  RENAME TABLE x TO x_new, x_old TO x;   (per table)"

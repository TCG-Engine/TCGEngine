#!/bin/bash
# SET_NNN identity migration — ROLLBACK.
#
#   ./9-rollback.sh --db=swudeck --backup-dir=/var/backups/swustats-XXXX          # dry run
#   ./9-rollback.sh --db=swudeck --backup-dir=/var/backups/swustats-XXXX --apply
#
# DRY RUN IS THE DEFAULT. It reports exactly what it would undo and changes nothing.
#
# ── It DETECTS state rather than assuming it ─────────────────────────────────
# The dangerous case is not "the migration ran" — it is "the migration ran HALFWAY". 03_swap.sql
# renames table by table, so an abort midway leaves some tables swapped and some not, and a script
# that blindly renames everything would swap the untouched ones FORWARD into a broken state. So
# this inspects information_schema per table and acts only where an `_old` actually exists.
#
# ── Order matters: undo in reverse ───────────────────────────────────────────
#   1. deck files   (restored from the §2.2 archive)
#   2. database     (RENAME the _old tables back)
# Doing the database first would leave a window where SET_NNN deck files are read against
# UUID-keyed tables. Nothing should be serving during a rollback anyway — keep maintenance ON
# until the very end.
#
# ── What this does NOT do ────────────────────────────────────────────────────
#   * redeploy the previous commit — do that yourself, and do it BEFORE turning maintenance off,
#     or the new code will run against restored old-shape data
#   * turn maintenance off — deliberate; you decide when service resumes
#   * DROP anything. Every rename is reversible, and the archive is left in place.
#
# After the *_old tables are dropped (runbook §2.10) this script can no longer help you: at that
# point the only rollback is restoring the mysqldump.
#
# Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §9

set -uo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

DB=""; BACKUP_DIR=""; APPLY=0; DO_DECKS=1; DO_DB=1; ROOT_DIR="${ROOT:-}"
for arg in "$@"; do
  case "$arg" in
    --db=*)         DB="${arg#--db=}" ;;
    --backup-dir=*) BACKUP_DIR="${arg#--backup-dir=}" ;;
    --root=*)       ROOT_DIR="${arg#--root=}" ;;
    --apply)        APPLY=1 ;;
    --db-only)      DO_DECKS=0 ;;
    --decks-only)   DO_DB=0 ;;
    -h|--help)      sed -n '2,12p' "${BASH_SOURCE[0]}"; exit 0 ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done
if [ "$DO_DB" -eq 1 ] && [ -z "$DB" ]; then
  echo "FATAL: --db=<database> is required (or pass --decks-only)." >&2; exit 2
fi
[ -n "$BACKUP_DIR" ] || { echo "FATAL: --backup-dir=<dir> is required." >&2; exit 2; }
ROOT_DIR="${ROOT_DIR:-/opt/lampp/htdocs/TCGEngine}"

MYCNF="${MYCNF:-}"
MY=("${MYSQL_BIN:-${MYSQL:-mysql}}"); [ -n "$MYCNF" ] && MY+=("$MYCNF")
sql() { "${MY[@]}" -N -B "$DB" -e "$1"; }

say()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
ok()   { printf '   \033[32mok\033[0m   %s\n' "$*"; }
warn() { printf '   \033[33mwarn\033[0m %s\n' "$*"; }
act()  { printf '   \033[36m->\033[0m   %s\n' "$*"; }
die()  { printf '\n\033[31mFATAL\033[0m %s\n\n' "$*" >&2; exit 1; }

[ "$APPLY" -eq 1 ] || printf '\n\033[33m*** DRY RUN — nothing will be changed. Add --apply to execute. ***\033[0m\n'

# ─────────────────────────────────────────────────────────────────────────────
SWAPPED=(); UNSWAPPED=(); ORPHAN_NEW=(); NOT_A_TARGET=()

# --decks-only must NOT require a database. Restoring deck files is a filesystem operation, and in
# a real emergency the database may be precisely what is unavailable — demanding a connection to do
# it would block the recovery at the worst moment.
if [ "$DO_DB" -eq 0 ]; then
  say "0. Deck files only — skipping database inspection"
  ok "no database connection needed for --decks-only"
else
say "0. What state is this database in?"
"${MY[@]}" -N -B -e "SELECT 1" >/dev/null 2>&1 || die "cannot connect. Is MYCNF set?"
for t in carddeckstats cardmetastats deckstats opponentdeckstats opponentnamedbasestats \
         deckmetastats deckmetamatchupstats completedgame favoritedeck meleetournamentdeck; do
  has=$(sql "SELECT GROUP_CONCAT(TABLE_NAME) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME IN ('$t','${t}_old','${t}_new');")
  case ",$has," in
    *"${t}_old"*) SWAPPED+=("$t") ;;
    *"${t}_new"*) ORPHAN_NEW+=("$t") ;;
    *"$t"*)
      # A table with neither _old nor _new was either skipped by 03, or was never a TARGET at all.
      # Distinguish them, or the partial-swap warning cries wolf: on this prod schema `deckstats`
      # has no leaderID column, so build-rekey-sql.php correctly emits no step for it — and calling
      # that a partial swap during a real rollback is exactly the wrong moment to hesitate.
      idcol=$(sql "SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME='$t'
                      AND COLUMN_NAME IN ('cardID','leaderID','baseID','opponentLeaderID',
                                          'opponentBaseID','WinningHero','LosingHero','hero','leader','base');")
      if [ "${idcol:-0}" -gt 0 ]; then UNSWAPPED+=("$t"); else NOT_A_TARGET+=("$t"); fi ;;
  esac
done

printf "   swapped (has _old, will roll back): %d\n" "${#SWAPPED[@]}"
[ ${#SWAPPED[@]} -gt 0 ] && printf "      %s\n" "${SWAPPED[*]}"
printf "   never swapped (leave alone):        %d\n" "${#UNSWAPPED[@]}"
[ ${#NOT_A_TARGET[@]} -gt 0 ] && printf "   not a migration target at all:      %d  (%s)\n" "${#NOT_A_TARGET[@]}" "${NOT_A_TARGET[*]}"
printf "   built but not swapped (_new left):  %d\n" "${#ORPHAN_NEW[@]}"
[ ${#ORPHAN_NEW[@]} -gt 0 ] && printf "      %s  <- 02 ran, 03 did not. Nothing to undo; drop these when done.\n" "${ORPHAN_NEW[*]}"

# A partial swap shows up EITHER as some tables never built (03 aborted before 02 finished) OR as
# some built-but-not-swapped (03 aborted midway, the common case). Both mean the same thing.
if [ ${#SWAPPED[@]} -gt 0 ] && { [ ${#UNSWAPPED[@]} -gt 0 ] || [ ${#ORPHAN_NEW[@]} -gt 0 ]; }; then
  warn "PARTIAL SWAP — 03_swap.sql aborted midway. Rolling back only the tables that actually"
  warn "swapped is correct: the rest still hold their original data and must NOT be touched."
fi
fi   # end: database inspection

# ─────────────────────────────────────────────────────────────────────────────
if [ "$DO_DECKS" -eq 1 ]; then
say "1. Deck files"
# Match BOTH the fixed names 2-snapshot.sh writes and the timestamped form an ad-hoc tar may use.
# These two scripts disagreed once — 2-snapshot wrote swudeck-gamestates.tar.gz while this globbed
# swudeck-gamestates-*.tar.gz — so a slim-only backup was silently skipped behind a warning. Prefer
# the FULL archive when both exist: it restores deck images too.
ARCHIVE=""
for c in "$BACKUP_DIR"/swudeck-games.tar.gz "$BACKUP_DIR"/swudeck-games-*.tar.gz \
         "$BACKUP_DIR"/swudeck-gamestates.tar.gz "$BACKUP_DIR"/swudeck-gamestates-*.tar.gz; do
  case "$c" in *.sha256) continue ;; esac
  [ -f "$c" ] && { ARCHIVE="$c"; break; }
done
if [ -z "$ARCHIVE" ]; then
  # Asking for a deck rollback and having no archive is not a warning — it is the whole operation
  # failing. A `warn` here scrolls past and leaves you believing the decks were restored.
  if [ "$DO_DB" -eq 0 ]; then
    die "no deck archive in $BACKUP_DIR, but --decks-only was requested.
Looked for: swudeck-games[-*].tar.gz, swudeck-gamestates[-*].tar.gz
Nothing was restored. Find the archive from §2.2 and pass its directory as --backup-dir."
  fi
  warn "no deck archive in $BACKUP_DIR — SKIPPING the deck restore entirely."
  warn "If the deck rewrite (step 21) ran, those files are NOT being rolled back."
else
  gunzip -t "$ARCHIVE" 2>/dev/null || die "$ARCHIVE is not valid gzip — do not rely on it"
  N=$(tar -tzf "$ARCHIVE" | grep -c '/Gamestate\.txt$')
  ok "archive $ARCHIVE ($(du -h "$ARCHIVE" | cut -f1), $N gamestates) — gzip verified"
  # Extract OVER the tree rather than delete-then-extract: that restores every Gamestate.txt to its
  # pre-migration bytes while leaving DeckImage.jpg and anything else untouched. A slim archive
  # (gamestates only) therefore restores correctly without destroying deck images.
  act "tar -xzf <archive> -C $ROOT_DIR/SWUDeck   (overwrites Gamestate.txt in place, deletes nothing)"
  if [ "$APPLY" -eq 1 ]; then
    tar -xzf "$ARCHIVE" -C "$ROOT_DIR/SWUDeck" || die "extract failed — deck tree may be half-restored"
    ok "deck files restored"
  fi
fi
fi

# ─────────────────────────────────────────────────────────────────────────────
if [ "$DO_DB" -eq 1 ]; then
say "2. Database"
if [ ${#SWAPPED[@]} -eq 0 ]; then
  ok "no *_old tables — the database was never swapped, nothing to undo"
else
  for t in "${SWAPPED[@]}"; do
    # If a *_new already exists (a re-run, or 02 ran again), park the migrated copy out of the way
    # instead of colliding. Renaming never destroys; dropping would.
    dest="${t}_new"
    if sql "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME='${t}_new';" | grep -q 1; then
      dest="${t}_rolledback_$(date +%H%M%S)"
    fi
    act "RENAME TABLE \`$t\` TO \`$dest\`, \`${t}_old\` TO \`$t\`;"
    if [ "$APPLY" -eq 1 ]; then
      sql "RENAME TABLE \`$t\` TO \`$dest\`, \`${t}_old\` TO \`$t\`;" \
        && ok "$t restored (migrated copy kept as $dest)" \
        || die "RENAME failed for $t — STOP and inspect by hand"
    fi
  done
fi
fi

# ─────────────────────────────────────────────────────────────────────────────
say "3. Verify"
if [ "$APPLY" -eq 1 ]; then
  # The strongest check available: if the rollback worked, the deck files are UUID-shaped again, so
  # a dry run of the rewrite tool should once more report a large number of files to change. If it
  # reports 0, the deck files are still migrated.
  act "re-running the deck-file dry run — it should report MANY files would change"
  "${PHP_BIN:-php}" "$HERE/tools/rewrite-deck-files.php" 2>&1 | grep -E '^files:|^identifiers:' | sed 's/^/   /'
  sql "SELECT TABLE_NAME FROM information_schema.TABLES
        WHERE TABLE_SCHEMA='$DB' AND (TABLE_NAME LIKE '%\\_old' OR TABLE_NAME LIKE '%\\_new'
           OR TABLE_NAME LIKE '%\\_rolledback\\_%') ORDER BY TABLE_NAME;" \
    | sed 's/^/   leftover: /'
  echo
  echo "   Still to do, and in this order:"
  echo "     1. redeploy the PREVIOUS commit — before service resumes, not after"
  echo "     2. spot-check a deck in the browser and one meta page"
  echo "     3. only then turn maintenance OFF in zzMaintenanceMode.php"
  echo "     4. keep every leftover table and the backups until you know what went wrong"
else
  echo "   (dry run — re-run with --apply, then this step verifies the result)"
fi

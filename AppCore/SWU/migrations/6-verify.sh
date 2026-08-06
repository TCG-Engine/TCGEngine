#!/bin/bash
# Runs every AUTOMATABLE check from DEPLOY DAY part B, in one pass.
#
#   ./6-verify.sh --db=swudeck [--backup-dir=/var/backups/... ] [--php=/opt/lampp/bin/php]
#
# Exit 0 only if everything passed. Anything non-zero means DO NOT RESTORE SERVICE.
#
# ⚠ This is not the whole of part B. It cannot open a browser, so the user-facing checks — a legacy
# deck rendering with its sideboard intact, deck search showing no tokens, cross-browser — stay
# manual and are listed at the end. A green run here means "nothing automated is broken", not
# "the migration is good".
set -uo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Prefer $ROOT (exported by 0-env.sh). Deriving it from $HERE only holds when this script sits in
# its normal place; run from a copy — /opt/mig, say — and "../../.." resolves to somewhere with no
# repo, so every test reports "missing" as though it had failed.
ROOT_DIR="${ROOT:-$(cd "$HERE/../../.." && pwd)}"
if [ ! -d "$ROOT_DIR/DevTools/tdd-regression" ]; then
  echo "WARNING: no repo at $ROOT_DIR — sections 1-2 cannot run." >&2
  echo "         Set ROOT, or run this from \$ROOT/AppCore/SWU/migrations/." >&2
  NO_REPO=1
fi

DB=""; BACKUP_DIR=""; PHP="${PHP_BIN:-php}"
for arg in "$@"; do
  case "$arg" in
    --db=*)         DB="${arg#--db=}" ;;
    --backup-dir=*) BACKUP_DIR="${arg#--backup-dir=}" ;;
    --php=*)        PHP="${arg#--php=}" ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done
[ -n "$DB" ] || { echo "FATAL: --db=<database> is required." >&2; exit 2; }

# MYCNF must be the FLAG form, not a bare path: it is passed to mysql as an argument, and a
# bare path is read as a database name. Normalise rather than fail with a misleading error.
MYCNF="${MYCNF:-}"
case "$MYCNF" in ""|--defaults-extra-file=*) ;; *) MYCNF="--defaults-extra-file=$MYCNF" ;; esac; MY=("${MYSQL_BIN:-${MYSQL:-mysql}}"); [ -n "$MYCNF" ] && MY+=("$MYCNF")
sql() { "${MY[@]}" -N -B "$DB" -e "$1" 2>/dev/null; }

PASS=0; FAIL=0
ok()   { PASS=$((PASS+1)); printf "   \033[32mPASS\033[0m  %s\n" "$*"; }
bad()  { FAIL=$((FAIL+1)); printf "   \033[31mFAIL\033[0m  %s\n" "$*"; }
head() { printf "\n\033[1m== %s\033[0m\n" "$*"; }

check() { # $1=label  $2=actual  $3=expected
  [ "$2" = "$3" ] && ok "$1" || bad "$1  (got '$2', expected '$3')"
}

# ── 1. the regression suite ──────────────────────────────────────────────────
head "1. Automated tests"
if [ "${NO_REPO:-0}" = "1" ]; then
  bad "SKIPPED — no repo at $ROOT_DIR. These checks did not run; that is not the same as passing."
else
for t in test_swu_stats_ingress test_swu_maintenance_guards test_swudeck_setnnn_dictionary \
         test_swudeck_art_paths_resolve test_swu_card_art_corpus test_swudeck_preview_cards; do
  f="$ROOT_DIR/DevTools/tdd-regression/$t.php"
  if [ ! -f "$f" ]; then bad "$t (missing)"; continue; fi
  out=$("$PHP" -d xdebug.mode=off "$f" 2>&1 | grep -E "^(PASS|FAIL)" | tail -1)
  case "$out" in PASS*) ok "$t — $out" ;; *) bad "$t — ${out:-no verdict}" ;; esac
done
fi

# ── 2. identity round-trip ───────────────────────────────────────────────────
head "2. Identity round-trip (every card: UUID -> SET_NNN -> UUID)"
rt=$("$PHP" -d xdebug.mode=off -r '
require "'"$ROOT_DIR"'/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
$n=0;$bad=0;
foreach (array_keys($GLOBALS["titleData"]) as $id) {
  $u=UUIDLookup($id); if($u===null||$u==="") continue; $n++;
  if (CardIDLookup($u)!==$id) $bad++;
}
echo "$n:$bad";' 2>/dev/null)
case "$rt" in
  *:0) ok "round-trip clean over ${rt%%:*} cards" ;;
  *:*) bad "round-trip: ${rt##*:} mismatch(es) over ${rt%%:*} cards" ;;
  *)   bad "round-trip probe produced NO OUTPUT — php or the dictionary is unavailable, so this
         was not checked. Do not read it as a pass or a failure." ;;
esac

# ── 3. the data actually migrated ────────────────────────────────────────────
head "3. Stats tables are SET_NNN-keyed"
# Prove the connection FIRST. Without this every query returns empty, the checks below report '?'
# and read exactly like "the migration failed" — which is a very different 2am diagnosis from
# "this shell cannot reach the database".
if [ "$(sql 'SELECT 1;')" != "1" ]; then
  bad "CANNOT QUERY '$DB' — every check in sections 3-4 below is meaningless."
  echo "         Set MYCNF (see runbook §0), and MYSQL_BIN if the client is not on PATH."
  echo "         This is a connection problem, NOT evidence about the migration."
  SKIP_DB=1
fi
if [ "${SKIP_DB:-0}" = "0" ]; then
LEFT=$(sql "SELECT COUNT(*) FROM carddeckstats WHERE cardID REGEXP '^[0-9]{10}$';")
check "no bare FFG UIDs left in carddeckstats" "${LEFT:-?}" "0"
LEFT2=$(sql "SELECT COUNT(*) FROM completedgame WHERE WinningHero REGEXP '^[0-9]{10}$' OR LosingHero REGEXP '^[0-9]{10}$';")
check "no bare FFG UIDs left in completedgame" "${LEFT2:-?}" "0"

# Class 2 must have survived VERBATIM — a colour rewritten to a card id is silent data loss.
COL=$(sql "SELECT COUNT(*) FROM deckmetamatchupstats WHERE opponentBaseID IN ('Green','Red','Blue','Yellow','colorless');")
[ "${COL:-0}" -gt 0 ] && ok "base colours preserved ($COL rows)" || bad "base colour rows are GONE — class 2 was not preserved"

# The Palpatine split should have collapsed into one row.
PALP=$(sql "SELECT COUNT(*) FROM completedgame WHERE WinningHero IN ('ad86d54e97','0026166404');")
check "Palpatine's split identities are gone (merged to TWI_017)" "${PALP:-?}" "0"
else
  echo "   (skipped — no database connection)"
fi

head "4. Rollback is still available"
if [ "${SKIP_DB:-0}" = "0" ]; then
OLD=$(sql "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME LIKE '%\\_old';")
[ "${OLD:-0}" -ge 7 ] && ok "$OLD *_old table(s) retained" \
  || bad "only ${OLD:-0} *_old table(s) — rollback may no longer be a RENAME"
else
  echo "   (skipped — no database connection)"
fi

# ── 5. deck files ────────────────────────────────────────────────────────────
head "5. Deck files"
if [ -f "$HERE/tools/rewrite-deck-files.php" ]; then
  out=$("$PHP" -d xdebug.mode=off "$HERE/tools/rewrite-deck-files.php" 2>&1 | grep '^files:')
  n=$(echo "$out" | sed -E 's/.*seen, ([0-9]+) .*/\1/')
  check "a re-run finds nothing left to change" "${n:-?}" "0"
fi
TMPF=$(find "$ROOT_DIR/SWUDeck/Games" -name '*.migtmp' 2>/dev/null | wc -l)
check "no .migtmp files left behind" "${TMPF:-?}" "0"

# ── 6. egress contract ───────────────────────────────────────────────────────
head "6. Egress contract"
if [ -n "$BACKUP_DIR" ] && [ -d "$BACKUP_DIR/egress-before" ]; then
  if [ -d "$BACKUP_DIR/egress-after" ]; then
    if "$HERE/3-capture-egress.sh" diff --backup-dir="$BACKUP_DIR" >/dev/null 2>&1; then
      ok "LoadDeck (default) and cardUid are byte-identical"
    else
      bad "egress differs — run: 3-capture-egress.sh diff --backup-dir=$BACKUP_DIR"
    fi
  else
    bad "no egress-after capture — run: 3-capture-egress.sh after --backup-dir=$BACKUP_DIR --deck=<id>"
  fi
else
  bad "no egress BASELINE — it had to be captured before the migration; this check is now impossible"
fi

# ─────────────────────────────────────────────────────────────────────────────
printf "\n\033[1m%d passed, %d failed\033[0m\n" "$PASS" "$FAIL"
cat <<'MANUAL'

STILL MANUAL — a green run above does NOT cover these:
  [ ] a legacy deck opens, exports both ways, re-saves, and its SIDEBOARD SURVIVES
  [ ] deck browse panes populate, and NO token appears in deck search
  [ ] an HMW/preview deck imports; legal in `open`, illegal in `premier`
  [ ] a meta page and a card-stats page render with art
  [ ] /card in Discord returns an image; a legacy Discord embed URL still 200s
  [ ] all of the above in Chromium, Firefox AND Safari
MANUAL
[ "$FAIL" -eq 0 ] || exit 1

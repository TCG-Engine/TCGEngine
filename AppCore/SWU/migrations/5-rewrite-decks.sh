#!/bin/bash
# Step 5 — rewrite deck files to SET_NNN. Dry run by default.
#
#   ./5-rewrite-decks.sh                      # dry run
#   ./5-rewrite-decks.sh --apply
#   ./5-rewrite-decks.sh --games-dir=/tmp/Rehearsal --apply     # rehearse against a COPY
#
# A thin wrapper around tools/rewrite-deck-files.php that ENFORCES the post-checks which were
# otherwise a manual checklist item: after --apply, a second dry run must report 0 files changing,
# no .migtmp may remain, and the file count must be unchanged.
#
# Switch maintenance to the FULL write freeze before running with --apply — this step needs deck
# saves stopped, not just stats. Autosave racing a format change is what caused the Leader2 loss.
set -uo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$HERE/../../.." && pwd)"
PHP="${PHP_BIN:-php}"
TOOL="$HERE/tools/rewrite-deck-files.php"

APPLY=0; GAMES_ARG=""; PASSTHRU=()
for arg in "$@"; do
  case "$arg" in
    --apply)      APPLY=1 ;;
    --games-dir=*) GAMES_ARG="$arg" ;;
    --php=*)      PHP="${arg#--php=}" ;;
    *) PASSTHRU+=("$arg") ;;
  esac
done
GAMES_DIR="${GAMES_ARG#--games-dir=}"; [ -n "$GAMES_ARG" ] || GAMES_DIR="$ROOT_DIR/SWUDeck/Games"

count() { find "$GAMES_DIR" -name Gamestate.txt 2>/dev/null | wc -l; }
BEFORE=$(count)
echo "== $BEFORE deck file(s) in $GAMES_DIR"

if [ "$APPLY" -eq 0 ]; then
  "$PHP" -d xdebug.mode=off "$TOOL" ${GAMES_ARG:+"$GAMES_ARG"} ${PASSTHRU+"${PASSTHRU[@]}"}
  exit $?
fi

echo "== APPLY  (~28 min at 105k files)"
S=$(date +%s)
"$PHP" -d xdebug.mode=off "$TOOL" --apply ${GAMES_ARG:+"$GAMES_ARG"} ${PASSTHRU+"${PASSTHRU[@]}"} || {
  echo "FATAL: rewrite failed — see above. Nothing further was checked."; exit 1; }
echo "   elapsed: $(( $(date +%s) - S ))s"

echo "== post-checks"
rc=0
AFTER=$(count)
[ "$AFTER" -eq "$BEFORE" ] && echo "   ok    file count unchanged ($AFTER)" \
  || { echo "   FAIL  file count $BEFORE -> $AFTER"; rc=1; }

T=$(find "$GAMES_DIR" -name '*.migtmp' 2>/dev/null | wc -l)
[ "$T" -eq 0 ] && echo "   ok    no .migtmp left behind" || { echo "   FAIL  $T .migtmp file(s) remain"; rc=1; }

N=$("$PHP" -d xdebug.mode=off "$TOOL" ${GAMES_ARG:+"$GAMES_ARG"} 2>/dev/null \
     | grep '^files:' | sed -E 's/.*seen, ([0-9]+) .*/\1/')
[ "${N:-x}" = "0" ] && echo "   ok    a second pass finds nothing to change (idempotent)" \
  || { echo "   FAIL  a second pass would still change ${N:-?} file(s)"; rc=1; }

[ $rc -eq 0 ] && echo "== DECK REWRITE COMPLETE" || echo "== POST-CHECKS FAILED — consider 9-rollback.sh"
exit $rc

#!/bin/bash
# Egress byte-identity check — the contract promise to Karabast and to anyone reading `cardUid`.
#
#   ./3-capture-egress.sh before --backup-dir=/var/backups/swustats-XXXX --deck=12345
#   ./3-capture-egress.sh after  --backup-dir=/var/backups/swustats-XXXX --deck=12345
#   ./3-capture-egress.sh diff   --backup-dir=/var/backups/swustats-XXXX
#
# `LoadDeck` at its DEFAULT (setId=false) and `CardMetaStatsAPI.cardUid` must return byte-identical
# output after the migration. Stats/APIs.php documents both as FFG UID format; the stored key
# becomes SET_NNN, so they are mapped back out on the way to the client.
#
# ⚠ RUN `before` WHILE THE TABLES ARE STILL UUID-KEYED. There is no way to reconstruct the baseline
# afterwards — miss it and the contract check simply cannot be run, which is the one check that
# proves we did not silently break an external consumer.
#
# Read-only: issues GETs and writes only into --backup-dir.
set -uo pipefail

MODE="${1:-}"; shift 2>/dev/null || true
BACKUP_DIR=""; DECK=""; BASE="${SWU_BASE_URL:-https://swustats.net}"
for arg in "$@"; do
  case "$arg" in
    --backup-dir=*) BACKUP_DIR="${arg#--backup-dir=}" ;;
    --deck=*)       DECK="${arg#--deck=}" ;;
    --base=*)       BASE="${arg#--base=}" ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done
[ -n "$BACKUP_DIR" ] || { echo "FATAL: --backup-dir=<dir> is required." >&2; exit 2; }

grab() { # $1=dir $2=name $3=url
  curl -s --max-time 60 "$3" > "$1/$2" || { echo "   FAILED: $3"; return 1; }
  local n; n=$(wc -c < "$1/$2")
  printf "   %-26s %8s bytes\n" "$2" "$n"
  [ "$n" -gt 2 ] || { echo "   ^^ EMPTY — a baseline you cannot compare against is worse than none"; return 1; }
}

capture() { # $1 = before|after
  local d="$BACKUP_DIR/egress-$1"
  [ -n "$DECK" ] || { echo "FATAL: --deck=<deckID that predates the migration> is required." >&2; exit 2; }
  mkdir -p "$d" || exit 1
  echo "== capturing '$1' from $BASE"
  grab "$d" loaddeck-default.json  "$BASE/TCGEngine/APIs/LoadDeck.php?deckID=$DECK&format=json"          || exit 1
  grab "$d" loaddeck-setid.json    "$BASE/TCGEngine/APIs/LoadDeck.php?deckID=$DECK&format=json&setId=true" || exit 1
  grab "$d" cardmetastats.json     "$BASE/TCGEngine/Stats/CardMetaStatsAPI.php"                          || exit 1
  echo "$DECK" > "$d/.deckid"
  echo "   saved to $d"
  [ "$1" = "before" ] && echo "   Run the SAME deckID for 'after', or the diff is meaningless."
}

case "$MODE" in
  before|after) capture "$MODE" ;;
  diff)
    B="$BACKUP_DIR/egress-before"; A="$BACKUP_DIR/egress-after"
    [ -d "$B" ] || { echo "FATAL: no baseline at $B — it needed capturing BEFORE the migration." >&2; exit 1; }
    [ -d "$A" ] || { echo "FATAL: no $A — run 'after' first." >&2; exit 1; }
    if [ -f "$B/.deckid" ] && [ -f "$A/.deckid" ] && ! diff -q "$B/.deckid" "$A/.deckid" >/dev/null; then
      echo "FATAL: before/after used DIFFERENT deckIDs ($(cat "$B/.deckid") vs $(cat "$A/.deckid"))." >&2
      exit 1
    fi
    rc=0
    echo "== these MUST be byte-identical (the documented UUID wire format)"
    for f in loaddeck-default.json cardmetastats.json; do
      if diff -q "$B/$f" "$A/$f" >/dev/null 2>&1; then
        printf "   \033[32mok\033[0m       %s\n" "$f"
      else
        printf "   \033[31mCHANGED\033[0m  %s   <- a consumer's contract just broke\n" "$f"
        diff "$B/$f" "$A/$f" | head -12 | sed 's/^/        /'
        rc=1
      fi
    done
    echo
    echo "== this one MAY differ, but only where a reprint folded to its canonical printing"
    if diff -q "$B/loaddeck-setid.json" "$A/loaddeck-setid.json" >/dev/null 2>&1; then
      echo "   identical (fine — this deck contains no reprint that folds)"
    else
      echo "   differs. Every changed id must be a known reprint, e.g. SHD_030 -> SOR_033:"
      diff "$B/loaddeck-setid.json" "$A/loaddeck-setid.json" | head -20 | sed 's/^/     /'
    fi
    echo
    [ $rc -eq 0 ] && echo "EGRESS CONTRACT INTACT" || echo "EGRESS CONTRACT BROKEN — do not restore service"
    exit $rc ;;
  *) sed -n '2,12p' "${BASH_SOURCE[0]}"; exit 2 ;;
esac

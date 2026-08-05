#!/bin/bash
# Step 2 — archive the deck tree. Read-only against the tree; writes only into --backup-dir.
#
#   ./2-snapshot.sh --backup-dir=/var/backups/swustats-XXXX [--root=/opt/lampp/htdocs/TCGEngine]
#
# Two archives, because they have different jobs:
#   swudeck-gamestates.tar.gz   Gamestate.txt ONLY (~72MB at 105k decks) — small enough to copy to a
#                               laptop for a rehearsal run, and it is what 9-rollback.sh restores
#   swudeck-games.tar.gz        everything, incl. DeckImage.jpg (much larger) — the full §2.2 backup
#
# The DATABASE dump is not here; 4-migrate.sh takes it as its own first step, so it is always
# adjacent in time to the migration it protects.
set -uo pipefail

BACKUP_DIR=""; ROOT_DIR="${ROOT:-/opt/lampp/htdocs/TCGEngine}"; SLIM_ONLY=0
for arg in "$@"; do
  case "$arg" in
    --backup-dir=*) BACKUP_DIR="${arg#--backup-dir=}" ;;
    --root=*)       ROOT_DIR="${arg#--root=}" ;;
    --slim-only)    SLIM_ONLY=1 ;;
    *) echo "unknown argument: $arg" >&2; exit 2 ;;
  esac
done
[ -n "$BACKUP_DIR" ] || { echo "FATAL: --backup-dir=<dir> is required." >&2; exit 2; }
GAMES="$ROOT_DIR/SWUDeck/Games"
[ -d "$GAMES" ] || { echo "FATAL: no such dir: $GAMES" >&2; exit 1; }
mkdir -p "$BACKUP_DIR" || exit 1

# ONE find, reused for the count and the file list. Two separate finds would drift on a live tree
# and report a false mismatch.
L=$(mktemp); trap 'rm -f "$L"' EXIT
find "$GAMES" -name Gamestate.txt -print0 > "$L"
N=$(tr -cd '\0' < "$L" | wc -c)
echo "== $N deck file(s); tree is $(du -sh "$GAMES" | cut -f1); $(df -Ph "$BACKUP_DIR" | awk 'NR==2{print $4}') free"
[ "$N" -gt 0 ] || { echo "FATAL: no deck files found" >&2; exit 1; }

verify() { # $1=archive $2=expected count
  gunzip -t "$1" 2>/dev/null || { echo "   FATAL: $1 is not valid gzip"; return 1; }
  local m; m=$(tar -tzf "$1" | grep -c '/Gamestate\.txt$')
  printf "   %-30s %7s  %6d gamestates  %s\n" "$(basename "$1")" "$(du -h "$1"|cut -f1)" "$m" \
    "$([ "$m" -eq "$2" ] && echo OK || echo "MISMATCH (expected $2)")"
  [ "$m" -eq "$2" ]
}

SLIM="$BACKUP_DIR/swudeck-gamestates.tar.gz"
echo "== slim archive (gamestates only)"
# tar exits 1 for "file changed as we read it" — normal on a live site, one stale deck at worst.
# Exit 2 is a real failure. Hence `;` not `&&`, then verify properly below.
( cd "$ROOT_DIR/SWUDeck" && find Games -name Gamestate.txt -print0 \
    | nice -n 10 tar --warning=no-file-changed --null -T - -czf "$SLIM" ); true
verify "$SLIM" "$N" || exit 1

if [ "$SLIM_ONLY" -eq 0 ]; then
  FULL="$BACKUP_DIR/swudeck-games.tar.gz"
  echo "== full archive (everything under Games/)"
  nice -n 10 tar --warning=no-file-changed -czf "$FULL" -C "$ROOT_DIR/SWUDeck" Games; true
  verify "$FULL" "$N" || exit 1
fi

sha256sum "$SLIM" | tee "$SLIM.sha256"
echo
echo "Copy the slim one down for a rehearsal run:"
echo "  scp <user>@<host>:$SLIM ."

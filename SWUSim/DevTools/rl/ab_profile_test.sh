#!/bin/bash
# A/B a HEURISTIC PROFILE over a SUBSET of decks, holding the opponents fixed.
#
#   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
#     bash SWUSim/DevTools/rl/ab_profile_test.sh <pairs-file> <outdir> [workers=8]
#
# Why this exists, and why it is NOT sweep_fixtures.sh: a sweep reads each deck's "# Style:" header, so a
# deck's DECK and its PROFILE always move together and their effects cannot be separated. Measured
# 2026-09-18, the bots' fidelity error is a style-level split (aggro +12.1 points, control -11.6), which has
# two completely different causes with opposite fixes:
#   (a) the control PROFILE plays badly  -> the same deck should do BETTER under another profile;
#   (b) the control DECKS are piloted badly at a level no profile touches -> the profile makes no difference.
# This runner takes the profile as an explicit column so one deck can be replayed under several, on the same
# seeds against the same opponents.
#
# pairs-file: one job per line, TAB-separated, no header —
#   <arm>  <deckA-path>  <profileA>  <deckB-path>  <profileB>  <seed>
# "arm" is a free label for the variant being compared; results are aggregated per arm.
#
# Output: $OUT/results.tsv — arm, deckA, deckB, seed, the raw SWUBOT_METRICS json, game id. The metrics line
# is stored VERBATIM rather than parsed here: it already carries winner, rounds, baseDamageDealt and the
# per-seat decision-layer coverage, and the analysis side can read whichever it needs. (An earlier cut
# sliced the winner out in bash with a hardcoded offset, got it off by one, and recorded 2,268 games as
# winner "?" — the games are the expensive part, so keep the raw line and parse downstream.)
# Resumable (one file per job under $OUT/games), and each game's folder is deleted once read, as in
# sweep_fixtures.sh. Read-only with respect to every tracked fixture: profiles come from the pairs file.
set -u
cd /var/www/html/TCGEngine
PAIRS=${1:?pairs file}; OUT=${2:-/tmp/ab_profile}; WORKERS=${3:-8}
mkdir -p "$OUT/games"
export OUT

run_one() {
  local arm=$1 da=$2 pa=$3 db=$4 pb=$5 s=$6
  local na nb key f out m g
  na=$(basename "$da" .txt); nb=$(basename "$db" .txt)
  key="$arm.$na.$nb.$s"; f="$OUT/games/$key.tsv"
  [ -s "$f" ] && return 0
  # A profile naming a chooser directly (e.g. "random", "first-legal") is used verbatim; a bare archetype
  # name gets the "heuristic-" prefix. Archetype ids carry no hyphen by construction (BotArchetypes.php).
  local ca cb
  case "$pa" in random|first-legal|*@*) ca="$pa" ;; *) ca="heuristic-$pa" ;; esac
  case "$pb" in random|first-legal|*@*) cb="$pb" ;; *) cb="heuristic-$pb" ;; esac
  out=$(timeout "${GAME_TIMEOUT:-90}" php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G \
        DevTools/SWUSimBotSelfPlayTest.php --games=1 --seed="$s" --first-player=1 --verbose \
        --chooser="$ca" --chooser2="$cb" --deck="$da" --deck2="$db" 2>/dev/null)
  g=$(printf '%s\n' "$out" | grep -m1 'game created:' | awk '{print $NF}')
  m=$(printf '%s\n' "$out" | grep -m1 '^SWUBOT_METRICS ')
  printf '%s\t%s\t%s\t%s\t%s\t%s\n' "$arm" "$na" "$nb" "$s" "$m" "$g" > "$f.tmp" && mv "$f.tmp" "$f"
  [[ "$g" =~ ^[0-9]+$ ]] && rm -rf "SWUSim/Games/$g"
}
export -f run_one

echo "[ab] $(wc -l < "$PAIRS") games, $WORKERS workers, out $OUT — $(date -u +%H:%M:%S)"
tr '\t' ' ' < "$PAIRS" | xargs -P "$WORKERS" -L1 bash -c 'run_one "$@"' _
# find -exec, not a glob: a 45,720-game run (2026-09-19) blew past ARG_MAX and wrote an EMPTY results.tsv while
# reporting "done — 0 results". The per-game files survived, but the run looked lost.
find "$OUT/games" -name "*.tsv" -exec cat {} + > "$OUT/results.tsv" 2>/dev/null
echo "[ab] done — $(wc -l < "$OUT/results.tsv") results — $(date -u +%H:%M:%S)"

#!/bin/bash
# Real-deck self-play sweep: every fixture deck against every other, BOTH seat orders, N seeds each, every
# deck on its own style's heuristic profile (the "# Style:" line in its header). Runs INSIDE the SWUSim
# container, in parallel.
#
#   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
#     bash SWUSim/DevTools/rl/sweep_fixtures.sh <seeds=100> <workers=8> <outdir=/tmp/fixture_sweep> [fixture dir]
#
# - Resumable: each game writes $OUT/games/<deck1>.<deck2>.<seed>.tsv; a finished game is skipped on re-run.
# - Tidy: a game's SWUSim/Games/<id> folder (~320 KB) is deleted once its results are read; a FAILED game's
#   folder is kept for diagnosis. Only folders this run created are ever touched.
# - Parallel-safe: game ids come from GetGameCounter(), which takes an exclusive flock (Core/HTTPLibraries.php).
# - Retro material, per game: $OUT/traces/<key>.jsonl (the combo decisions, SWUBOT_TRACE_MODE=combo) and
#   $OUT/logs/<key>.log (the game log). A retro every ~2,000 games mines them (…/scripts/retro.py).
# - Output: $OUT/results.tsv — deck1, deck2, seed, the SWUBOT_METRICS json, the [RESULT] json (coverage dropped),
#   and the game id (a failed game's SWUSim/Games/<id> folder is kept).
#   Analyse with docs/superpowers/research/2026-09-premier-meta/scripts/compare_all.py.
# With deterministic bots a seed plays the same game whichever seat goes first, so the first player is fixed
# at 1 and variety comes from the seed and from swapping which deck sits in seat 1.
set -u
cd /var/www/html/TCGEngine
SEEDS=${1:-100}; WORKERS=${2:-8}; OUT=${3:-/tmp/fixture_sweep}; DIR=${4:-SWUSim/Tests/BotFixtures/meta-2026-09}
mkdir -p "$OUT/games" "$OUT/traces" "$OUT/logs"
export DIR OUT

decks=$(ls "$DIR"/*.txt | xargs -n1 basename | sed 's/\.txt$//')
: > "$OUT/jobs.txt"
for a in $decks; do for b in $decks; do
  [ "$a" = "$b" ] && continue
  for s in $(seq -f "s%03g" 1 "$SEEDS"); do echo "$a $b $s" >> "$OUT/jobs.txt"; done
done; done
echo "[sweep] $(wc -l < "$OUT/jobs.txt") games, $WORKERS workers, output $OUT — $(date -u +%H:%M:%S)"

run_one() {
  local a=$1 b=$2 s=$3 f="$OUT/games/$1.$2.$3.tsv"
  [ -s "$f" ] && return 0
  local ca cb out g m r
  ca=$(grep -m1 '^# Style:' "$DIR/$a.txt" | awk '{print $3}')
  cb=$(grep -m1 '^# Style:' "$DIR/$b.txt" | awk '{print $3}')
  # A per-game cap: an engine loop must cost one worker 90 s, not the whole sweep. A capped game is recorded
  # as failureSignal "timeout" and its folder is kept.
  # Retro material (every ~2,000 games the retro mines these for combos no test covers): a COMBO trace of the
  # decisions that mark an interaction (SWUBOT_TRACE_MODE=combo, BotHeuristic.php) and the game log.
  local key="$a.$b.$s"
  rm -f "$OUT/traces/$key.jsonl"
  out=$(SWUBOT_TRACE="$OUT/traces/$key.jsonl" SWUBOT_TRACE_MODE=combo \
        timeout "${GAME_TIMEOUT:-90}" php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G DevTools/SWUSimBotSelfPlayTest.php --games=1 \
        --seed="$s" --first-player=1 --verbose --chooser="heuristic-$ca" --chooser2="heuristic-$cb" \
        --deck="$DIR/$a.txt" --deck2="$DIR/$b.txt" 2>/dev/null)
  g=$(printf '%s\n' "$out" | grep -m1 'game created:' | awk '{print $NF}')
  m=$(printf '%s\n' "$out" | grep -m1 '^SWUBOT_METRICS ')
  r=$(printf '%s\n' "$out" | grep -m1 '^\[RESULT\] ' | php -r '$j = json_decode(substr(stream_get_contents(STDIN), 9), true); if (is_array($j)) { unset($j["coverage"]); echo "[RESULT] " . json_encode($j, JSON_UNESCAPED_SLASHES); }')
  [ -z "$r" ] && r="[RESULT] {\"fail\":99,\"failureSignal\":\"timeout\",\"game\":\"$g\"}"
  # The game log is one "<NL>"-joined line of the gamestate (~4 KB); keep it before the folder goes.
  [[ "$g" =~ ^[0-9]+$ ]] && grep -m1 '<NL>' "SWUSim/Games/$g/Gamestate.txt" > "$OUT/logs/$key.log" 2>/dev/null
  printf '%s\t%s\t%s\t%s\t%s\t%s\n' "$a" "$b" "$s" "$m" "$r" "$g" > "$f.tmp" && mv "$f.tmp" "$f"
  # Keep a failed game's folder for diagnosis; delete a clean one.
  if [[ "$g" =~ ^[0-9]+$ ]] && [[ "$r" == *'"fail":0'* ]]; then rm -rf "SWUSim/Games/$g"; fi
}
export -f run_one

xargs -P "$WORKERS" -L 1 bash -c 'run_one "$@"' _ < "$OUT/jobs.txt"
cat "$OUT"/games/*.tsv > "$OUT/results.tsv"
echo "[sweep] done — $(wc -l < "$OUT/results.tsv") results — $(date -u +%H:%M:%S)"

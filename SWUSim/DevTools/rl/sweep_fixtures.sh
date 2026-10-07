#!/bin/bash
# Real-deck self-play sweep: every fixture deck against every other, BOTH seat orders, N seeds each, every
# deck on its own style's heuristic profile (the "# Style:" line in its header). Runs INSIDE the SWUSim
# container, in parallel.
#
#   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
#     bash SWUSim/DevTools/rl/sweep_fixtures.sh <seeds=100> <workers=10> <outdir=/tmp/fixture_sweep> [fixture dir]
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
# SUPERSET research mode (env, owner 2026-10-01) folds a deck's Sideboard section into its main deck:
#   SUPERSET=1                        every deck is folded (--superset);
#   SUPERSET_DECKS="<deck> [<deck>…]" only the named decks are, WHICHEVER seat they sit in (--superset=1|2), so a
#                                     deck's own sideboard answers can be measured apart from the opponent's dilution.
# VARIANT="<variant>" (env) runs BOTH seats on heuristic-<style>@<variant> (e.g. no-p18, no-doomedsac); it is part of
# the arm stamp, so a variant run can't resume into a plain run's outdir.
# Give each arm its OWN outdir: the resume check skips any game already on disk, so pointing a superset run at
# another arm's outdir would silently reuse that arm's results.
set -u
cd /var/www/html/TCGEngine
# 10 workers = the owner's M1 Max (10 cores); measured 2026-10-03, games/min plateaus there (8 → 104, 10 → 133, 16 → 131).
# opcache.file_cache shares compiled scripts across the one-game processes, which otherwise recompile the engine each
# time: −13% per game, identical results. Timestamps are still validated (revalidate_freq=0), so an edit is picked up.
SEEDS=${1:-100}; WORKERS=${2:-10}; OUT=${3:-/tmp/fixture_sweep}; DIR=${4:-SWUSim/Tests/BotFixtures/ash-meta-2026-09}
SUPERSET=${SUPERSET:-}; SUPERSET_DECKS=${SUPERSET_DECKS:-}; VARIANT=${VARIANT:-}
decks=$(ls "$DIR"/*.txt | xargs -n1 basename | sed 's/\.txt$//')
if [ -n "$SUPERSET" ] && [ -n "$SUPERSET_DECKS" ]; then echo "[sweep] set SUPERSET or SUPERSET_DECKS, not both" >&2; exit 1; fi
FOLD=$([ -n "$SUPERSET" ] && echo $decks || echo $SUPERSET_DECKS)
for d in $FOLD; do
  # A typo'd deck name would fold nothing and run a game-1 arm under a superset label.
  [ -f "$DIR/$d.txt" ] || { echo "[sweep] SUPERSET_DECKS: no $DIR/$d.txt" >&2; exit 1; }
  # The harness refuses a deck with no Sideboard section, but its stderr is discarded below, so a refusal would
  # only surface as a "timeout" game. Check every folded deck once, up front, instead.
  grep -q '^Sideboard[[:space:]]*$' "$DIR/$d.txt" || { echo "[sweep] superset: $d has no Sideboard section" >&2; exit 1; }
done
mkdir -p "$OUT/games" "$OUT/traces" "$OUT/logs" /tmp/swusim-opcache   # opcache skips a missing file_cache dir silently
# Refuse to mix arms in one outdir: the first run stamps it, a later run with another arm stops.
ARM=$(if [ -n "$SUPERSET" ]; then echo superset; elif [ -n "$FOLD" ]; then echo "superset:$(echo $FOLD | tr ' ' '\n' | sort | tr '\n' ',' | sed 's/,$//')"; else echo game1; fi)
[ -n "$VARIANT" ] && ARM="$ARM@$VARIANT"
# PAIRS_FILE="<path>" (env) — a fidelity SCREEN: play only the listed unordered pairs ("deckA deckB" per line, from
# SWUSim/DevTools/rl/fidelity_pairs.py), both seat orders. Part of the arm stamp, so a screen never resumes into a full sweep.
PAIRS_FILE=${PAIRS_FILE:-}
# SEED_PREFIX="<prefix>" (env, default "s") — a FRESH seed block, as in strength_test.sh: games are deterministic per seed, so a
# lever picked on the s-block is confirmed on another. Part of the arm stamp when it is not the default.
SEED_PREFIX=${SEED_PREFIX:-s}
[ "$SEED_PREFIX" != "s" ] && ARM="$ARM+seeds:$SEED_PREFIX"
if [ -n "$PAIRS_FILE" ]; then
  [ -s "$PAIRS_FILE" ] || { echo "[sweep] PAIRS_FILE $PAIRS_FILE is missing or empty" >&2; exit 1; }
  ARM="$ARM+pairs:$(basename "$PAIRS_FILE")"
fi
if [ -s "$OUT/arm" ] && [ "$(cat "$OUT/arm")" != "$ARM" ]; then
  echo "[sweep] $OUT holds a '$(cat "$OUT/arm")' run; this is '$ARM'. Use a separate outdir." >&2; exit 1
fi
echo "$ARM" > "$OUT/arm"
FOLD=" $FOLD "   # padded, so run_one can match a whole name with a substring test
export DIR OUT FOLD VARIANT
: > "$OUT/jobs.txt"
if [ -n "$PAIRS_FILE" ]; then
  while read -r a b; do
    [ -n "$a" ] || continue
    for d in "$a" "$b"; do [ -f "$DIR/$d.txt" ] || { echo "[sweep] PAIRS_FILE names no $DIR/$d.txt" >&2; exit 1; }; done
    for s in $(seq -f "${SEED_PREFIX}%03g" 1 "$SEEDS"); do echo "$a $b $s" >> "$OUT/jobs.txt"; echo "$b $a $s" >> "$OUT/jobs.txt"; done
  done < "$PAIRS_FILE"
else
  for a in $decks; do for b in $decks; do
    [ "$a" = "$b" ] && continue
    for s in $(seq -f "${SEED_PREFIX}%03g" 1 "$SEEDS"); do echo "$a $b $s" >> "$OUT/jobs.txt"; done
  done; done
fi
echo "[sweep] $(wc -l < "$OUT/jobs.txt") games, $WORKERS workers, arm $ARM, output $OUT — $(date -u +%H:%M:%S)"
[ -n "${JOBS_ONLY:-}" ] && exit 0   # write jobs.txt and stop: a dry check of the job list without playing a game

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
  local key="$a.$b.$s" ss=""
  # Which SEATS to fold follows from where the folded decks sit in this game.
  case "$FOLD" in *" $a "*) case "$FOLD" in *" $b "*) ss="--superset" ;; *) ss="--superset=1" ;; esac ;;
                  *) case "$FOLD" in *" $b "*) ss="--superset=2" ;; esac ;; esac
  rm -f "$OUT/traces/$key.jsonl"
  out=$(SWUBOT_TRACE="$OUT/traces/$key.jsonl" SWUBOT_TRACE_MODE=combo \
        timeout "${GAME_TIMEOUT:-90}" php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G -d opcache.file_cache=/tmp/swusim-opcache DevTools/SWUSimBotSelfPlayTest.php --games=1 \
        --seed="$s" --first-player=1 --verbose --chooser="heuristic-$ca${VARIANT:+@$VARIANT}" --chooser2="heuristic-$cb${VARIANT:+@$VARIANT}" \
        --deck="$DIR/$a.txt" --deck2="$DIR/$b.txt" $ss 2>/dev/null)
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

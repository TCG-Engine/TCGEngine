#!/bin/bash
# Strength test (RL bots spec, Section 7; owner ruling 2026-09-14 "raise the weak, never lower the strong"): a NEW
# heuristic stack against the OLD one on the fixture decks. Every ordered deck pair and seed is played twice — new
# in seat 1 / old in seat 2 (side 1), then old in seat 1 / new in seat 2 (side 2) — so seats and decks are mirrored.
# Each deck plays its own style's profile ("# Style:" header) plus the variant: "" = everything on, "base",
# "no-<feature>" (SWUSim/Custom/BotFeatures.php). Runs INSIDE the SWUSim container, in parallel:
#   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
#     bash SWUSim/DevTools/rl/strength_test.sh <new-variant> <old-variant> <seeds=10> <workers=8> <outdir> [fixture dir]
#   python3 SWUSim/DevTools/rl/strength_report.py <outdir>/results.tsv
# DECKS="a b c" limits the run to those fixtures. Identical variants are a built-in control: with deterministic bots
# the two mirrored games are the same game, so the new side wins exactly half.
set -u
cd /var/www/html/TCGEngine
# OLD defaults to 'base' only when the argument is MISSING: an explicit "" means the full stack (no variant).
# (It was ${2:-base}, which also turned "" into @base — the 2026-09-15 learned-policy evaluation compared against
# the pre-part-2 stack by mistake.)
NEW=${1:-}; OLD=${2-base}; SEEDS=${3:-10}; WORKERS=${4:-8}; OUT=${5:-/tmp/strength}; DIR=${6:-SWUSim/Tests/BotFixtures/meta-2026-09}
mkdir -p "$OUT/games"
export NEW OLD DIR OUT
decks=${DECKS:-$(ls "$DIR"/*.txt | xargs -n1 basename | sed 's/\.txt$//')}
: > "$OUT/jobs.txt"
# FOCUS="deckA deckB" keeps only pairings where at least one side is a focus deck. A change that touches a few decks
# (a flavour, a deck-specific rule) is otherwise drowned: with 4 of 22 decks affected, most mirrored pairs are
# identical in both arms and can only dilute the result.
for a in $decks; do for b in $decks; do
  [ "$a" = "$b" ] && continue
  if [ -n "${FOCUS:-}" ]; then
    case " $FOCUS " in *" $a "*|*" $b "*) ;; *) continue ;; esac
  fi
  for s in $(seq -f "s%03g" 1 "$SEEDS"); do echo "$a $b $s 1" >> "$OUT/jobs.txt"; echo "$a $b $s 2" >> "$OUT/jobs.txt"; done
done; done
echo "[strength] new='$NEW' old='$OLD' — $(wc -l < "$OUT/jobs.txt") games, $WORKERS workers — $(date -u +%H:%M:%S)"

run_one() {
  local a=$1 b=$2 s=$3 side=$4 f="$OUT/games/$1.$2.$3.$4.tsv"
  [ -s "$f" ] && return 0
  local ca cb va vb out g m
  ca=$(grep -m1 '^# Style:' "$DIR/$a.txt" | awk '{print $3}')
  cb=$(grep -m1 '^# Style:' "$DIR/$b.txt" | awk '{print $3}')
  if [ "$side" = 1 ]; then va=$NEW; vb=$OLD; else va=$OLD; vb=$NEW; fi
  out=$(timeout "${GAME_TIMEOUT:-90}" php -d apc.enable_cli=1 -d xdebug.mode=off -d memory_limit=1G DevTools/SWUSimBotSelfPlayTest.php --games=1 \
        --seed="$s" --first-player=1 --verbose --chooser="heuristic-$ca${va:+@$va}" --chooser2="heuristic-$cb${vb:+@$vb}" \
        --deck="$DIR/$a.txt" --deck2="$DIR/$b.txt" 2>/dev/null)
  g=$(printf '%s\n' "$out" | grep -m1 'game created:' | awk '{print $NF}')
  m=$(printf '%s\n' "$out" | grep -m1 '^SWUBOT_METRICS ')
  [ -z "$m" ] && m='SWUBOT_METRICS {"winner":0,"timeout":true}'
  printf '%s\t%s\t%s\t%s\t%s\t%s\n' "$a" "$b" "$s" "$side" "$m" "$g" > "$f.tmp" && mv "$f.tmp" "$f"
  # Keep a game folder only when the game produced no metrics (for diagnosis).
  if [[ "$g" =~ ^[0-9]+$ ]] && [[ "$m" != *'"timeout":true'* ]]; then rm -rf "SWUSim/Games/$g"; fi
}
export -f run_one
xargs -P "$WORKERS" -L 1 bash -c 'run_one "$@"' _ < "$OUT/jobs.txt"
cat "$OUT"/games/*.tsv > "$OUT/results.tsv"
echo "[strength] done — $(wc -l < "$OUT/results.tsv") results — $(date -u +%H:%M:%S)"

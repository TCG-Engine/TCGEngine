---
name: swusim-meta-bot-run
description: Use to measure the SWUSim bots against a real tournament META SET — "run the hmw meta", "meta bot run HMW", "test this feature/lever on the meta", compare a @try- variant or new code with a control arm, or rebuild a set's bot-vs-real matchup matrix. REQUIRES a set tag (e.g. HMW — the current meta; ASH is retired). Replays every real match of that set with both players' actual decklists (sideboards folded in), once from each seat, then pairs it against a control arm and the real results. Also covers freezing a set's baseline (only when the owner asks) and building a new set.
---

# SWUSim Meta Bot Run — `<SET>` required

Every real match from one tournament set, replayed by the bots with **both players' real lists**, **sideboards folded into the deck**
(a general test, not a realistic game 1), **twice** — once with each player going first. The result is compared with a control arm on
today's code (and the set's frozen baseline) and with what the humans actually did.

## The set tag (required)

The skill takes ONE set tag. `<set>` below = the tag lowercased = the folder `SWUSim/Tests/BotFixtures/meta-baselines/<set>/`.
- **No tag given → ask for it.** Never default to a set.
- A tag with no folder there → stop and say so (Mode C builds a new set).
- The set's `meta.json` carries its own analysis thresholds — **always pass them**: `analyzeArgs` → analyze.py, `matrixArgs` → matrix.py,
  `pairedMin` → paired.py (paired.py reads it itself). A small set without them gives empty archetype tables.

| tag | status | size | one arm |
|---|---|---|---|
| **HMW** | **the current meta (owner 2026-10-26)** — every bot feature is measured here | 5 events (melee 480807, 463642, 480819, 483483, 489470 — the last two added 2026-10-27), 342 real matches → 332 pairs → **664 games**. Arms from before 2026-10-27 played the 3-event set and do not pair with newer ones | **~23–32 min** (measured 2026-10-29; was 3–4) |
| ASH | **RETIRED 2026-10-26** — owner: "we dont have to run ASH metas anymore … we are 2 weeks into the HMW meta now". Run only if the owner explicitly asks for ASH. | 53 events, 9,933 matches → ~19,650 games | ~2.5 h |

## Owner policy — read first

- **Measure a feature as a PAIR on today's code:** a plain `''` control arm and the variant arm(s), queued together. The frozen baseline
  is context, not the comparison: it predates later code. HMW's current baseline is v3 (all 5 events, 664 games, `ca527053d`,
  frozen 2026-10-28).
- **Never re-freeze unasked** (Mode B).
- **Reports are local `.md` files**, never an Artifact per run (owner 2026-10-13). Publish the matrix only when asked.
- New bot features are default-OFF proposals measured this way; a measured null ships ON unless harmful (memory
  `bot-levers-ship-on-unless-harmful`). No unit tests for bot heuristics (memory `bot-features-no-unit-tests`).

## What is where

| Path | What |
|---|---|
| `SWUSim/DevTools/rl/meta_baseline/` | tools: `run.sh`, `analyze.py`, `paired.py`, `matrix.py` (+ `matrix_template.html`), `freeze.py`, `common.py`; rebuild: `export.php`, `fetch_lists.py`, `convert.php` |
| `SWUSim/Tests/BotFixtures/meta-baselines/<set>/` | the set, frozen: `fixtures.tar.gz` (real lists, each with a Sideboard section), `matches.tsv.gz` (real matches: lists, game score, archetypes, bot styles), `labels.json`, `events.txt` (melee ids), `meta.json` (thresholds, `curatedFixtures`, `matrixArtifact`) |
| `…/<set>/baseline/` | the frozen bot run: `bot_games.tsv.gz`, `analysis.txt`, `matrix.json`, `meta.json` (git HEAD it ran on, dates, note) |
| `.claude/tmp/<set>-meta-runs/<label>/` | run dirs (gitignored). One FRESH dir per arm — a resumed dir reuses its games, never point two arms at one |

HMW extras: curated fixtures with the owner's deck-by-deck style labels in `SWUSim/Tests/BotFixtures/hmw-meta-2026-09/` (memory
`hmw-meta-2026-09-fixtures`); matrix page https://claude.ai/artifact/7WWPM4jigHT6ss89YGwV7a. Hemlock levers are tested here (owner 2026-10-19).

## Mode A (default) — test the current code or a feature

1. **Pre-flight.** No other sweep is running (`docker exec otmtcge-swusim-web-server-1 bash -c "ps aux | grep -cE '[S]WUSimBotSelfPlay|[s]weep_fixtures|[s]trength_test'"`
   → 0 — and check for FOREIGN sweeps, memory `editing-engine-during-a-sweep`). Variant: `''` = the plain stack; `try-<proposal>` /
   `try-<theme>` / `no-<feature>` = a lever, applied to **both seats**.
2. **Queue every arm in ONE detached script** (an approval prompt between arms once stalled a run for hours), control first:
   ```
   # .claude/tmp/<set>-meta-runs/queue-<label>.sh — runs inside the container
   cd /var/www/html/TCGEngine
   L=.claude/tmp/<set>-meta-runs/queue-<label>.log
   run() { echo "$(date -u +%FT%TZ) start $2 [$3]" >> $L; bash SWUSim/DevTools/rl/meta_baseline/run.sh "$1" "$2" "$3"; echo "$(date -u +%FT%TZ) done $2" >> $L; }
   M=SWUSim/Tests/BotFixtures/meta-baselines/<set>; O=.claude/tmp/<set>-meta-runs
   run $M $O/<label>-control ""
   run $M $O/<label>-on "try-<lever or theme>"
   echo "$(date -u +%FT%TZ) ALL DONE" >> $L
   ```
   ```
   docker exec -d -w /var/www/html/TCGEngine -e PHP_INI_SCAN_DIR=":/tmp/swusim-ini" otmtcge-swusim-web-server-1 bash .claude/tmp/<set>-meta-runs/queue-<label>.sh
   ```
   A full HMW arm takes ~25–30 min (2026-10-29). To screen many levers, run each on the match SUBSET where it can fire: `sweep_fixtures.sh` with
   `PAIRS_FILE=<lever>.pairs SUPERSET=1 SEED_PREFIX=tb VARIANT=try-<lever>`. Pairs are deterministic, so a subset arm pairs exactly with the full control.
   Smoke test only (tools changed): `-e LIMIT=30` plays the first 30 matches; on unchanged code those games must be IDENTICAL to the
   control's.
3. **Watch** with a background until-loop on the queue log's `ALL DONE` (or `<run dir>/DONE`). A background command caps at 2 h — if it
   expires first, re-arm a new one immediately (owner 2026-10-13). Analyze the moment it finishes (memory `analyze-sweeps-when-they-finish`).
4. **Do not edit bot/engine code while it runs** — every game reads the live files.
5. **Analyze only finished arms** (never a partial — partial arms drift):
   - pair each variant arm with the control: `python3 SWUSim/DevTools/rl/meta_baseline/paired.py <arm> <control> SWUSim/Tests/BotFixtures/meta-baselines/<set>`
   - fidelity vs real: `python3 SWUSim/DevTools/rl/meta_baseline/analyze.py SWUSim/Tests/BotFixtures/meta-baselines/<set> <arm> <analyzeArgs>`
   - lever coverage: count `proposal:<lever>` in each game's `SWUBOT_METRICS` coverage (`<arm>/sweep/results.tsv`) — a lever that
     never fired is INERT on this field, say so; failed/timed-out games: the `[RESULT]` column (`fail`, `failureSignal`).
6. **Matrix page (optional):** `python3 SWUSim/DevTools/rl/meta_baseline/matrix.py <meta dir> <run dir> <run dir>/matrix.html --subtitle "<label>" <matrixArgs>` — a LOCAL file.
7. **Report** as `<variant arm>/REPORT.md`, summarised in chat: (a) the paired change vs the control — games whose winner changed, per
   style "newly won : newly lost" with the sign-test p, the largest archetype moves; (b) how often each lever fired; (c) fidelity vs real
   next to the control's (archetype Pearson, MAE vs the constant-50% line, the style-bias gradient). Frame it as a SAFETY check ("no deck
   hurt, N games changed"), not an acceptance gate.

## How to read the numbers

- ⚠ **Match-level winner agreement is not a fidelity metric.** Even the real data's own archetype favourite picks the real match winner
  only ~53%. Use archetype game WR (Pearson, MAE against the constant-50% line), the style-bias gradient, and matchup cells with enough
  real matches.
- ⚠ **Small-set caveat (HMW):** every game uses seed `tb001`, so a list in seat 1 opens with the same hand every game — the effective n is
  far below 538. A few-game swing is noise; read significance from the sign test, and treat single-archetype moves as leads to inspect.
- **Pairing:** same matches, same seed, same seats, deterministic bots — a changed winner is the code's doing.
- **Variant on both seats:** a `try-x` run changes every deck that can use the lever, opponents included — read it as the field-wide effect.
- **Intro Battle: Hoth bases are ignored** (owner 2026-10-12): IBH_054 / IBH_002 are double-sided and the sims play the 20 HP side;
  `common.load_matches` drops matches with either deck on one (games are still played). Guard: `SWUSim/DevTools/rl/meta_baseline_ibh_test.py`.
- **Real numbers carry noise too:** a cell's real WR comes from its Bo3 game scores; the matrix shows a 95% Wilson range under it.

## Mode B — freeze a NEW baseline for `<set>` (only when the owner asks)

Run Mode A with variant `''` to completion, then:
```
python3 SWUSim/DevTools/rl/meta_baseline/freeze.py SWUSim/Tests/BotFixtures/meta-baselines/<set> .claude/tmp/<set>-meta-runs/<label> --force --note "<why / what changed>"
```
`freeze.py` refuses to overwrite without `--force`. Check `baseline/meta.json`: `gitHead` must be the code the run started on (if the
owner committed mid-run, compare the commit date with the run's start and say so in `--note`). Republish the matrix to the URL in the set's
`meta.json` only if the owner asks; update memory `swusim-tournament-baseline`.

## Mode C — build or extend a set (a new meta, a new event, or lost data)

1. **Export** from the local SWUStats DB (tables `meleetournament` / `meleetournamentdeck` / `meleetournamentmatchup`, swudeck container;
   events must already be imported — `importMeleeTournamentById`, or the `swustats-melee-package` skill when melee.gg blocks the droplet):
   `docker cp <meta>/events.txt otmtcge-swudeck-web-server-1:/tmp/events.txt` then
   `docker exec otmtcge-swudeck-web-server-1 php -d memory_limit=1G /var/www/html/TCGEngine/SWUSim/DevTools/rl/meta_baseline/export.php /tmp/events.txt <curated fixture dir> > <work>/export.json`.
   The DB holds no card lists — only `sourceID` (melee DecklistId).
2. **Fetch the lists** through the local converter (keep it at 2 workers — every request reaches melee.gg):
   `python3 SWUSim/DevTools/rl/meta_baseline/fetch_lists.py <work>/export.json docs/superpowers/research/2026-09-premier-meta/decklists 2`.
   ⚠ That cache is gitignored and has been wiped once — the set's `fixtures.tar.gz` is the durable copy.
3. **Convert** (swusim container): `php SWUSim/DevTools/rl/meta_baseline/convert.php <work>/export.json <lists dir> <work> <curated fixture dir>`
   → `<work>/fx/*.txt`, `matches.tsv`, `labels.json`. Archetype = the fixture-style name (`SWUBotFixtureFileName` over main + SIDEBOARD, so
   the Homeworlds trait counts — owner 2026-10-11). Style per deck: the curated fixture of that exact archetype, else the leader + base-bucket
   key's fixture, else `SWUBotDeckStyle`; a list below its base minimum (a silently dropped card) is excluded and counted. New decks that
   take the classifier's style: show the owner the flagged ones.
4. **Store:** `COPYFILE_DISABLE=1 tar --no-xattrs --no-mac-metadata -czf <meta>/fixtures.tar.gz -C <work> fx` (without those flags macOS
   metadata makes the Linux tar warn), `gzip -c <work>/matches.tsv > <meta>/matches.tsv.gz`, copy `labels.json`, write `events.txt` and
   `meta.json` (copy the HMW one's shape, thresholds included). Then Mode A (+ Mode B if the owner wants a baseline).

## Gotchas already hit

- A card name with an accent (Sabé, Padmé) folds to ASCII in a slug (`sabe_…`) since 2026-10-11; before, the letter was dropped (`sab_…`).
- `export.php`'s matchup loop once lost its braces and kept 1 row — re-exporting the 53 ASH events must reproduce 3,239 decks / 20,184 rows.
- `sweep_fixtures.sh` / `strength_test.sh` used `cat games/*.tsv`: past ~15k games that is "Argument list too long" and `results.tsv`
  comes out EMPTY ("0 results"). Fixed 2026-10-11 to `find | xargs cat`. If you ever see "0 results", the games are still on disk.
- Rematches: `run.sh` plays each pair once (a deterministic rematch is the same game) and the analysis maps both real matches onto it.
- Superset folding renames `Sideboard` to `Deck`; a deck WITHOUT the header is refused, an empty section is fine.
- The seed block must stay `tb` — another prefix breaks the pairing with the control and the baseline.
- A bot loop on a prompt shows as timed-out games for ONE list (HMW 2026-10-24: Sabé DV looped on Greater Sarlacc's cost rider) — read
  the trace of a failed game before blaming the lever.

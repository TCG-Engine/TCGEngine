---
name: swusim-ash-meta-bot-run
description: Use to measure the SWUSim bots against the ASH meta — "run the ash meta", "ash meta bot run", "test this feature/lever against the ash baseline", compare a @try- variant or new code with the baseline, or rebuild the bot-vs-real matchup matrix. Replays EVERY real match of the 53-event ASH Premier tournament set with both players' actual decklists (sideboards folded in), once from each seat, then analyzes against the real results and the frozen ASH meta baseline and publishes the matchup matrix. Also covers freezing a NEW ASH baseline — only when the owner explicitly asks.
---

# SWUSim ASH Meta Bot Run

Every real match from the ASH Premier tournament set, replayed by the bots with **both players' real lists**, **sideboards folded into
the deck** (a general test, not a realistic game 1), **twice** — once with each player going first. The result is compared with what the
humans actually did, and with the frozen **ASH meta baseline**.

## Owner policy (2026-10-11) — read first

- **The ASH meta baseline is THE reference.** Every new feature is tested against it. Do **not** run new baselines, canaries or fixture
  sweeps to have something to compare with — this run plus its frozen baseline is the comparison.
- **Never re-freeze unasked.** The baseline changes only when the owner asks for a new "ASH meta baseline" (Mode B).
- The **HMW meta baseline** is a separate, smaller set: `SWUSim/Tests/BotFixtures/meta-baselines/hmw/` (melee 480807, Nexus Open: Homeworlds,
  42 matches / 84 games, frozen 2026-10-11; curated fixtures `hmw-meta-2026-09/` with the owner's deck-by-deck style labels; matrix
  https://claude.ai/artifact/7WWPM4jigHT6ss89YGwV7a). Same tools, its meta dir instead of `ash`; small, so `analyze.py … --min 3 --cell-min 2`
  and `matrix.py … --min 1` (both stored in its `meta.json`). Test features against it the same way.

## What is where

| Path | What |
|---|---|
| `SWUSim/DevTools/rl/meta_baseline/` | the tools: `run.sh`, `analyze.py`, `matrix.py` (+ `matrix_template.html`), `freeze.py`, `common.py`; rebuild: `export.php`, `fetch_lists.py`, `convert.php` |
| `SWUSim/Tests/BotFixtures/meta-baselines/ash/` | the ASH set, frozen: `fixtures.tar.gz` (3,207 real lists, each with a Sideboard section), `matches.tsv.gz` (9,933 real matches: lists, real game score, archetypes, bot styles), `labels.json`, `events.txt` (53 melee ids), `meta.json` |
| `…/meta-baselines/ash/baseline/` | the frozen bot run: `bot_games.tsv.gz` (19,647 games: deck1 deck2 winner rounds), `analysis.txt`, `matrix.json`, `meta.json` (git HEAD it ran on, dates, note) |
| ASH baseline matrix page | https://claude.ai/artifact/MrQcLgewzYmV57W7MiQxnk (also in `meta.json` → `matrixArtifact`) |

Baseline v1: frozen 2026-10-11, game code = HEAD `2c43fbfe`, full stack, superset, seed `tb001`. 9,924 of 9,933 matches replayed.
Fidelity vs real: archetype Pearson −0.02, MAE 0.102 vs 0.046 for a constant 50%; style bias hyper aggro +9.7pp, soft aggro +7.4, midrange
+0.6, soft control −8.4, hard control −12.4.

## Mode A (default) — test the current code or a feature against the baseline

1. **Pre-flight.** No other sweep is running (`docker exec otmtcge-swusim-web-server-1 bash -c "ps aux | grep -cE '[S]WUSimBotSelfPlay|[s]weep_fixtures|[s]trength_test'"`
   → 0). Pick a label and a FRESH run dir: `.claude/tmp/ash-meta-runs/<label>/` (a resumed dir reuses its games — never point two arms at one).
   Variant: `''` = the plain current stack; `try-<proposal>` / `try-<group>` / `no-<feature>` = a lever, applied to **both seats**.
2. **Launch detached, in ONE command** (an approval prompt between steps can stall an overnight run for hours):
   ```
   docker exec -d -w /var/www/html/TCGEngine -e PHP_INI_SCAN_DIR=":/tmp/swusim-ini" otmtcge-swusim-web-server-1 \
     bash SWUSim/DevTools/rl/meta_baseline/run.sh SWUSim/Tests/BotFixtures/meta-baselines/ash .claude/tmp/ash-meta-runs/<label> "<variant>"
   ```
   ~19,650 games, ~2.5 h at ~128 games/min (10 workers + the opcache file cache), ~0.4 GB of logs/traces. Progress:
   `ls .claude/tmp/ash-meta-runs/<label>/sweep/games | wc -l`; finished when `<run dir>/DONE` exists.
   Smoke test first if the tools changed: `-e LIMIT=30` plays the first 30 matches (60 games). On unchanged game code those games must be
   **identical** to the baseline (proven 60/60 on 2026-10-11) — if not, something broke the pairing.
3. **Watch** with a background until-loop on `DONE` (it caps at 2 h; when it expires, do not re-arm — check on the owner's next message).
4. **Do not edit bot/engine code while it runs** — every game reads the live files.
5. **Analyze only the finished run** (never report a partial — partial arms drift):
   `python3 SWUSim/DevTools/rl/meta_baseline/analyze.py SWUSim/Tests/BotFixtures/meta-baselines/ash .claude/tmp/ash-meta-runs/<label>`
   (`--min N` / `--cell-min N` lower the archetype / matchup-cell thresholds — needed on a small set like HMW)
6. **Matrix page:** `python3 SWUSim/DevTools/rl/meta_baseline/matrix.py <meta dir> <run dir> <scratchpad>/<label>-matrix.html --subtitle "<label>"`,
   then publish it with the Artifact tool (a new page per comparison run; it shows bot %, **baseline bot %** and real % per matchup).
7. **Report** in this order: (a) the paired change vs the baseline — games whose winner changed, per style "newly won : newly lost" with
   the sign-test p, the largest archetype moves; (b) fidelity vs real for this run next to the baseline's (archetype Pearson, MAE vs the
   constant-50% line, the style-bias gradient); (c) the matrix link.

## How to read the numbers

- ⚠ **Match-level winner agreement is not a fidelity metric.** Even the real data's own archetype favourite (leave-one-out) picks the real
  match winner only ~53%; tournament matches are close to coin flips given the archetypes. Use archetype game WR (Pearson, MAE against the
  constant-50% line), the style-bias gradient, and matchup cells with 10+ real matches.
- **Pairing:** same matches, same seed, same seats, deterministic bots — a changed winner is the code's doing. The comparison includes
  EVERY game-code change since the baseline's `gitHead` (`git log <gitHead>..HEAD -- SWUSim/Custom SWUSim/*.php Core`). To isolate one
  lever when other code has also changed, run the lever arm AND a plain `''` arm on today's code and compare the two — that is still not a
  new baseline.
- **Variant on both seats:** a `try-x` run changes every deck that can use the lever, opponents included — read it as the field-wide effect.
- **Intro Battle: Hoth bases are ignored** (owner 2026-10-12). IBH_054 Forward Command Post and IBH_002 Echo Caverns are double-sided:
  real lists played the 30 HP side, the sims play the 20 HP intro side (kept on purpose). `common.load_matches` drops every match
  where either deck is on one (68 of 9,933 ASH matches; analyze.py prints the count); games are still PLAYED, only the analyses skip
  them. `load_matches(meta, include_ibh=True)` for the raw set. Guard: `SWUSim/DevTools/rl/meta_baseline_ibh_test.py`.
  The frozen baseline's stored `analysis.txt` / `matrix.json` predate the rule; analyze.py recomputes the baseline section live.
- **Real numbers carry noise too:** a cell's real WR comes from its Bo3 game scores; the matrix shows a 95% Wilson range under it.

## Mode B — freeze a NEW ASH meta baseline (only when the owner asks)

Run Mode A with variant `''` to completion, then:
```
python3 SWUSim/DevTools/rl/meta_baseline/freeze.py SWUSim/Tests/BotFixtures/meta-baselines/ash .claude/tmp/ash-meta-runs/<label> --force --note "<why / what changed>"
```
`freeze.py` refuses to overwrite without `--force`. Check `baseline/meta.json`: `gitHead` must be the code the run started on (if the
owner committed or amended mid-run, compare the commit's committer date with the run's start and say so in `--note`). Republish the
baseline matrix to the URL in `meta.json` (`matrix.py <meta> --baseline …`), and update memory `swusim-tournament-baseline`.

## Mode C — rebuild a tournament set (data lost, or a new set such as HMW)

1. **Export** from the local SWUStats DB (tables `meleetournament` / `meleetournamentdeck` / `meleetournamentmatchup`, swudeck container;
   events must already be imported — `importMeleeTournamentById`, see memory `swusim-real-matchup-matrix`):
   `docker cp <meta>/events.txt otmtcge-swudeck-web-server-1:/tmp/events.txt` then
   `docker exec otmtcge-swudeck-web-server-1 php -d memory_limit=1G /var/www/html/TCGEngine/SWUSim/DevTools/rl/meta_baseline/export.php /tmp/events.txt <curated fixture dir> > <work>/export.json`.
   The DB holds no card lists — only `sourceID` (melee DecklistId).
2. **Fetch the lists** through the local converter (keep it at 2 workers — every request reaches melee.gg; ~25 min for 3,200):
   `python3 SWUSim/DevTools/rl/meta_baseline/fetch_lists.py <work>/export.json docs/superpowers/research/2026-09-premier-meta/decklists 2`.
   ⚠ That cache is gitignored and has been wiped once — the meta dir's `fixtures.tar.gz` is the durable copy.
3. **Convert** (swusim container): `php SWUSim/DevTools/rl/meta_baseline/convert.php <work>/export.json <lists dir> <work> <curated fixture dir>`
   → `<work>/fx/*.txt`, `matches.tsv`, `labels.json`. Archetype = the fixture-style name (`SWUBotFixtureFileName` over main + SIDEBOARD, so
   the Homeworlds trait counts — owner 2026-10-11). Style per deck: the curated fixture of that exact archetype, else the leader + base-bucket
   key's fixture, else `SWUBotDeckStyle`; a list below its base minimum (a silently dropped card) is excluded and counted.
4. **Store:** `COPYFILE_DISABLE=1 tar --no-xattrs --no-mac-metadata -czf <meta>/fixtures.tar.gz -C <work> fx` (without those flags macOS
   metadata makes the Linux tar warn), `gzip -c <work>/matches.tsv > <meta>/matches.tsv.gz`, copy `labels.json`, write `events.txt` and
   `meta.json` (copy the ASH one's shape). Then Mode A + Mode B to create that set's first baseline.

## Gotchas already hit

- A card name with an accent (Sabé, Padmé) folds to ASCII in a slug (`sabe_…`) since 2026-10-11; before, the letter was dropped (`sab_…`).
- `export.php`'s matchup loop once lost its braces and kept 1 row — re-exporting the 53 ASH events must reproduce 3,239 decks / 20,184 rows.

- `sweep_fixtures.sh` / `strength_test.sh` used `cat games/*.tsv`: past ~15k games that is "Argument list too long" and `results.tsv`
  comes out EMPTY ("0 results"). Fixed 2026-10-11 to `find | xargs cat`. If you ever see "0 results", the games are still on disk.
- Rematches: 105 pairs met twice in the ASH set; `run.sh` plays each pair once (a deterministic rematch is the same game) and the analysis
  maps both real matches onto it.
- Superset folding renames `Sideboard` to `Deck`; a deck WITHOUT the header is refused, an empty section is fine (34 lists have no sideboard).
- The seed block must stay `tb` — another prefix breaks the pairing with the baseline.

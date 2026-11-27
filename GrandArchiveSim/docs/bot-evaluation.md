# Heuristic bot evaluation

Run from the repository root with the normal generated Grand Archive runtime and card data available. Python uses only the standard library. PHP needs the same environment as `GABotSelfPlayTest.php`; its CLI APCu fallback is supported.

The committed `Tests/BotBaselines/2026-10-28` snapshot freezes the current chooser and its helper functions. It is a starting benchmark, not an older weaker opponent. Both seats use today's engine, card implementations, and legal-action enumerator. This isolates policy changes; it does not compare historical engine versions or RL models.

## Run

Start with two seeded mirror games, swapping the candidate seat:

```sh
python3 DevTools/grand-archive/ga-bot-evaluate.py run \
  --config GrandArchiveSim/Tests/BotFixtures/evaluation.json \
  --baseline GrandArchiveSim/Tests/BotBaselines/2026-10-28 \
  --output /tmp/ga-bot-smoke --smoke
```

Remove `--smoke` and choose a new output directory for the full suite. The supplied configuration has four decks, all 16 ordered candidate/baseline pairings (including mirrors), five seeds, and two seat assignments: **160 games**. The sample deck pool exercises Genbu, Diana, Ciel, and Lorraine; it is not a claim that these lists are current tournament recommendations or equally competitive.

- `--php=/path/to/php` chooses the PHP executable.
- `--max-steps=2000` bounds each game; `--timeout=180` bounds its wall time.
- Paths in the configuration are relative to that configuration file.
- Output directories must be new, preventing accidental overwrite of evidence.
- Games are sequential, each in a fresh PHP process, and create ordinary local test-game saves.

Every pair reuses the seed and reverses policy/deck seat assignments. The initial deterministic RNG counter is seeded before loading decks; subsequent randomness follows the existing persisted engine RNG. Different actions can diverge the random stream. Swapping different decks between seats does not promise identical opening hands. Identical configurations on the same runtime should reproduce action digests; reports include those digests for verification.

## Read results

`report.md` contains matchup wins, mean steps, decision timings, and optional ability coverage ranked by unsupported count. `summary.json` contains all results; numbered JSON and log files retain individual-game evidence. Metadata records baseline, candidate, deck, and relevant engine/card-code hashes. Reports are rewritten after each game, so completed work remains readable after interruption.

The rejected-actions section totals attempts that made no game-state change, including
attempts in otherwise passing games. Its game numbers point to logs containing the
action, phase, turn, and current flash message. The flash message is diagnostic context,
not a guaranteed fresh rejection reason; reproduce suspicious cases before changing
legality or strategy. A successful retry should not hide a recurring enumeration bug.

Only games that finish **and pass the self-play assertions** count toward wins. Crashes, timeouts, and incomplete games are reported separately, never as losses or wins. Investigate their numbered logs before interpreting strength results. Small samples are directional evidence, not statistically established improvements.

Coverage counts offered optional choices on each chooser invocation, including controller retries:

- **Selected:** the policy understood and chose the option (not proof the activation resolved).
- **Declined:** it understood the option but chose another action or passed.
- **Unsupported:** its optional scorer returned no policy.

`MZMAYCHOOSE` and `YESNO` are classified per option. Zero-minimum `MZMULTICHOOSE` is counted per prompt; currently only the floating-payment policy is recognized. Mandatory target selection and free-play hand actions are outside this optional-ability metric. A declined option is not necessarily a mistake, and a selected option is not necessarily optimal. Use the game log and focused decision fixtures to investigate.

Decision timings exclude coverage re-scoring and report generation. Total game time includes instrumentation. Compare timings on the same machine under similar load. The report's p95 column is the slowest per-game p95, not a pooled percentile.

## Improve the bot

1. Run the frozen comparison and find frequent unsupported choices.
2. Add a policy with both beneficial-use and deliberate-decline cases in `DevTools/grand-archive/GABotDecisionTest.php`.
3. Rerun those checks and the suite against the same baseline, decks, and seeds.
4. Review failures, matchup-specific results, and latency before keeping the change.

To deliberately establish a new benchmark after review:

```sh
python3 DevTools/grand-archive/ga-bot-evaluate.py freeze /path/to/new-baseline
```

Freezing copies the three policy source files, isolates their function/callback/global names, and records hashes. Never edit a frozen baseline in place; loading rejects changed hashes. Baselines are local executable PHP and should be snapshots you created or reviewed. Keep older snapshots to retain comparable results.

Harness checks:

```sh
python3 DevTools/grand-archive/test-ga-bot-evaluate.py
php -d xdebug.mode=off DevTools/grand-archive/GABotDecisionTest.php
```

## Initial validation, 2026-10-28

The 160-game suite completed with all self-play assertions passing and an 80–80 split between the identical candidate and frozen policies. Repeating seed 101 for all 16 ordered matchups and both seat assignments reproduced all 32 action digests exactly. A separate seed changed the trace. Forced step-limit and wall-time failures were excluded from wins. The harness's three unit tests and all 183 existing bot decision checks passed. Total measured game runtime was about 331 seconds on the development machine, with some concurrent verification runs; this is not a production performance benchmark.

Frequent unsupported offers included Enfeebling Orb, Diana, Duskstalker, and Fractal of Polar Depths. Frequency measures offered chooser opportunities, not unique mistakes or an estimate of win-rate impact.

## Attack reliability and unused-policy review, 2026-10-29

The full 160-game suite after filtering opening-turn attacks passed with 83 candidate
wins. Every winner and step count matched the preceding run. Rejected attempts fell
from 211 to 111; all remaining rejection messages were the Pride level restriction.
The bot now filters that restriction using `PrideAmount` and `PlayerLevel`, preserving
temporary Pride removal and ability suppression. The authoritative combat handler
still validates attacks before executing them.

After the Pride filter, all 16 games that had remaining rejections were replayed:
all passed with zero rejected attempts and unchanged winners and step counts.
The focused decision suite passed 511 checks, deck knowledge passed 31, and the
evaluation harness passed four tests. The other 144 games were not rerun after
the Pride filter.

Diagnostic replays of games 3, 35, 74, 89, and 126 examined five supported abilities
that had no selections. These are selected examples, not proof about every matchup:

| Ability | Observed reason for passing | Follow-up |
| --- | --- | --- |
| Tanggu | At most three cards in hand across 155 offers; current policy requires five | Consider floating-memory payment and the value of retaining response cards |
| Manxome Armoire | No exchange met the existing constraints across 101 offers | Evaluate useful exchanges beyond reducing reserve cost while preserving omen bonuses |
| Flash Grenade | In the Diana/Lorraine replay, no opposing combat targeted a distant unit | Retain the guard; add matchups with relevant threats |
| Safeguard Amulet | The only observed opposing declared spell target was Displace, not damage | Retain the guard; test more damage-heavy opponents |
| Clarent | No opposing declared spell targets in the sampled offers | Retain the guard; test more damage-heavy opponents |

No defensive-spending thresholds were relaxed based solely on zero selections.
Temporary diagnostic replays ran alongside part of the tournament; timing from this
run should not be used to claim a speed improvement or regression.

### Reserve-aware optional abilities (2026-10-29)

Tanggu now counts ready reservable non-unit field objects toward its three-reserve
payment while still retaining at least two cards in hand. Ordinary reserve-payment
choices prefer these sources over hand cards; combat units are not promoted as
spare resources. Armoire includes the same resources when checking affordability
of an acquired omen, excluding Armoire itself because activation banishes it.
Existing omen-bonus preservation, timing, pending-effect and draw-safety checks
remain in place. Floating memory is not a reserve-payment source.

Both-seat fixtures cover ready/rested sources, preserving combat units, an actual
Tanggu payment and memory draw, and Armoire affordability. Validation: 531 decision
checks, 31 knowledge checks and four evaluation-harness tests pass. These changes
need a full paired evaluation before drawing conclusions about strength or speed.

### Archetype tournament in ten-game review batches

This is heuristic self-play with human/agent-reviewed policy improvements, not
reinforcement-learning weight updates. Snapshot all selected menu archetypes and
current chooser before starting:

```sh
python3 DevTools/grand-archive/ga-bot-tournament-setup.py /tmp/ga-tournament
python3 DevTools/grand-archive/ga-bot-evaluate.py run \
  --config /tmp/ga-tournament/config.json \
  --baseline /tmp/ga-tournament/baseline \
  --output /tmp/ga-tournament/batch-001 --round-robin --start 0 --games 10
```

Advance `--start` by ten and use a new output folder for each batch. The circle
schedule spreads matchups across decks, skips mirrors, handles a bye for an odd
roster, and keeps same-seed seat swaps together. The 21 selected archetypes yield
420 games per seed, or 2,100 games across the five default seeds. Decks, source
provenance and the starting policy are snapshotted locally; changing menu prefills
later cannot silently change this tournament. Setup refuses to overwrite a run.

After each batch, inspect failed games, rejection reasons and optional-ability
coverage. Reproduce a concrete decision error, add a focused fixture, fix it,
then replay affected matches before continuing. Do not make a policy change just
because one archetype lost, or force activation of every declined ability.
Maintain the initial baseline; each batch records the candidate policy hashes.
Results across policy versions are diagnostic matchup results, not a single
fixed-policy strength ranking. Use separate seeds and a full paired comparison
before claiming improvement. Both policies share current legality/engine code,
so legality fixes affect both sides. Sideboarding between games is not simulated.

First batch (2026-10-29): all ten games completed. Ciel versus Tristan produced
89 rejected Ominous Shadow attacks across its paired games. Added a bot legality
filter requiring a surviving unit damaged by its champion this turn, unless the
Shadow's abilities are suppressed. Both-seat fixtures cover missing, surviving,
and departed targets plus suppression. Batch reports remain local under
`/tmp/ga-archetype-tournament-20261029`; these are temporary artifacts.
The two affected games replayed with zero rejected actions and unchanged winners
and step counts. Validation passed: 539 decision checks, 31 knowledge checks and
five harness tests. Next new batch starts at offset 10.

# PokeSim

Headless Pokémon TCG simulation using TCGEngine's generated zones, turn controller,
Decision Queue and saved card macros. Includes a local game table for bot and hotseat play.

## Setup

Run from the repository root with PHP 8.1+, curl, mysqli and mbstring enabled, and
MySQL running. Database credentials use the repo's `MYSQL_SERVER_NAME`,
`MYSQL_SERVER_USER_NAME` and `MYSQL_ROOT_PASSWORD` environment variables.
`MYSQL_DATABASE_NAME`, when set, must be `pokesim`. Setup creates this database.

```sh
# Import the supplied deck's 20 printings and artwork first.
php DevTools/PokeSim/import.php --deck=PokeSim/Decks/sinistcha.txt
php DevTools/PokeSim/setup.php

# Start a loopback-only development server; open http://127.0.0.1:3700/PokeSim/
php -S 127.0.0.1:3700 -t .

# Or use an existing local XAMPP Apache: http://localhost/TCGEngine/PokeSim/
```

Select each player's view to choose their starting Active Pokémon, Bench and
confirm setup. Mulligan bonus draws happen after Prize placement; confirm setup
again after those draws. Both players use the supplied deck by default. Custom
decks accept Limitless text exports for the included sets, or explicit TCGdex IDs
(`4 me05-039`). Click a card to see its legal moves; hover or focus card art for
the card spotlight and rules text. The table shows Active Pokémon, five Bench
slots, Prizes, deck/discard piles, HP and attached Energy. Searches and other
choices use artwork pickers. Match settings contains the player view, shuffle
seed, custom decks and hotseat start. History opens the match log.
The legal action buttons and card choosers use the same action gate as the CLI.
Clicking a card with exactly one legal action performs it immediately, including
setting the starting Active, playing a Basic to the Bench, or playing a Trainer.
Global moves such as End turn and Confirm setup do not count toward this choice.
Cards with multiple actions or targets still offer choices. Attacks always
require an explicit action-button click, even if only one attack is legal.
Select Energy or an evolution in hand, then click a highlighted legal Pokémon
on the field to apply it. Clear or Escape cancels selection. Clicking an invalid
field target keeps the source selected. Sidebar action buttons remain available.
On desktop the board and hand fit the viewport height; large hands scroll
horizontally and long spotlight text scrolls within its panel.

### Engine integration boundary

Rules use schema-generated zone classes/accessors, generated card dictionaries,
saved and generated card macros, shared zone helpers and the shared Decision
Queue (including serialized await choices). Attachment invokes the schema's
EnergyAttached macro. All local UI and CLI actions pass through PokeApplyAction
and its authoritative legal-action gate.

The local page is a custom observation renderer over api.php; it does not use
the generated InitialLayout/GetNextTurn client renderer, Core UI chooser widgets,
the shared polling transport, or the browser bot-controller transport. Its bot
runs synchronously on the server. CustomWidgetInput provides a shared-engine
action adapter, but the local page does not currently use that transport.
Do not mistake this local frontend for complete shared UI integration.

### Play against the bot

Open [Play vs bot](http://127.0.0.1:3700/PokeSim/?mode=bot&new=1), or click **Play vs bot**
on the local page. You play player 1; player 2 uses the supplied Sinistcha deck.
The bot sets up and advances automatically after your actions. It pauses for
your choices even when those choices occur during its turn. Reloading resumes
the session; the button starts a fresh game with the selected seed.

The policy in `Bot/HeuristicBot.php` ranks legal actions using the bot's own
observation and offered choices. Before reaching four Hide 'n' Sneak discards,
it prioritizes Brilliant Blender and the Transceiver/Call Bell/Pokégear → Petrel
→ Blender search routes ahead of draw Supporters. Blender fills the missing HNS
discards to reach four, then seeds recoverable basic Psychic Energy for an
unpowered attacker or a Dhelmise when a backup is missing. When these resources
are already available, it thins Call Bell instead. Spare slots after partial HNS
setup also thin Call Bells. It never treats Special Energy as a recovery target.
After the initial combo, it prioritizes the current Dhelmise and a powered
replacement on the Bench, useful Trainer searches, knockouts, and worthwhile
switches. Sinistcha remains a fallback spread attacker. It avoids recovering
discarded Hide 'n' Sneak Pokémon without a board-building reason. Prize choices
are uniform; the policy does not inspect hidden opposing identities or deck
order. This is a deterministic baseline heuristic, not an optimized competitive
player. Bot mode keeps the opposing hand hidden and rejects manual player 2
actions. Hotseat remains available through **Match settings → Start hotseat match**.

The CLI also supports `{"command":"bot-step","player":2}` for one chosen action
and `{"command":"bot-run","player":2}` to advance until player 2 needs the
human to act. These commands use the same policy and action validation.

### Main menu and bot-versus-bot batches

Click **Main menu** in the table's top bar, or open
[PokeSim](http://127.0.0.1:3700/PokeSim/). **Resume match** returns to your
saved playable game. The batch runner uses separate games without changing it.

Choose an even number of games (up to 10,000) and a starting seed, then click
**Run mirror batch**. Each seed runs twice, with seat 1 going first and then
second. Both seats use the supplied deck and the current heuristic, each acting
from its own observation. Starting order is fixed before the initial shuffle,
so the paired games share initial opening deals while later play can diverge.
Results include first/second win rates, seat 1's win rate under each order,
average total turns, incomplete games, and all individual game results in CSV.
Games reaching 1,500 actions or encountering errors are reported separately,
never treated as wins or included in the completed-game win-rate denominator.
Pause finishes the current group of up to ten games; Resume continues at the
next seed. Results are saved in this browser so refreshing or resuming your
playable match does not lose the batch. Download the CSV for a portable copy.
A bot/rules fingerprint
prevents combining chunks from different policies during a run.

For larger runs without a browser:

```sh
php DevTools/PokeSim/simulate.php --games=1000 --seed=1 --output=dhelmise-mirror.csv
```

The CLI prints a JSON summary and optionally creates a CSV; existing output
files are not overwritten. Exit status 2 means some games did not complete.

The browser endpoints accept only loopback requests and keep games in PHP
sessions outside the web root. They require a session token for writes and reject
stale revisions. The app is a local prototype; it does not provide online accounts
or matchmaking. Docker's bridge can present a non-loopback source address; use
the CLI inside the container, or run the UI through local PHP/XAMPP.

The optional Docker stack follows the repo's conventions:
`./docker-start.sh pokesim` (web 3700, phpMyAdmin 5107, Redis 6488).
Run setup inside its web container to use that stack's separate database.

## Catalog and images

```sh
# Full English paper TCG catalog, excluding Pokémon TCG Pocket.
php DevTools/PokeSim/import.php --no-images

# Full catalog plus all available high-resolution WebP images (large download).
php DevTools/PokeSim/import.php

# Refetch upstream metadata/art instead of reusing cached source records.
php DevTools/PokeSim/import.php --refresh --deck=PokeSim/Decks/sinistcha.txt

# Regenerate dictionaries after changing the imported catalog.
php zzCardCodeGenerator.php rootName=PokeSim downloadImages=0
```

TCGdex REST `/cards` returns brief records. The importer uses GraphQL to fetch
full attacks, abilities, HP, evolution, types, weakness, resistance, retreat,
Trainer/Energy types, Rule Box suffixes, effects and legality. The current upstream
GraphQL pagination/sort resolver fails on its structured arguments, so the
importer uses a full catalog query. Records with GraphQL field errors are repaired
through REST and the errors are retained for inspection. Interrupted metadata
imports preserve the previous usable catalog. Missing artwork is recorded in an
image manifest; failed downloads can be retried without replacing existing art.

Generated caches, source snapshots and images are gitignored. TCGdex does not
currently supply MEE 005 artwork: its display uses the equivalent CRZ 156 Psychic
Energy art, recorded under `sourceCard` in `GeneratedCode/imageManifest.json`.
POR 088 is marked as Special Energy in normalization because its printed card
face contradicts the upstream `energyType=Normal`; raw source remains intact.

Source documentation: [TCGdex REST](https://tcgdex.dev/rest),
[card assets](https://tcgdex.dev/assets). Pokémon card artwork belongs to its
respective owners. Importing metadata does **not** implement a card's rules.

## Headless JSON Lines protocol

```sh
php PokeSim/cli.php
```

Send one JSON object per line; each response is `{ "ok": true, "result": ... }`
or `{ "ok": false, "error": ... }`.

```json
{"command":"reset","seed":42,"firstPlayer":1,"player":1}
{"command":"observe","player":1}
{"command":"actions","player":1}
{"command":"step","player":1,"action":{"type":"setup-active","player":1,"source":"p1Hand-1"}}
{"command":"export"}
```

Use action objects returned by `actions`/`observe` directly; their references
are valid only for the current state. Interactive `decision` exposes the acting
player, prompt, bounds and choices. Submit a step action such as
`{"type":"decision","player":1,"value":"p1Deck-0"}`. Multiple card choices use
`&`-separated values; optional skips use `"-"`. Observations omit the opponent's
hand, both deck orders and Prize identities. During setup, the opposing field is
also concealed. `export` and `load` are privileged CLI commands containing full
hidden state. Use `{"command":"load","state":...}` to resume a snapshot,
including a pending generated await continuation. Invalid steps restore the
previous state in the transport.

## Rules and cards

Supports 60-card constructed decks, four-copy limits, one ACE SPEC, mulligans,
setup, five Bench slots, first-turn attack/Supporter restrictions, turn draws,
one hand Energy attachment, evolution timing and lineage, attack Energy costs,
retreat/payment, switching, weakness/resistance, damage versus damage-counter
effects, special conditions/checkup, simultaneous knockouts, Prize choices,
promotion, deck-out and sudden death. Generic Energy matching currently supports
one energy unit per attached card. Special Energy with other behavior needs
authored helpers/macros. Tools and Stadiums are supported through authored macros, along with field
passives, attached Energy protection and copied attacks. Other advanced
mechanics still require their own authored implementations.

All 20 distinct printings in `Decks/sinistcha.txt` have authored implementations.
The deck has no Shuppet: Banette can be discarded for Hide 'n' Sneak counts, and
its attack is implemented for states in which it is legitimately in play.
Other cards are rejected unless their mechanics are supported: vanilla attacks
and basic Energy can work directly from metadata, while effects need macros.
Format rotation/ban lists are not enforced; TCGdex legality is available in the
generated dictionaries for future deckbuilding tooling.

## Card authoring and regeneration

Schema source is in `Schemas/PokeSim/`. Shared rule helpers are in `Custom/`.
Versioned card source is `CardCode/DeckAbilities.php` and
`CardCode/LopunnyAbilities.php`, containing the same macro
code and prerequisites saved by the CardEditor repository. Setup saves it through
`CardAbilityRepository` with revision checks, then runs the standard generators.
Interactive effects use supported inline `await` choosers; scalar locals survive
across serialized await frames. Passive protection uses generated value modifiers.

```sh
# Save edited deck authoring source and regenerate macros/turn controller.
php DevTools/PokeSim/setup.php --macros-only

# After CardEditor/MCP edits already saved in the database, regenerate only.
php zzGameCodeGenerator.php rootName=PokeSim
php zzTurnGenerator.php rootName=PokeSim
```

Setup reimports the versioned deck source into the database. Preserve CardEditor
changes in that source before running setup again. Never edit generated PHP/JS.
Runtime gameplay needs generated files, but no database connection.

## Verification

```sh
php DevTools/PokeSim/tests.php
php DevTools/PokeSim/smoke.php
php DevTools/PokeSim/bot-tests.php
php DevTools/PokeSim/simulation-tests.php
php DevTools/PokeSim/lopunny-tests.php
```

Tests exercise every authored card, legality, persistence across multiple awaits,
discarded-slot references, evolution, retreat, protection, Prize/promotion and
deck-out. The smoke script completes three seeded mirror games while serializing
and restoring state between every action. Its simple action policy is an engine
exerciser, not a deck-strength evaluator or RL integration.

## Brisbane Lopunny

`Decks/brisbane-lopunny.txt` preserves the supplied 60-card export. Main-menu
selectors let you play either deck against either heuristic, or simulate any
pairing with both starting orders. The match settings offer the same choices.

The Brisbane policy uses Fan Call/Poffin to establish Buneary and Dunsparce,
searches evolution and Energy with Hilda, cycles Run Away Draw, attaches Air
Balloon for pivots into Gale Thrust, and heals damaged Mega Lopunny with Wally
before reattaching Energy. It considers Dudunsparce ex against boards with
several ex, uses Boss for valuable knockouts, and draws with Enriching Energy.
Both policies read only their own seat observation and legal chooser options.

All 28 distinct entries have supported effects and local artwork. TCGdex has
no `mee-013` record; the parser explicitly resolves the supplied Psychic Energy
MEE 13 to equivalent basic Psychic Energy `mee-005`. The original deck export
is retained. `30C 66` resolves to **`30th-066`**, whose TCGdex record has
**Memory Helix / Teleportation Burst**, rather than the Restart / Genome Hacking
Mew ex found in other sets. That printing is implemented as provided by TCGdex.
Mega ex suffixes missing upstream are normalized from the printed name so
rule-box restrictions and three-Prize knockouts remain correct.

```sh
php DevTools/PokeSim/import.php --deck=PokeSim/Decks/brisbane-lopunny.txt
php DevTools/PokeSim/setup.php
php DevTools/PokeSim/simulate.php --games=1000 --deck1=brisbane-lopunny --deck2=sinistcha --output=lopunny-vs-dhelmise.csv
```

JSON Lines reset accepts `deckKey1` / `deckKey2` (`sinistcha` or
`brisbane-lopunny`) in addition to custom `deck1` / `deck2` export strings.
Bot selection follows the seat's deck, and snapshots preserve that profile.
CSV results include both deck keys, seat winner, original starting order and
rules/policy fingerprint.

Local bot and hotseat matches choose a fresh server-generated seed when the
optional Shuffle seed field is blank. Match settings show the current seed
for reproduction. Explicit seeds and batch/CLI simulations remain deterministic.

Damage statistics appear below the live board and in the main menu batch results.
Each graph uses the player's own turn number. Totals combine damage to opposing
Active and Bench Pokémon after modifiers, including placed damage counters from
attacks and abilities. Self-damage, healing and Pokémon Checkup are excluded;
overkill counts as the full damage dealt. Finished zero-damage turns are retained.
The current turn is shown as in progress. Original starting order is retained
through sudden death, with player turn numbers continuing across the tiebreaker.

Completed-game averages can be filtered by deck and show going-first/going-second
series, sample counts, and damage frequencies for each turn. Unreached turns and
old results without tracking are excluded. Batch averages use the saved batch;
live-match averages use the latest 200 completed matches saved in this browser.
Reloading a completed live match does not count it twice. Browser storage must
be available to retain these averages. CSV exports include `damageTurns` as JSON.
Tracking is saved in engine state separately from the bounded match log and
does not require code generation or a database migration.

The Dhelmise bot benches its first Dhelmise before spending the turn's Energy
attachment, powers that attacker, and uses Poltchageist's free retreat to bring
it Active. Recovery prioritizes a missing first Dhelmise before spare Energy.
Poltchageist receives an opening attachment only after productive search/draw
routes have been exhausted and no Dhelmise setup is available. This includes
retreating to a ready Dhelmise even before the four-Hide-'n'-Sneak damage boost.
The opening report measures Seat 1 going second, since the first player cannot
attack on their first turn:

```sh
php DevTools/PokeSim/opening-report.php --games=100 --seed=1 --trace=2 --opponent=brisbane-lopunny
```

After reaching four HNS, the bot stops searching merely to reach six, recovers
the next Dhelmise before surplus Energy when the current attacker is powered,
and commits the replacement and its Energy before a hand shuffle or attack.
It uses draw to find missing setup rather than holding Lillie in a large hand;
opening hands where Petrel/Blender cannot supply both missing attacker and
Energy prefer a draw route. Offered Ultra Ball searches can chain HNS discards
to four without recovering them from Discard and reducing the existing count.
Optional Poltchageist stays in hand as discard fodder until the combo is online.

The full-game report measures damage and powered-backup presence specifically
for Seat 1's Dhelmise deck, across both starting orders. "Established" means
turns where the four-HNS threshold was reached; backup presence is measured
immediately before an attack. Results are empirical, not guaranteed rates.
The UI's All decks view combines both seats and reports average damage, not
the percentage of successful boosted attacks.

```sh
php DevTools/PokeSim/dhelmise-report.php --pairs=100 --seed=1 --opponent=brisbane-lopunny
```

Dhelmise's Boss's Orders policy uses the same public-board prize ranking to
choose when to play Boss and which target to select. It prioritizes a winning
knockout, then prizes gained and removal of powered attackers/draw engines.
It can gust a multi-prize KO even when the current Active is already a one-prize
KO. It keeps Boss when the Active already gives the final prizes, and takes a
winning attack before further setup or draw. Attack values weight prize payouts
using the engine's one/two/three-prize rules, capped at prizes remaining.

Only game-winning gusts override combo, recovery and setup draw. Ordinary
Boss KOs retain a priority of 90, below the draw needed to power a replacement
Dhelmise. Speculative two-hit gusts are disabled: a visible damage estimate
does not guarantee a second attack before the opponent retreats, heals or
takes a KO. Actual face-down prize choices remain uniform; hidden hand,
deck and prize identities are not used. Lopunny retains its separate gust policy.

```sh
php DevTools/PokeSim/prize-plan-tests.php
```

```sh
php DevTools/PokeSim/damage-stats-tests.php
node --test DevTools/tests/pokesim-damage-stats.test.mjs
```


## First eligible attack instrumentation

New batches show **First eligible attack**, grouped by deck and original starting
order. Both seats are measured: own turn 1 going second, own turn 2 going first.
Rates distinguish attack declaration, completed attack resolution, the configured
goal attack, and a resolved goal attack with its readiness condition satisfied.
Damage and prizes are totals for the eligible turn, including ability damage.
Games ending before that opportunity are reported separately and included in the
all-completed denominator. Capped/error games and old results without telemetry
are excluded from rates; incomplete tracked games have their own count. Win
conversion compares reached openings with and without a fully enabled goal.

Dhelmise's goal is Vengeful Anchor with four Hide 'n' Sneak discards. Lopunny's
is Gale Thrust after moving Active that turn. Other decks default to any attack.
Add deck goals/readiness/milestones in `Custom/OpeningProfiles.php`; legality,
Energy matching, turn timing, traces and aggregation remain shared.

Misses retain multiple observed blockers: missing attacker, insufficient Energy,
powered attacker on Bench, unmet goal condition, special condition, an available
attack left unused, fallback attack, or an attack that failed to resolve. Setup
snapshots include candidate attack costs/Energy deficits, attacker availability
in hand/discard, Energy in hand/discard, and spent attachment/Supporter flags.
These diagnose the played line, not whether some alternative line could succeed.
The tracker does not search counterfactual action sequences or inspect hidden
opposing cards, deck order or Prize identities.

Expand **Per-game damage and opening trace** to inspect milestones and actions.
Traces include both setup turns when going first and up to 160 actions per seat;
truncation is explicit. The saved seed, deck pair, starting order, exact deck hash
and rules/bot fingerprint allow replay with the same code and deck versions.
Snapshots and traces are private serialized state and never added to player
observations. Existing live saves without tracking remain playable. New live
matches record telemetry, while its report UI is currently in batch results.
`openingStats` is an additive JSON field in simulation results and CSV exports.
No regeneration or database migration is needed. The original specialized
`opening-report.php` remains available.

```sh
php DevTools/PokeSim/opening-stats-report.php --games=100 --seed=1 --deck1=sinistcha --deck2=brisbane-lopunny --trace=2
php DevTools/PokeSim/opening-stats-tests.php
node --test DevTools/tests/pokesim-opening-stats.test.mjs
```

Use the same seed/order pairs for comparisons, then validate on an untouched seed
range and several opponents. Keep bot-policy changes separate from deck changes.
Counts accompany every rate; conversion is observational rather than causal.
Paired games share a seed, so treat the seed pair as the unit when estimating
uncertainty outside the report. Backup readiness and later attack gaps are not
part of this opening-focused report.


## Card-count experiments

`DevTools/PokeSim/deck-experiment.php` tests validated 60-card variants without
changing `Decks/sinistcha.txt`, the bot policy, or a live session. Its initial
variant set swaps existing implemented cards into Dhelmise / Sinistcha. Add count
deltas to `PokeExperimentVariants()` for another experiment. It retains exact
decks, card changes, policy fingerprints, per-game outcomes and compact opening
statistics; full traces can be recreated from the saved seeds and deck arrays.
The simulation runner accepts optional deck-array overrides after its existing
arguments, so existing callers continue to use the registered decks.

```sh
php DevTools/PokeSim/deck-experiment.php --variant=baseline --pairs=100 --seed=1001 --output=baseline.json
php DevTools/PokeSim/deck-experiment.php --variant=retrieval-to-gear --pairs=100 --seed=1001 --output=gear.json
php DevTools/PokeSim/deck-experiment-tests.php
python DevTools/PokeSim/deck-experiment-analysis-tests.py
```

For comparisons, name run files `<phase>-<variant>.json`, including
`<phase>-baseline.json`, in one directory. The analyzer verifies matching policy,
opponent, seed range and sample size, and writes JSON/CSV comparisons:

```sh
python DevTools/PokeSim/analyze-deck-experiments.py DevTools/local/experiments/dhelmise-2026-10-02 --phase=validation
```

Paired win-rate differences use the same seed and starting order. Combined win
uncertainty clusters both orders by seed, rather than treating them as independent.
Changing card counts can change opening deals, mulligans and later RNG consumption;
the two decks do not necessarily receive identical opening hands. Approximate 95%
intervals use the observed variance of paired differences. Incomplete games are
excluded from comparisons, including their whole seed pair for combined wins.
The paired opening comparison uses all completed games, counting unreached
opportunities as zero; the main opening report also retains reached-only rates.
Use a fixed screening set, advance a small number of candidates, then evaluate
fresh validation seeds and another opponent. Screening estimates are exploratory
and do not account for selecting among variants.


The current experiment matrix fixes Special Red Card and Black Belt's Training
at one copy each; attempted changes to either are rejected. It varies Pok�mon
search, Supporter/draw access, Blender-search routes, Energy search, recursion,
Energy count and Hide 'n' Sneak Pok�mon ratios. `PokeExperimentVariants()` includes
single-card comparisons and a small set of predeclared two-card changes.
Historical results from earlier variant sets retain their exact tested decks.

A plan-driven runner can execute these independent PHP simulations with bounded
concurrency. Its phase configuration is saved before screening, with separate
seed ranges for validation and mirror checks:

```sh
python DevTools/PokeSim/run-deck-experiments.py DevTools/local/experiments/dhelmise-balance-2026-10-02 --phase=screen --workers=6
```

The 2026-10-02 balance plan advances the two strongest win-rate candidates and
one candidate chosen for opening consistency, subject to a win-rate floor.
Candidate selection uses screening seeds only. All variants preserve both
singleton tech cards and the original registered deck remains unchanged.


`dhelmise v2` is available in play, hotseat, and batch selectors. It has four
Lillie's Determination, one Energy Retrieval, four Pokegear, four Poke Pad, and
two Ultra Ball, with Black Belt removed. Its opening goal remains fully enabled Vengeful Anchor.
Exact card counts identify imported copies of this list regardless of entry order.

```sh
php DevTools/PokeSim/deck-experiment.php --base=dhelmise-v2 --variant=belt-to-pad --pairs=120 --seed=60001 --output=v2-pad.json
```

The v2 suite uses the frozen pre-swap list in
`DevTools/PokeSim/fixtures/dhelmise-v2-experiment-baseline.txt`, preserving the
historical experiments independently of the current selector list. It explores
Black Belt replacements, Ultra Ball to Poke Pad changes,
and Meowth ex as an additional Petrel/Blender route. The Dhelmise bot preserves
Meowth during setup and searches/benches it for needed Supporter access. This
uses only information exposed to that seat. Baselines without Meowth retain their
existing policy behavior. Poke Pad cannot search Meowth because it has a Rule Box.

Compact experiment openings additionally record `blenderTurn1Played` and
`blenderByOpportunityPlayed`, extracted from accepted Trainer actions before
removing private traces. These are card-specific analysis fields; the public
opening report continues to use deck-independent attack and readiness metrics.
First-turn Blender play is separate from the first eligible attack: going first,
the eligible attack is on own turn two. Reached-only opening rates and all-game
Blender rates have explicit denominators in comparison exports.

Results, predeclared phases, selected finalists, exact decks and research notes
are saved under `DevTools/local/experiments/dhelmise-v2-2026-10-02/`.


The Dhelmise policy now spends a legal Poke Pad before a planned Lillie draw and
prefers its free Pokemon search over Ultra Ball once four Hide 'n' Sneak cards
are discarded. The pre-draw rule applies only if Lillie was already the next
selected action, preserving available Blender/Petrel routes and winning attacks.
Ultra Ball retains priority when its discard cost completes the damage combo.
Regression tests exercise the actual card choices and verify preserved resources.
The repeated experiment matrix is saved separately under
`DevTools/local/experiments/dhelmise-sequencing-2026-10-02/`, using the same seed
ranges and deck lists as the earlier policy. These reruns isolate policy changes
and are not newly untouched validation samples.


Call Bell slot experiments use the updated v2 snapshot in
`DevTools/PokeSim/fixtures/dhelmise-call-bell-baseline.txt`:

```sh
php DevTools/PokeSim/deck-experiment.php --base=dhelmise-v2 --suite=call-bell --variant=bell-to-meowth --pairs=500 --seed=120001 --output=bell-meowth.json
```

This suite screens Ultra Ball, Meowth ex, Energy Retrieval, and basic Energy
replacements at several Call Bell counts. It records accepted Call Bell plays
and opening search targets before compacting traces. Usage is descriptive;
paired replacement comparisons measure slot tradeoffs. Screen and fresh-seed
confirmation plans and results are in
`DevTools/local/experiments/dhelmise-call-bell-2026-10-02/`.

## Relicanth / Fossils

Select Relicanth / Fossils for bot play, hotseat, or batch simulations. The
relicanth-fossils deck uses fourteen fossils, eight Basic Water Energy, four
Night Stretcher, two Energy Retrieval, three Energy Search, two Call Bell, and
Secret Box as its ACE SPEC. Its bot develops Fossil Quarry and an Antique fossil Bench,
holds spare Relicanth and Energy in hand while filling all five Bench slots with
Antiques. After an Active knockout, it promotes an Antique, benches Relicanth
on its next turn, releases the Active Antique, then attaches Energy and refills
to five Antiques through Quarry or hand. A safety backup is benched when no
Antique is available to prevent an empty-field loss.
Fossils count as Pokemon only in play, cannot start the game, retreat, or receive
Special Conditions, and can be discarded without awarding a Prize. Use Fossil
Quarry appears in Your moves once per player turn while the Stadium is in play.

```sh
php DevTools/PokeSim/import.php --deck=PokeSim/Decks/relicanth-fossils.txt
php DevTools/PokeSim/setup.php
php DevTools/PokeSim/relicanth-tests.php
php DevTools/PokeSim/simulate.php --games=100 --deck1=relicanth-fossils --deck2=brisbane-lopunny
```

Match stats are available from the sidebar dialog and open automatically at game
end. They no longer occupy the board during play. Relicanth opening readiness
measures four fossils; it is separate from whether a legal attack was made.


The 2026-10-02 sustain investigation reproduced the original damage decline.
The bot now searches Energy for unpowered backups, recovers Relicanth before
Energy when no attacker survives, and draws despite a large redundant fossil
hand when replacement attackers are missing. Secret Box discards spare fossils
before attackers and Energy, then searches separate Item, Tool, Supporter and
Stadium candidates. Its source is CardCode/RelicanthAbilities.php; setup saves
and regenerates it. Card text was checked against the official preview:
https://www.pokemon.com/us/news/check-out-hearthflame-mask-ogerpon-ex-secret-box-and-more-from-pokemon-tcg-scarlet-violet-twilight-masquerade

DevTools/PokeSim/relicanth-sustain.php uses the frozen original deck in
DevTools/PokeSim/fixtures/relicanth-sustain-baseline.txt. Exact decks, results
and traces are in DevTools/local/experiments/relicanth-*.json. Screening used
seeds 200001-200100, both orders (200 games per list): original policy/list,
corrected policy/original list, recovery, draw/Energy, lean, draw/Secret Box,
and lean/Secret Box. Fresh confirmation used seeds 210001-210300, both orders,
with the corrected bot held fixed:

| List vs Dhelmise v2 (600 games each) | Turn 2 damage | Turn 4 damage | Turn 6 damage | Turn 6 damaging turns | Wins |
| --- | ---: | ---: | ---: | ---: | ---: |
| Original composition | 109.5 | 82.9 | 59.7 | 39% | 29.2% |
| Lean without ACE SPEC | 120.8 | 110.7 | 86.5 | 58% | 33.2% |
| Lean with Secret Box (selected) | 123.4 | 107.2 | 91.1 | 62% | 31.0% |

The selected list prioritizes sustained damage. Secret Box was not demonstrated
to improve win rate over the lean list without it. Against Brisbane Lopunny on
seeds 220001-220100 (200 games each), original composition won 30.5%, selected
list 32%. Every simulation completed. Averages include zeros and only turns
reached: selected-list Dhelmise turn 6 has 466 samples; turn 7 has only 129.
Later-turn curves reflect survivor selection, not a guaranteed seven attacks.
Dhelmise remains a difficult matchup due to Relicanth's Grass weakness.

```sh
php DevTools/PokeSim/relicanth-sustain.php --variant=lean-box --pairs=300 --seed=210001 --output=new-relicanth-results.json
```


The 2026-10-03 replacement-cycle correction removes preemptive benching merely
because an opponent threatens a knockout. That policy spent the fifth Antique
slot on Relicanth and commonly capped base damage at 130. The bot now holds its
next attacker and attachment in hand, promotes an Antique after a knockout,
benches the replacement before Quarry can consume the open slot, releases the
Active Antique, promotes Relicanth, attaches, and refills before attacking.
Lillie is held when current resources and the replacement chain are ready,
so an unnecessary shuffle does not lose a held backup. Empty-field protection
still allows a safety backup when no Antique is available. Existing benched
Relicanth are promoted normally; releasing Antiques awards no additional Prize.

Paired seeds 230001-230100 (200 games per policy, unchanged 14-fossil deck)
raised Dhelmise wins from 69/200 to 113/200; turn-4 average damage from 113.7
to 122.8. Corrected-policy attacks had five Antiques 614/795 times (77.2%).
Nearby 12- and 16-fossil lists, swapping fossil slots with Call Bell and Energy
Search, won 106/200 and 101/200 respectively; neither displaced the 14-fossil
list. These are exploratory comparisons, not proof of a globally optimal deck.

Fresh seeds 250001-250300 (600 games against Dhelmise, both starting orders)
completed with 297 wins (49.5%) and 1740/2365 attacks with five Antiques (73.6%).
Turn-2, turn-4 and turn-6 damage averages were 129.6, 115.6 and 96.4, with
562, 489 and 420 reached-turn samples. Against Brisbane Lopunny on seeds
240001-240100 (200 games), 1027/1218 attacks had five Antiques (84.3%), with
88 wins (44%). All games completed. Exact results are in
DevTools/local/experiments/relicanth-cycle-*.json. The runner now records
attackFossils directly at attack time, separately from damage modifiers.

Regression tests cover the actual knockout, Antique promotion, replacement
benching, serialized release/promotion, Energy attachment, and a 160-damage
attack with refill from either hand or Quarry. They also check that available
refill actions precede a non-winning attack. No generated files require changes
for this bot policy correction.


## relicanth v2 - draw

The separate relicanth-v2-draw selector uses the supplied 60-card list in
Decks/relicanth-v2-draw.txt and Bot/RelicanthDrawBot.php. The original Relicanth
list and policy remain selectable. Imported draw lists are routed to the new
policy when they contain its draw/tool/Energy package. Bot matches, hotseat,
batch selectors, match labels, damage stats and opening stats recognize it.

The draw policy shares the five-Antique replacement cycle, prepares the next
attacker and attachment, equips Lucky Helmet before drawing, favors late Lacey
when it draws eight, and uses Iris to preserve held resources. Iris discards
spare fossils, spent Stadiums or surplus Supporters before attackers, Energy
and recovery. Judge is a lower-priority draw/disruption option. Unspent Legacy
Energy is preferred for a needed Relicanth attachment; basic-only search and
recovery cannot find or recover it.

Card authoring is in CardCode/RelicanthDrawAbilities.php. Lacey draws four or
eight using the opponent's remaining Prizes. Iris pays one other hand card
before drawing to six. Judge shuffles both hands and draws four each. Lucky
Helmet draws two for opposing attack damage to its Active wearer, including
lethal damage, before knockout resolution; counters, Bench damage, self damage
and prevented damage do not trigger it. Legacy provides all types but one
Energy, and reduces opposing attack-damage knockout Prizes once per owner per
game. It does not reduce counter/checkup/self-damage knockout Prizes. The use
flag and damage cause survive saved states. Generated modifier hooks live in
Schemas/PokeSim/GameSchema.txt; edit that source or card authoring, then regen.

Official card references:
[Lacey](https://www.pokemon.com/us/pokemon-tcg/pokemon-cards/series/sv07/139/),
[Iris](https://www.pokemon.com/us/pokemon-tcg/pokemon-cards/series/sv09/149/),
[Lucky Helmet](https://www.pokemon.com/us/pokemon-tcg/pokemon-cards/series/sv06/158/),
[Legacy Energy](https://asia.pokemon-card.com/sg/card-search/detail/12648/).

```sh
php DevTools/PokeSim/import.php --deck=PokeSim/Decks/relicanth-v2-draw.txt
php DevTools/PokeSim/setup.php
php DevTools/PokeSim/relicanth-draw-tests.php
php DevTools/PokeSim/simulate.php --games=200 --seed=280001 --deck1=relicanth-v2-draw --deck2=dhelmise-v2 --output=draw-results.csv
```


## Relicanth draw slot and package experiments (2026-10-03)

Replacing two Judge with two Brave Bangle was the strongest tested Lopunny
option. One Bangle for one Judge is the smaller consistency tradeoff.
Two Rare Candy plus one Bastiodon, cutting both Judge and one Cover Fossil,
also improved Lopunny while approximately maintaining Dhelmise results.
These are results for two bot archetypes, not a whole-metagame optimum.

| Changes | Dhelmise v2 wins | Lopunny wins | Original Dhelmise/Sinistcha wins |
| --- | ---: | ---: | ---: |
| Baseline | 60.7% | 45.0% | 64.7% |
| +1 Bangle, -1 Judge | 59.3% | 55.7% | 62.0% |
| +2 Bangle, -2 Judge | 59.3% | 61.3% | 60.3% |
| +2 Candy/+1 Bastiodon, -2 Judge/-1 Cover | 61.0% | 54.0% | 65.0% |
| +2 Candy/+1 Bastiodon/+1 Bangle, also -1 Plume | 55.3% | 57.0% | 59.7% |
| +2 Candy/+2 Bastiodon/+1 Bangle, also -1 Quarry | 56.0% | 59.3% | 57.7% |

Each validation cell has 300 games: 150 fresh seeds, both starting orders.
The paired Lopunny win gains are +10.7 points for one Bangle (approximate
95% interval +/-4.3), +16.3 for two (+/-4.9), and +9.0 for 2 Candy/1 Bastiodon
(+/-6.0). Intervals are clustered by seed, including both orders. Screened
variants are exploratory; finalists were chosen before validation.

Lopunny zero-damage rates on reached, attack-eligible own turns 1-7 were
6.3% baseline, 7.2% with one Bangle, 8.2% with two, and 9.1% with 2 Candy/1
Bastiodon. Mandatory first-player turn one is excluded; unreached turns
contribute no sample. Protection can lengthen games and survivorship changes
these rates, so inspect wins, damage, opening damage and samples together.

The single-copy screen tested all 19 entries with an inert replacement:
6,000 games including baseline, 75 seeds, two opponents and both orders.
Judge, Boss and redundant fossil copies had small marginal consistency costs.
Keep Armor at four when testing Candy. Removing one Relicanth raised pooled
zero-damage turns by about 4.6 points. Stretcher, basic Energy, search/recovery
and Helmet were more costly to cut; Legacy removal lost about seven pooled
win points. Individual fossil rankings are noisy and depend on which copy's
shuffle slot was replaced. Retain four Relicanth/Stretcher and Legacy.

The package screen ran 3,600 games: 18 variants, 50 seeds, two opponents and
both orders. It tested 1/2/3 Bangle replacing Judge or Helmet; Candy/Bastiodon
counts 1/1, 2/1, 2/2, 3/2 and 4/2; four combined packages; and cumulative
blank controls. Judge slots outperformed replacing Helmet. Two Judge blanks
lost 3-5 win points in that sample; five cumulative blanks lost 15-18.
Individually cheap cuts are not free when combined. Larger packages failed
to recover enough of that cost. Fresh validation added the original
Dhelmise/Sinistcha list and ran 5,400 games. All 15,000 games completed
without errors or action caps.

Bastiodon takes an Antique's Bench slot: damage falls from 160 to 130, or
from 190 to 160 with Bangle against ex. Fresh 330-HP Lopunny changes from
two hits to three. Dhelmise has 140 HP, so this also loses an immediate KO.
Bulwark protects the entire board from attack damage at at most two attached
Energy, respects disabled abilities, and permits damage counters. A lone
Bastiodon loses protection when gusted Active; this list has no switching
Item. The 2 Candy/1 Bastiodon package established Bulwark by own turn three
in only about 15-16% of validation games, and sometime in 35-47%.

Card macros are in CardCode/RelicanthDrawAbilities.php; helpers are in
Custom/FossilLogic.php. Rare Candy checks the printed intermediate ancestry
including Armor -> Shieldon -> Bastiodon, own turn and entry/evolution
restrictions. Damage, attachments and the fossil evolution stack survive.
Brave Bangle was already implemented. New artwork was imported.

The bot pursues reachable Candy pieces, preserves Armor during replacement,
discards surplus pieces and avoids late Bulwark when the opposing Active
already has three Energy or uses damage counters. It can pursue a second
Bulwark when Bangle preserves the hit count; none actually reached two on
the Bench in validation. Bangle wins tool priority against visible ex
threats; Helmet keeps priority against a non-ex board. Opponents build a
third Energy and forecast how Boss moves Bastiodon. Validation froze these
refinements and used a fresh baseline. Comparing different phases directly
would mix policy changes with deck changes.

Frozen plans are in DevTools/PokeSim/fixtures/relicanth-package-experiments/;
importable candidates are DevTools/PokeSim/fixtures/relicanth-draw-*.txt.
The registered draw deck still contains the user's original 60 cards.
Per-game JSON and screen/packages/validation-summary.csv are in
DevTools/local/experiments/relicanth-draw-packages-2026-10-03/.

Blank IDs exist only in the CLI process's dictionaries and macro registries.
They cannot be played or searched as Pokemon/Energy, occupy hand slots and
can pay Iris's discard. Normal web imports cannot resolve them. They replace
one original slot before shuffling; later decisions, searches and mulligans
can legitimately diverge. A corrected blank legality audit reproduced all
150 prior games exactly. The read-only observer records Bulwark milestones
throughout each game, rather than relying on the bounded log.

Package tests (104 including draw checks), experiment tests (82), and
existing bot/Lopunny/prize/simulation and Relicanth regression suites passed.
Regenerate macros on a fresh checkout. To reproduce, copy the frozen plans
into a new output directory and run each phase with its recorded settings.
Current code reproduces the validation policy; earlier phases record their
historical policy hashes and should only be compared with their own baseline.

```sh
php DevTools/PokeSim/setup.php
php DevTools/PokeSim/relicanth-package-tests.php
php DevTools/PokeSim/relicanth-experiment-tests.php
python DevTools/PokeSim/run-relicanth-packages.py <new-directory> --phase=screen --pairs=75 --seed=70001
python DevTools/PokeSim/run-relicanth-packages.py <new-directory> --phase=packages --pairs=50 --seed=80001
python DevTools/PokeSim/run-relicanth-packages.py <new-directory> --phase=validation --pairs=150 --seed=90001
python DevTools/PokeSim/analyze-relicanth-packages.py <new-directory> --phase=validation
```

Recorded policy hashes: screen `a122e050fe36b21b`, packages `aacf642fd5882971`, validation `2d103afa6c76c3cc`.


## Water Energy blank ablations (2026-10-03)

The original draw list was frozen with seven Basic Water plus one Legacy.
Only 1-4 Water copies were replaced with inert blanks in their original
shuffle slots. Legacy, three Energy Search, two Energy Retrieval and four
Night Stretcher stayed fixed, as did the bot policy. This isolates Water
slots; it does not test substituting useful Trainers or a Bangle variant.
The registered list was not edited.

The experiment ran 3,000 completed games without errors/caps: 150 fresh
seeds (110001-110150), both starting orders, against Dhelmise v2 and Lopunny.
Each count has 300 games per matchup. Zero-damage and energy-starved rates
cover reached own turns 1-7, excluding mandatory first-player turn one.
Opening damage means positive damage on the first attack-eligible turn;
unreached opening opportunities count as failures for that metric.

| Water left (plus Legacy) | Opening damage | Zero turns | Energy-starved turns | Dhelmise wins | Lopunny wins |
| --- | ---: | ---: | ---: | ---: | ---: |
| 7 | 95.0% | 7.5% | 2.6% | 67.3% | 44.3% |
| 6 | 94.2% | 8.5% | 3.5% | 64.7% | 40.0% |
| 5 | 91.7% | 10.4% | 5.0% | 63.0% | 41.0% |
| 4 | 89.7% | 12.4% | 7.1% | 62.0% | 36.3% |
| 3 | 85.3% | 17.5% | 12.0% | 54.3% | 34.3% |

One cut lost 3.5 pooled win points (approximate paired 95% interval
+/-2.4); two lost 3.8 (+/-3.5); three lost 6.7 (+/-3.8); four lost 11.5
(+/-4.4). Paired intervals cluster both matchups and starting orders by seed.
Small differences between six and five Water are noisy; increasing energy
starvation is the clearer trend. Six Water is a possible one-slot compromise,
but keep seven for consistency. At four or fewer Water the penalty is
substantial. Compare against this fresh baseline, not earlier seed sets.

The CLI observer now classifies every eligible completed zero turn as no
Active Relicanth, Active Relicanth without attack Energy, or other. This is
a descriptive end-turn snapshot, not proof of a unique cause. All recorded
zero turns were classified, and the observer only reads state.

Frozen plan: DevTools/PokeSim/fixtures/relicanth-package-experiments/water-plan.json.
Full results: DevTools/local/experiments/relicanth-water-ablations-2026-10-03/,
including water-summary.csv per matchup and water-pooled-summary.csv.
The multi-Water/Legacy retention checks pass in the 98-check experiment suite.

```sh
python DevTools/PokeSim/run-relicanth-packages.py <new-directory> --phase=water --pairs=150 --seed=110001
python DevTools/PokeSim/analyze-relicanth-packages.py <new-directory> --phase=water
```


## Energy Retrieval contribution (2026-10-03)

A fresh paired blank ablation ran 3,000 completed games (no errors/caps),
150 seeds 120001-120150, both starting orders, Dhelmise v2 and Lopunny.
Only Retrieval/Water slots changed; other recovery, search and policy stayed
fixed. Each variant has 300 games per matchup.

| Water | Retrieval copies | Late energy-starved turns | Dhelmise wins | Lopunny wins |
| --- | ---: | ---: | ---: | ---: |
| 7 | 2 | 2.5% | 70.0% | 43.3% |
| 7 | 1 | 3.3% | 68.7% | 43.0% |
| 7 | 0 | 4.9% | 64.0% | 43.3% |
| 6 | 2 | 3.4% | 67.7% | 44.3% |
| 6 | 0 | 6.5% | 62.7% | 41.7% |

With both copies, Retrieval was used in 51.3% of games,
played 352 times and returned 542 Water (0.90 per game).
72.7% of plays and 80.4% of returned Energy came on own turn four
or later. Night Stretcher returned 222 Water; Retrieval supplied
70.9% of Energy recovered from discard. These are recovery counts,
not proof that each returned card was later attached.

Keeping 1 Retrieval changed pooled wins by -0.8 points
(approximate paired 95% interval +/-1.7), versus two at seven Water.

Keeping 0 Retrieval changed pooled wins by -3.0 points
(approximate paired 95% interval +/-2.6), versus two at seven Water.

The second copy is a reasonable space candidate: keep one before removing
both. The no-Retrieval variant compensates with search and Stretcher, but
has more late energy stalls. At six Water, removing both also compounds
the consistency loss. These effects are for blank substitutions in the
original draw list, not a guarantee about a specific useful replacement.

Late stall rates cover reached own turns 4-7 and are weighted by turn samples.
An energy stall is a zero-damage end-turn snapshot with Relicanth Active and
unable to pay its attack cost. Usage counts cover complete games, including
turns after seven. Comparisons and intervals cluster both matchups and orders
by seed. Card plays are observed from the accepted Trainer log event, while
Energy returned is measured as the change in basic Energy count across the
recovery chooser. Zone references are not durable across compaction. An
initial usage-counter audit caught that error; all reported results are from
the rerun in the validated directory. Every corrected Retrieval play returns
one or two Energy, and per-game count consistency checks pass.

Frozen plan: DevTools/PokeSim/fixtures/relicanth-package-experiments/retrieval-plan.json.
Validated JSON, per-matchup retrieval-summary.csv and pooled results:
DevTools/local/experiments/relicanth-retrieval-validated-2026-10-03/.
The Retrieval/Water/blank invariants pass in the 106-check experiment suite.

```sh
python DevTools/PokeSim/run-relicanth-packages.py <new-directory> --phase=retrieval --pairs=150 --seed=120001
python DevTools/PokeSim/analyze-relicanth-packages.py <new-directory> --phase=retrieval
```


## Relicanth-v3-meta-tune

Selectable in play and simulations. Based on relicanth v2 - draw: +2 Brave Bangle, -2 Judge, -1 Energy Retrieval, +1 Boss's Orders. The 60-card list retains seven Water and one Legacy Energy. Its separate bot entry point shares the tested draw/fossil-cycle policy, including matchup-aware tool and gust choices.

Deck: `Decks/relicanth-v3-meta-tune.txt`. Regression: `php DevTools/PokeSim/relicanth-meta-tune-tests.php`.


## Relicanth v3 flex-slot measurements (2026-10-03)

7,600 completed paired blank-slot games against Dhelmise v2 and Brisbane Lopunny. Plans, raw games, paired summaries and methodology: `../DevTools/local/experiments/relicanth-v3-flex-2026-10-03/`. One Cover Fossil was the gentlest confirmed cut for missed-attack consistency. One Helmet or Iris incurred a measured draw-consistency cost; two-slot combinations lost roughly 6-8 win percentage points. Retain four Lacey and avoid cutting four fossils. Registered v3 remains unchanged.

`php DevTools/PokeSim/relicanth-consistency-tests.php` verifies draw diagnostics, including Helmet draws separately from automatic turn draws, last-card deck-out, and valid draw/fossil blank ablations.

### Relicanth v4 - Colress

`Decks/relicanth-v4-colress.txt` preserves the supplied 60-card list and printing codes. It is available for local play, bot matchups, deck previews, and Limitless text export. `Bot/RelicanthColressBot.php` uses the fossil replacement/draw plan, recognizes the new printings, prefers Spiky Energy for attachments and Colress searches, and plays Unfair Stamp when legal and useful.

Antique Jaw Fossil reduces incoming attack damage before Weakness/Resistance. Each attached Spiky Energy places two counters after opposing Active attack damage, including lethal damage. Unfair Stamp requires a Pokemon knockout during the preceding opponent turn.

Card source: `CardCode/RelicanthColressAbilities.php`, included by setup. Regression: `php DevTools/PokeSim/relicanth-colress-tests.php`.
### Relicanth v6 - Explorer's Guidance

`Decks/relicanth-v6-explorers-guidance.txt` contains the supplied 60-card list. It uses the Colress/Bastiodon bot policy, with Explorer's Guidance available for finding missing resources. Explorer's Guidance privately looks at up to six cards, keeps exactly two (or all available if fewer than two remain), and discards the rest without shuffling. Its authored macro lives in `CardCode/RelicanthColressAbilities.php` and is included by setup. Regression: `php DevTools/PokeSim/relicanth-explorers-tests.php`.
### Relicanth v7 - Lana's Aid

`Decks/relicanth-v7-lanas-aid.txt` contains the supplied 60-card list with four Relicanth, nine Energy including Legacy Energy, and 47 Trainers. The existing Colress bot policy now recovers missing attackers/basic Energy with Lana's Aid and uses Special Red Card when legal and useful. Lana's Aid allows up to three non-Rule-Box Pokemon and Basic Energy from discard; fossil Items and Special Energy are excluded. Its authored macro lives in `CardCode/RelicanthColressAbilities.php` and is included by setup. Regression: `php DevTools/PokeSim/relicanth-lanas-tests.php`.
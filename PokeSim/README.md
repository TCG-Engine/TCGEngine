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

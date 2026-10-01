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
It also prioritizes powered Dhelmise attackers, a Sinistcha evolution line,
useful Trainer searches, knockouts, and worthwhile switches. It avoids recovering
discarded Hide 'n' Sneak Pokémon without a board-building reason. Prize choices
are uniform; the policy does not inspect hidden opposing identities or deck
order. This is a deterministic baseline heuristic, not an optimized competitive
player. Bot mode keeps the opposing hand hidden and rejects manual player 2
actions. Hotseat remains available through **Match settings → Start hotseat match**.

The CLI also supports `{"command":"bot-step","player":2}` for one chosen action
and `{"command":"bot-run","player":2}` to advance until player 2 needs the
human to act. These commands use the same policy and action validation.

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
authored helpers/macros. Tools, Stadium effects and other advanced mechanics
are not implemented by this initial deck.

All 20 distinct printings in `Decks/sinistcha.txt` have authored implementations.
The deck has no Shuppet: Banette can be discarded for Hide 'n' Sneak counts, and
its attack is implemented for states in which it is legitimately in play.
Other cards are rejected unless their mechanics are supported: vanilla attacks
and basic Energy can work directly from metadata, while effects need macros.
Format rotation/ban lists are not enforced; TCGdex legality is available in the
generated dictionaries for future deckbuilding tooling.

## Card authoring and regeneration

Schema source is in `Schemas/PokeSim/`. Shared rule helpers are in `Custom/`.
Versioned card source is `CardCode/DeckAbilities.php`, containing the same macro
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
```

Tests exercise every authored card, legality, persistence across multiple awaits,
discarded-slot references, evolution, retreat, protection, Prize/promotion and
deck-out. The smoke script completes three seeded mirror games while serializing
and restoring state between every action. Its simple action policy is an engine
exerciser, not a deck-strength evaluator or RL integration.

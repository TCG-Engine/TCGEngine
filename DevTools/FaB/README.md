# FaBSim / FaBDeck generation

FaBSim follows Talishar's import boundary: functional card ids are normalized card name plus
pitch (`whelming_gustwave_red`), while printing ids remain metadata. Source card JSON comes from
`the-fab-cube/flesh-and-blood-cards`; FaBDeck reflects FaBSim's dictionary and art corpus.

```powershell
php zzCardCodeGenerator.php rootName=FaBSim withPreview=1
php zzGameCodeGenerator.php rootName=FaBSim
php zzGameCodeGenerator.php rootName=FaBDeck
php zzTurnGenerator.php rootName=FaBSim
php SharedUI/Render/GenerateSites.php FaBSim
php SharedUI/Render/GenerateSites.php FaBDeck
```

`Schemas/FaBSim/ImportSchema.txt` defaults `downloadImages=false`, because a full 4,900-card art
sync is large. Run `php zzCardCodeGenerator.php rootName=FaBSim downloadImages=1` for an
art-worker/deployment run; subsequent ordinary dictionary
regenerations use `FaBSim/GeneratedCode/cardArrayCache.json` and do not refetch the data.

Fabrary's integration endpoint requires an API key even when the deck itself is public. Configure
the same `$FaBraryKey` used by Talishar in `APIKeys/APIKeys.php`, or set `FABRARY_API_KEY` in the
server environment. FaBSim calls Fabrary's `/prod/v1/decks/{slug}` endpoint with `x-api-key` and
understands its `identifier`, `total`, and `sideboardTotal` fields. Without a configured key the
UI reports that setup requirement instead of incorrectly claiming the deck is private.

The first gameplay slice supports shared deck import, setup, draw, pitch, arsenal, resource
payment, basic play destinations, end-of-turn cleanup, and the FaB arena zones. Card-specific
rules are intentionally added through the normal CardEditor macro workflow rather than copied
from Talishar's GPL game implementation.

## UPF and Welcome to Rathe

The FaBSim menu hosts private four-player UPF rooms through the shared waiting room.
Share the invite link; each player imports a young-hero deck and marks ready. The host
can start after all four players have valid decks and are ready. Validation checks the
40-card starting deck, 52-card pool, pitch-specific copy limits, specializations, classes,
and equipment slots. The board keeps other players' hands and face-down banished cards
private. Attacks follow live-seat adjacency and combat-chain focus.

WTR rules combine generated card abilities with shared combat, cost, activation, and turn
hooks. `wtr_abilities.json` contains the complete 226-identity set snapshot, including
vanilla cards and cards implemented by those shared hooks. Interactive effects use generated
await continuations: optional costs, searches, hand exchanges, reactions, Katsu, Refraction
Bolters, Hope Merchant's Hood, and Spring Tunic. Hero/equipment activations use pitch payment
and the stack. End-of-turn cleanup lets every player order their pitched cards.

Import the snapshot through the configured CardEditor repository, then regenerate:

```powershell
# Set MYSQL_DATABASE_NAME to this installation's card-code database when using local MySQL.
php DevTools/FaB/import_wtr_abilities.php
php zzGameCodeGenerator.php rootName=FaBSim
php zzTurnGenerator.php rootName=FaBSim
php SharedUI/Render/GenerateSites.php FaBSim
```

The importer preserves unrelated macros and rejects conflicting authored bodies. The
compatibility command `mark_wtr_implemented.php` now imports actual authoring instead of
writing blanket implementation flags with direct SQL. After reviewed MCP edits, refresh
the snapshot with `export_wtr_abilities.php`. Never guess a production database mapping.

Run the regression checks after generation:

```powershell
php DevTools/FaB/wtr_test.php
./DevTools/FaB/upf_lobby_test.ps1
```

The PHP suite includes the existing smoke/UPF checks, generated choices for player four,
ability payment, card-family pitch variants, combo/defense/cost modifiers, damage prevention,
pitch ordering, and a deterministic match through three eliminations. The HTTP check needs
the local Apache/APCu server and creates a fresh test lobby/game; it checks readiness gates,
opening draws, and private hands from all four authenticated perspectives.

Browser checks exercised hosting, starting, target selection, Spring Tunic's modal, and
pitching to activate Bravo in Chromium. Firefox and Safari have not been exercised locally.
These regressions cover representative interactions, not every possible ordering of all
WTR card combinations.

Rules references: [UPF](https://rules.fabtcg.com/en/trp/09-special-formats/) and
[combat](https://rules.fabtcg.com/en/cr/07-combat/).
# UPF lobby bots

The host can select **Goldfish bot** in any empty UPF lobby slot and press **Add bot**.
Bots are ready immediately and can be removed with the existing **Remove** control.
A goldfish has 20 health, passes priority, and skips its turns; it needs no deck.

Bot choices come from the optional `LobbyBotAdapter` interface. Add profiles and setup
in `FaBSim/LobbyAdapter.php`, and implement the corresponding runtime behavior in
`FaBSim/CreateGame.php` / custom game logic. Bot profile IDs belong to Player identities,
so removing seats and compacting the table cannot move a bot onto a human's seat.

Checks: `php DevTools/FaB/lobby_bots_test.php`, `php DevTools/FaB/upf_bots_test.php`,
and `DevTools/FaB/upf_bots_lobby_test.ps1` against the local web server.

## UPF board layout

`FaBSim/Custom/MultiplayerLayout.php` owns the multiplayer presentation. The viewer's
field stays below three clockwise opponent summaries. Inspect expands an opponent;
All opponents returns to the overview. Both views use the same absolute seat/zone
references and the server's masked card data.

`FaBSim/Custom/MultiplayerTable.css` supplies the legacy-style table placement:
head/chest/legs down the left, arms beside chest, hero and weapons centered,
arsenal below, and utility piles on the right. Desktop events and chat are mounted
in a dedicated sidebar; narrow screens place activity below the wrapping field.

Stack and combat chain have separate floating panels. Their title bars support
pointer dragging and arrow-key movement, with positions saved locally and clamped
to the viewport. Hide buttons and Escape dismiss a panel; ordinary game updates
do not reopen it until an empty stack/chain becomes active again.

Visual checks: inspect each opponent and return; choose a target from a summary;
play through stack/chain transitions; hide and reopen both panels; move them and
reload; open chat and events at desktop and narrow widths. Confirm only the
viewer's hand is face up and that the viewer remains below the opponents for every seat.

## Fai heuristic bot

Select **Fai · Heuristic bot** in any empty UPF slot. For two-player practice, paste your deck on the main menu
and select **Play Fai bot · 1v1**; this starts immediately with the human in seat 1
and Fai in seat 2. `DevTools/FaB/fai_duel_test.ps1` checks this HTTP entry point.
The pinned deck is defined in
`FaBSim/BotDeck.php`, based on Fabrary `01HXDKBQNQB7TF0GNJN0MQ7CWC`.
The export has 39 main-deck cards; Salt the Wound from its inventory makes 40.
Tenacity remains in inventory. No Fabrary request is needed to start the bot.
Fai starts one Phoenix Flame in the graveyard before drawing; human Fai players
receive the optional setup choice.

`Custom/Bot.php` scores legal actions using its own hand and the public board.
It prefers go-again attacks before finishers, blue pitches, low-cost Kodachi
attacks, discounted Fai recursion, early Stubby/Art of War buffs, and low-life
adjacent targets. Blocking trades weaker cards for damage prevention and becomes
more defensive below nine life. It also resolves equipment and card choices,
sets arsenal, and returns pitch cards in their current order. This is a greedy
heuristic, without multi-action search or opponent-hand knowledge.

The existing shared mode-10017 transport drives one authoritative bot action per
request, supports all four seats, and waits when a human owes a decision.
Bot profiles persist in GameState through turn and chain resets; Goldfish remains
a separate passive profile. `Custom/FaiCards.php` holds shared card mechanics;
interactive abilities and per-card modifiers are authored through CardEditor.
The additive snapshot includes the new deck cards and both inventory cards;
existing WTR cards continue using the WTR implementation.

After deploying source changes, import the snapshot using the configured card-code
database and regenerate (Banish now persists counters and turn effects):

```powershell
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/fai_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/fai_bot_test.php
./DevTools/FaB/fai_lobby_test.ps1
```

Checks cover new card mechanics, generated await continuations, all-seat lobby
configuration, human-decision gating, and a full four-bot match. Browser checks
confirmed Fai setup, bot transport, equipment activation, and attack announcement.
Chain-link last-known properties follow [CR 7.0.3c](https://rules.fabtcg.com/en/cr/07-combat/).

## Arcane Rising (ARC)

`arc_catalog.json` contains all 219 ARC printing identities, including pitch
variants, heroes, equipment and tokens. It was extracted from the local FaB card
source cache by `printings[].set_id == "ARC"`, rather than the card's first set.
`build_arc_abilities.py` produces the complete, reviewable CardEditor snapshot
`arc_abilities.json` and fails if any identity lacks an implementation. Existing
Art of War code is reused from the Fai snapshot. Shared keyword and cost mechanics
also implement cards that need no individual macro body.

The implementation covers Mechanologist boost/items/steam and Dash setup;
Ranger face-up arsenal, arrows, reload and Azalea; Runeblade Runechants and
Viserai; Wizard instant permissions, arcane damage and barrier payments; and the
set's generic cards and equipment. Custom runtime helpers live in `ARCCards.php`
and `ARCAbilities.php`; interactive choices live in generated card continuations.
Arsenal visibility and steam counters are defined through schema/code generation.

Targeting uses absolute seat references in both duel and UPF. Spell targeting
respects UPF adjacency independently of combat focus, with the multiple-hero
exception for Forked Lightning. Runechants choose targets before priority resumes.
Cross-player prevention choices retain the original spell controller. Face-down
arsenal and private opt/search choices remain hidden from other seats.

Rules references: [ARC release notes](https://fabtcg.com/rules-and-policy-center/release-notes/arcane-rising/),
[UPF targeting](https://rules.fabtcg.com/en/trp/09-special-formats/), and
[Forked Lightning errata](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/errata-bulletins/errata-bulletin-5/).

Use the card-code database configured for the installation, then run:

```powershell
python DevTools/FaB/build_arc_abilities.py
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/arc_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/arc_test.php
php DevTools/FaB/fai_bot_test.php
./DevTools/FaB/arc_lobby_test.ps1
```

The importer refuses to overwrite different existing authored code; merge those
cards through CardEditor before retrying. Hard-refresh after regeneration.

ARC tests cover all 218 authored continuations from seat four plus targeted
assertions for duel/UPF spells, barrier, Forked Lightning, boost, Runechants,
Kano, Dash, arsenal, search, pitch/opt and hit effects. HTTP tests exercise a
four-hero lobby, seat-four setup and private-zone visibility, then Dash setup
through the main-menu duel route against Fai. These are regression and coverage
checks, not exhaustive proofs of every combination across sets. The existing
Fai bot remains the bot opponent; ARC-specific bot profiles are not added here.

## Professor Teklovossen bot — Round the Table

The `professor` profile uses the exact 40-card main deck and five starting weapon/
equipment cards from [Fabrary 01HAXKX6GY6J85NXAQ1FTCFX77](https://fabrary.net/decks/01HAXKX6GY6J85NXAQ1FTCFX77).
`professor_source.json` pins the public export; `FaBSim/ProfessorDeck.json` pins
its normalized deck. `professor_catalog.json` records the 26 card identities and
their rules text. Seven identities reuse ARC implementations; the additive
`professor_abilities.json` snapshot covers the remaining 19.

The shared bot transport now supports both `fai` and `professor`. The Professor
is selectable per UPF slot and through the main-menu bot selector for 1v1.
Existing duel requests without `botProfile` continue selecting Fai.

The Professor prioritizes missing Evo upgrades (especially Matrix and Rapid Fire),
plays eligible Evos from banish, retains useful blue pitch cards, uses equipment
and low-value cards to block, and boosts when its known resources support a
follow-up. It uses upgraded Blaster before other attacks when it grants go again,
and targets multiple legal heroes with Apocalypse Automaton. It does not inspect
opponents' hands or its deck order. These are greedy heuristics, not a search bot;
UPF-scaled Professor cards are naturally less efficient in a duel.

Evos are deck cards rather than starting equipment. Transformation stores base
cards as public subcards, clears their old counters/effects, and equips the Evo;
cards blocked from hand do not count as equipped Evos. Equipment on the combat
chain continues supplying equipped effects. Apocalypse Automaton supports zero
through X targets, clockwise defense declaration, a shared reaction step,
separate defense/damage totals, and elimination during multi-target damage.
The combat popup displays each target's damage separately.

Rules follow the [Bright Lights / Round the Table release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/bright-lights-round-the-table/).

After importing ARC, use the installation's configured card-code database:

```powershell
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/professor_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/professor_test.php
./DevTools/FaB/professor_lobby_test.ps1
```

Tests cover exact deck import, 1v1/UPF cost scaling, transformation/subcards,
Blaster upgrades, Firewall, Under Loop, Apocalypse's targeting and independent
defense reactions, and complete seeded duel/mirror/mixed bot matches. HTTP checks
exercise per-slot lobby selection and the duel profile route. Browser checks
cover the menu selector and playing/pitching/equipping an Evo.

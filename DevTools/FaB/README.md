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

## Crucible of War

`cru_catalog.json` covers CRU000–CRU197: 198 printing numbers mapped to 194
distinct card identities, including pitch variants and reprints. The reproducible
builder reuses existing card bodies and fails if an identity is unhandled.
`CRUCards.php` supplies shared continuous rules; interactive choices are saved in
CardEditor. `cru_support_abilities.json` adds Gambler's Gloves rerolls to the four
existing WTR dice effects. Import these after WTR/ARC/Fai/Professor:

```powershell
python DevTools/FaB/build_cru_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline' # Use this installation's configured database.
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/cru_abilities.json
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/cru_support_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/cru_test.php
```

The suite exercises 320 card continuations across duels and UPF, targeted rules
regressions, and complete seeded CRU hero/weapon/attack bot matches in both formats.
It covers all-opponent arcane damage, cross-seat barrier/trap payment, private
opponent-deck inspection, alternative Copper costs, dice rerolls, equipment
requirements, prevention, hero copying, Snag timing and per-chain versus per-turn
effects. It also runs the existing WTR and ARC checks.

Rules references: [CRU release notes](https://dhhim4ltzu1pj.cloudfront.net/media/documents/CRU_Release_Notes_v2.1.pdf)
and [current Foreboding Bolt text](https://cards.fabtcg.com/card/foreboding-bolt-3/CRU170-RF/).
The current card reference corrects the original printing's omitted “arcane”.
After regeneration, hard-refresh the game to load the updated combat-chain
equipment activation/highlighting bindings.

## Ira Crouching Tiger bot

`ira_source.json` pins Fabrary deck `01HAXKZMTRN4FR7CFTQA11A70N` (Round the
Table Ira). `FaBSim/IraDeck.json` contains its exact 40-card main deck, Edge of
Autumn, and four equipment. Select **Ira** in the main-menu bot selector or an
empty UPF lobby slot.

`ira_abilities.json` adds 19 identities, including Crouching Tiger, to the existing
WTR/CRU card implementations. `IraCards.php` handles Tiger creation, next-turn
banish permissions and combo effects; Ambush and Ephemeral use shared zone rules.
Unplayed Tigers remain banished after their play permission expires.

The heuristic uses its own hand and public information: generate Tigers before
attacking, buff them with Growl/Shuko, follow Tigers with Qi combos, favor cheap
go-again attacks, pitch lower-value blue cards, and use equipment to extend turns.
It uses the shared legal-target, blocking and priority logic in both formats.
This is a greedy heuristic, without game-tree search or learned policy.

```powershell
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/ira_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/ira_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/ira_lobby_test.ps1
```

Tests cover every added ability, exact deck import, combo prerequisites, equipment
costs/conditions, arsenal Ambush, next-turn permission through other UPF turns,
Ephemeral, and completed duel/mirror/mixed bot games. The HTTP test creates a
four-seat room with bots assigned out of order and a main-menu duel.

Rules references: [Round the Table release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/bright-lights-round-the-table/),
[Tiger Eye Reflex](https://cards.fabtcg.com/card/tiger-eye-reflex-3/TCC102/), and
[Comprehensive Rules](https://rules.fabtcg.com/pdf/en-fab-cr.pdf).

## Monarch

`mon_catalog.json` and `mon_abilities.json` cover all 307 MON card identities,
including pitch variants, heroes, equipment, tokens and reprints. The builder
explicitly accounts for every identity and fails on an unhandled card. Shared
continuous rules live in `MONCards.php`; activations live in `MONAbilities.php`;
interactive card bodies are imported into CardEditor and generated normally.

This adds the public, counted Soul zone to duels and UPF, charging, soul costs,
Soul Shackle, blood debt and banish permissions, Illusionist aura attacks,
phantasm, spectra, Ward and Spellvoid. Targets use actual seat IDs; all-opponent
effects visit every live opponent, and delayed effects expire on their owner's
turn. Prism, Boltyn, Chane and Levia work in both formats.

Import after the earlier set/deck snapshots. `mon_support_abilities.json` adds
Spellvoid choices to the existing ARC/CRU arcane effects. Its `previousCodeHash`
allows the importer to replace only the exact previously authored body; it
refuses to overwrite unrelated CardEditor edits. Importing the same snapshot
again is safe. Reapplying older ARC/CRU snapshots after MON may conflict with
these updated bodies.

```powershell
python DevTools/FaB/build_mon_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline' # Use this installation's configured database.
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/mon_abilities.json
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/mon_support_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/mon_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/mon_lobby_test.ps1
```

The tests exercise 500 authored continuations across duel and UPF seats,
targeted interactions and completed seeded games with the four MON heroes.
Regressions cover charge affordability, soul payment, Levia's blood debt,
Spellvoid against existing arcane cards, aura source destruction, phantasm,
Spectra, another player's Library activation, Sonata selection, all-opponent
Gateway damage and Glisten expiry. The HTTP test imports a 40-card Prism deck
and starts both a duel and a four-seat room with existing bots. These games use
the existing legal-action heuristic driver, not new Monarch-specific bots.

Rules references: [Monarch release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/monarch/),
[current keywords](https://rules.fabtcg.com/en/cr/08-keywords/), and
[combat rules](https://rules.fabtcg.com/en/cr/07-combat/). Spectral Shield uses
its current Ward 1 wording. Hard-refresh after regeneration to load the new
zone layout and bindings.

## Boltyn Light Warrior bot

`boltyn_source.json` pins Fabrary deck `01KP7ZQ311EMGC2S97KC79AEX0`, **Boltyn
Silver Age Deck**. `FaBSim/BoltynDeck.json` uses its exact 40-card main deck,
Raydn, and four starting equipment. Choose **Boltyn** from the main-menu bot
selector or an empty UPF lobby slot. It uses a fixed starting loadout and does
not sideboard; the source's full 10-card inventory would exceed UPF's 52-card
pool limit when combined with that loadout.

All source cards, including the sideboard, are supported. The additions are 15
card identities plus Agility, Courage and Flurry. They cover yellow soul checks,
Solflare, Unity/Temper, Sharpen, mandatory Roaring Beam charge, prevention and
token triggers. Player decisions use saved `await` macros. The importer now
recognizes separate Fabrary sideboard counts while retaining older inclusive
counts; hybrid Brute/Warrior equipment is accepted for either class.

The bot prioritizes a charge opener, yellow Light cards and Banneret for soul,
Duty Bound Blitz after a yellow soul entry, and charged Raydn attacks. It keeps
enough pitch for additional costs, uses attack reactions to push damage or enable
Boltyn, and spends soul on go again when another attack is available. Edict and
Flurry extend weapon turns. It uses only its own hand and public information,
with the shared legal-target, pitch, block and priority logic. This is a greedy
heuristic, without lookahead or hidden opponent-hand access.

```powershell
python DevTools/FaB/build_boltyn_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/boltyn_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/boltyn_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/boltyn_lobby_test.ps1
```

Tests cover the pinned deck, sideboard import, card interactions from duel and
UPF seats, completed Boltyn/Ira, four-Boltyn and mixed bot games, and the main-menu
and UPF lobby routes. Flurry is tested against granting a third weapon attack;
Agility waits for its owner's turn; Toe the Line prevents only its next damage
event; Radiant Touch banishes both costs. Existing MON, CRU, Professor and Ira
regressions also pass.

Sources: [the deck](https://fabrary.net/decks/01KP7ZQ311EMGC2S97KC79AEX0),
[keyword rules](https://rules.fabtcg.com/en/cr/08-keywords/),
[hybrid card rules](https://rules.fabtcg.com/en/cr/02-object-properties/), and
[Flat Trackers](https://cards.fabtcg.com/card/flat-trackers/HVY155-CF/).

## Levia Shadow Brute bot

`levia_source.json` pins [Fabrary's Levia Blitz Deck](https://fabrary.net/decks/01G7B1T1D1M2DAM61K876VJBDK).
`FaBSim/LeviaDeck.json` contains its exact 40-card main deck, Ravenous Meataxe,
Ebon Fold, Goliath Gauntlet, Hooves of the Shadowbeast and Spell Fray Cloak.
Choose **Levia** in the main-menu bot selector or an empty UPF lobby slot.

The two missing cards are included in `levia_abilities.json`. Lady Barthimont
uses an arsenal `CardPlayed` listener and saved `await` decisions for her reveal
and specialization search. She checks the actual event player, grants dominate
when a six-power card is banished, and replaces herself with a face-up
specialization after two lessons. Arsenal lesson counters are serialized and
displayed through the schema. Spell Fray Cloak uses the shared Spellvoid choice.
Deck validation rejects mentors with adult heroes.

The heuristic builds graveyard fuel, estimates the chance that a random banish
contains a six-power card, and prioritizes suppressing blood debt. It favors
Dread Screamer when another attack is affordable, avoids spending its last
graveyard fuel on a buff without a follow-up, pitches lower-value blues, and
uses Ebon Fold or Blood Tribute to avert blood debt when possible. It arsenals
and reveals Barthimont, saves Hooves for another attack, and uses Goliath before
an eligible attack. Decisions use its own hand and public information; opt
looks only at cards revealed by that decision, not unrevealed deck order.

```powershell
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/levia_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/levia_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/levia_lobby_test.ps1
```

Tests cover the exact deck, optional mentor reveal, opposing-seat isolation,
successful and failed lessons, specialization filtering, face-up arsenal
replacement, Spellvoid prevention, and graveyard probability calculations.
Completed games include Levia/Boltyn, four Levia bots, and Levia against the
Fai/Professor/Ira bots. HTTP tests verify the main-menu duel and out-of-order
UPF bot assignment. Boltyn and MON regressions also pass. Hard-refresh after
generation to load the arsenal counter binding.

Rules references: [MON release notes](https://dhhim4ltzu1pj.cloudfront.net/media/documents/MON_Release_Notes_v2.2.pdf)
and [mentor type rules](https://rules.fabtcg.com/en/cr/08-keywords/).


## Prism deck and heuristic bot

Pinned Fabrary source: https://fabrary.net/decks/01G7FCP2N7N0MNHWAH6JTP0KFN
(`prism_source.json`). `FaBSim/PrismDeck.json` contains the exact 40-card list,
Prism, Iris of Reality, and its four starting equipment cards. The two missing
identities are The Librarian and Spell Fray Leggings; other cards use the
existing WTR/MON implementations. Card art is available for all 29 identities.

The Librarian uses saved await macros for its optional start-turn reveal and
specialization search. Shield creation reserves its once-per-turn trigger
before resolving, including during other players' turns. Lessons draw first;
the third banishes the mentor and searches into face-up arsenal. Spell Fray
Leggings uses the shared Spellvoid prevention path. Hit processing refreshes
the source reference after effects move it, so Seek Enlightenment and Herald
hit abilities work together.

The Prism profile is available in the main-menu duel selector and all UPF bot
slots. Heuristics value Herald hits and soul, reserve blues for Iris attacks,
play buffs with affordable follow-ups, develop auras, and reveal the mentor.
The bot uses its own hand and public information, without reading opposing
hands or unrevealed deck order.

```powershell
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/prism_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/prism_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/prism_lobby_test.ps1
```

Tests cover the pinned deck, mentor reveal/lessons/search in duels and UPF,
Iris payment/power/go again, Spellvoid, and Seek Enlightenment with Herald of
Protection. Full matches exercise Prism/Boltyn, four Prism bots, and a mixed
Fai/Professor/Ira/Prism table. HTTP checks verify both entry points and UPF slot
assignment. Hard-refresh after generation.


## Tales of Aria (ELE)

`ele_catalog.json` pins the 238 functional card identities (120 families) with
ELE printings in the shared card corpus. `build_ele_abilities.py` builds the
reviewable CardEditor snapshot, reusing existing set implementations where
appropriate and rejecting unhandled families. All 238 identities have art.
Interactive card decisions are saved `await` macros, with serializable UIDs
and absolute player-zone references across decisions.

Shared ELE rules cover Earth/Ice/Lightning fusion, Frostbite and opposing cost
increases, elemental essence validation, Briar/Oldhim/Lexi, New Horizon's extra
arsenal space and destruction, bow activation limits, channel upkeep before
pitch return, Embodiments, chain damage bonuses, graveyard recycling, and
Korshem. Channel flow and Creepers bind counters are visible on cards. Oldhim
and Winter's Wail track cards pitched for their particular activation; floating
resources or unrelated pitched cards do not satisfy those effects.

Engine fixes include choosing an available ability when a bot's default action
has multiple activation entries (Voltaire), preserving newly created Frostbites
after weapon activation, and awarding go again to non-attack actions played as
instants. Scalar-zone `BeforeAdd` hooks distinguish resource gains from pitching
for Korshem; the generator itself needs no special-case changes.

```powershell
python DevTools/FaB/build_ele_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/ele_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/ele_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/ele_lobby_test.ps1
```

The game suite includes `ele_test.php` (628 saved macro executions across duel
and UPF seats) and `ele_rules_test.php` (outcome assertions for fused/declined
fusion, dual-element cards, tax payment, hero abilities, arsenal capacity,
channel upkeep, Creepers timing, bow limits, damage triggers, recycling, and
Korshem). Complete two- and four-player matches exercise repeated decisions,
combat, pitch, upkeep, and elimination. HTTP checks import an ELE Briar deck
into a duel and an UPF table against existing bots. MON, Levia, and Prism
regression suites pass. These fixture drivers are tests, not new selectable
ELE bot profiles. Hard-refresh after regeneration.

Rules reference: [official Tales of Aria release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/tales-aria-release-notes/).

## Lexi heuristic bot

`lexi_source.json` pins Fabrary deck 01G7K3WGPVKVDXG2J013GXSXNP;
`FaBSim/LexiDeck.json` is its normalized 40-card Shiver list. All card identities
are covered by the existing WTR/ARC/CRU/ELE implementations. The `lexi` profile
is available in the duel menu and UPF slot selector. It scores arrow loading,
arsenal setup, elemental reveals, buffs, fusion choices, pitch and defense using
its own hand and public information. Shiver favors power (including Bolt'n Shot);
this first heuristic does not search entire turns or model opponents' hidden hands.

Run `php DevTools/FaB/lexi_test.php` for source equality, seat-aware choices,
shared stack references, and complete duel / four-Lexi / mixed-UPF games.
Run `powershell -File DevTools/FaB/lexi_lobby_test.ps1` against the local server
for slot assignment, persisted profiles and the main-menu duel route.

## Everfest (EVR)

`evr_catalog.json` pins all 198 functional EVR identities from the shared card
corpus. `build_evr_abilities.py` authors the CardEditor snapshot, reuses seven
existing identities, and fails on unhandled families. `evr_support_abilities.json`
updates earlier dice abilities to share Ready to Roll / Skull Crushers handling.
The authoring patterns use saved `await` decisions and absolute seat references.

`EVRCards.php` and `EVRAbilities.php` supply shared event hooks, ability metadata,
heave, aura creation counters, items, dice effects, source-specific prevention,
Fractal Replication, and turn durations. Verse counters are exposed through the
schema. Private hand inspection uses temporary private copies; the actual hand
stays in its owner's zone. Targeted choices and all-hero effects handle live seats.

Regenerate with the standard importer and schema generator:

```powershell
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/evr_support_abilities.json
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/evr_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
```

Validation:

- `php DevTools/FaB/evr_rules_test.php`: complete snapshot / saved-decision sweep
  plus outcome checks in two and four seats.
- `php DevTools/FaB/evr_games_test.php`: complete mixed EVR mechanic fixture games.
- `powershell -File DevTools/FaB/evr_lobby_test.ps1`: legal Valda EVR deck import,
  four-seat lobby with bots, and main-menu duel board response.
- Earlier-set regression suites: ELE outcomes, MON outcomes/full games, and
  Lexi duel / four-Lexi / mixed-UPF games.

Rules reference: https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/everfest/

## Uprising (UPR)

`upr_catalog.json` pins 238 functional identities, including invocation dragon
faces and the physical checklist. `build_upr_abilities.py` produces the saved
CardEditor snapshot and fails on unhandled families. Previously authored Fai
cards are reused; Red Hot now uses the shared hero/ally damage path. Dragons of
Legend is a physical checklist: import its represented invocation instead.

`UPRCards.php` and `UPRAbilities.php` implement Ash transformations, dragons,
ally damage/endurance/health, freezing, affliction ownership, fusion triggers,
Quell, Alluvion prevention choices, source-specific prevention, and equipment
activations. Phantasm uses a stack trigger so Semblance can respond. Individual
Weathervane and Uprising effects retain their own target choices/use counts.
Interactive effects use saved `await` macros with explicit seats and stable UIDs.
The schema exposes ally health, endurance, frost, raze, and haunt counters.

```powershell
python DevTools/FaB/build_upr_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/upr_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/upr_games_test.php
powershell -File DevTools/FaB/upr_lobby_test.ps1
```

`upr_test.php` exercises 498 saved macro paths in duels and four-seat UPF.
`upr_rules_test.php` checks outcomes including dragon transformation/death,
endurance, Nekria, freezing duration, Themai, Hypothermia, affliction ownership,
phantasm responses, fusion taxes, Quell against UPR and older spells, Alluvion,
Ghostly Touch, deck inspection, and cost/type changes. `upr_games_test.php`
plays full duel and four-player fixtures with Dromai, Fai, and Iyslander mechanics.
The fixture driver exercises allies explicitly; it is not a new lobby bot.
The HTTP test imports a legal Dromai deck into both existing game routes.
Earlier WTR/ARC/CRU/MON/ELE/EVR and Fai/Prism/Lexi regressions also pass.

Rules references: [Uprising release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/uprising/)
and [UPF format rules](https://rules.fabtcg.com/en/trp/09-special-formats/).
Hard-refresh after regenerating.

## Dromai heuristic bot

`dromai_source.json` pins Fabrary deck `01G76H1R1ERRBRKS7RVCQAB8RX`.
`FaBSim/DromaiDeck.json` is the exact normalized 40-card Ashwing list with
Storm of Sandikai, Deep Blue, Ironhide Helm/Legs, and Silken Form. Every card
is covered by the existing saved WTR/ARC/MON/ELE/UPR implementations.

The `dromai` profile is selectable in the main-menu duel selector and in each
UPF lobby slot. It favors red pitch while short of Ash, switches to efficient
blue pitch once stocked, develops Ashwings, enables dragon go again before
attacking, and uses ready dragons before non-go-again finishers. It scores
Deep Blue, Silken Form, Ironhide costs, defense, healing, arsenal and optional
hand exchanges. Decisions use its own hand and public board information.
This is a heuristic policy, not a full-turn search or an opponent-hand model.

Run `php DevTools/FaB/dromai_test.php` for exact source equality, deck legality,
card coverage, seat-aware choices, sequencing, and complete duel / four-Dromai /
mixed-UPF games. Run `powershell -File DevTools/FaB/dromai_lobby_test.ps1` for
selectable profiles, assignment to the clicked seats, persisted decks, and the
main-menu duel route.


## Dynasty (DYN)

`dyn_catalog.json` pins all 247 functional identities (pitch colors and reverse
faces included). `build_dyn_abilities.py` accounts for every identity, reuses
implemented reprints, and writes the CardEditor snapshot `dyn_abilities.json`.
Interactive effects use inline `await` and stable UIDs across player decisions.

`DYNCards.php` and `DYNRuntime.php` implement contracts/Silver, Assassin equipment,
aim, surge, Ward, Royal effects, Tigers, Hyper Driver colors, Nitro Mechanoid,
Suraya, pitched-type bonuses, weapons, and equipment. UPF effects use explicit
seats, current adjacency, ownership, and live-seat enumeration. Aim and doom
counters are exposed by the schema. Existing duel and UPF import routes work.

```powershell
python DevTools/FaB/build_dyn_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/dyn_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/dyn_games_test.php
powershell -File DevTools/FaB/dyn_lobby_test.ps1
```

`dyn_test.php` exercises 436 authored macro paths across duels and four-seat UPF.
`dyn_rules_test.php` checks contracts, private inspection, aim/equipment counters,
overpower, Regicide, surge, Ward and unpreventable damage, transformations,
Bios Update, discard trigger ordering, Deathly Duet, Tranquil Passing, Yoji,
Emperor, and adjacency after elimination. `dyn_games_test.php` plays a full duel
and two mixed four-player fixtures. The HTTP test imports an Arakni deck,
assigns existing bots to slots 4/2/3, and starts both formats. The fixture driver
is test infrastructure, not a new Dynasty bot profile.

Rules references: [Dynasty release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/dynasty/)
and [UPF rules](https://rules.fabtcg.com/en/trp/09-special-formats/).
Hard-refresh after regeneration to load the new client bundle.


## Arakni heuristic bot

`arakni_source.json` pins Fabrary deck `01JJNEQ0SBZSFSDED4WQJG2YZA`.
`FaBSim/ArakniDeck.json` preserves the exact 40-card list, paired Spider's Bites,
Danger Digits, Leap Frog Slime Skin, Mask of Perdition, and Starting Point.
The `arakni` profile appears in the main-menu duel selector and UPF slot picker.

Eight previously missing identities are authored in `arakni_abilities.json`:
Danger Digits, Hunted or Hunter (red), Incision (red/blue), Leap Frog Slime Skin,
Starting Point, The Hand that Pulls the Strings, and Up Sticks and Run (blue).
`build_arakni_abilities.py` emits compiler-compatible await bodies.
`ArakniCards.php` handles reaction-step history, arsenal mentor effects,
dagger retrieval/payment, and triggered equipment defending another UPF hero.

The heuristic preserves red contracts and blue pitch, reserves resources for a
contract after dagger setup, spends reactions to get past defense, and values
Strings in arsenal. It uses only its own hand/public board and the private
inspection candidates exposed by Arakni/Cut to the Chase. It does not inspect
opponents' hidden hands or unexposed deck order.

Run `php DevTools/FaB/arakni_test.php` for exact deck/coverage checks, actual card
outcomes in duels and UPF, and targeted heuristic tests. Run
`php DevTools/FaB/arakni_games_test.php` for Fai, all-Arakni, and mixed-UPF matches;
`powershell -File DevTools/FaB/arakni_lobby_test.ps1` checks both HTTP routes and
bot assignment to slots 4/2/3. Import the snapshot with the standard importer
and regenerate FaBSim after changing authored abilities.

Retrieve follows the [official Hunted release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/the-hunted/):
it costs one resource and needs a vacant weapon slot.

## Outsiders (OUT)

`out_catalog.json` pins the 236 distinct functional identities in OUT (alternate
Marvel printings share their functional identities). `build_out_abilities.py`
accounts for every identity, reuses earlier-set implementations, and refuses to
write a snapshot with unhandled families. `out_abilities.json` is the reviewable
CardEditor authoring source; it uses the standard revision-checked importer.

`OUTCards.php` and `OUTRuntime.php` implement Uzuri's paid face-down attack swap,
Solitary Confinement, Riptide/traps, disease tokens, Codex effects, quivers,
Assassin daggers, aim, combo/name effects, prevention, and temporary permissions.
Choices use stable UIDs and explicit seats across saved await continuations.
Deck inspection and ordering use the card rearrangement popup. The active player
chooses the order of their disease/Ponder end-phase triggers.

The schema adds a private Inventory zone for Concealed Blade, including unique
IDs and undo snapshots. Deck import loads the actual inventory; quiver and new
hero-specialization restrictions are validated. Owner fields survive zone and
stack serialization so Infiltrate cards return to their owner's zones. Start a
new game after this schema update, and hard-refresh to load the generated UI.

```powershell
python DevTools/FaB/build_out_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/out_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/out_rules_test.php
php DevTools/FaB/out_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/out_lobby_test.ps1
```

Validation covers 376 saved macro paths in duels and four-seat UPF, plus outcome
checks for paid/invalid Uzuri swaps, Riptide conditions, all-seat Codex choices,
occupied arsenals, Inertia/Ponder ordering, Bloodrot payment, weapon attacks and
throws, defense-group debuffs, aim/pitch restrictions, gained names, inventory,
and stolen-card ownership/expiry. Full fixtures play Uzuri versus Riptide and a
four-player Arakni/Katsu/Riptide/Uzuri game to completion. The HTTP smoke test
imports a legal Riptide OUT deck through the UPF and main-menu duel routes.
Existing WTR/ARC/DYN, Arakni, MON, ELE, EVR and UPR regressions also pass.
These are regression fixtures using the existing bot driver, not new OUT bot
profiles. Browser visual QA across Chromium, Firefox, and Safari remains unrun.

Rules reference: [official Outsiders release notes](https://dhhim4ltzu1pj.cloudfront.net/media/documents/09_OUT_24_03_2023_Outsiders_release_notes.pdf).

## Uzuri bot

The `uzuri` profile pins the exact 40-card young Uzuri list from
[Fabrary 01GW2945GHPH2YSX3FTS7HCBT1](https://fabrary.net/decks/01GW2945GHPH2YSX3FTS7HCBT1).
`uzuri_source.json` retains the export; `FaBSim/UzuriDeck.json` is the normalized
list used by both the main-menu duel and UPF lobby. All cards use existing macros.
`FaBSim/Custom/UzuriBot.php` scores stealth openers, legal hand swaps (including
Sneak Attack's reaction bonus), dagger setup, reactions, defense, and equipment.
Choices use the bot's own cards and public/explicitly offered information.
This is a heuristic bot, not a game-tree search.

```powershell
php DevTools/FaB/uzuri_test.php
php DevTools/FaB/uzuri_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/uzuri_lobby_test.ps1
```

Tests check source equality, card coverage, swap resolution and invalid payloads
in two/four seats, Ironhide payment, hidden-hand independence, complete duel and
multiplayer games, and both HTTP lobby routes.

## Dusk till Dawn (DTD)

`dtd_catalog.json` accounts for 245 functional identities, including reverse
faces and reprints. `build_dtd_abilities.py` reuses earlier implementations and
rejects unhandled families; `dtd_abilities.json` contains 217 saved macros for
the revision-checked importer. Import this snapshot after the earlier sets.

`DTDCards.php` and `DTDRuntime.php` implement Prism's Figment/Angel transitions,
Vynnset and Rune Gate, Levia's demi-hero transformations, charge, Unity,
Diplomacy, life-loss tracking, and damage prevention. Choices retain explicit
seats and stable card UIDs through continuations. UPF opponents remain opponents
for party effects; they are not treated as teammates.

```powershell
python DevTools/FaB/build_dtd_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/dtd_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/dtd_rules_test.php
php DevTools/FaB/dtd_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/dtd_lobby_test.ps1
```

Validation exercises 434 saved continuation paths in two/four seats, targeted
rule outcomes, complete Vynnset/Prism and Levia/Prism/Boltyn/Vynnset games, and
legal Prism deck imports through both HTTP lobby routes. Earlier-set regression
suites and the Uzuri bot checks also pass. Full-game fixtures use the existing
bot driver with fixture-specific choices; this adds no DTD bot profile.
Hard-refresh after regeneration. Browser visual QA remains unrun.

Rules reference: [official Dusk till Dawn release notes](https://dhhim4ltzu1pj.cloudfront.net/media/documents/10_DTD_24_07_2023_Dusk_till_Dawn_release_notes.pdf).

## Bright Lights (EVO)

`evo_catalog.json` accounts for 252 functional identities found in the EVO
printings, including reverse faces and shared reprints. The builder rejects
unhandled families and produces 351 saved macros. Import after DTD and the
earlier sets; the importer checks revisions before replacing existing macros.

`EVOCards.php` and `EVORuntime.php` provide Evo transformations and materials,
Dash's private top-deck play, Maxx's Hyper Drivers, Teklovossen/Mechropotent,
Crank, Scrap, Galvanize, item upkeep, variable costs, and expansion-card effects.
Choices use explicit live seats and stable UIDs in duels and multiplayer UPF.
Transformations preserve underlying cards, including when building Nitro
Mechanoid. Mechropotent can defend without removing its hero target.

```powershell
python DevTools/FaB/build_evo_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/evo_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/evo_rules_test.php
php DevTools/FaB/evo_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/evo_lobby_test.ps1
```

Validation exercises 702 saved continuation paths, targeted rule outcomes,
complete Teklovossen/Maxx and Dash/Maxx/Teklovossen/Professor games, and both HTTP
lobby routes. Fixtures use the existing bot driver; no EVO bot profile is added.
Hard-refresh after regeneration and start a new game. Browser visual QA across
Chromium, Firefox, and Safari remains unrun.

The Deck schema and generator expose Dash's top card only to its authenticated
controller, preserving hidden cards for other seats and spectators. The deck
counter still shows the full pile size. Older Windows PHP 8.1 ZTS web requests
disable opcode caching locally to avoid a reproduced native cache crash with
the enlarged generated file. Linux production and CLI behavior are unchanged.

Rules reference: [official Bright Lights and Round the Table release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/bright-lights-round-the-table/).

## Maxx Armory Deck bot

`maxx_source.json` pins [Fabrary 01JRH0631MH5A9JPVGTP3TKJXN](https://fabrary.net/decks/01JRH0631MH5A9JPVGTP3TKJXN).
`FaBSim/MaxxDeck.json` is its normalized, unchanged 60-card Classic Constructed
list with adult Maxx. The `maxx` bot is available through the main-menu duel
route and is deliberately unavailable in UPF. No young-hero adaptation is made.

`AMXCards.php` and `amx_abilities.json` add Bank Breaker/Construct Bank Breaker,
Breaker Helm Protos, Clamp Press, Drive Brake, Fist Pump, Puffer Jacket, and
Twintek Charging Station. Reprints reuse existing set implementations. Import
AMX after EVO. Transformations preserve underlying cards and token materials;
Bank Breaker has a separate optional material choice for each attack.

`MaxxBot.php` prioritizes Hyper Driver setup, construction, wrench attacks,
boost chains, and material/steam choices. It uses its own hand and public or
explicitly offered information, with no inspection of hidden deck order or an
opponent's hand. It preserves attack hands against small hits and boosts when
the public resource budget supports a follow-up. This is a heuristic bot, not a
search-based opponent.

```powershell
python DevTools/FaB/build_amx_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/amx_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/maxx_test.php
php DevTools/FaB/maxx_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/maxx_lobby_test.ps1
```

The rule tests cover two/four seats, source-list equality, entry counters,
equipment triggers, material movement, twice-per-turn activation, and hidden
information independence. HTTP checks verify duel creation and rejection of
Maxx through the UPF AddBot endpoint. Complete mirror and Professor matches
pass; a separate prepared-position regression requires the bot to construct
Bank Breaker and pay for both attacks. Hard-refresh after regeneration.

## Heavy Hitters (HVY)

`hvy_catalog.json` and `build_hvy_abilities.py` cover all 255 HVY identities,
including reprints and expansion-slot cards. The builder rejects unhandled
identities and emits `hvy_abilities.json` with 230 saved macros.
Import HVY after the earlier set snapshots: Bare Fangs and Wild Ride now use
Kayo's effective power when checking a discarded card.

`HVYCards.php` and `HVYRuntime.php` implement Clash, Wager, Beat Chest, hero
and token effects, equipment restrictions, and activation/cost hooks. Wagers
record their opposing hero and use that hero's damage result in multi-target
combat. Optional costs preserve the resources needed to finish payment.
Private inspections use the chooser's Temp zone and return original identities.
Deck validation includes Kayo's one weapon zone and HVY specializations.

```powershell
python DevTools/FaB/build_hvy_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/hvy_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/hvy_rules_test.php
php DevTools/FaB/hvy_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/hvy_lobby_test.ps1
```

The tests execute 460 authored continuations across two/four seats, then assert
mechanic outcomes, ownership, private-choice cleanup, cost payment, and equipment
behavior. Complete Kayo/Victor and Betsy/Kassai/Olympia/Victor games exercise the
shared bot driver; this set does not add dedicated HVY bot profiles. HTTP checks
import an HVY Kayo deck and start both duel and four-seat UPF rooms.

Rules references: [HVY release notes](https://legacy.fabtcg.com/resources/rules-and-policy-center/release-notes/heavy-hitters/)
and [Run into Trouble](https://cards.fabtcg.com/card/run-into-trouble-1/ES_HVY161-RF/).
The cached text omits Run into Trouble's numeric damage; its ability deals 1.

## Part the Mistveil (MST)

`mst_catalog.json`, `build_mst_abilities.py`, and `mst_abilities.json` cover all
240 identities, including reprints, expansion-slot cards, and Enigma, New Moon.
The builder rejects unhandled cards and emits 197 saved macros. Import MST after
the earlier sets. `MSTCards.php` and `MSTRuntime.php` provide the shared rules.

Chi is a separately serialized subset of the resource pool: ordinary costs
spend it first, Chi costs require it, and resource taxes still apply separately.
Transcend keeps the original UID and owner while changing the card to Inner Chi.
Cloaked equipment starts face down, conceals its identity from other viewers,
and only exposes abilities that work while face down. The schema and generated
rendering include Chi displays and aura power counters.

Cosmo uses current Ward values for aura attacks. The shared damage-prevention
queue lets the defending player choose Ward order, including zero Ward and
unpreventable damage. Keep the Radiant Forcefield macro in `dtd_abilities.json`
aligned with `CodeGeneration.php`; `build_dtd_abilities.py` reads that fragment.
Nuu, private inspections, stolen cards, and added defenders retain their chosen
opponent, owner, and original identity across decisions.

```powershell
python DevTools/FaB/build_mst_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/dtd_abilities.json
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/mst_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/mst_rules_test.php
php DevTools/FaB/mst_games_test.php
powershell -NoProfile -ExecutionPolicy Bypass -File DevTools/FaB/mst_lobby_test.ps1
```

Tests exercise 394 saved continuations across two and four seats, plus payment,
Cloaked legality, private choices, Transcend ownership, dynamic Ward, alternative
costs, granted Boost, and multiplayer permissions. Game fixtures use the shared
driver with explicit aura attacks and an aggressive policy after the opening rounds; there is
no new dedicated MST bot profile. HTTP tests import an Enigma deck and create
both duel and UPF rooms. Hard-refresh the browser after regeneration.

Rules references: [MST release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/part-the-mistveil/),
[resource rules](https://rules.fabtcg.com/en/cr/01-game-concepts/), and
[Ward prevention order](https://legacy.fabtcg.com/en/articles/rules-reprise-14-light/).

## Rosetta (ROS)

`ros_catalog.json` covers all 257 distinct card identities in the local ROS
printing catalog. `build_ros_abilities.py` authors 271 saved macros, retains
current reprint implementations, and fails if a card family is unaccounted for.
The runtime lives in `ROSCards.php` and `ROSRuntime.php`.

Implemented mechanics include Meld (half selection, combined costs/properties,
right-half-first resolution and a priority window between halves), Amp/Surge,
Decompose, Arcane Shelter, discard activations, Sigil leave-arena triggers, and
Aurora, Florian, Oscilio and Verdance. Aura-token creation is counted per event;
adult Florian/Verdance use the catalog's eight-Earth threshold. Delayed end-phase
triggers resolve before pitch return and turn advancement. Effects involving
all heroes or opponents use live seats; targeted damage uses existing UPF
adjacency rules.

```powershell
python DevTools/FaB/build_ros_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/ros_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/ros_rules_test.php
php DevTools/FaB/ros_games_test.php
powershell -ExecutionPolicy Bypass -File DevTools/FaB/ros_lobby_test.ps1
```

The rules suite includes 542 saved-continuation executions across two and four
seats, plus outcome assertions for token replacement, life-gain triggers,
damage prevention, Surge, Meld, Decompose, equipment and turn-end timing.
The complete-game fixtures use the existing generic driver with an aggressive
policy after the opening rounds. They are test fixtures, not new selectable bot
profiles. The lobby check uses a legal young Aurora deck with existing Prism bots.

Rules references: [Rosetta release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/rosetta-1st-strike/),
[split cards](https://legacy.fabtcg.com/en/articles/rules-reprise-21-split-cards/),
[current keywords](https://rules.fabtcg.com/en/cr/08-keywords/), and
[UPF rules](https://rules.fabtcg.com/en/trp/09-special-formats/).

## The Hunted (HNT)

`hnt_catalog.json` covers 265 distinct identities in the local printing catalog.
`build_hnt_abilities.py` emits 243 saved macros and rejects unhandled identities.
It retains current reprints, including the earlier Arakni support-card revisions.
Shared mechanics live in `HNTCards.php` and `HNTRuntime.php`.

The implementation includes per-hero Marked conditions and a public hero badge,
Fealty and prospective Draconic play restrictions, Cindra/Fang, all six Agents
of Chaos, dagger flick hits, retrieve, chain-limited discounts, copy effects,
discard activations, secret-number choices, and the expansion cards. Marked is
captured at the hit event and removed before its hit abilities resolve; damage
without a hit leaves it intact. Off-chain dagger hits share this handling with
ordinary combat hits. All-hero effects iterate live seats; targeted effects use
the existing UPF targeting rules.

```powershell
python DevTools/FaB/build_hnt_abilities.py
$env:MYSQL_DATABASE_NAME='swuonline'
php DevTools/FaB/import_wtr_abilities.php DevTools/FaB/hnt_abilities.json
php zzGameCodeGenerator.php rootName=FaBSim
php DevTools/FaB/hnt_rules_test.php
php DevTools/FaB/hnt_games_test.php
powershell -ExecutionPolicy Bypass -File DevTools/FaB/hnt_lobby_test.ps1
```

The rules suite executes 484 saved continuations across two and four seats, plus
outcome checks for hit timing, transformation, copy identity, equipment costs,
prevention, secret choices, and stacked reactions. Complete-game fixtures use
the existing generic bot driver; they do not add a selectable HNT bot profile.
Lobby checks import a legal young Cindra deck for duel and four-player play.
Regenerate and hard-refresh after importing to load the Marked badge.

Rules references: [The Hunted release notes](https://legacy.fabtcg.com/en/resources/rules-and-policy-center/release-notes/the-hunted/)
and [current keyword rules](https://rules.fabtcg.com/en/cr/08-keywords/).

## Compendium of Rathe (PEN)

All 348 printing identities are covered by `pen_abilities.json`, built from
`build_pen_abilities.py`, with native support in `FaBSim/Custom/PENCards.php`.
The suite covers duels and three-/four-seat UPF, including adjacency exceptions,
all-hero effects, replacement choices, and delayed effects.

See [PEN implementation and rebuild instructions](PEN_IMPLEMENTATION.md).

## Omens of the Third Age (OMN)

The 251-identity catalog, saved authoring, runtime hooks, and duel/UPF regression
commands are documented in [OMN_IMPLEMENTATION.md](OMN_IMPLEMENTATION.md).
Rebuild `omn_abilities.json`, import through CardEditor, and regenerate FaBSim.

## Usurp the Shadow Throne (IAR)

The 261-identity catalog, CardEditor snapshot, shared rules, image generation,
and duel/UPF checks are documented in [IAR_IMPLEMENTATION.md](IAR_IMPLEMENTATION.md).
The card-data source is pinned, and the builder fails if any IAR identity is
unhandled.

---
name: hellbreaksim-implement-card
description: Use when implementing Hellbreak card abilities in HellbreakSim — looks up the card's reviewed text, builds a fixture that reaches the ability, writes the macro/listener rows, regenerates, and proves the behaviour. Supports a single card or a small batch.
---

# HellbreakSim — Implement Card

## Overview

A Hellbreak card ability is a **row in the MySQL `card_abilities` table**, compiled by the game code
generator into `HellbreakSim/GeneratedCode/GeneratedMacroCode.php` and dispatched at runtime by
`HellbreakDispatchMacroEvent()`. There is no per-card PHP file to edit — shared helpers live in
`HellbreakSim/Custom/`, the card's own logic lives in the database.

Test-first: a fixture that reaches the ability comes **before** the ability. The fixture is what
proves the macro you chose actually fires; a plausible-looking row on the wrong macro is silent, and
nothing else in this pipeline will tell you.

First invoke **`hellbreaksim-session-start`** if it has not run this session.

## The loop

```
1. Read the card          reviewed text + rulebook
2. Pick the dispatch point   which macro fires at that moment
3. Build the fixture       make-fixture -> record-fixture, reach the moment, RED
4. Write the row(s)        card-ability.php --set  (regenerates + lints, rolls back on failure)
5. Re-record + assert      GREEN, with a negative that proves the gate
6. Update the plan doc     and remind about the SQL export
```

Every command runs in the container from the repo root:

```bash
docker exec -w /var/www/html/TCGEngine otmtcge-hellbreaksim-web-server-1 php <script> <args>
```

⚠ Drop the `-w` and the integration runner reports a failed **assertion**, not an error.

## Step 1 — Read the card

Reviewed transcriptions are the authority, not the community workbook:

```bash
DICT=HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php
awk '/\$textData = array \(/{f=1;next} f&&/^  \$[a-zA-Z]+Data = array/{exit} f' $DICT | grep "'DOT_049'"
```

Also read `costData`, `combatData`, `healthData`, `traitsData`, `resourcesData`, `schemeData`,
`loyaltyAspectsData`, `uniqueData` for the same ID — the fixture needs real numbers, and a fixture
seeded from your own assumptions tests nothing.

- **A Monster's `textData` is its LURKING face only.** The unleashed side lives in `facesData` as
  JSON (`lurking` / `unleashed`), and it usually carries a whole extra Action ability. Each face is
  its own ability set; a monster is not done on its lurking side alone.
- **Abilities live on the base card.** A variant printing (borderless = base+200, poster/alt 4xx)
  resolves through `CardBaseMap.json`; `card-ability.php` does that for you and says so.
- When the printed text leaves a question open, check `.claude/HellbreakSim/refs/rules-of-play.md`.
  If the rulebook does not settle it, **say so and ask** rather than encoding a guess — there is no
  official rulings database for Hellbreak, so a guess here has nothing to correct it later.

## Step 2 — Pick the dispatch point

`Schemas/HellbreakSim/GameSchema.txt` defines 21 event macros and 10 value modifiers. Match the
card's wording to the moment:

| Card wording | Macro |
|---|---|
| "When you play this…" / "When this enters…" | `Played(mzID, fromZone, locationSlot)` |
| "Action — …" | `ActivateAbility(mzID, abilityIndex)` |
| "When this attacks" / declares a target / a defender | `AttackDeclared` · `TargetDeclared` · `DefenderDeclared` |
| "Scheme — …" / per-icon effects | `SchemeStarted` · `SchemeIcon(mzID, schemeType, amount, locationSlot)` |
| "Take Control — …" | `LocationTaken(mzID, locationSlot, previousController)` |
| damage / death / health-stack reveals | `DamageDealt` · `MinionKilled` · `MonsterHealthRevealed` · `HealthAbilityUsed` |
| "Jumpscare — …" | `JumpscareUsed(cardID, owner)` |
| "Flipped — …" | `MonsterFlipped(mzID, fromSide, toSide)` |
| initiative / feeding | `ResourcesCollected` · `InitiativeBidRevealed` · `InitiativeAssigned` |
| Refresh / round boundary | `RefreshReady(round)` · `RoundEnded(round)` |
| "this card gets +N …" / "costs N less" / static numbers | a **value modifier** — return a **delta**, never the final value |

**`macro` vs `listener`** is the question of *whose* event it is:

- **`macro`** — the card reacting to **its own** event ("when you play THIS card"). No zones.
- **`listener`** — the card observing **someone else's** event ("whenever an allied Dog enters
  play", "when a Creature deals damage"). Requires `listenerZones`, and the only zones the engine
  scans are **`Monster`, `Characters`, `Assets`, `Locations`** — the card must be sitting in one of
  them for the listener to be live. A listener with no zones is rejected at every layer.

Value modifiers: `CombatModifier`, `HealthModifier`, `TraitModifier`, `KeywordModifier`,
`SchemeModifier`, `PlayCostModifier`, `InitiativeBidModifier`, `LocationThresholdModifier`,
`DamageModifier`. All are `ModifierMode=Delta`, clamped at 0 in aggregate.

If no macro fires at the moment the card describes, **stop** — that is a new dispatch point, which
is a design decision for the user, not something to improvise a near-miss for.

## Step 3 — Build the fixture FIRST

```bash
php DevTools/Hellbreak/make-fixture.php  --slug=<slug> [--deck=gama|fixture] [--auto=2|1,2|none]
php DevTools/Hellbreak/record-fixture.php --slug=<slug> --choices="#0,#0,Play_Card,myHand-3"
php DevTools/RunIntegrationTests.php --root=HellbreakSim --test=<slug> --update-snapshots
```

`Tests/Integration/HellbreakSim/README.md` is the full reference. Grow `--choices` one step at a
time — each run replays from the start and prints the next options plus a board summary.

Write `assertions.json` **before** the ability exists and watch it go RED. The snapshot alone only
catches change, not correctness.

```json
{ "step": 5, "type": "card_exists", "zone": "myAssets", "viewerPlayerID": 1, "cardID": "DOT_049",
  "label": "Playing Vampire's Coffin from hand puts it into play" }
```

Types: `card_exists`, `zone_count`, `phase_is`, `turn_player_is`, `card_property_equals`,
`decision_queue_empty`.

⚠ **Loyalty is enforced** — a card is only offered when the vault provides its aspect, so pick a
deck whose monster supplies it or the card never appears in "Choose a card to play". Deck order is
the decklist order, so the opening hand is fixed and changing the deck changes every fixture.

## Step 4 — Write the rows

```bash
php DevTools/Hellbreak/card-ability.php --card=DOT_049                      # read current rows
php DevTools/Hellbreak/card-ability.php --card=DOT_049 --set=.claude/tmp/dot049.json
```

`--set` **replaces the card's whole ability list** (the editor's own semantics), then regenerates
and lints `GeneratedMacroCode.php`, restoring the previous rows if either fails. The JSON:

```json
[
  {
    "macroName": "Played",
    "abilityType": "macro",
    "abilityName": "Initiative Blood Drain",
    "abilityCode": "$opponent = HellbreakOtherPlayer($player);\nHellbreakLoseBlood($opponent, 1);",
    "prereqCode": "return intval(GetInitiativePlayer()) === intval($player);",
    "listenerZones": [],
    "isImplemented": true
  }
]
```

Write the JSON under `.claude/tmp/` (gitignored, and inside the repo so the container can see it —
the scratchpad directory is **not** mounted).

### What the code bodies receive

This asymmetry is the most common authoring mistake:

- **`abilityCode` takes only `$player`.** Every macro parameter is read back out of the queue:
  `$mzID = DecisionQueueController::GetVariable("mzID");`. The generator emits those lines for you.
- **`prereqCode` takes `$player` plus every macro parameter positionally**, and returns bool.
- **A listener body** also gets `$listenerMZ`, `$listenerCardID`, `$eventPlayer`, `$eventParams`,
  and one `$event_<param>` per parameter of the observed macro (`$event_damageType`, …).
- **A value modifier** takes the full parameter list directly (no queue preamble) and `return`s an
  int delta.

Use the shared helpers rather than reaching into zones — `get_helper_functions` via MCP, or grep
`HellbreakSim/Custom/CardLogic.php`, `CombatLogic.php`, `GameLogic.php` for the operation you need
(targeting, damage, kill, draw, discard, blood/malice payment, trait and keyword reads).

### Interactive abilities

An ability that asks a question uses `await`, which the generator compiles into a queued
continuation:

```php
$targets = HellbreakAllMinionTargets(intval($player));
if(count($targets) === 0) return;
$chosen = await $player.MZChoose(implode("&", $targets));
```

⚠ **`:N` indexes are positional per macro**, and continuation handlers are keyed
`"DOT_053:0:JumpscareUsed-1"`. Adding an ability ahead of an existing one for the same macro
renumbers it — which matters most for `ActivateAbility`, where the index is the player-facing menu
slot. Re-read the card's rows after any insert.

⚠ **Modal answers are indices**, not labels — the client sends `indices.join(',')`. The recorder
accepts a label for your convenience and sends the index.

## Step 5 — Prove it

Re-record the fixture through the new behaviour, snapshot, and run it clean:

```bash
php DevTools/RunIntegrationTests.php --root=HellbreakSim
```

A card is not done on its happy path. Before calling it done, each of these is either a fixture
section or a written reason it cannot apply:

1. **Positive** — the ability does its thing.
2. **Negative** — every condition in `prereqCode` and every `if` in the body needs its FALSE case,
   asserting the ability does **not** fire. This is the cell that is missed most and the one that
   proves the gate is load-bearing at all.
3. **Optional branch** — "you may" needs both the take and the decline.
4. **No legal target** — resolves cleanly, no dangling decision, and decide explicitly whether the
   rest of the text still happens.
5. **Both faces**, for a Monster — lurking and unleashed are separate ability sets.
6. **The listener's zone** — a listener must be proven live from the zone it declares, and inert
   when the card is somewhere else.

Then run the whole Hellbreak suite, not just your fixture:
`attachments`, `loyalty_aspects`, `variant_base_cards`, `fixture_card_overrides`, `tutorial`,
`hellbreakdeck_validation`, `bridge_modal_answers`.

**If a suite is red, say so with the failure text.** Never round a red suite up to done.

## Step 6 — Record and hand off

- Tick the card in `HellbreakSim/docs/<set>-implement.md` (or append it to `### Already Done`).
- Tick the matching Milestone 8 line in `HellbreakSim/Rules/ImplementationPlan.md` if one applies.
- **⚠ Remind the user to export the ability SQL.** The rows exist only in the local database and are
  not in git: `zzCodeGeneratorMain.php` → **Card ability SQL → Export SQL**, then Import SQL on prod
  (the import regenerates automatically). Without it, prod regenerates from an empty table and the
  card is silent.
- **Never commit.** The user commits manually.

## Common mistakes

| Mistake | Fix |
|---|---|
| Editing `GeneratedMacroCode.php` | It is generated from DB rows and gitignored. The fix is the row, the schema, or the generator. |
| Writing the ability before the fixture | Then nothing proves the macro you chose is the one that fires. RED first. |
| Classifying a Monster from `textData` | That is the lurking face. Read `facesData` for the unleashed side. |
| Putting a macro parameter in `abilityCode`'s signature | Bodies take `$player` only; parameters come from `DecisionQueueController::GetVariable()`. Prereqs take them positionally. |
| A `listener` with no zones, or the wrong zone | Only `Monster`, `Characters`, `Assets`, `Locations` are scanned. No zones = rejected. |
| A value modifier returning the final value | They are deltas, clamped at 0 in aggregate. |
| Keying an ability on a variant number | Abilities live on the base card; `card-ability.php` resolves and tells you. |
| Assuming a card is unimplemented because the macro file lacks it | Regenerate first — the file is built from local DB rows. |
| Running tests without `-w` | Reports a failed assertion that looks exactly like an engine regression. |
| Finishing without the SQL export reminder | The session's work exists only in the local DB. |

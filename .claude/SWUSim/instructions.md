# SWUSim — Repo Instructions & Developer Guide

This document provides a concise developer-oriented guide to the SWUSim repository, with focused guidance for editing Decision Queue, Turn / Next-Turn, and card ability code paths. Keep this file updated as you change behavior or generator outputs.

## Quick links (files to know)
- `Core/DecisionQueueController.php` — server-side logic that executes static decision-queue entries and invokes custom handlers.
- `Core/UILibraries.js` — client-side rendering pipeline used by the generated UI (PopulateZone, createCardHTML, card injection points).
- `zzCardCodeGenerator.php` — schema parser + code generator that produces server helpers and a `GeneratedUI_*.js` client helper.
- `Schemas/SWUSim/GameSchema.txt` — schema defining zones, macros, overlays, counters and display properties.
- `SWUSim/GeneratedUI_*.js` — generated client helpers (watch for the timestamped filename).
- `SWUSim/ZoneAccessors.php`, `ZoneClasses.php`, `GamestateParser.php` — generated server-side gamestate helpers.
- `SWUSim/GetNextTurn.php`, `NextTurnRender.php`, `InitialLayout.php` — important generated files used by runtime.
- `SWUSim/TurnController.php` — turn/phase state machine.
- `SWUSim/GeneratedCode/GeneratedMacroCode.php` — auto-generated macro handlers. **Do not manually edit.**
- `SWUSim/GeneratedCode/GeneratedKeywordCode.php` — auto-generated keyword handlers. **Do not manually edit.**
- `SWUSim/Custom/GameLogic.php` — main custom game logic; core helper functions and event hooks.
- `SWUSim/Custom/CombatLogic.php` — combat flow: attack declaration, damage, defeat, keyword effects.
- `SWUSim/Custom/CardDQHandlers.php` — card-specific decision-queue handlers.
- `SWUSim/Custom/CardLogic.php` — card-specific complex logic.
- `SWUSim/Custom/GameLayout.php` — initial board layout.

## CardID format
Regular cards use the `SET_NNN` format:
- `{set abbreviation (2–5 uppercase letters)}` + `_` + `{card number zero-padded to 3 digits}`
- Examples: `SOR_014`, `SHD_154`, `JTL_095`, `LOF_102`

Token cards use the `SET_T##` format:
- `{set abbreviation}` + `_T` + `{token number zero-padded to 2 digits}`
- Examples: `SOR_T01` (Experience), `SOR_T02` (Shield), `TWI_T01` (Battle Droid)
- Token IDs are derived from the `serialCode` field in the API (e.g. `0101T02` → `SOR_T02`). Phase 1b of the generator assigns these after Phase 1 completes, so they work from both cached and live-fetched data.

This format is the primary key across all game state (zone entries, DQ variables, mzIDs, generated dictionaries, and the deck JSON import format).

## High-level flow
1. Authoritative schema: `Schemas/SWUSim/GameSchema.txt`.
2. Run the generator: `zzCardCodeGenerator.php?rootName=SWUSim` (open via your dev host or run via CLI if you have a local PHP webserver). The generator parses the schema and writes the generated PHP files and the client `GeneratedUI_<timestamp>.js` into `./SWUSim`.
3. The frontend includes `InitialLayout.php` and the generated `GeneratedUI_*.js`. The `GeneratedUI_*.js` contains `GetZoneData()`, `OverlayRules`, `CounterRules`, and other generated helpers.
4. `Core/UILibraries.js` (client) uses those helpers to render zones (`PopulateZone`) and per-card HTML (`createCardHTML`). Counters and overlays are injected at well-known insertion points in `createCardHTML`.

If you change the schema or generator, re-run the generator and hard-refresh the browser so the client receives the updated `GeneratedUI_*.js` (the generator deletes old `GeneratedUI_*.js` files and writes a new timestamped one).

## Zone schema — what properties exist on cards in each zone

**GroundArena / SpaceArena** (units in play):
- `CardID` — SET_NNN identifier
- `Status` — 2=ready, 1=exhausted. Units enter play exhausted.
- `Owner` — player who owns the card (determines discard-pile destination on defeat)
- `Damage` — current damage counters
- `Controller` — player currently controlling (may differ from Owner via Take Control)
- `TurnEffects` — array of temporary effects for this turn/phase/attack. All buffs, debuffs, and keyword grants are encoded here as strings; numeric values are embedded in the string (e.g. `"RAID_2"`). No separate Counters field.
- `Subcards` — JSON array of attached upgrades and face-down captives
  - upgrades: `{CardID, Owner, Controller, TurnEffects[], IsPilot}`
  - captives: `{CardID, Owner, Damage, TurnEffects[], IsCaptive: true}`
- `UniqueID` — persistent ID used to track the unit across phase changes; matches `Leader.DeployedUniqueID` when it's a deployed leader unit

**Leader** (always stays in Leader zone; not on the arena):
- `CardID`, `EpicActionUsed`, `Ready`, `Deployed`, `DeployedUniqueID`, `Damage`, `TurnEffects`, `Counters`
- `Deployed=true` means there is a matching entry in GroundArena/SpaceArena representing the Leader Unit side
- On defeat of the deployed unit, `Deployed` resets to false and the leader returns exhausted (`Ready=false`)

**Base**:
- `CardID`, `Damage`, `EpicActionUsed`, `NumUses`, `TurnEffects`
- When `Damage >= base HP`, that player loses immediately

**Resources**:
- `CardID`, `Status` (2=ready, 1=exhausted), `Owner` (number), `Controller` (number)
- Cards are face-down; visibility is Self (owner can see their cards; count is public)
- Resource order does not need to be maintained (CR 8.36.4); resources may be rearranged freely at any time.
- `Owner` is the player who owns the card. `Controller` is the player currently controlling it. These differ when DJ (SHD_213) steals an enemy resource — when DJ leaves play, the resource returns to its `Owner`.

**Hand** — `CardID` only (visible to owner only)

**Deck** — `CardID` only (private; shuffled at setup)

**Discard** — `CardID`, `From`, `Turn`, `Modifier` (public, face-up):
- `From` — origin zone: `"HAND"` (discarded from hand), `"DECK"` (milled), `"PLAY"` (left the arena — unit defeated or event resolved), `"RESOURCES"` (played via Smuggle/similar)
- `Turn` — `TurnNumber` when discarded; used for "if discarded this phase/round" checks
- `Modifier` — `"TTFREE"` (may be played free this turn), `"OTTFREE"` (opponent may play it free this turn), or `""` (none)

**GlobalEffects** — per-player string flags for lasting game state (e.g. `"TOOK_INITIATIVE"`). Searched by string value.

## Card types
- `Unit` — unit cards played to GroundArena or SpaceArena
- `Leader` — leader cards; stay in the Leader zone; deploy via Epic Action
- `Leader Unit` — the unit side of a deployed leader (exists as a GroundArena/SpaceArena entry)
- `Base` — base cards; stay in the Base zone
- `Event` — played from hand, resolve immediately, then go to Discard
- `Upgrade` — attached to units as Subcards
- `Token Upgrade` — token upgrades (e.g. Shield, Experience)
- `Token Unit` — token units (e.g. Battle Droids, TIE Fighters, Clone Troopers)

Use `CardTargetArena($cardID)` to determine whether a Unit deploys to `"GroundArena"` or `"SpaceArena"`.

## Decision Queue — server-side (how it works)
- File: `Core/DecisionQueueController.php`.
- Core concept: each player has a `DecisionQueue` zone. Objects placed into that zone are processed by `DecisionQueueController::ExecuteStaticMethods()` to perform static, immediate actions that do not require interactive input.

Key points:
- `ExecuteStaticMethods($player, $lastDecision)` iterates the player's queue and handles specific decision Types (e.g., `MZMOVE`, `CUSTOM`).
- For `CUSTOM` decisions the code currently does:
  - split the `Param` string on `|` to produce parts
  - `array_shift` the first element to get the handler name
  - call the registered handler with signature: `$customDQHandlers[$handlerName]($player, $parts, $lastDecision)`

  So: the handler receives `$player` (int), `$parts` (an array of the remaining params — reindexed starting at 0), and `$lastDecision` (string|null). This is the canonical place to add short, non-interactive flows.

How to add a custom handler:
1. Register handlers in `SWUSim/Custom/CardDQHandlers.php` (or `GameLogic.php` for generic handlers) near where `$customDQHandlers` is defined. Example:

```php
$customDQHandlers["DealDamageToBase"] = function($player, $params, $lastDecision) {
    // $params is an array of strings (the parts after the handler name)
    // Example: $targetPlayer = intval($params[0]); $amount = intval($params[1]);
    SWUDealDamageToBase($amount, $targetPlayer);
};
```

2. When you queue the decision (`AddDecision`) ensure the `Param` string format matches how the handler expects it: e.g. `DealDamageToBase|2|3` (handlerName|targetPlayer|amount).

Important notes and gotchas:
- `ExecuteStaticMethods` will `PopDecision($player)` after executing the recognized static action — handlers must not assume the decision remains in the queue.
- Only use `CUSTOM` static handlers for short immediate changes; interactive decisions should use other decision Types (`YESNO`, `MZCHOOSE`, etc.).
- Handlers must be tolerant of malformed parameters (validate array indexes, numeric conversion). Prefer failing silently rather than throwing unhandled exceptions.

## Turn / phase structure
Phases in order: Action Phase → Regroup Phase. Within the Action Phase players alternate taking actions (play a card, attack, activate an ability, take the initiative, or pass). When both players pass consecutively the action phase ends.

Key turn functions in `GameLogic.php`:
- `SWUSwapTurnPlayer()` — swap the active player
- `SWUPassAction($player)` — player passes their action
- `SWUTakeInitiative($player)` — player claims initiative token
- `SWUAfterAction($player)` — cleanup after any action resolves
- `SWUExhaustResources($player, $count)` — exhaust N ready resources to pay a cost
- `PlayerHasIniative($player)` — returns true if this player currently holds initiative (note: function name has a typo in the codebase — `Iniative`)
- `DrawPhase()`, `ReadyPhase()`, `WakeUpPhase()` — phase transition helpers

## Generator notes
- The generator (`zzCardCodeGenerator.php`) outputs server files and a key client JS file (`GeneratedUI_<timestamp>.js`). Important behaviours:
  - It emits `OverlayRules` and `CounterRules` as `const` JS objects in the generated JS.
  - It emits zone metadata accessible by `GetZoneData(zoneName)` on the client.
  - It deletes old `GeneratedUI_*.js` files in the target folder and writes a new timestamped copy.
- **Null/empty filtering (SWUSim only):** All property arrays except `text` drop entries where the value is `null` or `''`. Lookup functions return `null` for absent entries. `CardText($cardID)` always returns a string (even `""`).
- **Ability stub generation (`GeneratedAbilityStubs.php`):** Phase 7 scans card text and emits stubs into six ability arrays. Rules:
  - `whenDefeated`, `onAttack`, `onDefense` — **unit-innate only**: card type must be Unit / Token Unit / Leader / Leader Unit, and the trigger keyword must not appear preceded by a double-quote (grant-style text like `gains "When Defeated:..."` is excluded — those are field-presence passives handled at dispatch time in the Collect* functions).
  - `whenPlayed`, `whenPlayedUsingSmuggle`, `whenPlayedAsUpgrade` — no type restriction; most-specific variant matched first.

## Where to change things (map of responsibilities)
- Decision queue static behavior: `Core/DecisionQueueController.php::ExecuteStaticMethods()`
- Card-specific DQ handlers: `SWUSim/Custom/CardDQHandlers.php`
- Turn/phase FSM: `SWUSim/TurnController.php`
- Combat flow (attack, damage, defeat, keywords): `SWUSim/Custom/CombatLogic.php`
- Core game helpers and hooks: `SWUSim/Custom/GameLogic.php`
- Card-specific complex multi-step logic: `SWUSim/Custom/CardLogic.php`
- Generator parsing & code emission: `zzCardCodeGenerator.php`
- Client rendering: `Core/UILibraries.js` (PopulateZone, createCardHTML, overlays, counters)
- Endpoint that serves next-turn data: generated `SWUSim/GetNextTurn.php`

---

## Card Ability Implementation Workflow

This is the canonical workflow for implementing SWU card abilities. Follow these steps in order.

### CRITICAL RULES
- **NEVER manually edit `SWUSim/GeneratedCode/GeneratedMacroCode.php`** — this file is auto-generated. Any manual edits will be overwritten on the next generator run.
- **NEVER manually edit `SWUSim/GeneratedCode/GeneratedKeywordCode.php`** — same reason.
- Helper functions that don't already exist should be added to the appropriate file in `SWUSim/Custom/` based on theme (combat in `CombatLogic.php`, card-specific in `CardDQHandlers.php` or `CardLogic.php`, general in `GameLogic.php`).
- **Prefer generated prereqs/restrictions over manual runtime guards** — if a play/activation restriction can be expressed as a generated prereq (`CanActivateAbility`, schema macro prereq arrays), implement it there first so UI button state, legality checks, and execution stay in sync.
- **Per-unit stat modifiers** — continuous effects that raise/lower a unit's Power or HP should be implemented by:
  1. Using `AddTurnEffect($mzCard, $effectID)` to tag the card with the effect when the ability resolves.
  2. Adding a `case "$effectID":` to the relevant `ObjectCurrentPower` or `ObjectCurrentHP` switch in `GameLogic.php`.
  The effect is automatically cleared at end of turn by `ExpireEffects`.
  3. When creating effect IDs, use the card ID plus a dash and suffix (e.g. `"SOR_123-POWERED_UP"`).
- **Field-presence passives** (e.g. "friendly units get +1 Power while you control X") belong in `ObjectCurrentPower` / `ObjectCurrentHP`. Loop the arena, switch on card ID, and deduplicate with a seen-set to prevent stacking.
- **Runtime card overrides** (e.g. "loses all abilities", "gains a keyword") — use `ApplyPersistentOverride($mzCard, $overrides)` to store indefinite overrides in `$obj->Counters['_overrides']`. For temporary end-of-turn suppression, use `AddTurnEffect($mzCard, 'NO_ABILITIES')`. Always use `EffectiveCardType($obj)` / `EffectiveCardSubtypes($obj)` (never raw `CardType($obj->CardID)`) when querying field objects in Custom/*.php — the Effective* wrappers check these overrides automatically.
- **PlayCostModifier** — cost modifiers for playing a card from hand should be implemented as a `PlayCostModifier` macro ability in the schema, not as manual switchboard edits in `GameLogic.php`.

### Step 1: Gather card information
Look up the card in the generated card dictionary (`SWUSim/GeneratedCode/GeneratedCardDictionaries_*.php`) or query the SWUStats DB. Get the card's name, effect text, CardID (SET_NNN format), type, cost, arena, and subtypes/aspects.

### Step 2: Understand the zone schema
Refer to the zone schema above. Key facts for SWU:
- **GroundArena / SpaceArena** have: `CardID`, `Status` (2=ready, 1=exhausted), `Owner`, `Damage`, `Controller`, `TurnEffects`, `Counters`, `Subcards`, `UniqueID`
- Units enter play exhausted (`Status=1`).
- Leader Unit entries live in GroundArena or SpaceArena just like regular units. They are distinguished by `Leader.Deployed=true` and matching `UniqueID`.
- Upgrades are stored in `Subcards`, not as separate arena entries.
- **Resources** has `CardID` + `Status` (2=ready, 1=exhausted). Pay costs by exhausting ready resources.
- **Hand, Deck, Discard** have only `CardID`.

### Step 3: Find existing helper functions
Search `SWUSim/Custom/GameLogic.php`, `CombatLogic.php`, and `CardDQHandlers.php` for relevant helpers before writing new code. Key helpers include:

**Zone search & traversal**
- `ZoneSearch($zoneName, $cardTypes, $floatingMemoryOnly, $cardElements, $cardSubtypes, $excludeSubtypes, $forPlayer, $cardClasses)` — returns array of mzIDs matching filters. All params after `$zoneName` are optional (pass `null` to skip). Example: `ZoneSearch("myGroundArena", ["Unit"])`.
- `ZoneCardSearch($zoneName, $cardID)` — find all mzIDs of a specific card in a zone.
- `ZoneContainsCardID($zoneName, $cardID)` — quick boolean check.

**Turn effects & global effects**
- `AddTurnEffect($mzCard, $effectID)` — add a per-card turn effect to a field unit's `TurnEffects` array. Cleared at end of turn by `ExpireEffects`.
- `AddGlobalEffects($player, $effectID)` — add a global effect flag (string) to a player's `GlobalEffects` zone.
- `GlobalEffectCount($player, $effectID)` — count how many times a global effect flag appears.
- `RemoveGlobalEffect($player, $effectID)` — remove one instance of a global effect flag.

**Dealing damage to units**
- `DealDamage(source, target, amount)` macro — deal damage from a source mzID to a **unit** mzID. Runs the full pipeline: shield prevention, keyword effects, lethal check, defeat. Use this for all unit-targeting damage abilities.
- `DealUnpreventableDamage($player, $source, $target, $amount)` — bypasses prevention and deals directly. Use only when card text says "unpreventable."

**Dealing damage to bases**
- `DamageBase(source, targetPlayer, amount)` macro — deal `amount` damage to player `targetPlayer`'s base. Checks win condition. Generates a `DamageBaseCallCount($player)` turn tracker. Use for abilities that say "deal X damage to a base." **Do not use `DealDamage` for bases** — that macro routes through unit damage logic.
- `SWUDealDamageToBase($damage, $targetPlayer)` — same underlying operation as a direct PHP call (used internally by combat resolution).

**Healing units**
- `HealUnit(mzCard, amount)` macro — remove up to `amount` damage counters from a unit mzID (clamped at 0). Also callable directly as `HealUnit($mzCard, $amount)`. Use for "restore X" or similar effects targeting a unit.

**Healing bases**
- `HealBase($player, $targetPlayer, $amount)` — remove up to `$amount` damage counters from `$targetPlayer`'s base (clamped at 0). Takes **three** args (source `$player`, target, amount). From a card handler, call the direct form **`OnHealBase($player, $targetPlayer, $amount)`** (e.g. heal your own base: `OnHealBase($player, $player, 2)`). Note: this is NOT a 2-arg `HealBase(targetPlayer, amount)` call.

> After modifying schema macros, re-run `zzCardCodeGenerator.php?rootName=SWUSim` to regenerate `GeneratedMacroCode.php` and call-count helpers.

**Other card actions (macro wrappers — prefer these)**
- `Draw(amount)` macro — draw cards
- `ExhaustCard(mzID)` macro — exhaust a unit
- `ReadyCard(mzID)` macro — ready a unit
- `ResourceCard(mzID)` macro — resource a card from hand
- `DiscardCard(mzID)` macro — discard a card from hand
- `Reveal(revealedMZ)` macro — reveal a card
- `CreateToken(tokenType, targetPlayer)` macro — create a token
- `GiveShieldToken(targetMZ)` macro — attach a shield token to a unit
- `GiveExperienceToken(targetMZ)` macro — attach an experience token to a unit
- `CaptureUnit(capturingMZ, capturedMZ)` macro — capture a unit (CR 8.34)
- `RescueUnit(guardingMZ)` macro — rescue a captured unit

**Direct helpers (use when macros are not available in await context)**
- `DoDrawCard($player, $amount)` — draw cards
- `DoDiscardCard($player, $mzCard)` — discard a specific card
- `DoRevealCard($player, $revealedMZ)` — reveal a card
- `DoResourceCard($player, $mzID)` — resource a card
- `OnExhaustCard($player, $mzID)` — exhaust a card
- `OnReadyCard($player, $mzID)` — ready a card
- `SWUDealDamageToBase($damage, $targetPlayer)` — deal damage directly to a player's base
- `SWUDefeatUnit($player, $unitMzID)` — defeat a unit
- `SWUExhaustResources($player, $count)` — exhaust N resources to pay a cost
- `DiscardCards($player, $amount)` — queue N sequential discard-from-hand choices

**Stats & properties**
- `ObjectCurrentPower($obj)` — computed power (respects TurnEffects, Counters, passives)
- `ObjectCurrentHP($obj)` — computed HP
- `GetShieldTokenCount($obj)` — number of shield tokens on a unit
- `GetExperienceTokenCount($obj)` — number of experience tokens on a unit
- `CardTargetArena($cardID)` — returns `"GroundArena"` or `"SpaceArena"`
- `CardCost($cardID)` — base cost from card dictionary
- `CardType($cardID)` — base type string from card dictionary (use `EffectiveCardType` for field objects)
- `CardSubtypes($cardID)`, `CardClasses($cardID)` — subtype/aspect lookup
- `GetCardUUID($cardID)` — returns the card's documentId (Strapi v5) or cardUid (old API); used for stat reporting to SWUDeck/SWUStats

**Effective (runtime-override-aware) getters — always use these for field objects**
- `EffectiveCardType($obj)` — type respecting persistent overrides
- `EffectiveCardSubtypes($obj)` — subtypes respecting overrides
- `EffectiveCardClasses($obj)` — aspects/classes respecting overrides
- `EffectiveCardElement($obj)` — element respecting overrides
- `HasNoAbilities($obj)` — true if `NO_ABILITIES` turn effect or persistent override is set

**Persistent overrides**
- `ApplyPersistentOverride($mzCard, $overrides)` — write indefinite overrides into `$obj->Counters['_overrides']`. Supported keys: `type`, `subtypes`, `classes`, `element`, `NO_ABILITIES`, `granted_keywords`. Survive serialization until the unit leaves the arena.
- `HasGrantedKeyword($obj, $keyword)` — check for a keyword explicitly granted via persistent override.

**Turn/phase helpers**
- `GetOpponent($player)` — get the opposing player ID
- `PlayerHasIniative($player)` — check if player has initiative (note: function name typo in codebase)
- `SWUPassAction($player)` — player passes their action
- `SWUTakeInitiative($player)` — player claims initiative
- `SWUAfterAction($player)` — post-action cleanup

**Call-count helpers (macro invocation counters)**
Every generated macro has a corresponding `*CallCount($player)` helper (e.g. `WhenPlayedCallCount($player)`, `OnAttackCallCount($player)`). These read from `MacroTurnIndex` and reset at the start of each turn. Useful for "when you played your Nth card this turn" effects.

### Step 4: Study existing examples
Search `SWUSim/Custom/CardDQHandlers.php` and `SWUSim/Custom/GameLogic.php` for existing cards with similar effects. Look for how similar timing windows (`WhenPlayed`, `WhenDefeated`, `OnAttack`, `OnDefense`, `OnAttackEnd`) are used.

### Step 5: Write the ability code

**Key Point:** Write ONLY the function body (closure body). The code generator automatically wraps your code in:
```php
$whenPlayedAbilities["SOR_123:0"] = function($player) {
  // YOUR CODE HERE
};
```
or
```php
$onAttackAbilities["SOR_123:0"] = function($player) {
  // YOUR CODE HERE
};
```

So in the ability code, you have access to:
- `$player` — the player who owns/triggered the card
- `DecisionQueueController::GetVariable("mzID")` — the mzID of the card (auto-retrieved by generator)
- Any helper functions from Step 3
- **Await syntax** for player choices (see Multi-Step Ability Patterns below):
  - `$var = await $player.MZChoose("zone-0&zone-1")` — mandatory card choice
  - `$var = await $player.MZMayChoose("zone-0&zone-1")` — optional card choice
  - `$var = await $player.MZMultiChoose($targets, $min, $max, "tooltip")` — select between `$min` and `$max` cards in one popup; returns an `&`-delimited mzID string or `"-"`
  - `$var = await $player.YesNo("prompt")` — yes/no choice
  - `$var = await $player.NumberChoose(min, max, "prompt")` — choose a number in a range
  - `$var = await $player.MZSplitAssign($targets, $amount, "prompt")` — split-assign a pool across targets; returns comma-separated `mzID:amount` pairs
  - `await FunctionName($player, $args)` — call a function that queues decisions

**Critical await constraints:**
The code generator splits your ability code at each `await` line. Pre-await code goes into the main ability function; post-await code goes into a custom DQ handler. **Braces, variables, and scope do NOT carry across the split.** This means:
  - **NO await inside conditionals or loops.** Ever. Use early `return` statements to flatten control flow so `await` is always at top level.
  - **NO variables used after await** that were computed before it. The generator cannot propagate them across function boundaries. Recompute any needed values after the await (in the handler section).
  - **NO function calls as await parameters.** Pre-compute into a variable: `$str = implode("&", $arr);` then `await $player.MZChoose($str)`.
  - **NO tooltip parameter as second arg to MZChoose/MZMayChoose.** Omit it: just `await $player.MZChoose($targetStr)`.

**Correct pattern:** Early returns + pre-computed variables + top-level await
```php
$targets = ZoneSearch("theirGroundArena", ["Unit"]);
if(empty($targets)) return;                          // Early return instead of nested if
$targetStr = implode("&", $targets);                 // Pre-compute before await
$chosen = await $player.MZChoose($targetStr);        // await at top level
// After await — recompute what you need; $chosen is the mzID the player selected
$obj = GetZoneObject($chosen);
DealDamage($player, DecisionQueueController::GetVariable("mzID"), $chosen, 3);
```

### Step 6: Register the ability
Add the ability closure to the appropriate ability array in the correct `Custom/` file. The ability macro name determines which timing window fires it:

| Macro | When it fires |
|---|---|
| `WhenPlayed` | When the unit/event enters play from hand |
| `WhenDefeated` | When the unit is defeated |
| `OnAttack` | At the start of an attack by this unit |
| `OnAttackEnd` | At the end of an attack by this unit |
| `OnDefense` | When this unit is declared as a defender |
| `OnDefenseEnd` | When the attack targeting this unit ends |
| `ActivateAbility` | Active ability (costs resources to use) |

**Important:** For any manual edits to `GameLogic.php` that accompany ability code:
- If using `AddGlobalEffects(...)` with a conditional scope, add a matching filter in any lookup that reads those effects.
- If adding per-turn stat modifiers via `AddGlobalEffects`, add a `case "$cardID":` to `ObjectCurrentPower` or `ObjectCurrentHP`.
- If registering custom DQ handlers, ensure those handlers exist in `CardDQHandlers.php` or `GameLogic.php`.
- If a card's cost reduction can be expressed as a `PlayCostModifier` macro, prefer that over editing manual cost-calculation code.

### Multi-Step Ability Patterns (YesNo, Target Selection)

**Pattern:** When an ability requires player input (YesNo, card choice), write ability code that *queues* decisions using await. The generator compiles these into custom DQ handlers.

**Example: Deal damage to a chosen target**
```php
$targets = array_merge(
    ZoneSearch("theirGroundArena", ["Unit"]),
    ZoneSearch("theirSpaceArena", ["Unit"])
);
if(empty($targets)) return;
$targetStr = implode("&", $targets);
$chosen = await $player.MZChoose($targetStr);
DealDamage($player, DecisionQueueController::GetVariable("mzID"), $chosen, 4);
```

**Example: Optional effect with YesNo**
```php
$answer = await $player.YesNo("Deal_3_damage_to_your_own_unit_to_draw_2_cards?");
if($answer != "YES") return;
$myUnits = array_merge(
    ZoneSearch("myGroundArena", ["Unit"]),
    ZoneSearch("mySpaceArena", ["Unit"])
);
if(empty($myUnits)) return;
$targetStr = implode("&", $myUnits);
$chosen = await $player.MZChoose($targetStr);
$mzID = DecisionQueueController::GetVariable("mzID");
DealDamage($player, $mzID, $chosen, 3);
DoDrawCard($player, 2);
```

### MZMultiChoose — Single Popup Multi-Select Pattern

**Await syntax:** `$var = await $player.MZMultiChoose($targets, $min, $max, "tooltip")`
- `$targets`: `&`-delimited mzID string
- `$min`: minimum required selections
- `$max`: maximum allowed selections
- `"tooltip"` (optional): underscore-separated prompt

**Return value:** `&`-delimited selected mzIDs. Returns `"-"` when zero selections are allowed and the player confirms none.

**Example — choose up to 2 enemy units to exhaust:**
```php
$targets = array_merge(
    ZoneSearch("theirGroundArena", ["Unit"]),
    ZoneSearch("theirSpaceArena", ["Unit"])
);
if(empty($targets)) return;
$targetStr = implode("&", $targets);
$selected = await $player.MZMultiChoose($targetStr, 0, 2, "Exhaust_up_to_2_enemy_units");
if($selected === "-") return;
foreach(explode("&", $selected) as $chosen) {
    if($chosen === "") continue;
    OnExhaustCard($player, $chosen);
}
```

### MZSplitAssign — Split Damage Pattern

**Await syntax:** `$var = await $player.MZSplitAssign($targets, $amount, "tooltip")`
- `$targets`: `&`-delimited mzID string
- `$amount`: integer pool to distribute
- `"tooltip"` (optional): underscore-separated prompt

**Return value:** Comma-separated `mzID:amount` pairs (e.g. `"myGroundArena-0:3,theirGroundArena-1:2"`). Returns `"-"` if no valid targets.

**Processing the result:** Use `ProcessSplitDamage($player, $source, $assignmentStr)` to call `DealDamage` for each non-zero assignment.

**Example — deal 5 damage split among units:**
```php
$allUnits = array_merge(
    ZoneSearch("myGroundArena", ["Unit", "Leader Unit"]),
    ZoneSearch("theirGroundArena", ["Unit", "Leader Unit"])
);
if(empty($allUnits)) return;
$targetStr = implode("&", $allUnits);
$assignments = await $player.MZSplitAssign($targetStr, 5, "Split_5_damage_among_units");
ProcessSplitDamage($player, DecisionQueueController::GetVariable("mzID"), $assignments);
```

### Step 7: Add any new helper functions
Group by theme:
- Combat-related (attacks, damage, defeat, keywords) → `CombatLogic.php`
- Card-specific complex flows → `CardDQHandlers.php` or `CardLogic.php`
- General game logic → `GameLogic.php`

### Step 8: Prefer Framework Hooks Before Manual Switchboards

Before editing large manual switchboards in `GameLogic.php`, check whether the behavior belongs in one of the generated framework hooks instead:
- **Restrictions / legality** — generated macro prereqs such as `CanActivateAbility` for card-local restrictions.
- **Scalar cost changes** — `PlayCostModifier` macro.
- **One-shot consumable modifiers** — return `['delta' => ..., 'consume' => true]` so the framework can remove the source effect after a successful application.

Manual `GameLogic.php` edits are still appropriate for:
- Cross-card framework rules that affect many cards at once.
- Costs that are not simple scalar modifiers (sacrifice, capture, discard, reveal, multi-step alternative payment).
- Cases where the current generated macro surface cannot represent the rule without awkward workarounds.

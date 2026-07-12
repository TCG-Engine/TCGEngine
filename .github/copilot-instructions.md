# TCGEngine — Repo Instructions & Developer Guide

This document provides a concise developer-oriented guide to the repository, with focused guidance for editing Decision Queue and Turn / Next-Turn code paths. Keep this file updated as you change behavior or generator outputs.

## Quick links (files to know)
- `Core/DecisionQueueController.php` — server-side logic that executes static decision-queue entries and invokes custom handlers.
- `Core/UILibraries.js` — client-side rendering pipeline used by the generated UI (PopulateZone, createCardHTML, card injection points).
- `zzGameCodeGenerator.php` — schema parser + code generator that produces server helpers and a `GeneratedUI_*.js` client helper.
- `Schemas/<RootName>/GameSchema.txt` — schema defining zones, overlays, counters and display properties.
- `<RootName>/GeneratedUI_*.js` — generated client helpers (watch for the timestamped filename in the game folder).
- `<RootName>/ZoneAccessors.php`, `ZoneClasses.php`, `GamestateParser.php` — generated server-side gamestate helpers.
- `<RootName>/GetNextTurn.php`, `NextTurnRender.php`, `InitialLayout.php` — important generated files used by runtime.
- `<RootName>/Custom/*` (e.g. `RBSim/Custom/GameLogic.php`) — custom game logic where customDQHandlers are normally registered.

## High-level flow
1. Authoritative schema: `Schemas/<Root>/GameSchema.txt`.
2. Run the generator: `zzGameCodeGenerator.php?rootName=<Root>` (open via your dev host or run via CLI if you have a local PHP webserver). The generator parses the schema and writes the generated PHP files and the client `GeneratedUI_<timestamp>.js` into `./<Root>`.
3. The frontend includes `InitialLayout.php` and the generated `GeneratedUI_*.js`. The `GeneratedUI_*.js` contains `GetZoneData()`, `OverlayRules`, `CounterRules`, and other generated helpers.
4. `Core/UILibraries.js` (client) uses those helpers to render zones (`PopulateZone`) and per-card HTML (`createCardHTML`). We inject counters and overlays at well-known insertion points in `createCardHTML`.

If you change the schema or generator, re-run the generator and hard-refresh the browser so the client receives the updated `GeneratedUI_*.js` (the generator deletes old `GeneratedUI_*.js` files and writes a new timestamped one).

## Decision Queue — server-side (how it works)
- File: `Core/DecisionQueueController.php`.
- Core concept: each player has a `DecisionQueue` zone (parsed from schema). Objects placed into that zone are processed by `DecisionQueueController::ExecuteStaticMethods()` to perform static, immediate actions that do not require interactive input.

Key points:
- `ExecuteStaticMethods($player, $lastDecision)` iterates the player's queue and handles specific decision Types (e.g., `MZMOVE`, `CUSTOM`).
- For `CUSTOM` decisions the code currently does:

  - split the `Param` string on `|` to produce parts
  - `array_shift` the first element to get the handler name
  - call the registered handler with signature: `$customDQHandlers[$handlerName]($player, $parts, $lastDecision)`

  So: the handler receives `$player` (int), `$parts` (an array of the remaining params — reindexed starting at 0), and `$lastDecision` (string|null). This is the canonical place to add short, non-interactive flows.

How to add a custom handler
1. Register handlers in your game's custom logic file (e.g. `RBSim/Custom/GameLogic.php`) near where `$customDQHandlers` is defined. Example:

```php
$customDQHandlers = [];
$customDQHandlers["DealDamage"] = function($player, $params, $lastDecision) {
    // $params is an array of strings (the parts after the handler name)
    // Example usage: $targetZone = $params[0]; $amount = intval($params[1]);
    // Implement small, stateless actions here. Keep them short and idempotent.
};
```

2. When you queue the decision (AddDecision) ensure the `Param` string format matches how the handler expects it: e.g. `DealDamage|myBattlefield-3|2` (handlerName|targetMZ|amount). The generator/renderer may serialize things differently; inspect how decisions are enqueued in your game flow.

Important notes and gotchas
- `ExecuteStaticMethods` will `PopDecision($player)` after executing the recognized static action — make sure handlers do not assume the decision remains in the queue.
- Only use `CUSTOM` static handlers for short immediate changes; interactive decisions should use other decision Types (YESNO, MZCHOOSE, etc.).
- Handlers must be tolerant of malformed parameters (validate array indexes, numeric conversion). Prefer failing silently and logging rather than throwing unhandled exceptions that could kill the loop.

## Turn controller / generator notes
- The generator (`zzGameCodeGenerator.php`) outputs a number of server files and a key client JS file (`GeneratedUI_<timestamp>.js`). Important generator behaviours:
  - It emits `OverlayRules` and `CounterRules` as `const` JS objects in the generated JS. Client code reads these constants for overlays/counters.
  - It emits zone metadata accessible by `GetZoneData(zoneName)` on the client.
  - It deletes old `GeneratedUI_*.js` files in the target folder and writes a new timestamped copy.

## Where to change things (map of responsibilities)
- Decision queue static behavior: `Core/DecisionQueueController.php::ExecuteStaticMethods()`
- Register game-specific handlers: `<RootName>/Custom/GameLogic.php` (see `$customDQHandlers` array)
- Additional activation costs: `<RootName>/Custom/GameLogic.php` (see `$additionalActivationCosts` array + `DeclareAdditionalCost` handler)
- Generator parsing & code emission: `zzGameCodeGenerator.php` (Counters, Overlays, Zone metadata, GeneratedUI emission)
- Client rendering: `Core/UILibraries.js` (PopulateZone, createCardHTML, CreateCountersHTML, overlays injection)
- Endpoint that serves next-turn data: generated `<RootName>/GetNextTurn.php` (see `zzGameCodeGenerator.php` which writes this file)

## Browser-driven bot controllers
- Shared transport and retry behavior lives in `Core/BotController.php`, `Core/jsInclude.js`, `NextTurn.php`, and process-input mode `10017` in `Core/EngineActionRunner.php`.
- A game opts in by defining `GameBotControllerMode()`, `GetBotControllerPlayers()`, `BotControllerPendingPlayerForClient()`, and `ProcessBotControllerStep()` in its custom runtime.
- `GetNextTurn.php` publishes the current controller state with each board update. The shared client invokes mode `10017` only when the authoritative `pendingPlayer` is a configured bot seat.
- `NextTurn.php` keeps polling while animations play, but accepted board updates must pass through its ordered render queue. Never schedule independent delayed `RenderUpdate(...)` calls, because a shorter-delay newer update can otherwise render before an older update and then be overwritten by stale state.
- The request uses the viewing player's normal authentication. Never expose a bot seat auth key to the browser; `ProcessBotControllerStep()` must re-evaluate the authoritative pending bot seat on the server before applying an action.
- Keep game-specific layouts and waiting-message renderers free of bot invocation hooks. They may display turn/response state, but the shared update transport owns bot scheduling.

---

## Card Ability Implementation Workflow (for AI agents)

This is the canonical workflow for implementing card abilities. Follow these steps in order.

### CRITICAL RULES
- **NEVER manually edit `<RootName>/GeneratedCode/GeneratedMacroCode.php`** — this file is auto-generated from the database. The MCP `save_card_abilities` tool saves to the DB and triggers the code generator automatically. Any manual edits will be overwritten.
- **NEVER manually edit `<RootName>/GeneratedCode/GeneratedMacroCount.js`** — same reason.
- Helper functions that don't already exist should be added to the appropriate file in `<RootName>/Custom/` based on theme (e.g. combat helpers in CombatLogic.php, general helpers in GameLogic.php).
- **Prefer generated prereqs/restrictions over manual runtime guards** — if a card-local play/activation restriction can be expressed as a generated prereq (`CanActivateAbility`, generated macro prereq arrays, schema macro prereq hooks), implement it there first so UI button state, legality checks, and execution stay in sync. Reserve manual guards in `DoActivateCard` / `DoActivatedAbility` for cross-card framework logic or cases the macro layer cannot yet express cleanly.
- **Prefer generated numerical modifier macros for scalar cost math** — Grand Archive now supports `MemoryCostModifier`, `ReserveCostModifier`, `PlayCostModifier`, and `ActivationCostModifier` schema macros (`MacroType=ValueModifier`). Use these for card-driven +/- cost logic before extending manual switchboards in `GameLogic.php`.
- **Modifier macro return contract** — modifier abilities may return either an `int` delta or an array like `['delta' => -1, 'consume' => true, 'applied' => true]`. The generated evaluator passes results through `ParseModifierResult(...)`; `consume` removes the source effect only when the modifier actually applied.
- **Zone-active macro listeners** — CardEditor abilities can be saved as `abilityType='listener'` with `listenerZones` listing schema zone names where that card listens. These are not automatically timed by the generator: app code chooses the rules timing by calling `DispatchMacroListeners($macroName, $eventPlayer, $eventParams, $candidateZones = null)`. Listener bodies receive `$listenerMZ`, `$listenerCardID`, `$eventMacro`, `$eventPlayer`, `$eventParams`, and `$event_<paramName>` variables based on the observed macro's schema parameters. Do not assume a macro has `mzID`; pass the observed macro's own named parameters in `$eventParams`.
- **Per-card stat modifiers** — continuous effects that raise/lower a single card's POWER, HP, or Level should be implemented by:
  1. Using `AddTurnEffect($mzCard, $effectID)` to tag the card with the effect when the ability resolves.
  2. Adding a `case "$effectID":` to the relevant `ObjectCurrentPower`, `ObjectCurrentHP`, or `ObjectCurrentLevel` switch in `GameLogic.php`.
  The effect is automatically cleared at end of turn by `ExpireEffects`.
  3. When creating effect ids, make it be the card id plus a dash and suffix (if that card needs multiple distinct effects or other information encoded)
- **Field-presence passives** (e.g. "champion gets +1 level while you control X") belong in `ObjectCurrentLevel`/`ObjectCurrentPower`/`ObjectCurrentHP`. Use the established pattern: loop the field once, switch on card ID, deduplicate with `$appliedPassives[$fID]` to prevent duplicate copies from stacking inadvertently.
- **Runtime card overrides** ("becomes a Phantasia", "loses all abilities", "cards in graveyards are NORM element") — use `ApplyPersistentOverride($mzCard, $overrides)` to store indefinite overrides in `$obj->Counters['_overrides']`. For temporary end-of-turn suppression, use `AddTurnEffect($mzCard, 'NO_ABILITIES')`. Always use `EffectiveCardType/Subtype/Classes/Element($obj)` (never raw `CardType($obj->CardID)` etc.) when querying field objects in Custom/*.php — the Effective* wrappers check these overrides automatically.

### Step 1: Gather card information
Call the MCP `get_card_info` tool with the card ID to get the card's name, effect text, element, type, cost, and other metadata.

### Step 2: Discover the zone schema
Call the MCP `get_zone_schema` tool to understand what properties exist on cards in each zone. Key facts for Grand Archive:
- **Field zone** has: `CardID`, `Status` (1=exhausted, 2=ready), `Owner`, `Damage` (integer counter), `Controller`, `TurnEffects` (array of effect IDs)
- Champions are on the Field, just like allies. They are distinguished by `CardType == "CHAMPION"`.
- Champion damage is tracked via the `Damage` property on the champion object in the field, NOT via `GetHealth()`. The `Health` zone is a legacy artifact.
- **Memory zone** has only: `CardID`
- **Graveyard, Hand, Deck** have only: `CardID`

### Step 3: Find existing helper functions
Call the MCP `get_helper_functions` tool to discover what helper functions already exist. Search with relevant terms. Key helpers for Grand Archive include:
- `ZoneSearch(zoneName, cardTypes, floatingMemoryOnly, cardElements, cardSubtypes)` — search a zone by type/element/subtype. All params after `zoneName` are optional and can be passed by name (e.g. `ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"])`). If the card is a spell, you'll need to use FilterSpellshroudTargets to filter the results.
- `DealChampionDamage(player, amount)` — add damage to the player's champion on the field
- `RecoverChampion(player, amount)` — remove damage from the player's champion on the field
- `DealDamage(player, source, target, amount)` — deal damage from a source to a target (via macro)
- `Draw(player, amount)` — draw cards (macro call, preferred over DoDrawCard)
- `MZMove(player, mzCard, destZone)` — move a card between zones
- `IsClassBonusActive(player, classes)` — check if champion's class matches
- `AddGlobalEffects(player, effectID)` — add a global effect that affects all matching cards this turn (via `doesGlobalEffectApply` / `CardCurrentEffects`)
- `AddTurnEffect(mzCard, effectID)` — add a per-card turn effect to a specific field card's `TurnEffects` array. Use this when the effect targets a single card (e.g. "+2 POWER until end of turn on a specific ally") rather than `AddGlobalEffects` which broadcasts to all matching cards. The effect ID is conventionally the source card's ID. It is cleared at end of turn by `ExpireEffects`.
- `ProcessSplitDamage(player, source, assignmentStr)` — process the comma-separated `mzID:amount` result from MZSplitAssign, calling `DealDamage` for each non-zero assignment
- `Delevel(player)` — delevel the player's champion by returning its current card to material deck and promoting the top subcard. Returns false if champion has no lineage.
- `ExhaustCard(player, mzID)` — exhaust a card
- `WakeupCard(player, mzID)` — ready a card
- `ObjectCurrentPower(obj)`, `ObjectCurrentHP(obj)` — get computed stats
- `CardElement(cardID)`, `CardType(cardID)`, `CardClasses(cardID)` — card dictionary lookups
- `MacroNameCallCount($player)` — how many times a macro was invoked for a player this turn. Every generated macro has a corresponding `*CallCount($player)` helper (e.g. `CardActivatedCallCount($player)`, `EnterCallCount($player)`). These read from `MacroTurnIndex` and are reset at the start of each turn. Useful for cards that depend on how many times you did something this turn.
- `HasNoAbilities($obj)` — returns true if a field object has the `NO_ABILITIES` TurnEffect (temporary) or `$obj->Counters['_overrides']['NO_ABILITIES']` (persistent). All keyword wrappers and ability dispatch already check this automatically; you don't need to guard code manually.
- `EffectiveCardType($obj)` / `EffectiveCardSubtypes($obj)` / `EffectiveCardClasses($obj)` / `EffectiveCardElement($obj)` — get a field object's runtime-overridden type/subtypes/classes/element. Always use these (not raw `CardType($obj->CardID)` etc.) when querying field objects in Custom/*.php code.
- `ApplyPersistentOverride($mzCard, $overrides)` — write indefinite overrides into `$obj->Counters['_overrides']`. Keys are `type`, `subtypes`, `classes`, `element`, `NO_ABILITIES`, `granted_keywords` (array of keyword names). Overrides survive serialization and persist until the card leaves the field. Used by Fracturize.
- `HasGrantedKeyword($obj, $keyword)` — check for a keyword explicitly granted by a persistent override (e.g. `'Reservable'`). This bypasses `HasNoAbilities`, so a Fracturized card can still be reserved.
- `ZoneContainsCardID($zoneName, $cardID)` — scan a zone for a card ID; used by field-presence passives (e.g. Nullifying Lantern's graveyard-element override in `EffectiveCardElement`).

### Step 4: Study existing examples
Call the MCP `get_implemented_examples` tool with the relevant macro name (e.g. "CardActivated", "Enter", `ActivationCostModifier`, `MemoryCostModifier`) to see how similar abilities are coded. This shows you the exact pattern to follow.

### Step 5: Write the ability code

**Key Point:** Write ONLY the function body (closure body). The code generator automatically wraps your code in:
```php
$enterAbilities["cardID:0"] = function($player) {
  // YOUR CODE HERE
};
```
or
```php
$cardActivatedAbilities["cardID:0"] = function($player) {
  // YOUR CODE HERE
};
```

So in the ability code, you have access to:
- `$player` — the player who owns/activated the card
- `DecisionQueueController::GetVariable("mzID")` — the mzID of the card (auto-retrieved by generator)
- Any helper functions from Step 3
- **Await syntax** for player choices (see Multi-Step Ability Patterns below):
  - `$var = await $player.MZChoose("zone-0&zone-1")` — mandatory card choice
  - `$var = await $player.MZMayChoose("zone-0&zone-1")` — optional card choice
  - `$var = await $player.MZMultiChoose($targets, $min, $max, "tooltip")` — select between `$min` and `$max` cards in one popup; returns an `&`-delimited mzID string or `"-"`
  - `$var = await $player.YesNo("prompt")` — yes/no choice
  - `$var = await $player.NumberChoose(min, max, "prompt")` — choose a number in a range
  - `$var = await $player.MZSplitAssign($targets, $amount, "prompt")` — split-assign a pool across targets. Returns comma-separated `mzID:amount` pairs (e.g. `"myField-0:3,myField-1:2"`). `$targets` is an `&`-delimited mzID string, `$amount` is the total pool to assign.
  - `await FunctionName($player, $args)` — call a function that queues decisions
  - `$var = await $player.Modal($min, $max, "label1&label2", "tooltip")` - choose labeled non-card modes; returns comma-separated 0-based option indexes
  - `$var = await $player.Rearrange($param)` - reorder/reassign revealed cards between named piles; returns the pile serialization
  - `$var = await $player.NameCard($previewParam, "tooltip")` - choose/type a card name; pass `""` as `$previewParam` for an unrestricted name search

**Critical await constraints:**
The code generator supports inline `await` for player choices, including awaits nested inside `if`/`else`, `for`, and `while` blocks. Prefer this for card-local interactive flows instead of hand-writing one-off Decision Queue handlers.
  - **Control blocks are supported.** `await` may appear inside conditionals and loops when that directly expresses the card text.
  - **Keep await state serializable.** Scalar values and arrays can be carried through the generated await frame. Recompute live zone objects after an await instead of relying on pre-await object references.
  - **NO function calls as await parameters.** Pre-compute into a variable: `$str = implode("&", $arr);` then `await $player.MZChoose($str)`.
  - **NO tooltip parameter as second arg to MZChoose/MZMayChoose.** The await syntax does not support `await $player.MZChoose($targetStr, "tooltip")` — the generator produces broken PHP. Omit it: just `await $player.MZChoose($targetStr)`.

**Correct pattern:** Pre-computed chooser strings + inline await
```php
if(!empty($targets)) {
    $targetStr = implode("&", $targets);      // Pre-compute before await
    $chosen = await $player.MZChoose($targetStr);
    $chosenObj = GetZoneObject($chosen);      // Recompute live objects after await
}
```

### Step 6: Save via MCP
Call `save_card_abilities` with the card ID, macro name, and ability code. The MCP server saves to the database AND automatically runs the code generator, so `GeneratedMacroCode.php` is updated.

**Important:** `save_card_abilities` auto-generates the macro code (e.g., `cardActivatedAbilities["cardID:0"] = function($player) { ... }`), but you remain responsible for any custom GameLogic.php edits:
- If using `AddGlobalEffects(...)`, you must manually add a filter in `$doesGlobalEffectApply[$cardID]` if the effect applies conditionally (e.g., only to allies).
- If adding per-turn stat modifiers via `AddGlobalEffects`, manually add a `case "$cardID":` to `ObjectCurrentPower`, `ObjectCurrentHP`, or `ObjectCurrentLevel` to declare the modifier.
- If registering custom DQ handlers in the ability code (e.g., to handle multi-step flows), ensure those handlers exist in `Custom/GameLogic.php` (the generator will wrap them but won't invent new handlers).
- If a card's scalar cost change can be expressed as a generated value modifier, prefer saving a `MemoryCostModifier` / `ReserveCostModifier` / `PlayCostModifier` / `ActivationCostModifier` ability instead of editing manual cost-calculation code in `GameLogic.php`.

### Multi-Step Ability Patterns (YesNo, Target Selection)

**Pattern:** When an ability requires player input (YesNo, card choice), write the ability with inline `await`. The generator will compile those awaits into Decision Queue entries and generated resume machinery.

**Example: Card with YesNo + target selection**
```php
// Ability code (write only the body — generator wraps in function($player) { })
$targetSelf = await $player.YesNo("Target_yourself?");
$deckRef = $targetSelf == "YES" ? "myDeck" : "theirDeck";
$gravRef = $targetSelf == "YES" ? "myGraveyard" : "theirGraveyard";
for($i = 0; $i < 3; ++$i) {
    if(empty(ZoneSearch($deckRef))) break;
    MZMove($player, $deckRef . "-0", $gravRef);
}
```

The generator translates `await` statements into queued `DecisionQueueController::AddDecision()` calls. Each `await` becomes:
1. A decision queue entry (YESNO, MZCHOOSE, MZMAYCHOOSE, MZMULTICHOOSE, etc.)
2. Generated resume logic that processes the player's response

Custom DQ handlers are still useful for shared helpers, runtime hooks, and short non-interactive effects, but card-local multi-step prompts should generally be expressed with inline `await`.

### Choosing the Right UI Interaction

For new card-local prompts, prefer these modern interactions:

| Player choice needed | Preferred interaction | Await syntax? | Return shape |
| --- | --- | --- | --- |
| Binary yes/no | `YESNO` | `await $player.YesNo("prompt")` | `"YES"` or `"NO"` |
| Exactly one visible card/object | `MZCHOOSE` | `await $player.MZChoose($targetStr)` | selected mzID |
| Optional one visible card/object | `MZMAYCHOOSE` | `await $player.MZMayChoose($targetStr)` | selected mzID or pass sentinel |
| Any/up to/exactly N cards from one known set | `MZMULTICHOOSE` | `await $player.MZMultiChoose($targetStr, $min, $max, "prompt")` | `&`-delimited mzIDs or `"-"` |
| One or more labeled modes, not cards | `MZMODAL` | `await $player.Modal($min, $max, "label1&label2", "prompt")` | comma-separated 0-based indexes or `"-"` |
| Integer amount in a range | `NUMBERCHOOSE` | `await $player.NumberChoose($min, $max, "prompt")` | selected number as a string |
| Numeric pool distributed among card targets | `MZSPLITASSIGN` | `await $player.MZSplitAssign($targetStr, $amount, "prompt")` | comma-separated `mzID:amount` pairs |
| Reorder/reassign revealed cards between piles | `MZREARRANGE` | `await $player.Rearrange($param)` | pile serialization, e.g. `Top=a,b;Bottom=c` |
| Type or choose a card name | `NAMECARD` | `await $player.NameCard($previewParam, "prompt")` | selected card name |

`TWOSIDEDSLIDER` also exists as a specialized queued UI:
- `TWOSIDEDSLIDER` is a specialized numeric split UI and is a good candidate for first-class await support if kept.

`OPTIONCHOOSE` and `ICONCHOICE` are deprecated for new card-authoring:
- `OPTIONCHOOSE` is largely a label-return predecessor to `MZMODAL`; keep existing direct-queue paths working, but use `MZMODAL` for new labeled mode choices.
- `ICONCHOICE` is a specialized directional/icon picker; keep existing Shifting Currents-style paths working, but use `MZMODAL` for new icon/direction choices unless the visual compass presentation is essential.

Older/specialized client decision types such as `TOPDECKSEARCH`, `SCRY`, `REVEALARRANGE`, and `CHOOSEZONE` exist for legacy or framework-specific flows. Do not reach for them in new saved ability code unless you are maintaining an existing path that already uses them.

### MZChoose / MZMayChoose - Single Card Choice Pattern

Use `MZChoose` when the player must choose one card/object from a visible candidate set. Use `MZMayChoose` when passing is legal. Always precompute the `&`-delimited target string before the await, and guard optional results against `"-"`, `""`, and `"PASS"`.

```php
$targets = ZoneSearch("myField", ["ALLY"]);
if(empty($targets)) return;
$targetStr = implode("&", $targets);
$chosen = await $player.MZMayChoose($targetStr);
if($chosen === "-" || $chosen === "" || $chosen === "PASS") return;
AddCounters($player, $chosen, "buff", 1);
```

### MZModal - Labeled Mode Choice Pattern

**Decision type:** `MZMODAL` - lets the player choose one or more labeled modes that are not themselves cards.

**Await syntax:** `$var = await $player.Modal($min, $max, "label1&label2&label3", "tooltip")`

**Return value:** comma-separated 0-based indexes of the chosen labels, e.g. `"0,2"`. Returns `"-"` if no options were chosen and `min` is `0`. The return value is indexes, not label text.

**When to use:** Use `MZMODAL` for "choose one" / "choose two" mode cards, element/type declarations, or other labeled choices where the options are rules text rather than board cards.

```php
$modeChoice = await $player.Modal(1, 1, "Recover_3&Draw_a_card", "Choose_a_mode");
if($modeChoice === "0") {
    RecoverChampion($player, 3);
} else if($modeChoice === "1") {
    Draw($player, 1);
}
```

### NameCard - Card Name Input Pattern

**Decision type:** `NAMECARD` - lets the player type/search for a card name, optionally constrained or previewed by a provided card set.

**Await syntax:** `$var = await $player.NameCard($previewParam, "tooltip")`

**Return value:** selected card name as a string.

**When to use:** Use this for "name a card" effects. Do not model open card-name input as `MZMODAL`; `MZMODAL` is for a finite list of labeled modes.

```php
$namedCard = await $player.NameCard("", "Name_a_card");
AddNamedCardEffect($player, $namedCard);
```

If the UI should show a constrained preview set, precompute the preview parameter before the await:

```php
$previewCards = implode("&", $candidateCardIDs);
$previewParam = "Common_named_allies||" . $previewCards;
$namedCard = await $player.NameCard($previewParam, "Name_an_ally");
```

### NumberChoose - Numeric Amount Pattern

**Decision type:** `NUMBERCHOOSE` - lets the player choose an integer between a minimum and maximum.

**Await syntax:** `$var = await $player.NumberChoose($min, $max, "tooltip")`

**Return value:** selected number as a string; cast with `intval(...)` before arithmetic.

**When to use:** Use this when the card asks "choose a number" or "up to N" and the chosen count drives one shared resolution. For mill/discard/damage counts, prefer one bounded `NUMBERCHOOSE` followed by one shared resolver rather than recursive prompts.

```php
$amount = await $player.NumberChoose(0, $maxAmount, "Choose_amount_to_mill");
MillCards($player, intval($amount));
```

### MZMultiChoose — Single Popup Multi-Select Pattern

**Decision type:** `MZMULTICHOOSE` — lets the player select a configurable minimum/maximum number of cards in one popup instead of repeating `MZCHOOSE` prompts.

**Await syntax:** `$var = await $player.MZMultiChoose($targets, $min, $max, "tooltip")`
- `$targets`: `&`-delimited mzID string (for example `"myMemory-0&myMemory-1&myMemory-2"`)
- `$min`: minimum number of required selections
- `$max`: maximum number of allowed selections
- `"tooltip"` (optional): underscore-separated prompt shown in the popup

**Return value:** `&`-delimited selected mzIDs, for example `"myMemory-0&myMemory-2"`. Returns `"-"` when zero selections are allowed and the player confirms none.

**UI behavior:** A single popup shows all candidate cards, highlights selected cards, displays the current selection count, and enforces the configured min/max before confirm.

**Example — reveal any number of wind cards from memory:**
```php
$choices = ZoneSearch("myMemory", cardElements: ["WIND"]);
if(empty($choices)) return;
$targetStr = implode("&", $choices);
$selected = await $player.MZMultiChoose($targetStr, 0, count($choices), "Reveal_any_number_of_wind_cards_from_memory");
if($selected === "-") return;
foreach(explode("&", $selected) as $chosen) {
  if($chosen === "") continue;
  Reveal($chosen);
}
```

**When to use vs. repeated `MZCHOOSE`:** Use `MZMultiChoose` when the player should choose several cards from one visible candidate set in a single interaction. Keep repeated `MZCHOOSE` flows for stepwise decisions where each pick changes the next legal set or needs separate processing between picks.

### MZRearrange — Ordered Top/Bottom Pile Pattern

**Decision type:** `MZREARRANGE` — lets the player drag revealed card IDs between named piles and order cards within each pile.

**When to prefer it:** Use `MZREARRANGE` for "put the rest on the top or bottom in any order" and for bottom-only reorder flows after looking at or revealing cards from deck/temp zone. Do not fake these rearrangement UIs with `MZMultiChoose`.

**Await syntax:** `$var = await $player.Rearrange($param)`

**Parameter format:** `"Top=cardA,cardB;Bottom=cardC"` where each pile is a comma-separated list of card IDs.

**Common patterns:**
- Top-or-bottom reorder: `Top=<ids>;Bottom=`
- Bottom-only reorder: `Top=;Bottom=<ids>`

**Queue pattern:** Use this form from helper functions or custom runtime paths that are already queuing decisions directly.
```php
$param = "Top=;Bottom=" . implode(",", $remaining);
DecisionQueueController::AddDecision($player, "MZREARRANGE", $param, 1, "Put_remaining_on_bottom_of_deck_in_any_order");
DecisionQueueController::AddDecision($player, "CUSTOM", "MyRearrangeApply", 1);
```

**Apply pattern:** Parse `$lastDecision` by `;` and `=`, remove the temp-zone objects, then append `Bottom` pile cards to deck and handle `Top` pile explicitly when that mode is allowed. For bottom-only flows, append `array_merge($piles["Bottom"], $piles["Top"])` so misplaced cards still resolve safely.

### MZSplitAssign — Split Damage / Split Pool Pattern

**Decision type:** `MZSPLITASSIGN` — lets the player distribute a numeric pool (e.g., damage) across multiple card targets on the board.

**Await syntax:** `$var = await $player.MZSplitAssign($targets, $amount, "tooltip")`
- `$targets`: `&`-delimited mzID string (e.g. `"myField-0&theirField-2"`)
- `$amount`: integer pool to distribute
- `"tooltip"` (optional): underscore-separated prompt shown in the UI

**Return value:** Comma-separated `mzID:amount` pairs for non-zero assignments, e.g. `"myField-0:3,theirField-1:2"`. Returns `"-"` if the player had no valid targets.

**Processing the result:** Use `ProcessSplitDamage($player, $source, $assignmentStr)` to deal damage for each assignment. This is a shared helper in `GameLogic.php` that calls `DealDamage()` for each non-zero pair.

**Full example — deal N damage split among all units:**
```php
$allUnits = array_merge(
    ZoneSearch("myField", ["ALLY", "CHAMPION"]),
    ZoneSearch("theirField", ["ALLY", "CHAMPION"])
);
$allUnits = FilterSpellshroudTargets($allUnits);
if(empty($allUnits)) return;
$targetStr = implode("&", $allUnits);
$assignments = await $player.MZSplitAssign($targetStr, $damageAmount, "Split_damage_among_units");
ProcessSplitDamage($player, $mzID, $assignments);
```

**UI behavior:** Inline overlay on each target card with +/− buttons and a counter. A bottom banner shows the remaining pool. The Confirm button is disabled until the entire pool is assigned (all-or-nothing).

**When to use vs. repeated MZCHOOSE:** Use `MZSplitAssign` when the total pool can be split freely. Use repeated `MZCHOOSE` when each "instance" must be a fixed amount (e.g., "for each card banished, choose a unit and deal 2 damage to it").

### TWOSIDEDSLIDER — Numeric Split Between Two Outcomes

**Decision type:** `TWOSIDEDSLIDER` — lets the player choose a numeric split between two outcomes in one compact chooser instead of repeated `YESNO` or one-choice modal prompts.

`TWOSIDEDSLIDER` currently uses direct queue syntax. If new saved ability code needs it often, add first-class await syntax instead of adding more direct queue call sites.

**Queue syntax:** `DecisionQueueController::AddDecision($player, "TWOSIDEDSLIDER", "min|max|leftSpec|rightSpec", 1, tooltip:"Prompt_text");`
- `min`, `max`: inclusive integer range for the left-side count.
- `leftSpec`, `rightSpec`: one of `label~Caption_text`, `card~CARDID`, or `cardlabel~CARDID~Caption_text`.

**Return value:** the chosen left-side count as a string. The right-side count is `max - chosen`.

**When to use:** Prefer this when one effect produces `N` identical replacement events and the player should choose how many become option A versus option B.

### Additional Activation Costs (optional reserve at activation time)

Some cards say "As an additional cost to activate this card, you may pay (N)." These costs are declared and paid **at activation time** (Grand Archive rules step 1.3), before the opponent gets priority, not at resolution time inside the ability.

**Framework:** `$additionalActivationCosts` in `GameLogic.php` — a registry mapping `cardID` to cost config.

**How it works:**
1. `DoActivateCard()` checks `$additionalActivationCosts[$cardID]` after computing the base reserve cost.
2. If the entry exists and its `condition` callback returns true and the player has enough cards in hand, a YESNO decision is queued.
3. The `DeclareAdditionalCost` DQ handler processes the answer, stores `"additionalCostPaid"` as `"YES"` or `"NO"`, then queues all reserve payments (base + extra if YES) followed by `EffectStackOpportunity`.
4. At resolution time, ability code reads `DecisionQueueController::GetVariable("additionalCostPaid")` to branch.

**To register a new additional cost:**
```php
$additionalActivationCosts["<cardID>"] = [
    'prompt'       => 'Pay_N_extra_for_effect?',   // YesNo prompt text
    'extraReserve' => 2,                            // extra hand→memory payments
    'condition'    => function($player) {            // optional — when to offer
        return !empty(ZoneSearch("myGraveyard", cardElements: ["CRUX"]));
    }
];
```

**In the ability code** (saved via `save_card_abilities`), check the result:
```php
$additionalCostPaid = DecisionQueueController::GetVariable("additionalCostPaid");
if($additionalCostPaid === "YES") {
    // additional-cost-specific effects (e.g. banish self, recover card)
}
```

**Important:** The cost payment (hand→memory) is handled automatically by the framework. The ability code should only implement the *effects* that happen when the additional cost was paid — do NOT re-implement the reserve payment inside ability code.

### Step 7: Add any new helper functions
If the card requires a new helper function (like `RecoverChampion`), add it to the appropriate Custom/*.php file. Group by theme:
- Combat-related → `CombatLogic.php`
- Materialize-related → `MaterializeLogic.php`
- General game logic → `GameLogic.php`
- Card-specific complex logic → `CardLogic.php`

### Step 8: Prefer Framework Hooks Before Manual Switchboards

Before editing large manual switchboards in `GameLogic.php`, check whether the behavior belongs in one of the generated framework hooks instead:
- **Restrictions / legality** — generated macro prereqs such as `CanActivateAbility` and other prereq arrays for card-local restrictions.
- **Scalar cost changes** — `MemoryCostModifier`, `ReserveCostModifier`, `PlayCostModifier`, `ActivationCostModifier`.
- **One-shot consumable modifiers** — return `['delta' => ..., 'consume' => true]` so the framework can remove the source effect after a successful application.

Manual `GameLogic.php` edits are still appropriate for:
- Cross-card framework rules that affect many cards at once.
- Costs that are not simple scalar modifiers, such as `REST`, sacrifice, banish, discard, reveal, or multi-step alternative payment flows.
- Cases where the current generated macro surface cannot represent the rule without awkward or fragile workarounds.


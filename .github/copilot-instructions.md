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

---

## Card Ability Implementation Workflow (for AI agents)

This is the canonical workflow for implementing card abilities. Follow these steps in order.

### CRITICAL RULES
- **NEVER manually edit `<RootName>/GeneratedCode/GeneratedMacroCode.php`** — this file is auto-generated from the database. The MCP `save_card_abilities` tool saves to the DB and triggers the code generator automatically. Any manual edits will be overwritten.
- **NEVER manually edit `<RootName>/GeneratedCode/GeneratedMacroCount.js`** — same reason.
- Helper functions that don't already exist should be added to the appropriate file in `<RootName>/Custom/` based on theme (e.g. combat helpers in CombatLogic.php, general helpers in GameLogic.php).
- **Per-card stat modifiers** — continuous effects that raise/lower a single card's POWER, HP, or Level should be implemented by:
  1. Using `AddTurnEffect($mzCard, $effectID)` to tag the card with the effect when the ability resolves.
  2. Adding a `case "$effectID":` to the relevant `ObjectCurrentPower`, `ObjectCurrentHP`, or `ObjectCurrentLevel` switch in `GameLogic.php`.
  The effect is automatically cleared at end of turn by `ExpireEffects`.
- **Field-presence passives** (e.g. "champion gets +1 level while you control X") belong in `ObjectCurrentLevel`/`ObjectCurrentPower`/`ObjectCurrentHP`. Use the established pattern: loop the field once, switch on card ID, deduplicate with `$appliedPassives[$fID]` to prevent duplicate copies from stacking inadvertently.

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
- `ZoneSearch(zoneName, cardTypes, floatingMemoryOnly, cardElements, cardSubtypes)` — search a zone by type/element/subtype. All params after `zoneName` are optional and can be passed by name (e.g. `ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"])`).
- `DealChampionDamage(player, amount)` — add damage to the player's champion on the field
- `RecoverChampion(player, amount)` — remove damage from the player's champion on the field
- `DealDamage(player, source, target, amount)` — deal damage from a source to a target (via macro)
- `Draw(player, amount)` — draw cards (macro call, preferred over DoDrawCard)
- `MZMove(player, mzCard, destZone)` — move a card between zones
- `IsClassBonusActive(player, classes)` — check if champion's class matches
- `AddGlobalEffects(player, effectID)` — add a global effect that affects all matching cards this turn (via `doesGlobalEffectApply` / `CardCurrentEffects`)
- `AddTurnEffect(mzCard, effectID)` — add a per-card turn effect to a specific field card's `TurnEffects` array. Use this when the effect targets a single card (e.g. "+2 POWER until end of turn on a specific ally") rather than `AddGlobalEffects` which broadcasts to all matching cards. The effect ID is conventionally the source card's ID. It is cleared at end of turn by `ExpireEffects`.
- `ExhaustCard(player, mzID)` — exhaust a card
- `WakeupCard(player, mzID)` — ready a card
- `ObjectCurrentPower(obj)`, `ObjectCurrentHP(obj)` — get computed stats
- `CardElement(cardID)`, `CardType(cardID)`, `CardClasses(cardID)` — card dictionary lookups

### Step 4: Study existing examples
Call the MCP `get_implemented_examples` tool with the relevant macro name (e.g. "CardActivated", "Enter") to see how similar abilities are coded. This shows you the exact pattern to follow.

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
  - `$var = await $player.YesNo("prompt")` — yes/no choice
  - `await FunctionName($player, $args)` — call a function that queues decisions
  - await does not currently support being inside loops or conditionals, but you can queue custom functions that themselves queue decisions to achieve multi-step flows.

### Step 6: Save via MCP
Call `save_card_abilities` with the card ID, macro name, and ability code. The MCP server saves to the database AND automatically runs the code generator, so `GeneratedMacroCode.php` is updated.

**Important:** `save_card_abilities` auto-generates the macro code (e.g., `cardActivatedAbilities["cardID:0"] = function($player) { ... }`), but you remain responsible for any custom GameLogic.php edits:
- If using `AddGlobalEffects(...)`, you must manually add a filter in `$doesGlobalEffectApply[$cardID]` if the effect applies conditionally (e.g., only to allies).
- If adding per-turn stat modifiers via `AddGlobalEffects`, manually add a `case "$cardID":` to `ObjectCurrentPower`, `ObjectCurrentHP`, or `ObjectCurrentLevel` to declare the modifier.
- If registering custom DQ handlers in the ability code (e.g., to handle multi-step flows), ensure those handlers exist in `Custom/GameLogic.php` (the generator will wrap them but won't invent new handlers).

### Multi-Step Ability Patterns (YesNo, Target Selection)

**Pattern:** When an ability requires player input (YesNo, card choice), write ability code that *queues* decisions rather than awaiting inline. The generator will compile these into custom DQ handlers.

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
1. A decision queue entry (YESNO, MZCHOOSE, or MZMAYCHOOSE)
2. A custom DQ handler that processes the player's response

The `lastDecision` parameter in the handler receives the player's choice ("YES"/"NO" for YesNo, or the mzID for card choices).

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


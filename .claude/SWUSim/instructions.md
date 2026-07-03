# SWUSim — Repo Orientation

Lean orientation + pointers. The **card-ability workflow, DSL cheat-sheets, helper tables, and
where-to-add-each-ability-type are NOT here** — they live in the **`swusim-implement-card` skill**
(the source of truth; far more detailed and kept current). Engine architecture, conventions, and
pitfalls live in project memory (`swusim-project.md`, `swusim-project-pitfalls.md`,
`reference-swusim-generated-engine-files`). This file keeps only the file index, the Decision-Queue
plumbing that isn't documented elsewhere, a compact zone-schema, and a few load-bearing rules.

## Quick links (files to know)
- `Core/DecisionQueueController.php` — executes decision-queue entries; invokes custom handlers.
- `Core/UILibraries<YYYYMMDD>.js` — client render pipeline (`PopulateZone`, `createCardHTML`, overlay/counter injection). ⚠ **cache-busted / timestamped** — the datestamp changes (the `bump-uilibraries-cache` skill renames it); glob for the newest, don't hardcode. (`Core/UILibraries.php` is a separate server-side file.)
- `zzCardCodeGenerator.php` — schema→code generator (card dictionaries + client `GeneratedUI_<timestamp>.js`).
- `Schemas/SWUSim/GameSchema.txt` — zone / macro / overlay / counter / display definitions.
- `SWUSim/GeneratedCode/GeneratedCardDictionaries.php` — generated card data (17 `SET_NNN` arrays).
- `SWUSim/{ZoneAccessors,ZoneClasses,GamestateParser,GetNextTurn,NextTurnRender,InitialLayout}.php` — generated runtime helpers.
- `SWUSim/TurnController.php` — turn/phase state machine.
- `SWUSim/GeneratedCode/{GeneratedMacroCode,GeneratedKeywordCode,GeneratedAbilityStubs}.php` — auto-generated.
- `SWUSim/Custom/{GameLogic,CombatLogic,CardDQHandlers,CardLogic,LeaderAbilities,BaseAbilities,KeywordEffects,CardEffects}.php` — hand-written card logic + shared helpers.

## Load-bearing rules
- **NEVER hand-edit generated files** (`GeneratedMacroCode.php`, `GeneratedKeywordCode.php`, `GeneratedCardDictionaries.php`, `GamestateParser.php`, `GetNextTurn.php`, `ZoneAccessors.php`, `ZoneClasses.php`, `GeneratedAbilityStubs.php`) — a regen wipes hand-edits with no git trace. Edit the **generator** (gate by `$rootName`) and regenerate. See `reference-swusim-generated-engine-files` memory.
- **CardID format `SET_NNN`** (`{2–5 upper}_{3-digit}`, e.g. `SOR_014`); tokens `SET_T##` (e.g. `SOR_T02` Shield). Primary key across zones, DQ vars, mzIDs, dictionaries, deck JSON.

## Zone schema (compact — full field lists in the `swusim-implement-card` skill / `swusim-project.md`)
- **GroundArena / SpaceArena** (units): `CardID`, `Status` (**1=ready, 0=exhausted** — there is NO "2"; units enter play exhausted), `Owner`, `Controller`, `Damage`, `TurnEffects[]`, `Subcards[]` (attached upgrades + face-down captives), `UniqueID` (equals `Leader.DeployedUniqueID` for a deployed leader). No serialized `Counters` field.
- **Leader** — stays in its zone; `Deployed` / `DeployedUniqueID` link to its arena unit; `EpicActionUsed` (once-per-game). **Base** — `Damage ≥ base HP` ⇒ that player loses. **Resources** — `Status` (1/0), `Owner`/`Controller` (differ when stolen). **Hand / Deck** — `CardID` only. **Discard** — `CardID`, `From` (`HAND`/`DECK`/`PLAY`/`RESOURCES`), `Turn`, `Modifier`.
- **Discard `Modifier` family** (cleared at RegroupPhaseStart): `TPF` = owner may play it free this phase · `TPP` = owner at cost · `OTPF` = opponent free · `OTPP` = opponent at cost · `OTPN` = opponent at cost ignoring the aspect penalty.
- **GlobalEffects** — per-player space-free string flags for lasting game state.

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
Player-facing loop: **Action Phase** (players alternate — play a card / attack / activate an ability / take the initiative / pass; two consecutive passes end it) → **Regroup**. Full TurnSchema: `APS → MAIN → RGS → DRAW → RES → READY`.
Turn helpers in `GameLogic.php`: `SWUSwapTurnPlayer`, `SWUPassAction`, `SWUTakeInitiative`, `SWUAfterAction`, `SWUExhaustResources`, `DrawPhase`/`ReadyPhase`/`WakeUpPhase`. ⚠ `PlayerHasIniative` — the codebase spells it `Iniative` (typo, load-bearing).

## Everything else → sources of truth
- **Card-ability implementation** (full workflow, DSL `GIVEN/WHEN/EXPECT`, `CommonSetup` codes, per-ability-type "where to add it" table, MZChoose / MZMultiChoose / MZSplitAssign patterns, fixtures, gotchas): **`swusim-implement-card` skill**.
- **Zone schema (full field lists) + card types**: `swusim-implement-card` skill (Step 2) + `swusim-project.md`.
- **Engine architecture, conventions, combat / stat / zone gotchas**: `swusim-project.md` + `swusim-project-pitfalls.md`.
- **Generator internals & regen hazards** (SWUSim-only null/empty filtering, ability-stub detection rules, per-viewer transport pieces): `reference-swusim-generated-engine-files` memory.

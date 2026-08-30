# TCGEngine Instructions

## What this repo is

A multi-app PHP/JS monorepo for trading card game simulators. Each game lives in its own `<Root>/` directory (SWUSim, AzukiSim, GrandArchiveSim, HellbreakSim, FaBSim, etc.). Shared engine logic lives in `Core/`. Shared UI in `SharedUI/`. Per-game shared card data (SWU-specific helpers, deck validation, etc.) in `AppCore/`.

Schema-to-codegen is the core workflow: `Schemas/<Root>/GameSchema.txt` → `zzGameCodeGenerator.php` → generated PHP + client JS. Most per-app files are generated and gitignored.

## Critical: never edit generated files

These files are produced by `zzGameCodeGenerator.php` and are **gitignored** — manual edits are overwritten on regen:
- `<Root>/GeneratedCode/GeneratedMacroCode.php`
- `<Root>/GeneratedCode/GeneratedMacroCount.js`
- `<Root>/GeneratedUI_*.js` (timestamped, one per regen)
- `<Root>/GetNextTurn.php`, `InitialLayout.php`, `NextTurnRender.php`
- `<Root>/GamestateParser.php`, `ZoneAccessors.php`, `ZoneClasses.php`, `TurnStates.php`, `TurnController.php`

If you need to change behavior, edit the schema (`Schemas/<Root>/GameSchema.txt`) or the generator (`zzGameCodeGenerator.php`), then regenerate.

## After clone / fresh setup

```bash
# SharedUI site pointers (Header.php, MenuBar.php, etc.) are generated from templates
php SharedUI/Render/GenerateSites.php

# MCP server (for card editor AI workflow)
cd McpServer && npm install && npm run build
```

## Docker dev environment

`docker-compose.yml` defines per-game stacks. Each gets its own MySQL database, Redis, and PHP app server:

| Game | Web port | phpMyAdmin |
|---|---|---|
| SWUDeck (swustats) | 3100 | 5101 |
| GrandArchiveSim | 3200 | 5102 |
| AzukiSim | 3300 | 5103 |
| SWUSim | 3400 | 5104 |
| HellbreakSim | 3500 | 5105 |

MySQL creds: root/secret. Databases named after the game (e.g. `swusim`).

## Running the code generator

```bash
# CLI (from repo root, requires PHP with webserver or direct CLI execution):
php zzGameCodeGenerator.php rootName=SWUSim

# Or via the web UI:
# Open zzCodeGeneratorMain.php in your dev host and pick the root
```

After regenerating, hard-refresh the browser (the timestamped `GeneratedUI_*.js` URL changes).

## Testing

```bash
# SWUSim schema-based unit tests
php zzRunSWUSimTests.php

# Integration tests (fixture-based, snapshot comparison)
php DevTools/RunIntegrationTests.php --root=SWUSim
php DevTools/RunIntegrationTests.php --root=AzukiSim --test=<slug> --verbose

# Node tests (tiny suite, Core JS helpers)
node --test DevTools/tests/*.test.mjs
```

## Card implementation workflow (MCP card editor)

Prefer the `tcgengine-card-editor` MCP workflow for card work. Standard sequence:
1. `get_card_info` — card metadata
2. `get_zone_schema` — what properties exist per zone
3. `get_helper_functions` — discover existing helpers
4. `get_implemented_examples` — patterns for similar macros
5. `save_card_abilities` — saves to DB + triggers codegen automatically

Full details and examples: `.github/copilot-instructions.md` (canonical for all codegen, await, and Decision Queue patterns).

## Await / codegen constraints

Treat `.github/copilot-instructions.md` as canonical. Key rules:
- `await` is supported inside `if`/`else`, `for`, and `while` blocks.
- Recompute live zone objects after an `await`; only rely on serializable scalar/array locals crossing the await frame.
- Precompute chooser strings before `await $player.MZChoose(...)`, `await $player.MZMultiChoose(...)`, or similar calls. **No function calls as await parameters.**

## UI interaction chooser matrix

Use the "Choosing the Right UI Interaction" table in `.github/copilot-instructions.md`. Key points:
- `MZChoose`/`MZMayChoose`, `MZMultiChoose`, `Modal`, `NumberChoose`, `MZSplitAssign`, `Rearrange`, `NameCard` — the modern set.
- `OPTIONCHOOSE`/`ICONCHOICE` — **deprecated** for new card-authoring; prefer `Modal`.
- `TWOSIDEDSLIDER` — specialized numeric split; param format `"min|max|leftSpec|rightSpec"`.
- `MZMultiChoose`: one popup for selecting several cards from one candidate set; returns `&`-delimited mzIDs.

## Where to add new logic

- **New helpers** → `<Root>/Custom/` by theme:
  - Combat → `CombatLogic.php`
  - Materialize → `MaterializeLogic.php`
  - General runtime/game → `GameLogic.php`
  - Card-specific complex → `CardLogic.php`
- **Custom Decision Queue handlers** → register in `<Root>/Custom/GameLogic.php` on `$customDQHandlers`.
- **Additional activation costs** → register in `<Root>/Custom/GameLogic.php` on `$additionalActivationCosts`.

## Prefer framework hooks over manual code

- **Restrictions/legality** → generated macro prereqs (`CanActivateAbility`, etc.) before manual guards.
- **Scalar cost changes** → `MemoryCostModifier`, `ReserveCostModifier`, `PlayCostModifier`, `ActivationCostModifier` before manual cost math.
- **Per-turn stat changes** → `AddTurnEffect(...)` + `ObjectCurrentPower`/`ObjectCurrentHP`/`ObjectCurrentLevel` switch case.
- **Persistent overrides** → `ApplyPersistentOverride(...)`. Temporary suppression → `AddTurnEffect($mzCard, 'NO_ABILITIES')`.
- **Field-presence passives** → `ObjectCurrentPower`/`ObjectCurrentHP`/`ObjectCurrentLevel` with `$appliedPassives` deduping.
- **Runtime field queries** → always use `EffectiveCardType()`, `EffectiveCardSubtypes()`, `EffectiveCardClasses()`, `EffectiveCardElement()` (not raw `CardType($obj->CardID)` etc.).

## Decision Queue custom handlers

- Keep them short, non-interactive, tolerant of malformed parameters.
- Card-local interactive flows → use inline `await` and supported decision types, not custom DQ handlers.
- Handler signature: `$customDQHandlers[$name]($player, $parts, $lastDecision)`.

## Cross-cutting gotchas

- **WebP on prod**: prod LAMPP PHP GD is compiled without WebP support. Read WebP via Imagick (or `SWUDeck/lib/CardImageLoader.php`); hand GD a PNG blob. `imagecreatefromwebp` will fatal on prod even if it works locally.
- **UI changes**: must work in Chromium, Firefox, AND Safari. Layout behavior diverges (e.g. `height:100%` in flex containers).
- **Public API contracts**: `Stats/APIs.php` and public `APIs/` endpoints are contracts — changes must be additive and backward-compatible.
- **Shared SWU card art**: both SWU apps read `AppCore/SWU/Images/` (shared corpus). SWUDeck/WebpImages/ and SWUSim/WebpImages/ are gitignored except for tracked mock preview art.
- **ElevenLabs key**: use `ELEVENLABS_API_KEY` env var for sound assets; never commit or print the key.

## Conflict resolution

If this file and `.github/copilot-instructions.md` conflict, follow the more specific rule for the files you are editing and prefer the detailed engine guidance in `.github/copilot-instructions.md`.

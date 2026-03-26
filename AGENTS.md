# TCGEngine Instructions

Read `.github/copilot-instructions.md` before making substantive engine changes. It is the detailed source of truth for this repository's engine workflow, generator behavior, Decision Queue patterns, and card-implementation rules.

Use this file as the compact default guidance:

- Prefer the `tcgengine-card-editor` MCP workflow for card work. Standard sequence:
  - `get_card_info`
  - `get_zone_schema`
  - `get_helper_functions`
  - `get_implemented_examples`
  - `save_card_abilities`
- Treat `.github/copilot-instructions.md` as canonical for await/codegen constraints. In particular:
  - Do not put `await` inside conditionals or loops.
  - Do not rely on pre-`await` locals after an `await`; recompute what you need.
  - Precompute chooser strings before `await $player.MZChoose(...)` / similar calls.
- Do not manually edit generated files such as `<RootName>/GeneratedCode/GeneratedMacroCode.php`, `GeneratedMacroCount.js`, or generated `GeneratedUI_*.js` outputs unless the task is specifically about the generator.
- For card implementations, prefer the MCP card editor workflow: inspect card info, inspect schema/helpers/examples, save abilities through MCP, and let the generator update derived macro code.
- Add new non-generated helper logic under `<RootName>/Custom/` in the most appropriate file instead of patching generated code.
- When working in Grand Archive, use established helpers and effective runtime wrappers such as `EffectiveCardType`, `EffectiveCardSubtypes`, `EffectiveCardClasses`, and `EffectiveCardElement` rather than raw card-dictionary lookups on field objects.
- For per-turn single-card stat changes, use `AddTurnEffect(...)` plus the corresponding `ObjectCurrentPower`, `ObjectCurrentHP`, or `ObjectCurrentLevel` switch case in `GameLogic.php`.
- For persistent field-object overrides, use `ApplyPersistentOverride(...)`; for temporary suppression, use `AddTurnEffect($mzCard, 'NO_ABILITIES')`.
- Field-presence passives belong in `ObjectCurrentPower`, `ObjectCurrentHP`, or `ObjectCurrentLevel`, using the established passive-deduping pattern.
- Keep Decision Queue custom handlers short, non-interactive, and tolerant of malformed parameters. Interactive flows should use the supported decision types and established `await` patterns described in `.github/copilot-instructions.md`.
- Register game-specific custom Decision Queue handlers and additional activation costs in `<RootName>/Custom/GameLogic.php`, not in generated code.
- Use the appropriate custom file for new helpers:
  - combat helpers -> `CombatLogic.php`
  - materialize helpers -> `MaterializeLogic.php`
  - general runtime/game helpers -> `GameLogic.php`
- If you change schema or generator behavior, regenerate outputs and account for the timestamped `GeneratedUI_*.js` file behavior noted in `.github/copilot-instructions.md`.

Priority note:

- If this file and `.github/copilot-instructions.md` ever conflict, follow the more specific rule for the files you are editing and prefer the detailed engine guidance in `.github/copilot-instructions.md`.

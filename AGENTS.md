# TCGEngine Instructions

Read `.github/copilot-instructions.md` before making substantive engine changes. It is the detailed source of truth for this repository's engine workflow and card-implementation rules.

Use this file as the compact default guidance:

- Do not manually edit generated files such as `<RootName>/GeneratedCode/GeneratedMacroCode.php`, `GeneratedMacroCount.js`, or generated `GeneratedUI_*.js` outputs unless the task is specifically about the generator.
- For card implementations, prefer the MCP card editor workflow: inspect card info, inspect schema/helpers/examples, save abilities through MCP, and let the generator update derived macro code.
- Add new non-generated helper logic under `<RootName>/Custom/` in the most appropriate file instead of patching generated code.
- When working in Grand Archive, use established helpers and effective runtime wrappers such as `EffectiveCardType`, `EffectiveCardSubtypes`, `EffectiveCardClasses`, and `EffectiveCardElement` rather than raw card-dictionary lookups on field objects.
- For per-turn single-card stat changes, use `AddTurnEffect(...)` plus the corresponding `ObjectCurrentPower`, `ObjectCurrentHP`, or `ObjectCurrentLevel` switch case in `GameLogic.php`.
- For persistent field-object overrides, use `ApplyPersistentOverride(...)`; for temporary suppression, use `AddTurnEffect($mzCard, 'NO_ABILITIES')`.
- Keep Decision Queue custom handlers short, non-interactive, and tolerant of malformed parameters. Interactive flows should use the supported decision types and established `await` patterns described in `.github/copilot-instructions.md`.
- If you change schema or generator behavior, regenerate outputs and account for the timestamped `GeneratedUI_*.js` file behavior noted in `.github/copilot-instructions.md`.

Priority note:

- If this file and `.github/copilot-instructions.md` ever conflict, follow the more specific rule for the files you are editing and prefer the detailed engine guidance in `.github/copilot-instructions.md`.

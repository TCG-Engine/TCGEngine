---
name: swusim-session-start
description: Use when starting any SWUSim coding session, before touching any SWUSim files. Orients the agent to project state, architecture decisions, and next tasks.
---

# SWUSim Session Start

## Step 1 — Load Full Project Context

Read both files before doing anything else:

```
/Users/mariotorresjr/.claude/projects/-Users-mariotorresjr-Documents-GitHub-Karabast-SWU-SWUStats/memory/swusim-project.md
.claude/SWUSim/instructions.md
```

The memory file is the source of truth for: zone schema, turn model, file status (done vs not started), architecture decisions, CardID format, and reference file locations. The instructions file contains session-specific guidance and requirements that take precedence.

## Step 2 — What's Next (Priority Order)

From the memory file as of 2026-06-04:

| # | Task | File | Notes |
|---|------|------|-------|
| 1 | Run zone/accessor generator | `SWUSim/ZoneClasses.php`, `SWUSim/ZoneAccessors.php` | `zzGameCodeGenerator.php?rootName=SWUSim` — quick win, needed by GameLogic |
| 2 | Run card dictionary generator | `SWUSim/GeneratedCode/GeneratedCardDictionaries.php` | `zzCardCodeGenerator.php?rootName=SWUSim&withPreview=1` — quick win |
| 3 | Write GameLogic.php | `SWUSim/Custom/GameLogic.php` | Biggest piece — phase handlers, resource payment, play card, keywords, tokens |
| 4 | Write CombatLogic.php | `SWUSim/Custom/CombatLogic.php` | Attacker exhausts, target selection, shield intercept, simultaneous damage |
| 5 | Write CustomInput.php | `SWUSim/Custom/CustomInput.php` | SWU input routing: pass, attack, play card, resource cost payment |

## Step 3 — Architecture Rules (Never Forget)

- **GA is the template.** Every SWU file mirrors GA patterns but replaces GA mechanics. Before writing anything, read the equivalent GA file first.
- **Leader never leaves its zone.** When deployed, a `GroundArena` entry is created; `Leader.DeployedUniqueID` holds its `UniqueID`. On defeat, leader returns exhausted; `Deployed=false`.
- **`TurnPlayer` swaps in game code**, not by the engine. Consecutive-pass tracking lives in `GameLogic.php`. Second consecutive pass calls `AdvanceAndExecute("PASS")`.
- **Boolean zone properties** — `GameLogic.php` must use string `'true'`/`'false'` when writing zone fields (`EpicActionUsed`, `Ready`, `Deployed`). The generator now handles deserialization, but writes must be explicit strings.
- **All CardIDs are `SET_NNN`** — `{set abbreviation}_{card number zero-padded to 3 digits}`. Never use raw numbers or UUIDs as zone keys.
- **Arenas are per-player zones.** Use ZoneSearch cross-player lookups (same pattern as GA's `myField`/`theirField`).

## Step 4 — Key Reference Files

| File | Purpose |
|------|---------|
| `Schemas/SWUSim/GameSchema.txt` | Zone definitions, all macros |
| `Schemas/SWUSim/TurnSchema.txt` | Phase sequence, PerAction turn model |
| `.claude/SWUSim/refs/comprehensive-rules.md` | CR v7.0 full rules |
| `.claude/SWUSim/refs/game-refs.md` | Condensed rules + mechanics notes |
| `APIs/Lobbies/Classes/Player.php` | Engine entry point — understand before touching DQ handlers |

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Starting GameLogic without generated zone classes | Run generator first (task 1) |
| Writing combat before basic phase loop works | GameLogic first, CombatLogic after |
| Using `true`/`false` PHP booleans in zone writes | Use string `'true'`/`'false'` |
| Inventing a new zone or macro | Check GameSchema.txt — it's probably already defined |
| Forgetting Leader's `EpicActionUsed` persists | It's a once-per-game flag; do NOT reset during ReadyPhase |
| Patching generated files without updating their source | **Any change to a generated file must also be made in its generator.** The three generators for SWUSim are: `zzGameCodeGenerator.php` (ZoneClasses, ZoneAccessors, GamestateParser, GeneratedMacroCode, GeneratedKeywordCode, GeneratedAbilityStubs), `zzCardCodeGenerator.php` (GeneratedCardDictionaries), and `Data/ProcessKeywordsSWU` (keyword logic). Patch the generator AND the file so re-runs don't revert your work. For `GamestateParser.php` specifically, includes come from `ServerInclude:` lines in `Schemas/SWUSim/GameSchema.txt`. |

## Debugging Habits

- **Naming conventions are archaeology.** Generated code follows strict naming patterns (`$g<ZoneName>` for global zone accessors, `$p1<ZoneName>`/`$p2<ZoneName>` for player zones, `window.<ZoneName>Data` on the client). A variable matching one of these patterns with no corresponding schema zone (e.g. `$gGameLog` before the GameLog zone existed) reveals an intended-but-never-built feature — search for both the producer and the consumer before concluding how something "works." A consumer with no producer is a half-built feature or an erased hand-patch, not working code.
- **If Bash output is lost to a full temp filesystem (ENOSPC),** redirect command output to a file inside the workspace (e.g. `> .claude/tmp/tmp_out.txt`) and read/cat that file instead. Delete the scratch file when done.

## Session Rules

- **Never commit.** The user commits manually. Do not run `git commit` or `git add` under any circumstances — not even as part of a plan step or subagent task.
- **Always ask before updating confirmed unit tests.** If a task involves changing existing unit tests, ask the user for confirmation before making any changes. If they say no, find an alternative solution that doesn't involve modifying existing tests.

---
name: swusim-debug-game
description: Use when debugging a specific SWUSim in-game bug or unexpected behavior tied to a real game (e.g. "game 2619 auto-resolves this leader ability wrong", "why did this prompt appear", "this ability should have auto-passed", "the shield popped when it shouldn't"). For reproducing and root-cause-fixing a live-game defect from its saved gamestate.
---

# SWUSim Debug Game

Reproduce a live SWUSim game bug from its saved gamestate, root-cause it, and land a TDD'd fix — the disciplined loop, not a guess-and-patch.

**This skill drives `superpowers:systematic-debugging`.** It supplies the SWUSim-specific mechanics (how to snapshot a game, where tests live, how to run them reliably); systematic-debugging supplies the discipline (root cause before fix, one hypothesis at a time).

Run everything from the repo root: `/Users/mariotorresjr/Documents/GitHub/Karabast-SWU/SWUStats`.

---

## Step 0 — Prerequisite gate (STOP if it fails)

This skill **requires** `superpowers:systematic-debugging`. Before anything else, confirm it is available (it appears in your skills list / you can invoke it via the Skill tool).

**If it is NOT installed/enabled → STOP.** Do not snapshot, investigate, or fix. Emit exactly this warning and end:

> ⚠ `swusim-debug-game` requires the **superpowers systematic-debugging** skill, which isn't installed/enabled. Enable Superpowers and re-run.

If it IS available, invoke `superpowers:systematic-debugging` now and follow its four-phase process for the rest of this skill.

---

## Step 1 — Get the game id

If the user named a game (id like `2619`), use it. **If they did not, ASK** — don't guess or pick one:

> Which game should I snapshot? (the game id/name under `SWUSim/Games/<id>/`)

Confirm it exists before snapshotting:

```bash
docker exec -w /var/www/html/TCGEngine swustats-swusim-web-server-1 \
  sh -c 'test -f ./SWUSim/Games/<id>/Gamestate.txt && echo EXISTS || echo MISSING'
```

---

## Step 2 — Snapshot the game with the dev tool

`DevTools/swusim-snapshot-test.php` reconstructs the exact board from the live `Gamestate.txt` into a GIVEN-only DSL `.md`. Write it under `SWUSim/Tests/Snapshots/`:

```bash
docker exec -w /var/www/html/TCGEngine swustats-swusim-web-server-1 \
  php -d xdebug.mode=off DevTools/swusim-snapshot-test.php <id> \
  > SWUSim/Tests/Snapshots/<id>.md
```

Read the result. It reports both players' leaders/bases/arenas/hands/decks and prints `# ⚠` warnings for anything a static snapshot can't carry (a non-MAIN live phase, an in-flight pending decision — the board is captured, the queued decision is NOT). Note those warnings; they shape the repro.

The Xdebug "Could not connect to debugging client" line on stderr is harmless — the snapshot still writes.

---

## Step 3 — Get the bug repro from the user

Ask the user for the reproduction and expected-vs-actual:

> What's the repro? (the action to take — e.g. "click P2 leader" — plus what you saw vs. what you expected.)

Map their plain-language action to the board you just snapshotted (which player is P1/P2, which unit is the leader, what's in each arena).

---

## Step 4 — Investigate to root cause (94% confidence gate)

Follow systematic-debugging Phase 1–2: read the card text from the generated dictionaries, find the handler, trace the data flow to the origin. **Do not propose a fix until you understand WHY.**

**Confidence gate:** if your confidence in *both understanding and fixing* the bug is **below 94%** — the repro is ambiguous, expected behavior is unclear, or multiple root causes are plausible — **ASK a clarifying question** before continuing. Cheap places this bites: which unit/player the action targets, whether a "may" was expected to auto-pass, what the correct game-rules outcome is.

Useful lookups:
```bash
# Card title / text / type for a CardID (from the generated dictionary):
grep -o '"SEC_069":"[^"]*"' SWUSim/GeneratedCode/GeneratedCardDictionaries_*.js
# Where a card's ability handler lives:
grep -rn "SEC_069" SWUSim/Custom/*.php SWUSim/GeneratedCode/GeneratedAbilityStubs.php
```

Handlers live in `SWUSim/Custom/*.php` (hand-written). **Never hand-edit `SWUSim/GeneratedCode/*` or the parser/accessors — they're regenerated** (see the "generated engine files" project memory). Prefer the existing convention: grep for a comparable card and match how it does the same thing.

---

## Step 5 — TDD the fix (RED → GREEN, then full regression)

Use `superpowers:test-driven-development`. Tests are GIVEN/WHEN/EXPECT `.md` schema files under `SWUSim/Tests/Cases/<set>/` (set = lowercase, e.g. `sec/`). Drop a `.md` anywhere under `Tests/Cases/` and it auto-registers.

**RED — write the failing test first.** Recreate the minimal board that triggers the bug (the snapshot is your reference; strip it to the essentials). Assert the *correct* behavior. Run ONLY that file and watch it fail:

```bash
docker exec -w /var/www/html/TCGEngine swustats-swusim-web-server-1 \
  php -d xdebug.mode=off .claude/skills/swusim-debug-game/scripts/run-schema-tests.php \
  SWUSim/Tests/Cases/<set>/<YourTest>.md
```

A red result must fail for the RIGHT reason (the assertion tied to the bug), not a setup typo — read the message.

**GREEN — fix the root cause** in the `Custom/` handler, then re-run the same file until it passes.

**Regression — run the FULL suite** and confirm no other test broke (and that your new one is counted):

```bash
docker exec -w /var/www/html/TCGEngine swustats-swusim-web-server-1 \
  php -d xdebug.mode=off .claude/skills/swusim-debug-game/scripts/run-schema-tests.php \
  2>/dev/null | sed 's/<[^>]*>//g' | grep -E 'passed|✗'
```

Green = `N passed  0 failed`. Any `✗` line names the broken test — fix or reconcile before claiming done.

---

## Test-runner reference (why the helper exists — don't skip it)

**Use `.claude/skills/swusim-debug-game/scripts/run-schema-tests.php`, run via the container's PHP.** It clones `zzRegressionSWUSim.php`'s exact environment. Two naïve alternatives lie:

| Naïve run | What goes wrong |
|---|---|
| `curl …/zzRegressionSWUSim.php` (full suite) | ~60s gateway timeout → **HTTP 500, empty body**. Looks like a crash; it's just the web limit. The suite genuinely takes longer than the gateway allows under load. |
| `zzRunSWUSimTests.php` (container CLI) | Omits the animation stubs → every combat/damage test fatals → **~1000 phantom failures**. |
| `curl …?filter=SEC069` | Returns **0 passed 0 failed**. The `filter` matches `*Test.php` filenames; ALL schema tests live in one `SchemaBasedTest.php`, so a card-id filter excludes it entirely. You cannot isolate a schema test by card id via the web runner — use the helper's targeted mode instead. |

The helper avoids all three: stubs defined (no phantom fails), CLI (no gateway timeout), and a targeted mode that takes explicit file paths.
- **Targeted mode** (`run-schema-tests.php <file.md> …`): fast RED/GREEN on just your test; exit code 0/1.
- **Full mode** (no args): authoritative regression; renders HTML → pipe through `sed 's/<[^>]*>//g'`.

Host PHP is off-limits (per the test-runner memory) — always the container's PHP. `docker exec … php` is the container's PHP, which is correct.

Handy assertions seen in cases: `P1NODECISION` (no pending decision — proves an auto-pass), `P1GROUNDARENAUNIT:0:EXHAUSTED` / `:READY`, `P1GROUNDARENAUNIT:0:UPGRADECOUNT:1`, `P2BASEDMG:4`. Browse `SWUSim/Tests/Cases/` for the vocabulary and `SWUSim/Tests/_TEMPLATE.md` for the shape.

**Assert the offered pool/amount, not just the applied result.** For "distribute up to N" / split effects (Advantage, indirect), the harness applies the answer's counts *without capping to the offered pool* (the live UI caps; the harness doesn't) — so `ADVANTAGECOUNT`/`BASEDMG` on the receiver can't catch a wrong *pool*. The pool is embedded in the decision prompt; assert it with **`P<n>DECISIONTOOLTIP:<exact>`** (e.g. `Distribute_up_to_6_Advantage_among_friendly_units`). Leave the decision pending (don't answer it) so it's still there to read.

**Driving a non-active player's queued trigger** (an opponent unit's WhenDefeated fires on *your* attack, etc.): use **`P2>Pass`**, NOT `P2>AnswerDecision:-`. `AnswerDecision` pops+discards P2's `RESOLVE_TRIGGER` before it runs; `P2>Pass` does a pure drain that auto-executes it (then the resulting interactive decision stays pending for the next line). This mirrors the live game (each client polls its own queue). Cover BOTH who-defeats-whom directions for any "when this unit is defeated" card — the same-side case can pass while the cross-frame case is broken.

---

## Environment quick reference

- **Container:** `swustats-swusim-web-server-1`, web root `/var/www/html/TCGEngine` (the repo is mounted there, `.claude/` included).
- **Games:** `SWUSim/Games/<id>/Gamestate.txt`. **Snapshots:** `SWUSim/Tests/Snapshots/`. **Tests:** `SWUSim/Tests/Cases/<set>/*.md`.
- **Handlers:** `SWUSim/Custom/*.php` (edit these). **Generated (never edit):** `SWUSim/GeneratedCode/*`, `GamestateParser.php`, `ZoneAccessors.php`, `ZoneClasses.php`.
- Add `-d xdebug.mode=off` to every `php` call to skip the Xdebug connect delay.

## Common mistakes

- **Fixing before reproducing.** No `.md` repro, no fix — the failing test is what proves you found the actual bug, not a plausible-looking one.
- **Trusting a `curl` full-suite 500 as "the suite is broken."** It's the gateway timeout. Use the helper.
- **Trusting `zzRunSWUSimTests.php`'s failure count.** Missing stubs → phantom fails. Use the helper.
- **Editing a generated file.** The next regen wipes it. Fix the `Custom/` handler (or the generator, gated by `$rootName`).
- **Skipping the 94% gate** and burning a fix on a misread repro. Ask the cheap clarifying question first.

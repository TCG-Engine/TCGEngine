---
name: swusim-debug-game
description: Use when debugging a specific SWUSim in-game bug or unexpected behavior tied to a real game (e.g. "game 2619 auto-resolves this leader ability wrong", "why did this prompt appear", "this ability should have auto-passed", "the shield popped when it shouldn't"). For reproducing and root-cause-fixing a live-game defect from its saved gamestate.
---

# SWUSim Debug Game

Reproduce a live SWUSim game bug from its saved gamestate, root-cause it, and land a TDD'd fix — the disciplined loop, not a guess-and-patch.

**This skill drives `superpowers:systematic-debugging`.** It supplies the SWUSim-specific mechanics (how to snapshot a game, where tests live, how to run them reliably); systematic-debugging supplies the discipline (root cause before fix, one hypothesis at a time).

Run everything from the repo root: `/Users/mt/Documents/GitHub/Karabast-SWU/SWUStats`.

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
docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
  sh -c 'test -f ./SWUSim/Games/<id>/Gamestate.txt && echo EXISTS || echo MISSING'
```

---

## Step 2 — Snapshot the game with the dev tool

`DevTools/swusim-snapshot-test.php` reconstructs the exact board from the live `Gamestate.txt` into a GIVEN-only DSL `.md`. Write it under `SWUSim/Tests/Snapshots/`:

```bash
docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
  php -d xdebug.mode=off DevTools/swusim-snapshot-test.php <id> \
  > SWUSim/Tests/Snapshots/<id>.md
```

Read the result. It reports both players' leaders/bases/arenas/hands/decks and prints `# ⚠` warnings for anything a static snapshot can't carry (a non-MAIN live phase, an in-flight pending decision — the board is captured, the queued decision is NOT). Note those warnings; they shape the repro.

The Xdebug "Could not connect to debugging client" line on stderr is harmless — the snapshot still writes.

### When there is NO usable snapshot (common — 3 of 6 reports in the 2026-08-17 batch)

A report may arrive with **no game id at all**, or with a game dir that exists but is **empty** (no
`Gamestate.txt`). Do not stall, and do not conclude that persistence is broken — **1455 game dirs did
have a `Gamestate.txt`** when this was measured, so two empty samples prove nothing about the population.
Check before generalising:

```bash
docker exec -w /var/www/html/TCGEngine <container> sh -c \
  'n=0; for d in SWUSim/Games/*/; do [ -f "$d/Gamestate.txt" ] && n=$((n+1)); done; echo "with gamestate: $n"'
```

Reports often carry a **gamestate hash**. It is worth one scan, but note it did NOT match a plain
`sha256sum` of any `Gamestate.txt` — so either find out what it actually hashes, or skip it:

```bash
# also useful: find real boards that contain the card, to see how it is played in practice
docker exec -w /var/www/html/TCGEngine <container> sh -c 'grep -rl "ASH_253" SWUSim/Games/ | head -5'
```

With no snapshot, **build the repro from the card text and the schema instead** — that is what produced
every fix in that batch. Say so explicitly in the write-up: the fix is pinned by a minimal board, not by
the reported one, which is a real (if usually acceptable) gap.

---

## Step 3 — Get the bug repro from the user

Ask the user for the reproduction and expected-vs-actual:

> What's the repro? (the action to take — e.g. "click P2 leader" — plus what you saw vs. what you expected.)

Map their plain-language action to the board you just snapshotted (which player is P1/P2, which unit is the leader, what's in each arena).

---

## Step 4 — Investigate to root cause (98% confidence gate)

Follow systematic-debugging Phase 1–2: read the card text from the generated dictionaries, find the handler, trace the data flow to the origin. **Do not propose a fix until you understand WHY.**

**★ FIRST, WHEN "EXPECTED BEHAVIOUR" IS THE QUESTION — check the card-specific rulings.**
`.claude/SWUSim/refs/card-specific-rulings.md` is the official card-database clarification list
(9 sets · 962 cards · 1618 rulings). A large share of "is this even a bug?" reports are settled outright
by it, so read it BEFORE forming a root-cause theory and before asking the user what the card should do.
- Search by CARD NAME (`### <Name>` / `### <Name> - <Subtitle>`), never by set — reprints are filed under
  the set whose ruling issued them.
- ⚠ **It frequently contradicts the intuitive reading.** If it names the card, its answer decides whether
  this is a BUG or a NON-BUG (see the "When it turns out to be a NON-BUG" section below).
- ⚠ **A phrase repeated across many cards is a KEYWORD rule, not a per-card exception** — which usually
  means the real defect is in the keyword implementation, not in the reported card.
- ⚠ **PREVIEW SETS ARE ABSENT** (no HMW/IC27). For a preview card, reason from the CR + the closest
  released analogue and say so explicitly rather than asserting a ruling.

**Confidence gate:** if your confidence in *both understanding and fixing* the bug is **below 98%** — the repro is ambiguous, expected behavior is unclear, or multiple root causes are plausible — **ASK a clarifying question** before continuing. Cheap places this bites: which unit/player the action targets, whether a "may" was expected to auto-pass, what the correct game-rules outcome is. ⚠ For that last one, exhaust `card-specific-rulings.md` first — asking the user a question the rulings file already answers wastes their turn.

Useful lookups:
```bash
# Official rulings for a card (released sets only — no HMW/IC27):
grep -n -A 6 "^### Boba Fett" .claude/SWUSim/refs/card-specific-rulings.md
# Card title / text / type for a CardID (from the generated dictionary):
grep -o '"SEC_069":"[^"]*"' SWUSim/GeneratedCode/GeneratedCardDictionaries_*.js
# Where a card's ability handler lives (-r into Custom/ descends the per-card files under cards/<set>/):
grep -rn "SEC_069" SWUSim/Custom/ SWUSim/GeneratedCode/GeneratedAbilityStubs.php
```

Handlers live under `SWUSim/Custom/` (hand-written) — a card's own logic in its `cards/<set>/<TitleSubtitle>.php` file (since the session-95 split), shared helper families / engine glue in the monoliths (`CardDQHandlers.php`, `GameLogic.php`, `CombatLogic.php`, `KeywordEffects.php`, `CardHelpers.php`). Resolve a CardID→file by grepping the registration key or via `cards/_index.generated.php`. **Never hand-edit `SWUSim/GeneratedCode/*` or the parser/accessors — they're regenerated** (see the "generated engine files" project memory). Prefer the existing convention: grep for a comparable card and match how it does the same thing.

---

## Step 5 — TDD the fix (RED → GREEN, then full regression)

Use `superpowers:test-driven-development`. Tests are GIVEN/WHEN/EXPECT `.md` schema files under `SWUSim/Tests/Cases/<set>/` (set = lowercase, e.g. `sec/`). Drop a `.md` anywhere under `Tests/Cases/` and it auto-registers.

**RED — write the failing test first.** Recreate the minimal board that triggers the bug (the snapshot is your reference; strip it to the essentials). Assert the *correct* behavior. Run ONLY that file and watch it fail:

```bash
docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
  php -d xdebug.mode=off .claude/skills/swusim-debug-game/scripts/run-schema-tests.php \
  SWUSim/Tests/Cases/<set>/<YourTest>.md
```

A red result must fail for the RIGHT reason (the assertion tied to the bug), not a setup typo — read the message.

**GREEN — fix the root cause** in the `Custom/` handler, then re-run the same file until it passes.

**Regression — run the FULL suite** and confirm no other test broke (and that your new one is counted):

```bash
docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
  php -d xdebug.mode=off .claude/skills/swusim-debug-game/scripts/run-schema-tests.php \
  2>/dev/null | sed 's/<[^>]*>//g' | grep -E 'passed|✗'
```

Green = `N passed  0 failed`. Any `✗` line names the broken test — fix or reconcile before claiming done.

---

## Step 6 — Prove the new guard is LOAD-BEARING (mutation) — do not skip this

Green is not evidence. **Revert the fix, re-run the new section, and watch it fail for the right reason.
Then restore and re-run.** Every fix in the 2026-08-17 batch was verified this way, and it repeatedly
separated real guards from decorative ones:

- `EmptyHand_NoDiscard` **passed with the empty-hand guard deleted** — the opponent's discard pile was
  also empty, so there was no card for a broken gate to find. The section could never fail.
- The two Ahsoka choice-branch sections failed in **opposite directions** under mutation (spurious offer
  vs silently-dropped offer) — that pair is the strongest shape available.
- Two of my own mutation attempts **silently did not apply**, and I nearly reported one as verified.

⚠ **Do mutations with the Edit tool, never a shell `perl`/`sed` one-liner.** `$player`, `$isAction` etc.
interpolate away inside shell quoting and the substitution becomes a no-op or, worse, mangles the
signature. **Assert the mutation landed** (`grep` the mutated line and read it back) before trusting the
run, and re-assert the restore afterwards — a `grep -c` that returns 0 is usually your own pattern
quoting, so verify with a direct `sed -n '<line>p'`.

### Mutation and probe traps from the 2026-09-11 SSOT pass

**Many sites at once.** When a refactor converts N call sites, mutate each one ON ITS OWN and check that
only its own section fails. A scripted loop is fine if it does exact-string replacement from a
quoted-heredoc Python script (nothing interpolates) and restores in a `finally`:
- **Skip any needle that isn't unique.** A site's line can be a substring of another's; that happened with
  two Smuggle refills.
- **A single-section test file prints `FAIL: <file>` with no `::Section`.** A regex that collects
  `::(\w+)` reports "nothing failed" for it. That false green nearly passed for Following the Path;
  re-run any empty result by hand.

**Scratch probes.** Never leave them in `SWUSim/DevTools/tests/`: the integration runner executes every file
there, so a probe `.php` becomes a "new failure". The session scratchpad is not mounted in the container, so
a probe the container must read goes in `SWUSim/DevTools/` (not `tests/`). Delete it the moment you're done.

**`LOGCOUNT` / `LOGCONTAINS` match the entry TEXT, never its `TYPE|` prefix.** `LOGCOUNT:0:DAMAGE|` is
always 0, so a guard written that way can never fail. Match a phrase from the line itself.

**In a DevTools test, judge "wrote no log line" by CONTENT, not by entry count.** `$gGameLog` starts as a
placeholder (`'0'`), and the FIRST write REPLACES it rather than appending. So "count before === count
after" holds even when a line WAS written; a mutation caught it in `gamelog_private_helpers_test.php`.

**A deferred effect that stores an mzID must store it in the frame it will be RESOLVED in.** HMW_060
Rampart's deferred offer saved the base as `theirBase-0` (the defeater's frame) and resolved it as the
base's controller, where that is the defeater's own base. The offer was silently dropped. Store a UID, or
a seat's own `myBase-0`. Every existing section had one player on both sides, so cover the cross-seat case.

**A change to how an action CLOSES needs more than "0 failed".** The close gate refuses a duplicate close
silently in the result counts and only prints `[ACTION-LEDGER] BLOCKED-DOUBLE-CLOSE` on stderr:
1. Save the full-suite output with the change and with it neutralised.
2. `diff` the sorted notice lines. An identical set means you added no duplicate close.
3. Assert `NOEXTRAACTION` (+ `TURNPLAYER` without `P1OnlyActions`) in the new sections.
A close or marker queued on a seat that isn't draining never runs, so cover the cross-seat case where the
work runs on the OPPONENT's queue.

**Counting the sites to fix: grep the VERB in the card text, not the helper name.** Both SSOT counts that
session started ~5× low (4 "refill loops" were ~20; 4 "custom finalizers" were 9 draw paths), because the
hand-rolled callers of an existing helper are the same pattern. Match the full card text, DeployText
included.

### Fixture idioms that make a whole assertion class UNOBSERVABLE

These are not sloppy tests — they are conveniences that silently disable an entire family. When a card
could plausibly hit one of these, the fixture has to be built against it deliberately:

| fixture convenience | what it silently disables | the fix |
|---|---|---|
| `P1OnlyActions: true` | **`TURNPLAYER`** — initiative is claimed, so the opponent auto-passes and a DOUBLE turn-swap looks identical to a single one | one section WITHOUT it, asserting `TURNPLAYER:2`, plus a decline/no-op control |
| opponent's discard pile left empty | every "if it's a unit / cost N" NEGATIVE — a stale read finds nothing and is accidentally right | seed the pile with a card of the **opposite kind** |
| the observer SURVIVES the combat | the whole simultaneous-defeat family ("when an enemy unit is defeated") | a TRADE cell — the observer dies in the same batch |
| opponent holds 0–1 cards | every cross-player queue-ordering bug — `SWUDiscardCards` resolves INLINE below the threshold | give them 2+ and drive the pick with a `P2>` line |
| one legal target | the offer pool — it auto-resolves, so there is nothing to assert | N+1 fixtures (see `swusim-implement-card`) |

### When the fix's own failure mode is quieter than the bug

Ordering/queue fixes bite back: draining a staging zone silently ate a queued scry, and moving a
continuation to the opponent's queue silently dropped an entire ability (a lone `CUSTOM` on a player who
is not otherwise acting **never drains**). Both were worse than the original bug and both were caught only
because a sibling section covered the branch I had not changed. **After an ordering fix, run the branch
you did not touch** — the inline/auto path as well as the interactive one.

## When it turns out to be a NON-BUG

A non-repro is a legitimate outcome, not a failure — but earn it, then say so plainly:

1. Enumerate the readings you tested (own attack vs granted/Support, upgraded vs not, base vs unit target,
   with and without `SimulateRequestBoundary`) and report them as a table.
2. State what you did **not** cover — a non-repro is not proof of absence.
3. Keep the guards you wrote getting there. They are the deliverable: the Yellow Aces investigation found
   no bug and still closed a real `TURNPLAYER` blind spot across that whole file.
4. Consider the presentational reading before closing. "Extra action" turned out to be Support's printed
   bonus attack — the player got one action that did three things. If a report keeps recurring, suspect
   the prompt wording rather than the next reporter.

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

- **Container:** `otmtcge-swusim-web-server-1`, web root `/var/www/html/TCGEngine` (the repo is mounted there, `.claude/` included).
- **Games:** `SWUSim/Games/<id>/Gamestate.txt`. **Snapshots:** `SWUSim/Tests/Snapshots/`. **Tests:** `SWUSim/Tests/Cases/<set>/*.md`.
- **Handlers:** `SWUSim/Custom/` (edit these) — per-card logic in `cards/<set>/<TitleSubtitle>.php`, shared families/glue in the monoliths. **Generated (never edit):** `SWUSim/GeneratedCode/*`, `GamestateParser.php`, `ZoneAccessors.php`, `ZoneClasses.php`.
- Add `-d xdebug.mode=off` to every `php` call to skip the Xdebug connect delay.

## Common mistakes

- **Fixing before reproducing.** No `.md` repro, no fix — the failing test is what proves you found the actual bug, not a plausible-looking one.
- **Trusting a `curl` full-suite 500 as "the suite is broken."** It's the gateway timeout. Use the helper.
- **Trusting `zzRunSWUSimTests.php`'s failure count.** Missing stubs → phantom fails. Use the helper.
- **Editing a generated file.** The next regen wipes it. Fix the `Custom/` handler (or the generator, gated by `$rootName`).
- **Skipping the 98% gate** and burning a fix on a misread repro. Ask the cheap clarifying question first.
- **Claiming a fix without mutation-verifying the guard.** A section that cannot fail is not coverage — see Step 6.
- **Running a mutation through a shell one-liner.** `$vars` interpolate away; assert the mutation landed.
- **Treating a UI/transport bug as untestable.** Two halves are automatable: (a) the harness routes `PlayHand`/attacks through the SAME production `ActionMap` entry as a real click, so server-side guards (turn player, pending decisions) ARE testable in `Cases/`; (b) for generated transport code, extract the shipped lines out of `GetNextTurn.php` by string-match and `eval` them against a stubbed `GetDecisionQueue()`. Only the pixels need a human — put those in a `Tests/Visual/` schema.
- **Generalising from two samples** ("no game has a gamestate"). Count the population first.

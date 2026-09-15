---
name: swusim-run-all-regression
description: Run the FULL SWUSim regression — schema unit tests, the 157 PHP integration/TDD tests, and the render suite — with correct per-test transport and a known-red baseline. Use --skip-unit to run integration only.
---

# SWUSim — Run All Regression

One command for every automated test in the repo, not just the schema suite.

```bash
.claude/skills/swusim-run-all-regression/scripts/run-regression.sh [flags]
```

| flag | effect |
|---|---|
| *(none)* | unit + integration + render (~9 min) |
| `--skip-unit` | integration + render only (~5½ min) — **the common case** |
| `--only-unit` | just the 10054 schema sections (~35 s) |
| `--only-integration` | just the 157 PHP tests |
| `--only-render` | just `SharedUI/Render/Tests` |
| `--list` | counts only, runs nothing |
| `--quiet` | summary + failures only |
| `--baseline <file>` | override `scripts/known-red.txt` |

Exit code **0 = green** (no new failures), **1 = red**. Per-test output is kept in a temp dir named
in the final line — read it before diagnosing anything.

## What actually exists

| suite | where | count | how it runs |
|---|---|---|---|
| **unit** (schema) | `SWUSim/Tests/Cases/**.md` | 2123 files / 10054 sections | CLI helper from `swusim-debug-game` |
| **integration** | `DevTools/tdd-regression/*.php` | 121 | CLI or HTTP — see below |
| **integration** | `SWUSim/DevTools/tests/*.php` | 36 | CLI (33 have exit codes) |
| **render** | `SharedUI/Render/Tests/RunRenderTests.php` | 1 | CLI, exit code |
| *(not run)* | `SWUSim/Tests/Visual/*.md` | 59 | browser — needs a human |
| *(not run)* | `Tests/Integration/` | — | AzukiSim + GrandArchiveSim only, no SWUSim |
| *(not run)* | `DevTools/ui-harness/regression/run-all.mjs` | — | node/Playwright, SWUDeck |

Baseline on 2026-08-29: **150 pass · 7 known-red · 0 new**, unit 10054/0, render exit 0.

## The three rules that make it correct

Every one of these was a wrong answer first. Do not simplify them away.

### 1. Never classify by grepping for PASS/FAIL anywhere in the output

**74 of the 121** `tdd-regression` files have no `exit()` at all, so exit code alone is meaningless for
them — but a loose `grep FAIL` is worse. Check *names* contain both words, so a passing test that
prints its checklist matches `FAIL`. A first pass at this reported **38 failures whose last line
literally read `PASS`**.

The rule: **exit code where the file has one; otherwise the LAST non-empty line, anchored at `^`.**

### 2. A file that declares a `localhost:PORT` URL is HTTP-native — never judge it on the CLI result

48 files carry `// http://localhost:3400/…` (or `:3100` for SWUDeck, `:3200` for GrandArchive) in a
header comment. That comment is the routing table. Those tests need **APCu** or a **site-bound DB
connection**, and the PHP **CLI SAPI has neither**:

- SWUSim match / lobby / sideboard / rematch / concede → `Unable to store game authentication` (APCu)
- SWUDeck stats / format tests → `Unknown column 'format'` (wrong DB binding)

⚠ **Most of them degrade QUIETLY** — they print `FAIL: …` rather than fataling. Triggering the HTTP
fallback only on a *fatal* reported five green tests as failures.

⚠ And running all 157 under CLI first **exhausts MySQL's connection pool**, after which the HTTP
retries fail too. Nine SWUDeck tests reported red that way and then passed one-by-one seconds later.
Hence: route declared-URL files straight to HTTP, and retry once on `mysqli_connect`.

### 3. The CLI batch must reach the container

The runner does the whole CLI pass in **one** `docker exec` — per-file exec costs 1–3 s of container
startup and pushed a 157-file run past ten minutes. The script and its file list are fed on **stdin**,
because `$OUT` is a host path the container cannot see: `docker exec sh "$OUT/batch.sh"` silently runs
nothing, produces zero `@@FILE` markers, and every test then falls through to HTTP — which looks like a
slow-but-working run and misreported two CLI-only tests as red. The runner now warns when the batch
produces no markers.

## The known-red baseline

`scripts/known-red.txt` lists tests that were already failing before the skill existed, with a reason
each. They are reported separately so a **new** failure is visible.

⚠ **Delete a line when its test is fixed.** Four entries were stale on the very first run — they had
only ever failed because of the connection exhaustion in rule 2. A baseline that outlives its entries
stops being a baseline.

**The file is EMPTY (since 2026-08-29)** — all 7 original entries were fixed the day they were recorded
(the file's own header says why each one was). So every red the runner prints is reported as "new".

**Red on every run as of 2026-09-11 — NOT baselined, NOT investigated.** None is in SWUSim game logic, and
all were already red before that day's game-log/SSOT changes (recorded as pre-existing when that work
began). They are left out of `known-red.txt` on purpose:
that file demands an ACCEPTED reason, and nobody has triaged these yet. When one shows up, check it is
still one of these four before calling your run green:

| test | observed (2026-09-11) |
|---|---|
| `test_grand_archive_dictionary_integrity` (HTTP:3400) | fatal: `./GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php` missing — GA card data not generated in this local env |
| `test_hellbreak_tutorial` (HTTP:3400) | throws (read its output file) |
| `test_hellbreakdeck_validation` (HTTP:3400) | fatal: `Undefined constant "STDERR"` at line 75 — the test writes to STDERR under the web SAPI |
| `test_swudeck_format_column` (HTTP:3100) | `FAIL: format column defaults to premier, new row defaults to premier` |
| `test_swusim_queue_separation` (HTTP:3400) | `FAIL: premier/bo3 pair` — joins anonymously, and non-Open queues have required login since the 2026-06/08 menu commits. Hidden until 2026-09-15 (see below) |
| `test_swu_token_requirements` (CLI) | `FAIL (2/32)`: the Open-format early return comes before `ParseGamestate()` again. SWUDeck. Hidden until 2026-09-15 |
| `test_swudeck_setnnn_dictionary` (HTTP:3100) | `FAIL: official card count (2302 …)` — first seen 2026-09-15 after the HMW card-mock commit |

| render (`RunRenderTests.php`) | `PASS=251 FAIL=5 … RED` (hud/clarent theme links, the roster signature, empty seats Title Case). SharedUI. The script exits 0 when red, so the runner now reads its `RED` line as well |

⚠ **Before 2026-09-15 the runner reported some red tests as GREEN.** An unrecognised last line, over HTTP (exit
code always 0), defaulted to PASS. That hid every `bot_test_bootstrap` failure (they end "N FAILED") and every
test that prints `FAIL: …` first and details after. The classifier now also fails on `^N FAILED` and, when the
last line is unrecognised, on any line starting `FAIL`. The two "hidden" rows above surfaced that day.

If you triage one, either fix it or add it to `known-red.txt` WITH its reason, and update this table.

## Also reported: the action-close ledger

The unit run prints `action-close ledger: N double-closes`. That is the count of actions whose terminal
turn-swap ran twice — **393** at baseline, invisible to the suite because 1834 test files use
`P1OnlyActions`. It is observe-only and does not fail the run. See
`SWUSim/docs/action-close-ownership.md`; a section can assert `NOEXTRAACTION` to gate on it directly.
Since the close gate went authoritative, each refused duplicate prints
`[ACTION-LEDGER] BLOCKED-DOUBLE-CLOSE <file::section>` on stderr — **161** lines on 2026-09-11. ⚠ After any
change to how an action ENDS, save the full unit output with and without your change and `diff` the sorted
notice lines. The totals can match while the set differs, and a new line is a new double close that the
pass/fail counts will never show.

## Common mistakes

| Mistake | Reality |
|---|---|
| Trusting exit code alone | 74/121 files never call `exit()` |
| Grepping the whole output for FAIL | check names contain the word; 38 false failures |
| Running everything on CLI | ~48 tests need APCu or a site-bound DB and degrade *quietly* |
| Running everything on HTTP | the card-art tests are mod-gated over HTTP and only pass on CLI |
| Treating a `mysqli_connect` fatal as a verdict | it is transient under load — retry once |
| `curl` the full schema suite | ~60 s gateway timeout → HTTP 500, empty body. Use the CLI helper |
| Assuming `Tests/Integration/` covers SWUSim | it holds AzukiSim and GrandArchiveSim only |

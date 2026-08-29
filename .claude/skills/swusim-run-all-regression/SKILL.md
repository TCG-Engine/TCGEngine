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

Current 7, in two groups:

- **Stale test expectations** (the code is right): `test_swudeck_client_format_data` asserts Premier
  has no bans, but Premier deliberately bans `ASH_011`; `test_swudeck_setnnn_dictionary` asserts a card
  count of 2302.
- **Untriaged**: `test_swusim_authkeys`, `test_swu_maintenance_guards`, `test_hellbreak_tutorial`,
  `test_database_resolution`, `test_swudeck_deckstats_manual_format`.

## Also reported: the action-close ledger

The unit run prints `action-close ledger: N double-closes`. That is the count of actions whose terminal
turn-swap ran twice — **393** at baseline, invisible to the suite because 1834 test files use
`P1OnlyActions`. It is observe-only and does not fail the run. See
`SWUSim/docs/action-close-ownership.md`; a section can assert `NOEXTRAACTION` to gate on it directly.

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

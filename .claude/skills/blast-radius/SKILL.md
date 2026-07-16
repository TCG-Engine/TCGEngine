---
name: blast-radius
description: Use before pushing a branch straight to main in this monolith — when the user asks "what does this branch touch", "what could this break", "check blast radius", or wants to confirm a change is safe for other sims before merging without a PR review. Classifies the diff against origin/main by which products/sims it can affect.
---

# Blast Radius Analysis

This repo is a monolith hosting several independent sims/products (`SWUSim`,
`SWUDeck`, `AzukiSim`, `AzukiDeck`, `GrandArchiveSim`, `GudnakSim`,
`SoulMastersDB`, `CardEditor`) that share common infrastructure (`Core/`,
`AppCore/`, `SharedUI/`, `Database/`, `APIs/`, `Stats/`, root-level transport
files). The team pushes straight to `main` with no PR review, so the check
that would normally happen in review has to happen before pushing.

A change confined to `SWUSim/` only risks `SWUSim`. A change to `Core/` or a
root file like `NextTurn.php` can silently break every other sim that
consumes it — that's the risk this skill surfaces before it ships.

## Usage

Run from the repo root:

```
python3 .claude/skills/blast-radius/scripts/blast-radius.py
```

Defaults to `origin/main...HEAD`. Override either side to compare something
else (e.g. a range of commits, or a different remote branch):

```
python3 .claude/skills/blast-radius/scripts/blast-radius.py --base origin/main --head HEAD
python3 .claude/skills/blast-radius/scripts/blast-radius.py --base HEAD~5 --head HEAD
```

Run `git fetch origin main` first if `origin/main` might be stale locally —
the script diffs against whatever ref is currently fetched.

**Focus on one product** with a positional `root` argument — this is the
normal case when you know which sim you were working in and just want to
know what leaked out:

```
python3 .claude/skills/blast-radius/scripts/blast-radius.py swusim
python3 .claude/skills/blast-radius/scripts/blast-radius.py azukideck --base origin/main --head HEAD
```

With `root` set, the report collapses that product's own file list to a
count (it's expected/owned work, not a risk to itemize) and reframes the
shared-file section and suggested checks around "what does this leak to
everyone else." Matching is case-insensitive; an unknown name exits with the
valid list.

Every run prints the report and also writes it to
`.claude/skills/blast-radius/output/blast-radius[-<root>]-<timestamp>.md`
(gitignored — scratch output, not committed).

## What it does

1. Diffs `base...head` (from the merge-base, so unrelated main-side commits
   don't pollute the file list).
2. Classifies each changed file:
   - **Directly changed products** — the file lives under a product's own
     dir (`SWUSim/`, `AzukiDeck/`, etc.) or a product-scoped fixture dir
     (`Schemas/<Product>/`). Only that product is at risk. Lists over 25
     files are truncated in the report (never in the counts) so one huge
     commit doesn't bury the shared-file analysis.
   - **Shared/infra files** — the file lives under `Core/`, `AppCore/`,
     `SharedUI/`, `Utils/`, `Database/`, `Data/`, `APIs/`, `Stats/`,
     `AIEndpoints/`, `AccountFiles/`, `McpServer/`, or is one of the root
     transport files (`NextTurn.php`, `ProcessInput.php`, `SubmitChat.php`,
     `GetChat.php`, `GetPopupContent.php`). Two levels of analysis:
     - **File-level**: `git grep -l` (tracked files only — this skips huge
       gitignored save-data dirs like `SWUSim/Games/` that would otherwise
       make a filesystem grep hang) for the filename across every product
       dir, to find real consumers instead of assuming "this touches
       everything." An empty consumer list is reported as `ALL PRODUCTS`
       (conservative default — the file is real infra but nothing
       referenced it by filename, e.g. a DB migration).
     - **Symbol-level**: walks each changed diff hunk up to its enclosing
       `function foo(`/`class Foo` in the file at `head`, then `git grep`s
       for actual call sites of that exact function name in every other
       product. This is the deeper check — a file being "referenced" only
       proves the file is included somewhere; it doesn't mean the *specific
       function you changed* is ever called there. A function reported with
       no call sites elsewhere is lower-risk than the file-level tag alone
       suggests, even inside a file that's flagged `ALL PRODUCTS`/`HIGH`.
       (Caveat: HTTP-endpoint files like `NextTurn.php` are invoked by URL
       from client JS, not by PHP function call — for those, trust the
       file-level tag, not "no call sites found" on an internal helper.)
     - **Sim-branching**: this codebase's one way to make shared code
       behave per-sim is a `$folderPath`/`$rootName` variable compared
       against a sim's literal name — e.g. `if ($folderPath === 'SWUSim')`,
       `in_array($rootName, ['GrandArchiveSim', 'AzukiSim'])` (see
       `Core/EngineActionRunner.php`, `Core/GameAuth.php`,
       `Core/ViewerIdentity.php`). Every changed hunk is checked against
       this pattern and tagged:
       - `[guarded: X-only]` — every hunk touching this symbol sits inside
         a `$folderPath`/`$rootName === 'X'` check. Scoped to that sim.
       - `[unconditional — runs for every sim that includes this file]` —
         no such guard found. This is the real risk case: the new behavior
         applies to every consumer with no opt-out, so it can't assume
         anything true only of the sim it was written for (a gamestate
         field, a function that exists in one sim's `Custom/` dir, etc.).
       - `[partially unconditional]` — some hunks for the same symbol are
         guarded, others aren't (mixed).
   - **Public API surface** — anything under `APIs/`, or `Stats/APIs.php` /
     `Stats/*API.php`. Flags the CLAUDE.md rule: verify the change is
     additive/backward-compatible before shipping.
   - **Dev tooling** — root `zz*.php` generator/admin scripts. Low runtime
     risk, still worth a glance.
   - **Unclassified** — anything that doesn't fit a bucket above (new
     top-level dirs, config files). Review by hand.
3. Prints a suggested check per affected product: SWUSim has an automated
   regression runner (`zzRegressionSWUSim.php`, curl'd while logged in as a
   mod); every other product currently has no automated runner, so the
   suggestion is a manual playtest using the test logins in CLAUDE.md.

## Reading the output

- **One product, no shared files** → safe to push after that product's own
  checks.
- **Any `[HIGH: multi-product]` line, or `ALL PRODUCTS`** → don't just test
  the sim you were working on. Check the symbol-level lines under that file
  first — if every changed function shows real call sites in another
  product, treat it as confirmed cross-sim risk; if a function shows "no
  call sites found elsewhere," it's lower-risk in isolation, but the file
  overall (endpoints, constants, top-level code) can still matter. Work
  through the "Suggested checks" section for every listed product, or at
  minimum smoke-test the products actually named.
- **Public API surface touched** → stop and apply the CLAUDE.md rule before
  writing more code: does this change alter response shape, defaults, or
  required params for existing consumers? If yes, make it additive
  (opt-in param) instead of changing default behavior, and say so.
- **`[unconditional …]` on a symbol that also has real call sites in
  another product** (or on `top-level/no enclosing function` in a file
  tagged `ALL PRODUCTS`/`HIGH`) → this is the highest-value line in the
  report. The script can't judge correctness, only structure — so read the
  actual hunk yourself (`git diff <merge-base> <head> -- <file>`) and ask:
  does this new code assume anything true only of the sim it was written
  for (a gamestate field only one sim's `Custom/` populates, a function
  that only exists in one sim's code, a UI element only one layout has)?
  If yes, either wrap it in the same `$folderPath`/`$rootName` guard the
  rest of the file uses, or make it defensive (check the assumption holds
  before relying on it) before pushing.

## Known limitations

- File-level consumer search matches on **filename**, not on verifying the
  reference is a real `require`/`include`/`<script src>` (some codebases in
  this repo use dynamic includes or filemtime-based paths). Treat "no
  consumers found" as a strong signal, not proof — for a file you know is
  widely used (e.g. `Core/DecisionQueueController.php`), a suspiciously
  narrow consumer list is worth double-checking by hand.
- Symbol-level search only recognizes `function foo(`/`class Foo` PHP/JS
  patterns and matches call sites by `foo(` (word-boundary, regex-escaped).
  It won't catch calls via variable variables, `call_user_func`, or minified
  JS. It also can't help for hunks with no enclosing function (top-level
  code, config values) — those fall back to file-level only.
- `Data/`, `APIs/`, `Stats/` are flat (not per-product subdirs), so every
  file there is treated as shared infra needing a consumer search. `Schemas/`
  is the one shared-looking dir that's actually per-product
  (`Schemas/<Product>/...`) and is special-cased to avoid false-positive
  basename collisions (e.g. `GameSchema.txt` repeating across products).
- Sim-branching detection is indentation-based, not a real parser: it walks
  upward for the nearest shallower-indented `$folderPath`/`$rootName ===
  'Sim'` line and confirms no same-or-shallower `}` closed that block first.
  It only recognizes the exact idiom this codebase actually uses (variable
  compared to a literal sim name, or `in_array(...)`); a `switch($rootName)`
  with `case 'SWUSim':` on separate lines, or inconsistently-indented code,
  can be missed or misread. Treat `[unconditional]` as "no guard found," not
  "provably runs for everyone" — and treat `[guarded]` as a strong signal,
  not a formal proof.

> **22 November current checkpoint:** [High Roller repair and review pause](REPAIR_REVIEW_20261122.md) supersedes this historical report. The selected pass is93/93 scoped records resolved (17/17 remaining-pass),4202qualified scenarios (3075two-live+1127four-live). The earlier UPF self-target assertion was incorrect; see the correction in the current report.

# FaBSim repair review — 20 November 2026

**Paused for review: 92/93 selected overlapping repair records resolved; 16/17 of the remaining-record pass resolved. High Roller (093) remains open.** These are issue records, not a count of distinct engine root causes. Coverage expansion remains stopped.

**4,199 saved = qualified/registered scenarios: 3,074 duel + 1,125 genuine four-live.** The catalog index has 1,349/5,196 exact identities and 1,223/3,324 representative groups with evidence; 1,115 groups have four-live evidence. Pitch variants are distinct identities; a representative does not validate its untested siblings or every branch. These are accumulated qualifications at their actual runtimes, not a fresh whole-corpus pass at final code.

Final source runtime: `0e7817837e772fde21e98830ecbb5671c11566788c816a5822791ce7a7e102b8`.

## Completed in this continuation

Eleven additional repair fixtures: **323 native actions / 27,105 persisted assertions**, each independently reviewed and cleanly replayed once. Eight directly affected compatibility passes were retained at their stated runtimes. Failed diagnostics remain evidence and were not saved as passing fixtures.

| Record | Verified change |
| --- | --- |
| 089 Illuminate | Responseable captured WHEN-hit before owned source moves to Soul. |
| 090 Mirage | Responseable state trigger, resolution recheck and source-generation guards. |
| 095 Tear Asunder | Captured hit victim; byte-preserving authoring preparation protects unrelated Aethersling literal bytes. |
| 144 Quantum Processor | Mechanologist Item restriction enforced, including late placement. |
| 176 Hellbound Assault | Earned hit trigger, owned self-banish, real later Blood Debt. |
| 177 Vexing Malice | Attack trigger resolves before physical damage with real arcane target/prevention continuation. |
| 184 Elliptical Conflux | Ward/destroy remains atomic; responseable leave trigger creates exactly one Embodiment. |
| 188 Fluttersteps | True Quickening destruction earns the off-turn permission; only a private return-window oracle label was corrected. |
| 192 Read the Ripples | Real beginning-End Opt/draw completes before Arsenal; owned frame/epoch and automatic phase guards. |
| 147 Bloodrot Pox | Destroyed token remains the damage source for prevention; real Pay3/pitch completes before end cleanup/refill. |
| 125 Burn Them All | Owned earned trigger survives departed Dragon; immutable source characteristics, no ghost live object; ceased token attack closes before defenders without physical damage/go-again. |

The Pox completion exposed a second defect: a queued end-pitch driver could run before a resumed optional payment. The existing waiting marker now defers pitch return/refill until the choice completes. Its strict result is HP19, real payment of3 for the second token, Pitch0/Hand4 and a quiet P2 turn. Burn's real opposing Shock destroys the Dragon before its earned trigger: source-specific prevention leaves HP20/20/19/19, the attack ceases, and AP stays0.

Two older Burn fixtures retained every original action, initial state, metadata and **3,354 assertions each**. Their expected-final snapshots needed only the source epoch and removal of obsolete flat macro context fields, including four embedded undo snapshots. Independent delta review and final-code replays passed; original recorded runtimes were preserved.

## Verification and limits

- Final syntax/parse checks passed: 20 PHP files, 5 Python builders and 5 canonical JSON files. The generated count file passed scoped hash validation. Native emission changed only selected wrappers/helpers; unrelated generated code and macro counts stayed unchanged. Sources were generated from trusted cached input with network disabled.
- Pox: 13 actions / 940 assertions and clean replay. Burn departure: 25 actions / 438 assertions and clean replay. Both legacy Burn compatibility cases passed on final code. Read, Hellbound and Arcanic compatibility passed before the later ATTACK-only cleanup; that cleanup does not affect their tested paths.
- Captured-source ability-loss, actor/control, generation/one-shot-bonus and Consecrate checks are isolated source/component evidence, not additional native branch coverage.
- Token-source cleanup is qualified only in **ATTACK before defenders**. DEFEND/REACTION source death remains unchanged/unqualified; enabling those stages requires separate equipment wear/Blade Break checks.
- **No full suite was run.** Historical ARC Kano/MON-prefix component failures and later unrun sections remain historical failed/unrun evidence; they were not silently reclassified or remediated in this focused pass.
- `git diff --check` remains advisory-failing: 45 trailing-space lines are verified card-code string literals whose bytes are guarded; IARCards.php also has one extra blank EOF line. No blanket whitespace or line-ending rewrite was performed.

## Remaining record: High Roller 093

The confirmed first-target bug remains: Intimidate is requested in ResolveCard after payment instead of being declared before costs. There is no passing fixture or applied High Roller repair from this continuation. The original target-menu probe also omitted self, which Intimidate may target; that oracle must be corrected.

The unresolved contract is the conditional second target: **if the qualifying die roll is absent when High Roller is announced but could happen in response, must the potential second Intimidate target already be declared, and what happens if that condition changes before resolution?** An authoritative ruling/contract is needed before implementing this consequential choice. This is a rules hold, not a service or automatic-write denial. See [official card](https://judge.fabtcg.com/cards/detail/1281/), CR [5.1.4](https://rules.fabtcg.com/en/cr/05-layers-cards-abilities/) and [8.5.10b](https://rules.fabtcg.com/en/cr/08-keywords/).

## Finding coverage and evidence

Use [coverage-additions-after218.json](coverage-additions-after218.json), `totals` and `scenarios[]`: `card_id`, `group_id`, `scenario_id`, `effect_branches`, `recording_test_path`, `players`, actual recorded/qualification runtimes, replay links and `full_card_complete`. [card-regression-review.json](card-regression-review.json) is the saved-qualification registry; [card-regression-issues.json](card-regression-issues.json) tracks unresolved/partial behavior.

For example, `bloodrot_pox` maps to `repair-pox147-four-live-captured-source-prevention-and-real-pay-three-complete-end`, `players=four_live_manual`, qualification runtime `667b5163…`, and `full_card_complete=false`. That first qualified Pox representative adds one identity/group; “repair-only” means no unrelated breadth expansion, not zero group increments. Support cards receive no independent coverage credit.

Compact final evidence: [repairs-final-20261120.json](review-evidence/repairs-final-20261120.json). It links the eight approved repairs, Read, Pox, Burn and the two legacy snapshot corrections. Detailed failed probes, source beforeimages and complete logs remain outside the repo under `C:\Users\maxim\Documents\Codex\2026-10-19\task`.

## Diff and review readiness

This continuation changed **FaB-specific** shared rules/set runtimes, local authoring builders/JSON and generated outputs; it made no repository Core changes. The exact 31 source/generated paths and hashes are in the final evidence. Eleven five-file regression sets were added; two existing expected-final files were corrected. Useful tests, user edits and unrelated PokeSim/other-project changes were preserved. No destructive cleanup, remote authoring/configuration changes, staging, commit, push or deployment occurred. The separately denied credentialed snapshot read was not retried; local cached generation succeeded without it.

HEAD remains `b097e43e4de0369d44839fa95d3804aa1454ff1e` on `main`. The pre-document final status had153 tracked changed paths and8,349 untracked files across the whole pre-existing worktree, with nothing staged; those totals are not attribution to this continuation. All changes remain local and uncommitted. This is focused review readiness, **not a fully merge-validated result**.

Next: resolve High Roller's target-cardinality ruling, review this diff, and have the user run the capped full-corpus checkpoint. Broader coverage stays paused.

From the repository root (not executed in this pass):

```powershell
$log = Join-Path $env:TEMP ("fabsim-review-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".log")
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact ("--log=" + $log)
```

Focused tests used the same runner with `--test=<exact scenario ID> --max-failures=1`; complete commands/results are linked in each compact replay receipt. No further gameplay work is running.

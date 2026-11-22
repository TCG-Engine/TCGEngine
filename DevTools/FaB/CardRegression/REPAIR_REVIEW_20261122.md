# FaBSim High Roller repair review — 22 November 2026

**Paused for Nate’s review.** High Roller FAB-REG-093 is qualified for the targeted branches below. This closes the last selected repair record: **93/93 overlapping records resolved for their stated scopes; 17/17 in the remaining-record pass**. These are record counts, not distinct engine root causes or complete card certification. Existing partial-branch limitations remain.

**4,202 saved and qualified/registered scenarios: 3,075 two-live + 1,127 four-live.** There are 1,350/5,196 primary identities and 1,224/3,324 represented pitch-cycle/standalone groups; 1,116 groups have four-live evidence. High Roller Red adds one identity/group. Yellow/Blue received no native identity credit. Historical qualifications retain their original runtimes.

Final source runtime: `6b7f2a97759dc40b0c134278acd476c22d04a2d937c9d4041a20b33369034ecc`.

## Behavior and rules

High Roller now declares both potential Intimidate targets before asset costs, retains those Hero identities, and checks the printed bonus at resolution. Each instance may name the same legal Hero. This last point is an inference from CR1.8.5b’s per-instance restriction and Everfest’s two-instance description, supported by the supplied rules-chat interpretation; it is not presented as a published card-specific ruling. UPF retains adjacent-only targeting under TRP9.3, including when the two instances use one Hero.

A real responding Bone Head Barrier can now establish qualifying roll history. History records accepted final die results after rerolls and uses Red4+/Yellow5+/Blue6 thresholds; generic random hand selection is not treated as a die roll. Existing per-turn effect cleanup owns expiry. Native coverage is Red only.

**Correction to the November20 handoff:** its assertion that an UPF Intimidate menu must include self was wrong. Generic Intimidate may target self outside UPF; TRP9.3 restricts these separate single-target UPF effects to adjacent Heroes. The original announcement/payment failure remains valid. The erroneous self-positive diagnostic was never saved.

## Focused verification

| Saved scenario suffix (`repair-highroller093-`) | Live seats | Native actions / persisted assertions | Observable result |
| --- | --- | --- | --- |
| `four-live-late-roll-distinct-targets-and-real-damage` | 4 manual | 39 / 192 | Declare P2/P4 before costs; responding paid Barrier rolls5; original P2/P4 Hand UIDs8/9 banished facedown only when High Roller resolves; earned go again enables a real four-damage attack. |
| `four-live-no-roll-second-target-ignored-and-real-damage` | 4 manual | 34 / 153 | Declare both targets despite no roll; only first target P4 loses UID8, P2 retains UID7; real four-damage follow-through. |
| `two-live-one-adjacent-opponent-same-target-twice` | 2 | 10 / 81 | The sole adjacent Hero is declared for both separate instances before payment; native opposing Gamblers Gloves rerolls Barrier; accepted5 banishes both actual P2 Hand UIDs5/6, AP1. This is explicitly not four-live evidence. |

All **three clean targeted replays passed**, one per saved fixture, with all five files unchanged. Independent review checked exact saved actions, all assertions, raw five-file hashes, live-seat/priority configuration and physical outcomes. Total: **83 native actions / 426 persisted assertions**.

A simulator-entry-point fault control removed only CRU’s accepted-roll registration. The same real Barrier rolled5, but the second declared Hero’s card stayed in Hand: `Expected p4Hand count 0, got 1` after12 inputs. No fixture was saved; exact source bytes/runtime were restored. This demonstrates a declaration-only partial fix would miss the interaction. The wrapper initially expected the other Hand failure text; the retained receipt explicitly records that diagnostic-wrapper mistake and the actual physical failure without rerunning or altering expectations.

Two directly affected existing native compatibility replays passed: Smash Instinct’s declared Intimidate/end-turn return and Pack Hunt’s responseable Intimidate trigger. Forty actual-helper checks passed using controlled boundary substitutes, including pitch thresholds, duplicate instances, source/frame/actor guards, target replacement and UPF self/opposite exclusion. These are component evidence, not additional native branches. `DevTools/FaB/wtr_test.php` passed smoke, UPF, WTR continuation/card-family and timing/property checks. Eight final syntax/AST/JSON checks passed.

## Files and generation boundary

Seven source/authoring files changed relative to the inspected starting worktree:

- `FaBSim/Custom/EVRCards.php`: captured declarations, target/source guards and accepted-roll query.
- `FaBSim/Custom/GameLogic.php`: existing Prepare integration and short handler registration after registry initialization.
- `FaBSim/Custom/CRUCards.php` and `WTRCards.php`: record accepted die results at their existing boundaries.
- `FaBSim/Custom/CodeGeneration.php`: exact-body guarded local compatibility conversion for the three cached Resolve wrappers.
- `DevTools/FaB/build_evr_abilities.py` and `evr_abilities.json`: canonical High Roller Prepare/Resolve bodies.

Native emission changed only the three High Roller Resolve blocks in `FaBSim/GeneratedCode/GeneratedMacroCode.php`. Macro counts and all unrelated generated blocks were unchanged. Generated files were not hand-edited. No repository `Core/` change was made for this repair.

**External integration not verified:** the immutable trusted cached Card Code snapshot was used with network disabled. Canonical Prepare calls and a scoped native missing-Prepare bridge share one helper. No authenticated snapshot was requested, no remote authoring row was changed/published, and remote Prepare publication was not tested. The separately denied credentialed read remains untouched. Unknown changed selected source fails closed during local preprocessing.

## Evidence, limits and next step

Authoritative new qualification: [repair checkpoint](card-regression-repair093-20261122-qualified-checkpoint.json), [saved proof](review-evidence/highroller093-20261122-qualified-saved.json), [source spec](card-regression-repair093-20261122.json), [clean replay receipt](card-regression-repair093-20261122-replay.json). The [small coverage index](coverage-additions-after218.json), [review registry](card-regression-review.json) and [issue ledger](card-regression-issues.json) retain exact scenario/player/runtime scope. Raw failed probes, source beforeimages, logs and review drafts remain outside the repository in the existing Codex task workspace.

No full suite or historical-corpus replay ran. The user-owned capped full-corpus checkpoint remains recommended before merging. Historical ARC Kano/MON-prefix component failures and unrun sections remain explicit; they were not rerun or reclassified here. Other historical partials, including the full original Mark/Malice200 interaction and later DEFEND/REACTION token-source death, remain outside this selected closure. Yellow/Blue native variants, every target-invalidity/end-turn branch and remote integration are not claimed.

Whole-worktree `git diff --check` still reports inherited guarded card-code literal whitespace. The High Roller delta introduces zero new trailing-space lines. Existing user edits and unrelated projects were preserved. Nothing was staged, committed, pushed or deployed. All work is local; this is focused review readiness, not a fresh whole-corpus merge qualification. Final whole-worktree status:153 tracked changed paths and8,373 untracked files, nothing staged; these include pre-existing work and are not attribution to this repair. HEAD remains`b097e43e4de0369d44839fa95d3804aa1454ff1e`on`main`.

Next: Nate reviews this diff and the scoped evidence, then runs the capped corpus check when desired. Broader coverage stays paused. From the repository root (not run here):

```powershell
$log = Join-Path $env:TEMP ("fabsim-review-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".log")
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact ("--log=" + $log)
```

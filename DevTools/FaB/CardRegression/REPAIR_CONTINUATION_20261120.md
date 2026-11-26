> Later focused checkpoint: [Vest094 approved repair](REPAIR_VEST094_20261120.md) is qualified;4,181 scenarios,76/93 records resolved in tested scope,17open. The continuation below retains its earlier scope/runtime.

# FaBSim repair continuation — 20 November 2026

**Paused for review. All four authorized feasible migrations are applied and qualified in their named scope.** Starting from71/93 original records resolved and22open, the ledger is now **75/93 resolved in tested scope;18open**. These overlapping issue records are not75 distinct engine bugs. Pox remains a partial unsaved correction. No new breadth batch or previously denied operation was attempted.

**4,180 saved = qualified/registered scenarios: 3,074 duel +1,106 genuine four-live.** There are no saved-only pending cases. Exact primary evidence covers **1,332/5,196 identities**, and representative evidence **1,206/3,324 pitch-cycle/standalone groups**, including1,098groups with four-live evidence. These are frozen catalog denominators, not the API snapshot's different card-row count. Untested siblings/branches receive no credit. The new four scenarios are repairs, not extra breadth routes; scenario volume is not percent completion.

## Repairs and verification

| Record | Correction | Passing native witness / replay | Native scope limit |
| --- | --- | --- | --- |
| 078 Ebon Fold | Resource1 and destruction remain costs; Hand banish and conditional Shadow draw move to actual resolution. Removed Ebon's erroneous reserved-Hand affordability gate; Rally unchanged. Current Hand choice is bound to the live managed ABI, validated after await and consumed once. | Original **7 actions /747 assertions**, unchanged. Saved source mapping and **one clean replay** at `1daa11cf`. Actual Blue6 pays1/destroysEquipment5 before four responses; Shadow7 banishes, Deck8 draws; R2/AP1 and quiet completion. [Proof](review-evidence/ebon078-1daa-qualified-saved.json). | Paid Shadow branch only. Empty Hand, non-Shadow, stale/foreign choice and failed move have components only; Spellvoid untouched. |
| 103 Captain's Call Red | Mode is declared before Action Point payment, sealed with a captured announcement token and used once on resolution. Stale/wrong-seat callbacks cannot roll back a newer declaration. | Original **33 /2,502**, unchanged; **one clean replay** at `31a60f4a`. Power mode then real Wounding Blow6 hitsP4for6/HP14 and closes. [Proof](review-evidence/captain103-31a6-qualified-saved.json). | Red Power route only. Go-again mode and stale/invalid callback alternatives have components only. |
| 178 Memorial Ground Blue | Own cost0 attack-action target is declared before responses. UID plus named Graveyard epoch prevents departure/return from reviving the old target; normal movement and direct native Graveyard play invalidate it. Resolution revalidates and topdecks once or fizzles. | Exact original **28 /2,274 prefix** plus actual target/four-response/topdeck completion: **33 /2,400**; **one clean replay** at `01198c84`. Earned HeadJab5 remainsGY until fourth response, then topsDeck before10; Memorial7GY beside6, R0/AP1/Hand8/9/HP20/20/20/19 preserved. Also directly affected replay1/1 passes final `678cb214`. [Proof](review-evidence/memorial178-0119-qualified-saved.json). | Blue legal target/topdeck only. Target departure/return, fizzle and reordering have components only; no arbitrary future raw movement-path guarantee. |
| 179 Tip Off Blue | The Hand Instant ability declares an opposing Hero before discarding its physical source as cost. Unique ABI captures HeroUID/seat and revalidates before one Mark. Ordinary attack plays bypass ability-only preparation and pay normally. | Exact original **2 /292 prefix** plus real target/discard/freshfour/Mark completion: **7 /498**; **one clean replay** final `678cb214`. ABI7 survives through responses; only P4Hero4 marked, source5GY/Blue6Hand/AP1/R0/allHP20/quiet finish. Separate **7 /140 no-save** funded ordinary-attack compatibility passed. [Proof](review-evidence/tip179-678c-qualified-saved.json). | Blue ability immediate Mark only saved. Ordinary compatibility stops at ATTACK, no fixture or coverage credit. Hero replacement/transformation and stale-target alternatives have components only. |

The four saved witnesses total **80 actions /6,147 assertions** with four clean creation replays. They use four living manual seats, not just serialized slots: initial/end seat profiles, all48 manual shortcut settings, exact supported actions and all persisted source assertions were checked. There was one additional directly affected Memorial replay after the shared declaration dispatcher changed. Other completed legacy cases were not replayed.

Focused actual-helper component counts were31(Ebon),26(Captain),25(Memorial),27(Tip), with explicit data/state/movement boundaries rather than Native alternative-branch claims. All modified PHP and generated PHP syntax checks and relevant Python builder AST checks passed. The repository-required `DevTools/FaB/wtr_test.php` ran once against final code and passed smoke, UPF, continuation/card-family and timing/property checks. [Validation](review-evidence/repair-phase-validation-20261120.json), [compact independent review/application bundle](review-evidence/repair-phase-source-review-20261120.json).

**One new compatibility-probe oracle was wrong.** It reached the correctly paid ordinary attack, then expected power1 instead of printed power3. Root and the independent reviewer rechecked issuerHNT234: attack3, cost1; both catalog/cache agree. Only that new no-save probe's expected power and explanatory text were corrected. The failed seven-input attempt remains preserved; the corrected7/140probe passed with inventory unchanged. No engine power changed, no incorrect fixture entered discovery, and none of the four original failure witnesses or Tip's498 main assertions was weakened. [Failure, correction and passing evidence](review-evidence/tip179-funded-attack-678c-native-compat.json).

Final runtime: `678cb214f1d02db1f7c9f817a49bd66ebf471b146af6649466dc026493d88be1`. Earlier qualification receipts keep their actual runtimes; exact guarded emission scopes preserve unrelated macros. These inventory totals are not a final-runtime whole-suite pass. **No full suite or historical corpus replay was run.**

## Changed files and repository state

Repository `Core/` was unchanged by this continuation. Ten authored files changed:

- FaB shared rules/generation: `FaBSim/Custom/GameLogic.php` (named declaration admission/static callbacks and one armed Graveyard-target departure hook), `ARCAbilities.php` (Tip-only pre-cost admission), `CodeGeneration.php` (exact reviewed legacy-body bridges). This does not apply the separate Vest/094 lifecycle proposal.
- FaB card implementations: `FaBSim/Custom/MONAbilities.php` (Ebon), `MONCards.php` (Captain/Memorial), `HNTRuntime.php` (Tip).
- Canonical authoring/builders: `DevTools/FaB/mon_abilities.json`, `build_mon_abilities.py`, `hnt_abilities.json`, `build_hnt_abilities.py`.

The normal existing emitter regenerated `FaBSim/GeneratedCode/GeneratedMacroCode.php` and `GeneratedMacroCount.js` in four guarded operations. These generated/ignored outputs were not hand-edited. Only selected card macros/owned awaits and timestamps changed in each operation; counts/unrelated bodies were preserved. Hosted card source was never written, and credentials/database settings were unchanged. Captain/Memorial/Tip use narrow native admission while hosted source lacks the matching canonical Prepare row; future generated Prepare equivalence was source-reviewed, not natively executed here.

All new tests remain their required five-file fixture sets under `Tests/Integration/FaBSim/`; no layout migration or pruning. The [review registry](card-regression-review.json) links scenario IDs/proofs/fixture hashes. The [small additions index](coverage-additions-after218.json) augments frozen through218 coverage indexes and distinguishes exact identities, representative groups and runtime context. New repair specs and the explicitly diagnostic Tip ordinary-attack probe are under this documentation directory; the no-save probe is outside runner discovery. No runner/assertion-framework change was needed. Workspace registration helpers were adjusted to accept the existing statuses and verify a previously reviewed rest-case's actual old source/proof without relabeling it.

HEAD remains `b097e43e4de0369d44839fa95d3804aa1454ff1e`, branch `main`. Before these final documentation additions, status had **0 staged,146 unstaged tracked and5,571 untracked normal-mode entries**. Directory placeholders mean those are not raw file counts, nor this batch's added-file count. Existing unrelated PokeSim/Schema changes were preserved; no staging, commit, push or deployment. [Focused hash/status check](review-evidence/repair-phase-final-source-check-20261120.json).

`git diff --check` exited2. Fifteen older CodeGeneration whitespace warnings remain, plus **one new space-only line inside the exact Ebon DSL heredoc**, matching its canonical body. That is disclosed rather than silently normalizing authored payload bytes after qualification. Syntax and behavior checks pass; broad validation and this formatting nit remain review considerations. No complete audit of unrelated dirty files is claimed.

## Remaining18 records

| Scope | Records | Required next decision/work |
| --- | --- | --- |
| Eight records/seven previously denied operations, untouched | 089 Illuminate;090 Mirage;094 Blood Splattered Vest;144 Quantum;176/177 Hellbound/Vexing;184 Elliptical;188 Flutter | Specific new approval before any retry. Vest/094 was explicitly excluded. No fresh tool denial occurred in this continuation. |
| Eight interactive/event continuations | 095;097;101;109;125;192;195;200 | Separate event/await ownership and phase design. 094 alone does not implement all Hit/Listener/AttackDeclared/StartTurn adapters; Read192 also needs automatic begin-End flow. Malice-only remains separately qualified; full Mark200 remains open. |
| High Roller rule/design | 093 | Resolve conditional one/two-target cardinality when the qualifying die roll arrives during responses, then bind declarations. |
| Partial Pox correction/evidence | 147 | Generic damage type is applied and its unchanged10/1,095 no-save diagnostic passed at `f5b53e63`. No durable qualified fixture; second-token/full-End/pay3/prevention/ResolveAbility and exact token-sourceUID/LKI remain unqualified/unobserved. No new Pox run or source attribution claim here. |

The four previously feasible authoring migrations are exhausted in their tested representative scope. Review these remaining approval, design/rule and evidence scopes before further execution; they are not all one094 blocker. Untested pitch siblings were neither migrated nor validated by this pass. Keep the Smashing Item-choice and older historical branch limitations in their existing records. **Breadth remains paused; do not restart it automatically.**

## Commands and recoverable evidence

From `C:\xampp\htdocs\TCGEngine`, targeted replay commands are:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=card-ebon-fold-four-live-original-correct-paid-destroy-before-response-hand-banish-shadow-draw-resolution-probe
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=card-captains-call-red-four-live-original-correct-mode-before-assets-and-real-next-attack-two-power
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=repair-memorial178-blue-four-live-declared-graveyard-head-jab-to-deck-top
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=repair-tip-off179-blue-four-live-target-before-discard-then-mark-captured-hero
& 'C:\xampp\php\php.exe' DevTools/FaB/wtr_test.php
```

The Tip compatibility is deliberately unsaved:

```powershell
& 'C:\xampp\php\php.exe' DevTools/FaB/CardRegression/record_card_regressions.php DevTools/FaB/CardRegression/card-regression-repair-tip179-ordinary-funded-attack-probe.json --probe-only
```

The optional user-owned broader command below was **not run**:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact "--log=$env:TEMP\fabsim-after-repairs-$(Get-Date -Format yyyyMMdd-HHmmss).log"
```

Recoverable source/generated beforeimages, candidate drafts, component programs, full local diagnostics and both Tip compatibility attempts remain outside the repository under `C:\Users\maxim\Documents\Codex\2026-10-19\task`. The four `nov20-{ebon078,captain103,memorial178,tip179}-guarded-application-and-emission` directories each have verified `before/` files and a backup manifest. Final metadata beforeimages are in `nov20-continuation-final-handoff-metadata-before`. Compare intervening edits before restoring any individual file; never bulk-reset the worktree. Compact useful proofs are retained in the repository without raw snapshot/log duplication. [Machine checkpoint](review-evidence/repair-continuation-20261120.json).

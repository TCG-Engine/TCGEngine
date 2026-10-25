# FaBSim 15-failure repair - 25 October 2026

The reported 15 failures are resolved. Final full-suite result: **3,363 total / 3,363 passed / 0 failed**, exit 0. The gameplay runtime remains `86b37a5e69d333081bd1eb0f983797b8fd1b1bd551d138c5a0821406b860fca0`; no new engine defect was found and no gameplay/Core/generated source change was needed in this investigation. Work is complete and stopped.

Baseline reproduced exactly **3,363 total / 3,348 passed /15 failed**. Every failure occurred at final snapshot comparison after its recorded actions and explicit assertions passed. The old expected snapshots lagged behind the previously reviewed visibility and ordinary-source lifetime fixes.

## Corrections and safeguards

Twelve fixtures (eleven Command and Conquer plus one Skybody Keikoi) incorrectly expected a destroyed card to remain private in the Graveyard. Corrected terminal `FaceDown1` to`0` and the same physical card's two saved-history copies: exactly 36 bytes across 12 files. Initial hidden-card states and activation restrictions remain unchanged. This follows [CR3.0.8 and the public Graveyard rules](https://rules.fabtcg.com/en/cr/03-zones/).

Three fixtures (Laden with Lightning, Talisman of Tithes and This Round's on Me) retained the old chronology where the resolving action left the stack before its effects finished. Corrected only the reviewed terminal event-history order/source-reference fields, plus Tithes' item-before-action Graveyard order. Effects, resources, ownership, damage, draws and card membership remain pinned. No global sorting, new normalization, ignored fields or weakened oracle was introduced.

Independent reviewers checked all actual-vs-expected bytes and parsed changes against card text, rules and implementation. All **220 actions and 4,899 explicit assertions** in these 15 fixtures are byte-identical. Only 15 `expected_final_gamestate.txt` files changed; every other byte in all 3,363 five-file fixtures is preserved. Existing review provenance and recoverable old copies remain available. See [independent delta approvals](review-evidence/failure15-20261025-approved-snapshot-delta-review.json).

| Existing scenario | Corrected expectation |
| --- | --- |
| Command and Conquer / real Sigil Hand response before hit destruction | Public Graveyard visibility |
| Command and Conquer / ordinary / hand-floating-overfunded | Public Graveyard visibility |
| Command and Conquer / ordinary / hand-yellow-pitch-exact | Public Graveyard visibility |
| Command and Conquer / ordinary / hand-blue-pitch-one-surplus | Public Graveyard visibility |
| Command and Conquer / ordinary / hand-two-red-pitches-exact | Public Graveyard visibility |
| Command and Conquer / ordinary / hand-one-floating-plus-red-exact | Public Graveyard visibility |
| Command and Conquer / ordinary / arsenal-facedown-floating-exact | Public Graveyard visibility |
| Command and Conquer / ordinary / arsenal-faceup-floating-exact | Public Graveyard visibility |
| Command and Conquer / ordinary / partial-three-damage-still-destroys-arsenal | Public Graveyard visibility |
| Command and Conquer / ordinary / hero-hit-destroys-both-legal-new-horizon-arsenals | Public Graveyard visibility |
| Command and Conquer / mandatory when-hit trigger / arsenal-present | Public Graveyard visibility |
| Activation guard / face-down Skybody Keikoi still activates | Public Graveyard visibility |
| Laden with Lightning / four live sparse positive effect | Resolution/source-clear chronology |
| Talisman of Tithes / four live first-opponent draw replacement / unordered graveyard membership | Resolution/source-clear chronology |
| This Round's on Me / four live sparse positive effect | Resolution/source-clear chronology |

## Validation and current coverage

Exactly two full-suite invocations were made: the failing reproduction and one final verification. Fifteen isolated candidate captures enabled review before canonical replacement; fifteen clean focused comparisons then passed. The final full verification passed all 3,363 cases. Complete diagnostics and hash receipts are linked in [validation](failure-repair-validation-20261025.json); the [canonical final full report](card-regression-replay-failure15-20261025-final.json) retains all runner output.

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim
```

Saved scope remains 3,363 scenarios: 3,074 duels and 289 genuine four-live cases; 522/5,196 exact primary identities and 396/3,324 cycles or standalone groups. No new card/cycle coverage was added. All 3,363 now have final-runtime compatibility evidence; 37 current fixture hashes have at least two clean comparison passes. This does not repeat or expand historical semantic review, inherit coverage to pitch siblings, or certify exhaustive card behavior. The strict finite-completion gates remain separate; a second clean full run was not performed merely to satisfy that classifier.

Authoritative current lookup: [card registry](card-regression-coverage.json), [cycle index](cycle-coverage-current.json), [review registry](card-regression-review.json). The [24October handoff](REPAIR_HANDOFF_20261024.md) remains a dated account of the earlier repair phase, not today's verification status.

## Local handoff

Changes remain local and uncommitted. No staging, commit, push, deployment, fixture reorganization, new coverage batch or unrelated DFGSim edit occurred. The five previously modified FaB source files remain exactly as before this investigation; generated PHP/JS remain untouched. Diff whitespace checks passed.

Raw baseline/final logs and per-fixture original files are outside the repository at `C:\Users\maxim\Documents\Codex\2026-10-19\task` under `fab-full-suite-20261025-baseline`, `fab-full-suite-20261025-final` and the short directories mapped by `fab-failure-snapshot-candidates-20261025/receipt.json`. Registry/metadata recovery copies are in `fab-failure15-20261025-metadata-backup`. The repository retains runnable fixtures, compact independent approvals, focused/full replay receipts, coverage records and this assessment.

No failure remains from the reported 15. Existing limits remain: Skyzyk/Visit the Blacksmith/Peaceful Sanctuary source gaps; Potion of Luck shuffle and Slog rejection-recording limitations; legacy/alternative Intimidate, broader ownership/targets and untested pitch branches; and the historical 590-entry review caveat. A passing corpus does not remove those documented coverage limits. The user can review and select the FaB diff for a commit separately from their DFGSim work.

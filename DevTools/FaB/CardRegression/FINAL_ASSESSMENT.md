# FaBSim regression handoff — 20 October 2026

The frozen corpus has **3,079 saved and qualified scenarios**, all independently supported within their named scope and passing two clean replays of the same gameplay runtime. It provides useful depth around ordinary play, payment, reactions and several repaired timing defects. It is still narrow catalog coverage, and four-player coverage is a five-scenario pilot.

Work is consolidated and stopped. No push, deployment, publishing, test deletion or bulk rewrite was performed. Authorized recoverable cleanup removed only 1,597 review files after verified external backup. All changes remain local and uncommitted; the pre-existing `debug.log` edit is preserved.

## Coverage and where to inspect it

| Measure | Frozen result |
| --- | ---: |
| Saved / qualified scenarios | 3,079 / 3,079 |
| Two-player scenarios | 3,074 |
| Four live-player scenarios | 5 |
| Primary subject identities with verified branches | 239 / 5,196 |
| Complete declared finite baselines / partial / untested identities | 75 / 164 / 4,957 |
| Cycles or standalone groups with a tested primary subject | 114 / 3,324 |
| Groups without a tested primary subject | 3,210 |
| Implementation-candidate identities / groups still without a tested primary subject | 4,575 / 2,891 |

The 5,196 identities include distinct pitch variants; 17,188 printing records are metadata, not additional card implementations. The 4,811 implementation candidates are a source-based census, not a guarantee that each card works. Three tested subjects lack that census flag; its denominator has not been silently changed. The 3,324 groups include pitch cycles and standalone identities, with exceptional names and tokens kept distinct. A representative test does **not** validate untested pitch variants. These are breadth counts, not an overall completion percentage.

Start with [COVERAGE_GUIDE.md](COVERAGE_GUIDE.md). The authoritative machine index is [card-regression-coverage.json](card-regression-coverage.json): `cards[].card_id`  to  `effect_branches[].id`  to  `recording_test_path`, exact `fixture_sha256`, independent review and runtime-bound replay results. [card-regression-checkpoint.json](card-regression-checkpoint.json) freezes qualification; [card-regression-completion-scopes.json](card-regression-completion-scopes.json) defines the finite baselines. `complete_declared_baseline` does not mean every possible interaction.

Physical layout: all 3,079 canonical folders are flat under `Tests/Integration/FaBSim/`; none is in the application `FaBSim/` root. No fixture was relocated. The guide outlines a possible nested cycle layout and the coordinated loader/recorder/inventory/path/replay changes it requires.

The five genuine four-player cases are Sigil of Solace, Strike Gold, Aether Dart, Seek Horizon and Read the Runes. They use four living heroes, manually exercised priority and explicit per-seat checks; the Strike Gold case has responses from players 2, 3 and 4, and Dart has an actual player-4 Spellvoid decision. Merely serialized empty player slots do not count. Other support cards, heroes and created tokens are not automatically counted as primary tested identities. None of the existing two-player matrices was deleted or converted.

The earlier matrix strategy averaged12.88 scenarios per primary identity. Its largest eight batches total1,242 cases: plain arcane226, Boost213, buffs198, ordinary attacks143, Pummel/Razor135, mandatory discard111, Arsenal bonuses108 and defense reactions108. Slug-shape normalization identifies1,634 additional pitch-variant instances; this is a diagnostic count, not proof they are dispensable.

## Defects and remaining limits

The audit conservatively groups **26 applied gameplay fixes** by mechanism, rather than counting every affected card, pitch, symptom or file. All 26 are qualified within their independently reviewed bounded scopes on the frozen runtime; zero applied groups remain pending whole-corpus qualification. Two additional Gorganian source/component defects remain open and unapplied, with no native before/after claim. Examples include arrow/bow legality, asset payment before Flock reveal / Boost banishment, responseable hit triggers, source/controller attribution through a choice, Command and Conquer's hit layer, Lunging Press target continuation, and two FaB four-seat integration repairs. The latter use FaB `GameLogic.php`; the existing Core decision-queue controller was not edited.

Two historical runner defects (fresh initial-state loading and persisted-state observation) and two representation guards in the new fault-control helper (blank decision variables and prepare-queue tails) are separate from those 26 gameplay groups. Authoring-oracle corrections are also separate: physical blockers/final damage instead of stale cached CnC defense, complete target descriptors, legitimate turn bookkeeping and legal scenario sentinels. UTF-8 capture and legacy review-proof schema compatibility are additional harness/metadata maintenance, separate from the 26 gameplay groups. No expected damage was reduced to make a failing card pass.

[card-regression-issues.json](card-regression-issues.json) preserves before-fix evidence and bounded resolutions. `FAB-REG-029`–`033` cover the later CnC, Lunging, token-hit, held-priority and configured-seat repairs. `033` has source/component evidence for the before mechanism and native after evidence; there is no invented native before recording. The token dispatcher has a twelve-identity source whitelist; twelve typed scenarios do not prove twelve distinct pitch variants.

Known open work:

- **Gorganian Tome (`034`) remains unapplied and uncovered.** Missing printed +1 and premature source movement can cancel into a misleading draw total. Evidence is source/component only; there is no native passing Tome fixture. The held candidate also has foreign-owner/regeneration and suppression concerns.
- **Lunging Press is fixed within its tested scope.** All 31 full cases, five rejection cases and one announcement-only probe pass both full replays. Current/prior/opponent attack-action targets, UID-distinct duplicates and real Rout departure/fizzle are covered. The early probe stops with Lunging pending, so earns no resolution or cleanup credit. Remaining holds include public targets outside CombatChain, same-UID/same-link reentry, foreign source/suppression/copy, four-player elimination, exceptional save/Undo/compaction and universal duration handling.
- Generic awaited-ability lifetime, legacy source identifiers, ownership/departure, dynamic ability loss/restoration and broad multiplayer interactions remain outside the bounded fixes. Five four-player endpoints do not establish UPF completeness.

The legacy standalone coverage builder omits nine reviewed suffix inputs and its qualifier misses the four-suite filename. Final reconciliation and strict post-cleanup checks explicitly include these inputs; blind regeneration can underreport this frozen registry. That maintenance limitation remains documented instead of changing more tooling during the bounded finish.

## Frozen execution evidence

Gameplay runtime: `561899c9b57e783baf4a1771b553db6e7ef1e5d283a914d60241b90f13c420bc`. All 3,079 five-file fixtures were preserved during the final runs. No snapshot-update mode was used.

| Command / check | Result and evidence |
| --- | --- |
| `php DevTools/RunIntegrationTests.php --root=FaBSim` twice | 3,079/3,079 each, exit 0; `card-regression-replay-full3079-561899-run1.json` and `run2.json` |
| `python DevTools/FaB/CardRegression/card_regression_negative_controls.py` | 77/77 intended assertion failures detected; latest report and immutable `negative-control-runs/20261020T152737Z-583cdfbc-after.json` |
| Same command with `FAB_FOUR_LIVE_CONTROLS` selecting the four documented controls | 4/4 intended failures detected; `card-regression-four-live-negative-control-results.json` |
| `php DevTools/FaB/wtr_test.php`, `arc_test.php`, `upf_test.php`, `shortcuts_test.php` | All exit 0 on the frozen gameplay runtime; `card-regression-native-four-suite-four-live-priority-pilots-561899.json` |
| Existing Lunging expected-rejection CLI/UI all/next contract | 30 paths pass: 15 positive and 15 negative detections, 2,376 checks; separate from saved-card counts |

The two replay report SHA-256 values are `2f253c0b0c6eccaa79ca68ae6bd8aa813f3fa96c635bf476a8275fd9466fdbb4` and `f1bc7eb1c4c813939f5c1f7426c1930f2d3b5ce9c7a83d619a5219a671a9beec`. The default-control report is `8902462267474a15d0f14676d1b5f174feeff735d2794181d27139593b76c5e8`; the multiplayer-control report is `afd43c92acc906706b811038e704e335240357a568ba7d59fb136dbe98b81d16`.

The first two multiplayer control attempts stopped at overly strict helper representation guards. Those failed receipts remain preserved. The final retry passed after narrowly reviewed guard corrections; assertions and mutations were retained. Initial consolidation metadata drafts also exposed legacy filename/runtime bindings and stale incoming review overlays; these were corrected against existing exact evidence, without new game behavior or relaxed assertions. Independent final source/evidence review and strict post-cleanup qualification are recorded in the final evidence index. No assertion was weakened.

## Changed files and repository state

There are 25 modified tracked paths and two untracked top-level trees (`DevTools/FaB/CardRegression/` and `Tests/Integration/FaBSim/`); nothing is staged. All source and test changes are uncommitted. The count of tracked paths includes the preserved pre-existing `debug.log` edit, whose contents are not attributed to this task.

- **Repository Core/test infrastructure:** `Core/RegressionTestFramework.php`, `DevTools/RunIntegrationTests.php`. Core changes support recorder/assertion/replay behavior; there is no changed cross-game Core gameplay controller.
- **FaB shared rules/integration:** `FaBSim/Custom/GameLogic.php`, `CardChoices.php`, shared helper sections of `WTRAbilities.php`/`WTRCards.php`, and `Schemas/FaBSim/GameSchema.txt` for action-point rule support. Some files combine shared logic with card-family implementations.
- **FaB card/family implementations:** Custom `ARCAbilities.php`, `ARCCards.php`, `CRUCards.php`, `DTDCards.php`, `ELECards.php`, `EVORuntime.php`, `MONCards.php`, `MSTCards.php`, `OUTRuntime.php`, `ROSRuntime.php`, `SUPCards.php`, `UPRCards.php`, `WTRAbilities.php`, `WTRCards.php`.
- **Canonical authoring/generator support:** `DevTools/FaB/build_cru_abilities.py`, `cru_abilities.json`, `zzGameCodeGenerator.php`; guidance in `.github/copilot-instructions.md`. Generated files were not manually edited.
- **Test tooling and artifacts:** `DevTools/FaB/smoke.php`, the new CardRegression tooling/manifests/docs/evidence tree, and the native five-file scenario folders. Final tooling corrections include explicit UTF-8 capture and opt-in four-player fault controls. The ordinary default control set remains unchanged.

## File volume and completed cleanup

The final retained snapshot is recorded in `final-evidence-index.json`. The stable pre-consolidation audit at 15:30:18 UTC counted 18,290 untracked files / 534,028,973 bytes. Of those, **15,395 files / 80,092,846 bytes are the 3,079 canonical scenarios × five files**. The runner uses `initial_gamestate.txt`, `actions.json`, `assertions.json`, `expected_final_gamestate.txt` and `meta.json`; removing these loses or breaks saved tests.

At that snapshot `source-review-evidence/` contained 1,615 files / 167,752,292 bytes. The ordinary replay runner does not read it. Coverage/checkpoint/issues and historical reconstruction records do reference parts of it. `review-evidence/` is a different directory: the coverage qualifier directly checks referenced review proofs and their hashes. Byte-identical duplicates are not automatically disposable because exact paths are referenced.

The maintainable deliverable is the canonical source fixes, existing native fixtures, reusable recorder/runner/control tools, current coverage/scope/issues/checkpoint records, the two current replay reports, current controls/suites and a compact retained proof index. Intermediate proposal copies, repeated inventories and historical reconstruction bundles can be packaged outside Git after dependency review. The authorized dependency-safe cleanup removed 1,597 files /167,690,037 bytes in two passes after verifying every external backup file. See [REPOSITORY_HYGIENE.md](REPOSITORY_HYGIENE.md) and [archived-review-artifacts.json](archived-review-artifacts.json) for exact paths, hashes and restoration. Source-review evidence now retains only 18 meaningful files /62,255 bytes: five current issue reports and thirteen apply receipts. Historical links are explicitly archive-backed; incidental metadata links did not force retention of bulk copies. No tests were pruned, and no ignore rules were added.

## Prioritized next steps — not executed

1. Review the local source/test diff and the completed cleanup receipt; preserve its external backup. Keep all existing tests. Before regenerating coverage, correct the legacy builder's nine suffix-input omissions and qualifier suite-filename inventory; the frozen registry used those explicit inputs and is authoritative.
2. Address Gorganian's precise count/lifetime defect pair without accepting numerical cancellation; retain the untested candidate as held until independently reviewed and exercised.
3. If coverage work resumes, default to four live players and one representative per pitch cycle. Add a case only for materially distinct behavior or a specific defect; track untested variants explicitly.
4. Broaden cycles and multiplayer lifecycle/ownership/elimination coverage before treating these depth-heavy ordinary matrices as broad game correctness.

No further execution follows this bounded handoff. No action is denied, pending or of uncertain outcome. Known defects and test/rebuild limitations above are the remaining work, with no further execution scheduled.

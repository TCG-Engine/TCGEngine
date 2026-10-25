# FaBSim repair handoff - 24 October 2026

The bounded repair wrap-up is complete with focused passing evidence. Source and generated runtime are active; no execution blocker remains. The local change is ready for review, with a full-corpus compatibility run still required before claiming merge readiness. Work has stopped; no new breadth is planned in this handoff.

Final runtime: `86b37a5e69d333081bd1eb0f983797b8fd1b1bd551d138c5a0821406b860fca0`. Git HEAD at restart: `897ba7d9732fb0a3cf0301ca250cd955b74ff101`.

## Coverage and evidence boundaries

| Measure | Retained saved / independently reviewed | Fresh final-runtime targeted qualification |
| --- | ---: | ---: |
| Scenarios | 3,363 | 22, each with two matching clean replays |
| Two-player scenarios | 3,074 | 0 rerun |
| Genuine four-live scenarios | 289 | 22 |
| Exact primary identities, including separate pitch variants / 5,196 | 522 | 22 |
| Pitch cycles or standalone groups / 3,324 | 396 | 22 |
| Genuine four-live groups | 287 | 22 |

The original 3,341 scenarios retain their historical qualification at runtime e947; they were not replayed or semantically re-reviewed after these shared changes. The 22 new repair cases add21 previously unrepresented exact identities and20 groups. These are positive representatives, not complete-card or sibling-pitch certification. The 3,002 implementation-candidate groups are discovery hints, not validated implementation. The historical 590-entry family-review caveat remains unchanged. The 75 frozen finite baselines retain their old scoped evidence; the strict current full-completion gate remains zero because broad current validation is intentionally absent.

Authoritative lookup: [card registry](card-regression-coverage.json), [cycle index](cycle-coverage-current.json), [exact independent reviews](card-regression-review.json), [current validation](repair-validation-20261024.json), and [bug/hold reconciliation](repair-status-20261024.json). In `cards[]`, find `card_id`, then its scenarios/branches/source specifications and review/replay evidence; in `groups[]`, find `group_id`, exact primary members, untested members and representative slug. Each slug maps to `Tests/Integration/FaBSim/<slug>/` and its five runner files. `secondaryCardIDs` never award primary coverage.

Examples: Gorganian Tome's `identity:gorganian_tome` representative draws four from three other graveyards after all four live seats pass; its 100 original assertions plus10 explicit initial-state guards are retained. The Smash Instinct red representative follows captured-target Intimidate through its separate beginning-of-end-phase return and the next turn. Other pitch members and alternatives receive no inherited credit.

The 22 exact primary subjects are: gorganian_tome, nip_at_the_heels_blue, shred_red, talisman_of_warfare_yellow, abyssal_bite_blue, blasmophets_boon_blue, flying_high_blue, head_shot_red, icy_encounter_red, tome_of_harvests_blue, rouse_the_ancients_blue, chorus_of_rotwood_red, battle_prep_red, opal_amulet_blue, potion_of_deja_vu_blue, runechant, regain_composure_blue, bloodtorn_bodice, laden_with_lightning_red, rousing_aether_red, aether_quickening_red, smash_instinct_red.

## Repairs and remaining holds

The 16 previously applied repair work groups are now active and represented by focused four-live tests. Two additional Quickening groups were applied; the Intimidate group also gained its delayed-return repair. These 18 overlapping work groups are not18 independent new bugs and must not be added mechanically to the 27 historically grouped fixes. The 42-entry issue ledger includes harness/oracle entries, historical scopes and overlapping symptoms. New issue IDs040/041/042 describe Quickening classification, post-await caster attribution and Intimidate delayed return.

| Repair work group | Named native representatives (overlap) | Status |
| --- | ---: | --- |
| Own inline go again parser | 1 | Qualified within named representatives; alternatives untested |
| Public Graveyard visibility and responseable Warfare trigger | 1 | Qualified within named representatives; alternatives untested |
| Gorganian printed addend and source/GA lifetime | 1 | Qualified within named representatives; alternatives untested |
| Nip/Shred announcement-time physical target binding | 2 | Qualified within named representatives; alternatives untested |
| Boon combat base uses controlled Blasmophet | 1 | Qualified within named representatives; alternatives untested |
| Flying High pitch-specific next-attack bonus | 1 | Qualified within named representatives; alternatives untested |
| Head Shot public-load trigger and captured entry | 1 | Qualified within named representatives; alternatives untested |
| Icy hit trigger and creator-owned Frostbite | 1 | Native hit/creator branch qualified; Smoldering replacement-await ownership component-tested only |
| Tome/Rouse and Bodice asset-before-effect costs | 3 | Qualified within named representatives; alternatives untested |
| Bounded 17-ID ordinary spell source/AP lifetime | 8 | Qualified within named representatives; alternatives untested |
| Opal/Deja ability and marked Runechant proxy lifetime | 3 | Qualified within named representatives; alternatives untested |
| Runechant source survives until its queued trigger resolves | 1 | Qualified within named representatives; alternatives untested |
| Deja Vu stages declared physical Pitch cards | 1 | Qualified within named representatives; alternatives untested |
| Regain hero untap is a captured hit trigger | 1 | Qualified within named representatives; alternatives untested |
| Named attack Intimidate captured-target trigger, marker survival and responseable beginning-of-end-phase per-invocation return | 1 | Qualified within named representatives; alternatives untested |
| Embodiment/Laden token grant resolves on a separate layer | 1 | Qualified within named representatives; alternatives untested |
| Quickening omitted from Crucible arcane-card classifier | 1 | Qualified within named representatives; alternatives untested |
| Quickening damage attribution after opposing barrier await | 1 | Qualified within named representatives; alternatives untested |

Wrong expectations were rejected rather than recorded as passing: the two initial wizard drafts and stale Smash snapshot have been superseded; earlier incorrect Warfare and Tome fixtures remain outside discovery in `held-incorrect-gameplay/`. Gorganian's guard-only strengthening preserved all actions, initial/final state and the original100 assertions. It is a valid superseded version, not an incorrect-gameplay quarantine. No confirmed newly identified defect remains unresolved within the tested repair scope.

Remaining limits: Skyzyk red, Visit the Blacksmith blue and Peaceful Sanctuary red have bounded implementation/source gaps; Potion of Luck's multi-card shuffle determinism and Slog's mode10002 rejection recording remain unsupported/unproved. Legacy flat Intimidate saves lack source/controller/group data and retain an unqualified late-cleanup fallback. Anonymous legacy sources, simultaneous group ordering, eliminated-controller behavior, wider targets, ownership/copy/dynamic restoration and untested pitch siblings remain unqualified. Rune Flash was supporting evidence only; Pack Hunt's shared route has no new independent primary case. Lunging Press retains only its earlier bounded evidence; no new branch coverage is claimed here.

## Validation performed

The original 44 targeted passes are preserved. Only Gorganian Tome was rerecorded and replayed twice after its ten initial guards were added:46 passing runner invocations total,22 current fixture hashes each supported by two clean passes. All seven native controls detected their intended first failure, retained 3,416 suffix assertions and omitted none. The Smash fault checks its original target binding; the45-action return follow-through is a positive witness, not a separate native return fault. Current cases contain 524 supported actions and 19,159 assertions; 449 manual passes exercise all four seats.

Final ELE, EVR, IAR and shortcut families passed (including their inherited smoke/UPF/WTR/ARC scopes). Gorganian's36 actual-function component profiles/541 assertions passed and both in-memory faults failed as intended. Intimidate's10 marker contracts/15 checks and20 group contracts/29 checks passed. Five actual persisted await prefixes were independently inspected at their earlier capture runtime; that evidence was not relabeled as final-runtime execution. Stable32 keyword and19 payment contracts plus the reaction component proof were reused with explicit function-hash preservation, not rerun as new coverage. Component doubles supplement native action recordings and do not certify every branch.

Focused replay command (repeat only an affected case when needed):

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=<scenario-slug>
```

Recommended full compatibility command, **not run by the agent**:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim
```

The Card Code snapshot service returned HTTP200 with 6,261 rows. Guarded macro-only generation succeeded, and independent comparison found exactly 10 changed top-level units, no added/removed units. Generated PHP SHA: `ab56ba30ed55b339fce05cf0c47dedc1c2a268ca911d1fabc0927e2507913241`. Only the two expected macro outputs changed; generated files were never hand-edited. Source-generator changes are already in HEAD and must remain the durable implementation.

## Local diff, retained artifacts and recovery

Compared with restart HEAD, the remaining gameplay diff is five files, 105 insertions/18 deletions. Repository `Core/`: no new change in this repair resumption. FaB shared rules: `FaBSim/Custom/GameLogic.php` (Intimidate grouping/movement/phase flow), `ROSCards.php` (return dispatch). Card-family implementations: `WTRCards.php` (captured source), `ARCCards.php` (Quickening classification), `UPRCards.php` (post-await caster). The earlier 16 repair groups and generator support were already present in the user's HEAD. Generated macro outputs are ignored build artifacts.

Test infrastructure retains the 22 five-file fixtures (110 required files), three declarative source batches, small registered semantic proofs, eight targeted replay reports, the seven-control report, authoritative coverage/issue records, two reusable Intimidate component checks and this compact handoff. Scratch proposals, raw detailed component receipts, intermediate generation comparisons and recovery copies stay outside the repo. No fixtures were reorganized or pruned, and no new `source-review-evidence` bulk was added.

External backup root: `C:\Users\maxim\Documents\Codex\2026-10-19\task\repair-source-backups-20261024`. Important subdirectories: `macro-generated-outputs-attempt1`, `stale-unregistered-smash193`, `final-evidence-registration`, `issue-ledger-reconciliation`, `gorganian-initial-guard-strengthening`, `final-handoff-promotion`. Earlier strict patch-helper backups are also in `repair-source-backups-20261022/` under tags ending20261024. Each has a hash receipt; restore by copying verified saved files to the recorded original paths. Backup paths are historical recovery material, not runner inputs.

All original 16,705 fixture files were verified byte-identical; subsequent writes touched only the new Gorganian fixture and metadata/tooling. Changes are local and uncommitted; nothing was staged, pushed, deployed or published. Unrelated DFGSim user changes remain outside this work and must not be staged with it.

Next steps: review the focused diff and the bounded compatibility limits, run the full command when desired, then select the FaB source/tests/metadata for a commit while excluding DFGSim work and local recovery artifacts. Broad compatibility is the material remaining readiness gate; this handoff does not claim complete catalog or exhaustive rules coverage.

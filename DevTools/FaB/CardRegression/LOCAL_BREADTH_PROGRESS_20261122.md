# Local FaBSim breadth progress — 22 November 2026

> **Latest local continuation through Batch1013:** 4,755 scenarios (3,075 two-live + 1,680 four-live), 1,777/3,324 groups; 479 new groups since Batch882. Local/uncommitted; no routine review gate. [Current results and next step](BREADTH_FROM882_20261122.md).





















Continuation is authorized; this is a durable checkpoint, not a user review gate or catalog completion. Work is isolated in `C:\Users\maxim\AppData\Local\Temp\fm1122` at unchanged head `aa67228188d97c1437e4785e471fc44daece8f17`. No engine/framework source changed. No full suite, staging, commit, push, merge, PR, deployment, Card Code authentication or credential/configuration change occurred. The original checkout, its index and all 2,095 baseline dirty files were verified unchanged.

| Metric | Before continuation (after874) | Current |
| --- | ---: | ---: |
| Saved and qualified scenarios | 4,212 | 4,276 |
| Two-live | 3,075 | 3,075 |
| Four-live | 1,137 | 1,201 |
| Represented groups /3,324 | 1,234 | 1,298 |
| Tested primary identities /5,196 | 1,360 | 1,424 |

**64 additional scenarios across 64 previously uncovered groups; all genuinely four-live.** Including preserved Batch874, 74 local fixtures were added after the published refreshed head. Every representative uses natural initial hero health, actual owned cards, native costs/choices and manual priority inputs from all four seats. Vortex exercises all four players' own Arsenal choices; the token replacement cases act across multiple seats. Other cases mainly use caster/P4 effects while the other players respond; exhaustive multiplayer strategies are not claimed.

## Qualified batches

| Batch | New groups / fixtures | Native actions | Persisted assertions | Final revision replay |
| --- | ---: | ---: | ---: | --- |
| [874](BREADTH_BATCH874_20261122.md) | 10 | 68 | 917 | 10/10 PASS |
| [875](BREADTH_BATCH875_20261122.md) | 10 | 67 | 911 | 10/10 PASS |
| [876](BREADTH_BATCH876_20261122.md) | 9 | 58 | 789 | 9/9 PASS |
| [877](BREADTH_BATCH877_20261122.md) | 10 | 63 | 857 | 10/10 PASS |
| [878](BREADTH_BATCH878_20261122.md) | 10 | 64 | 840 | 10/10 PASS |
| [879](BREADTH_BATCH879_20261122.md) | 9 | 54 | 790 | 9/9 PASS |
| [880](BREADTH_BATCH880_20261122.md) | 9 | 56 | 752 | 9/9 PASS |
| [881](BREADTH_BATCH881_20261122.md) | 7 | 44 | 674 | 7/7 PASS |

- **875**: `burn_bare`, `aether_icevein_red`, `freezing_point_red`, `icebind_red`, `polar_cap_red`, `etchings_of_arcana_red`, `eternal_inferno_red`, `overflow_the_aetherwell_red`, `gaze_the_ages_blue`, `lesson_in_lava_yellow`.
- **876**: `become_the_shadow_lord_blue`, `dimenxxional_ferryman_blue`, `enshrine_sin_red`, `deadwood_dirge_red`, `chart_the_high_seas_blue`, `emboldened_blade_blue`, `dimenxxional_vortex`, `evo_sentry_base_head_red`, `evo_sentry_base_chest_red`.
- **877**: `forbidden_harvest_yellow`, `goldwing_turbine_red`, `hit_the_gas_blue`, `hook_blue`, `line_blue`, `look_within_blue`, `portside_exchange_blue`, `pull_from_beyond_red`, `phoenix_bannerman_arms_red`, `prayer_of_bellona_yellow`.
- **878**: `phoenix_bannerman_chest_red`, `phoenix_bannerman_head_red`, `phoenix_bannerman_legs_red`, `pour_the_mold_red`, `restvine_elixir_red`, `rake_the_embers_red`, `read_the_glide_path_red`, `re_charge_red`, `painful_passage_red`, `ominous_excavation_blue`.
- **879**: `evo_sentry_base_arms_red`, `evo_sentry_base_legs_red`, `evo_charging_rods_yellow`, `evo_energy_matrix_blue`, `evo_rapid_fire_blue`, `evo_scatter_shot_blue`, `construct_bank_breaker_yellow`, `renounce_violence_blue`, `rip_up_their_virtues_blue`.
- **880**: `sea_floor_salvage_blue`, `shadowrealm_solace_blue`, `shadowrealm_strength_red`, `shadowrealm_swiftness_yellow`, `sinker_blue`, `song_of_sinew_yellow`, `starlight_road_blue`, `strategic_planning_red`, `sutcliffes_research_notes_red`.
- **881**: `scrap_trader_red`, `save_the_thought_red`, `settle_the_bill_red`, `tome_of_duplicity_blue`, `teklovossens_workshop_red`, `twintek_charging_station_red`, `visit_the_boneyard_blue`.

One representative per pitch cycle/standalone group; no support-card, untested sibling, alternate-branch or whole-card credit. Equipment cases pin physical transformation/materials, not later static weapon bonuses or activations. Several spell representatives deliberately cover base packets with fusion/Surge false; their positive conditions are untested. Likewise, the named card movement/Opt branches do not certify later attack buffs, token end phases or deferred draws. These are partial first representations, not fully tested cards or percent completion.

## Findings and limitations

- **FAB-REG-203 — Codex of Bloodrot**: four native own-Hand choices completed, then P1 had Bloodrot Pox instead of its Ponder. Correct expectations retained; no fixture saved, no coverage credit. [Held source](card-regression-sparse876-codex-held-20261122.json), [evidence](review-evidence/sparse876-codex-held-20261122.json). Producer actor-context root remains unisolated.
- **FAB-REG-205 — Song of Larinkmorth White**: P2's actual Frostbite is owned by2 instead of the instructed creator1. First failed ownership query is retained; later token ownership queries were not reached. No fixture saved or credit. [Held source](card-regression-sparse880-song-held-20261122.json), [evidence and official rule](review-evidence/sparse880-song-held-20261122.json). [CR1.3.1a](https://rules.fabtcg.com/en/cr/01-game-concepts/) assigns created-card ownership to the player instructed to create it. This is an existing ownership producer-family extension, not a newly claimed distinct shared root.
- **FAB-REG-204 — replay limitation**: search's `FaBShuffleDeck()` calls `EngineShuffle(deck,true)`, which uses fresh `random_int()` entropy. Phoenix Bannerman Arms passed its native card assertions but its first replay swapped two irrelevant remaining deck cards and failed snapshot comparison. Only four inspected local search fixtures were amended to a single irrelevant remainder, retaining actual eligibility, native search/choice and physical movement checks. Original five-file copies were hash-verified before replacement; four directly affected replays passed. No shuffle-randomness, production RNG change, historical-corpus audit or broad determinism certification is claimed. [Evidence](review-evidence/search-strong-entropy-replay-limitation-20261122.json). Backup and restoration instructions: `C:\Users\maxim\Documents\Codex\2026-10-19\task\search-replay-boundary-20261122`.

There are **two new confirmed card failures, zero gameplay fixes in this continuation**, plus the separate replay limitation. Existing denied/held repairs remain untouched. Other corrected attempts were fixture assumptions: Portside now uses legal Pirate Gravy Bones to isolate it from Marlynn's legitimate draw trigger; Construct's interim Arena oracle includes the persistent Construct source while its choices remain pending. Final physical outcome assertions were preserved.

## Validation and coverage lookup

Routine cases received one independent cached-text/source-spec batch review and one clean targeted replay per final fixture revision. Root verified exact source-to-saved assertions/actions, raw five-file hashes, manual seat inputs and terminal state. No duplicate independent saved-file review or new native fault controls are claimed. Since resuming: **68 targeted replay invocations, 67 pass and1 retained old-revision shuffle failure**; this includes four directly affected search amendments. Current named revisions all pass. Unchanged prior checks were reused, not rerun. Runtime remains `54409edb1ba1e0691c3beeb648e8ce984a09074b3339b7e303e53c61154e1494`.

Accumulated totals include qualifications at their stated historical runtimes. They are not a new whole-corpus result on current main. 2,026 groups still lack first representation in the current indexes, and untested pitch siblings remain unverified.

Authoritative lookup: map an ID through [cycle group map](cycle-group-map.compact.json), then consult the union of [frozen coverage](cycle-coverage-current.json) and [current additions](coverage-additions-after218.json). `tested_primary_ids`, `scenario_ids`, `effect_branches`, `players` and `coverage_status` distinguish exact tested members and named branches. [Review registry](card-regression-review.json) is keyed by scenario ID and points to exact proof hashes. Scenario IDs are directories under `Tests/Integration/FaBSim/<scenario-id>`; each contains the runner's five required files. Pitch variants are distinct catalog IDs grouped for first representation; reprints that share a canonical ID receive no separate scenario credit.

Runner command used separately for each new/modified exact scenario ID:

```powershell
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --test=<exact-scenario-id> --max-failures=1 --compact --log=<new-local-log>
```

No full-suite command was executed. Complete native attempt logs, review drafts and backups remain outside the proposed patch under the workspace; repository files retain runnable five-file fixtures, compact source/checkpoint/proof/replay records and the two no-save reproducers. Next continuation is **Batch882**, selecting new groups from the current union; no routine approval is needed. No CLI operation is left in flight at the last verified checkpoint.

## Continuation point

This turn ends at its working-context budget after seven successive batches; no global execution blocker or new user approval is required to continue. Batch882 has not been authored or executed. Visit Goldmane Estate remains an unexecuted source-only timing concern; it is listed separately in the coverage additions index, without a fixture or credit. Do not retry held repairs or expand this source-only concern into a broad repair pass.

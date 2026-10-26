# Sparse four-player first pass

<!-- SPARSE-CURRENT-20261025 -->
> **26 October, latest registration batch254-canonical-combat-windows:** 3,521 saved/registered scenarios (3,074 duels +447 four-live), 677 recorded primary IDs/5,196 and 551 groups/3,324. This phase qualified 158 scenarios across 155 groups at their stated runtimes. 8 new gameplay defect groups fixed, plus 8 existing-family extensions. No full-suite run. [Progress and current lookup](SPARSE_PROGRESS_20261025.md) preserves exact branches, untested siblings and historical contexts.

<!-- REPAIR-HANDOFF-20261025 -->
> **25 October compatibility repair: final full suite3,363/3,363 passed,0 failed.** The15 reproduced failures were stale expected snapshots; all actions/assertions and the gameplay runtime were preserved. See [current assessment](FAILURE_REPAIR_20261025.md) and [validation](failure-repair-validation-20261025.json). Coverage remains522 exact IDs/396 groups; historical dated evidence below keeps its original scope.

Resumed 20 October 2026. Future coverage uses one positive representative per previously uncovered pitch cycle or standalone group. Existing matrices stay intact. Each new scenario uses four living owned young heroes, manual priority and actual supported actions; an all-player effect must also pin each affected seat. Pitch siblings and support cards receive no primary credit.

<!-- INCREMENTAL-BEGIN -->
## Incremental progress

Metadata refreshed 2026-10-22 21:49 UTC, runtime `e94720ede760f8364583450069c93d68771f7311a641aeab5f0f790c1539df27`. Frozen historical checkpoints below retain their original inventories and dates.

| Measure | Saved | Retained independent review with current replay evidence |
| --- | ---: | ---: |
| Scenarios | 3,341 | 3,341 |
| Genuine four-live scenarios / groups | 267 | 267 |
| Primary identities / 5,196 | 501 | 501 |
| Cycles or standalone groups / 3,324 | 376 | 376 |

Since 20 October, 262 new representatives have been saved and 262 qualified; the original 3,074 duel scenarios remain preserved. The 22 October resumption accounts for 180 saved / 180 qualified additions. Untested pitch siblings receive no individual credit. The implementation-candidate subset contains 3,002 groups; candidate flags do not prove implementation.

For test-only batches, run only new or modified scenarios. Each new representative receives independent semantic review, two clean targeted creation replays and a focused sensitivity control. Reuse unchanged historical evidence without retagging its runtime. The current index separates positive representative evidence from full-card completion and from the stricter two-current-replay diagnostic.

From 22 October, full-suite runs are left to the user: no restart baseline, between-batch full run or final full checkpoint is performed. If a gameplay/framework change warrants broader validation, report the recommended command and its unrun status. Selected-fixture byte guards protect only the named batch and do not claim a fresh whole-inventory rehash.

The legacy finite-completion classifier remains separate: zero does not mean the 75 frozen finite baselines failed. No historical semantic review or qualification scope is being expanded in this breadth pass.
<!-- INCREMENTAL-END -->

<!-- RESUMED-20261022-BEGIN -->
## Resumed breadth pass - 22 October 2026

The user's new request supersedes the 21 October pause. At restart Git was clean at `765666dbfa49a106597a4ade32b078de19a05e56`; the FaBSim runtime and deferred Batch 104 guards were unchanged. No full baseline was run. Full-suite validation is left to the user, including after any future shared change; only new/modified scenarios and directly affected focused checks execute here.

| Batch | Exact tested identities | Qualified four-live representatives |
| --- | --- | ---: |
| [104](review-evidence/sparse-b104-qualified-independent-review-b68b189d0187.json) | strength_of_four_seasons_red; runerager_swarm_red; hit_the_high_notes_red; shrill_of_skullform_red | 4 |
| [105](review-evidence/sparse-b105-qualified-independent-review-9256780f68c6.json) | adrenaline_rush_red; spring_load_red; spellblade_strike_red; spellblade_assault_red | 4 |
| [106](review-evidence/sparse-b106-qualified-independent-review-90c50212d2a3.json) | fyendals_fighting_spirit_red; life_for_a_life_red; overload_red; performance_bonus_red | 4 |
| [107](review-evidence/sparse-b107-qualified-independent-review-870d6461969b.json) | high_striker_red; plunder_run_red; premeditate_red; exorcism_red | 4 |
| [108](review-evidence/sparse-b108-qualified-independent-review-4bc98c4074cb.json) | starting_stake_yellow; this_rounds_on_me_blue; poison_the_well_blue; sirens_of_safe_harbor_red | 4 |
| [109](review-evidence/sparse-b109-safe-three-qualified-independent-review-62b3cbddea80.json) | onyx_amulet_blue; imperial_ledger_red; talisman_of_recompense_yellow | 3 |
| [110](review-evidence/sparse-b110-qualified-independent-review-5ebfa91bff40.json) | ravenous_rabble_red; prime_the_crowd_red; prismatic_leyline_yellow; trot_along_blue | 4 |
| [111](review-evidence/sparse-b111-qualified-independent-review-67cb4f995b12.json) | torrent_of_tempo_red; sweeping_blow_red; hocus_pocus_red; soulbead_strike_red | 4 |
| [112](review-evidence/sparse-b112-qualified-independent-review-b26dff071146.json) | seismic_stir_red; sky_fire_lanterns_red; cruel_ambition_red; runic_reckoning_red | 4 |
| [113](review-evidence/sparse-b113-qualified-independent-review-6bbc1b26ca8d.json) | agile_windup_red; mighty_windup_red; vigorous_windup_red; fruits_of_the_forest_red | 4 |
| [114](review-evidence/sparse-b114-qualified-independent-review-34f4ed518b19.json) | seismic_eruption_yellow; tome_of_quandaries_blue; starworld_warning_yellow; runic_disposition_red | 4 |
| [115](review-evidence/sparse-b115-qualified-independent-review-494f24ec61a1.json) | goblet_of_bloodrun_wine_blue; pint_of_strong_and_stout_blue; smashback_alehorn_blue; humble_entrance_blue | 4 |
| [116](review-evidence/sparse-b116-qualified-independent-review-c788ac1640a1.json) | electrostatic_discharge_red; tooth_of_the_dragon_red; whispering_mist_blue; snag_blue | 4 |
| [117](review-evidence/sparse-b117-qualified-independent-review-005248081f93.json) | prismatic_shield_red; future_sight_red; oath_of_oak_red; envelop_in_darkness_red | 4 |
| [118](review-evidence/sparse-b118-qualified-independent-review-1c122edc2498.json) | uprising_red; billowing_mist_blue; vigorous_roar_red; water_glow_lanterns_red | 4 |
| [119](review-evidence/sparse-b119-qualified-independent-review-921e4dcbcaa1.json) | rites_of_nightfall_blue; planar_chaos_red; pledge_fealty_red; spectral_manifestations_red | 4 |
| [120](review-evidence/sparse-b120-qualified-independent-review-135fa7c0212e.json) | blessing_of_salvation_red; electryn_joltstep_red; fasting_carcass_blue; embrace_sin_yellow | 4 |
| [121](review-evidence/sparse-b121-qualified-independent-review-5b1a565a361c.json) | seeds_of_strength_red; laden_with_earth_red | 2 |
| [122](review-evidence/sparse-b122-qualified-independent-review-f60789c81e43.json) | lead_with_power_red; lead_with_heart_red; laden_with_lightning_red; leech_vitality_red | 4 |
| [123](review-evidence/sparse-b123-qualified-independent-review-9c7a79940931.json) | funeral_moon_red; requiem_for_the_damned_red; peace_of_mind_red; heroic_grit_yellow | 4 |
| [124](review-evidence/sparse-b124-qualified-independent-review-20e99f26aa2a.json) | homage_to_ancestors_blue; tome_of_torment_red; three_of_a_kind_red; grim_feast_red | 4 |
| [125](review-evidence/sparse-b125-qualified-independent-review-ceaee6d5e9c4.json) | oath_of_the_arknight_red; otherworldly_sins_red; revolting_gesture_red | 3 |
| [126](review-evidence/sparse-b126-qualified-independent-review-cc707ca7aa7e.json) | draw_swords_red; high_octane_red; cash_in_yellow | 3 |
| [128](review-evidence/sparse-b128-qualified-independent-review-d8d73dfab590.json) | howl_from_beyond_red | 1 |
| [129](review-evidence/sparse-b129-qualified-independent-review-30f4fb908e3f.json) | weave_earth_red; weave_ice_red; weave_lightning_red | 3 |
| [130](review-evidence/sparse-b130-qualified-independent-review-076de58f7bc9.json) | beat_the_same_drum_blue | 1 |
| [131](review-evidence/sparse-b131-qualified-independent-review-113f0610a24e.json) | tome_of_the_arknight_blue; heartbeat_of_candlehold_blue | 2 |
| [132](review-evidence/sparse-b132-qualified-independent-review-129fbcc21c25.json) | unsheathed_red; dauntless_red; tiger_form_incantation_red | 3 |
| [133](review-evidence/sparse-b133-qualified-independent-review-c5dd62cfdb1d.json) | flying_high_red; pulse_of_volthaven_red | 2 |
| [134](review-evidence/sparse-b134-qualified-independent-review-3fbbdf9b2f85.json) | driving_blade_red; precision_press_red | 2 |
| [135](review-evidence/sparse-b135-qualified-independent-review-da2d2f8658aa.json) | predatory_streak_red; roar_of_the_tiger_yellow | 2 |
| [136](review-evidence/sparse-b136-qualified-independent-review-f6b57a039e05.json) | slice_and_dice_red; engaged_swiftblade_red | 2 |
| [137](review-evidence/sparse-b137-qualified-independent-review-adb04a038c36.json) | draw_back_the_hammer_red; monkey_powder_red | 2 |
| [138](review-evidence/sparse-b138-qualified-independent-review-aad53ca60bff3.json) | commanding_performance_red; plow_through_red | 2 |
| [139](review-evidence/sparse-b139-qualified-independent-review-dabbfbb1cd4d.json) | locked_and_loaded_red; rotary_ram_red | 2 |
| [140](review-evidence/sparse-b140-qualified-independent-review-c204afce46f5.json) | flash_red; hit_and_run_red | 2 |
| [141](review-evidence/sparse-b141-qualified-independent-review-4305b43b9e09.json) | gigawatt_red; tighten_the_screws_red | 2 |
| [142](review-evidence/sparse-b142-qualified-independent-review-3321f9ce8d82.json) | phantasmify_red; spreading_mist_blue | 2 |
| [143](review-evidence/sparse-b143-qualified-independent-review-b3524cdbded3.json) | sprout_strength_red; push_forward_red | 2 |
| [144](review-evidence/sparse-b144-qualified-independent-review-ba5ff4a21769.json) | transmogrify_red; veiled_intentions_red | 2 |
| [145](review-evidence/sparse-b145-qualified-independent-review-4c151c221322.json) | vexing_quillhand; grasp_of_the_arknight | 2 |
| [146](review-evidence/sparse-b146-qualified-independent-review-02272fadae42.json) | quick_succession_red; kindle_red | 2 |
| [147](review-evidence/sparse-b147-qualified-independent-review-a1261aa8cc29.json) | robe_of_rapture; bracers_of_belief | 2 |
| [148](review-evidence/sparse-b148-qualified-independent-review-79353856b8a4.json) | runehold_release; vigor_girth | 2 |
| [149](review-evidence/sparse-b149-qualified-independent-review-06f61c156fac.json) | gauntlet_of_might; flat_trackers | 2 |
| [150](review-evidence/sparse-b150-qualified-independent-review-454ea0c64e47.json) | threadbare_tunic; blue_sea_tricorn | 2 |
| [151](review-evidence/sparse-b151-qualified-independent-review-7dd68b51c840.json) | richter_scale; buccaneers_bounty | 2 |
| [152](review-evidence/sparse-b152-qualified-independent-review-7c104e003ded.json) | time_skippers; skullhorn | 2 |
| [153](review-evidence/sparse-b153-qualified-independent-review-e14dbf7fb324.json) | cracker_jax; coat_of_allegiance | 2 |
| [154](review-evidence/sparse-b154-qualified-independent-review-7b44085d680c.json) | calming_gesture | 1 |
| [155](review-evidence/sparse-b155-qualified-independent-review-7489c386e795.json) | garland_of_spring; courage_of_bladehold | 2 |
| [156](review-evidence/sparse-b156-qualified-independent-review-7d80d871b38e.json) | peg_leg; fish_fingers | 2 |
| [157](review-evidence/sparse-b157-qualified-independent-review-d05dfc5059da.json) | monstrous_veil | 1 |
| [158](review-evidence/sparse-b158-qualified-independent-review-2d32def4a6df.json) | quartermasters_boots | 1 |
| [159](review-evidence/sparse-b159-qualified-independent-review-79bcdaadadb3.json) | rift_skitter_red; rune_flash_red | 2 |
| [160](review-evidence/sparse-b160-qualified-independent-review-a643871b32b2.json) | spears_of_surreality_red; dunebreaker_cenipai_red | 2 |
| [161](review-evidence/sparse-b161-qualified-independent-review-c229f5a443b7.json) | hundred_winds_red; spinning_wheel_kick_red | 2 |
| [162](review-evidence/sparse-b162-qualified-independent-review-5bf57f9380bb.json) | ball_lightning_red; grow_claws_red | 2 |
| [163](review-evidence/sparse-b163-qualified-independent-review-7936d4cf8f3a.json) | zephyr_needle; edge_of_autumn | 2 |
| [164](review-evidence/sparse-b164-qualified-independent-review-5b9672b67bed.json) | flameborn_retribution_red; give_and_take_red | 2 |
| [165](review-evidence/sparse-b165-qualified-independent-review-ca32a2441013.json) | talismanic_lens | 1 |
| [166](review-evidence/sparse-b166-qualified-independent-review-94e2a0dadd7a.json) | amulet_of_ignition_yellow | 1 |
| [167](review-evidence/sparse-b167-qualified-independent-review-55369c30b315.json) | deep_blue | 1 |
| [168](review-evidence/sparse-b168-unordered-qualified-independent-review-07bedadee619.json) | talisman_of_tithes_blue | 1 |
| [169](review-evidence/sparse-b169-qualified-independent-review-3301d92b4f08.json) | tearing_shuko | 1 |
| [170](review-evidence/sparse-b170-qualified-independent-review-8e289c972d03.json) | tectonic_plating | 1 |
| [171](review-evidence/sparse-b171-qualified-independent-review-010d94b4f6ad.json) | heart_of_ice | 1 |
| [172](review-evidence/sparse-b172-qualified-independent-review-55d39ff8bd7a.json) | captains_coat | 1 |
| [173](review-evidence/sparse-b173-qualified-independent-review-d2a060623f43.json) | ragamuffins_hat | 1 |
| [174](review-evidence/sparse-b174-qualified-independent-review-97d8bbf11aa9.json) | dream_weavers | 1 |
| [175](review-evidence/sparse-b175-qualified-independent-review-1727901a44ce.json) | pouncing_paws | 1 |
| [176](review-evidence/sparse-b176-qualified-independent-review-2981a8775fe6.json) | aether_ironweave | 1 |
| [177](review-evidence/sparse-b177-qualified-independent-review-205357497053.json) | blood_scent | 1 |
| [178](review-evidence/sparse-b178-qualified-independent-review-8301b4ee6c27.json) | punching_gloves | 1 |
| [180](review-evidence/sparse-b180-qualified-independent-review-5d1ab66c37ff.json) | clarity_potion_blue | 1 |
| [182](review-evidence/sparse-b182-qualified-independent-review-fc3e381a6976.json) | braveforge_bracers | 1 |
| [183](review-evidence/sparse-b183-qualified-independent-review-c7e287955943.json) | rip_off_the_top_yellow | 1 |
| [184](review-evidence/sparse-b184-qualified-independent-review-fcadd638aecd.json) | gallantry_gold | 1 |
| [185](review-evidence/sparse-b185-qualified-independent-review-2d59435695e6.json) | bear_hug_red | 1 |
| [186](review-evidence/sparse-b186-qualified-independent-review-b23494d27056.json) | fog_down_yellow | 1 |
| [187](review-evidence/sparse-b187-qualified-independent-review-2ac9134bc5ab.json) | kano | 1 |
| [188](review-evidence/sparse-b188-qualified-independent-review-348556a0452b.json) | clearwater_elixir_red | 1 |

Each linked compact proof binds the approved source, exact saved files, independent positive-effect model, two targeted replay receipts and completed focused fault report. A synthetic control proves sensitivity only within its stated scope; it does not add legal negative-branch or sibling coverage. Batch 104's original control selector failed preparation and earns no qualification; its corrected, completed control report is explicitly identified in the retained packet. No gameplay source change has been applied in this resumption.

The coverage JSON's global negative-control counters summarize the older central registry. New sparse-batch controls are recorded in the linked compact qualification proofs; those global counters do not aggregate these new controls.

Batch 168's original attempt saved no fixture and failed an over-specific graveyard-index oracle. The [bounded rules and failure classification](review-evidence/sparse-b168-original-failure-classification-ebf627182a79.json) distinguishes freely rearrangeable graveyard order from the primary draw-replacement effect. Its immutable successor preserves the actions, draw/economy checks, both cards' membership and independent physical UID audit. A separate source-derived concern about early non-attack-action graveyard cleanup remains unresolved; this representative does not certify that internal timing. The original failed input and receipt remain preserved.

The test-only `negative_control_state.php` helper now accepts an optional positive integer UID when moving one of several identical tokens. Its existing no-UID behavior is preserved. Batch 114's compact proof includes independent review and all 18 focused selector-contract checks; later token-removal controls bind the updated helper hash. This helper is outside the gameplay runtime fingerprint. No full-suite validation is claimed for this test-infrastructure change.

The prior 590-entry historical review caveat remains unchanged. The paused checkpoint and original preserved-fixture hashes are historical witnesses; this pass does not freshly re-audit unchanged legacy cases or rehash unrelated fixtures on every small batch. Existing fixtures are never rerecorded by these new inputs.


Quarantined incorrect historical recordings are outside the runnable corpus and receive no card/group coverage:

- `FAB-REG-037`: the earlier Warfare five-file snapshot is preserved at `held-incorrect-gameplay/FAB-REG-037`. [Verified quarantine receipt](held-incorrect-gameplay/FAB-REG-037-quarantine.json) confirms native runner discovery excludes it. Its original incomplete assertions could replay green; that is not semantic correctness.

The separate [correct-expectation Warfare probe](card-regression-batch109-warfare-faceup-rule-reproducer.json) added the required public Graveyard `FaceDown=0` checks, failed natively as expected, and saved **no fixture**. It is distinct from the quarantined earlier snapshot. Repair remains open; no gameplay source was changed.

[Source-only planning holds](sparse-source-only-holds.json) also record unattempted Potion of Deja Vu staging, Icy Encounter token ownership, and the suspected shared Intimidate trigger-response timing route for Smash Instinct and Pack Hunt. They receive no native defect count or passing credit; replacements are used for breadth instead of pinning suspect behavior.

`FAB-REG-038`: [Abyssal Bite native hold](review-evidence/sparse-b128-abyssal-bite-printed-go-again-hold-e1955e2405ca.json) preserves the correct printed own go again expectation. After six supported four-seat actions the engine left AP0 instead of AP1 because its FaB shared keyword parser requires a whole text line. No Bite fixture was saved or qualified, no retry or gameplay repair was applied, and Howl was qualified separately with an explicit source subset.

Batch 127's original Tiger attempts over-specified raw `Owner=1`. The [bounded ownership interpretation](review-evidence/sparse-b127-tiger-ownership-representation-review-4fc2a133a868.json) explains default-zero objects in the reviewed own-zone play/move paths; it does not claim all raw-zero ownership is safe. Those attempts saved no fixtures, and their raw values alone establish no gameplay ownership defect. The separate Chorus Decompose-prompt mismatch remains unqualified pending classification.

[Batch 135 corrected Tiger representatives](review-evidence/sparse-b135-qualified-independent-review-da2d2f8658aa.json) qualify Predatory Streak and Roar of the Tiger through actual same-UID play, normalized physical ownership/control and zero/one damage. Original failed Batch 127 inputs remain unchanged. The selected endpoint is RESOLUTION; Ephemeral cleanup and later expiry are untested.

`FAB-REG-039`: the [bounded cost-order review](review-evidence/sparse-b179-b181-cost-order-hold-905811cc8a25.json) revoked Batch 179 approval: it required actual Arsenal-bottom payment before resource/AP assets, contrary to current CR5.1.7 before5.1.9. Its seven-action snapshot and two targeted PASS receipts are rejected timing evidence, not coverage. All five original files are preserved outside runner discovery at `held-incorrect-gameplay/b179-tome-of-harvests-cost-order`; the [verified quarantine receipt](held-incorrect-gameplay/FAB-REG-039-quarantine.json) binds recovery hashes and native list-only exclusion. Rouse the Ancients (Batch 181) remains an uninstalled, unsaved draft for the same preparation-order concern. This is a confirmed incorrect timing oracle with a source-supported shared concern; no separately demonstrated trigger gameplay defect or gameplay repair is claimed. Regurgitating Slog's unsupported block-rejection recording and Bloodtorn Bodice's unattempted preparation concern are recorded in the source-only holds. Other groups continue through supported actions.
<!-- RESUMED-20261022-END -->

<!-- RISE-CUTOVER -->
## Rise Above runtime validation

The incremental table above reports its own current runtime; the frozen pre-repair checkpoint below remains at `89d2a5b14134e`. The reviewed Rise Above save-choice repair is now applied in `FaBSim/Custom/WTRCards.php`, producing runtime `e94720ede760f8364583450069c93d68771f7311a641aeab5f0f790c1539df27`. All 3,124 earlier fixture sets remain byte-identical. The original correct Rise recipe is now independently qualified with its Hand-0 assertion unchanged: two targeted clean replays, one intended top-deck fault detected, one full 3,125/3,125 replay and all four existing native suites passed. [Qualified proof](review-evidence/sparse-rise-e947-qualified-independent-review-0c4e9127c596.json) retains the exact hashes. This is one red representative; sibling pitches remain individually unverified. Later appended tests use targeted validation and are not claimed to be included in that frozen 3,125 run.

The change rebuilds only the matching pending Rise alternative-cost Hand references on a copied queue during serialization. It changes no Core file, generated card implementation, card rule, cost or expected test output. [Application receipt](rise-above-choice-save-repair.json) retains source hashes, the source/component review and the outside-repository backup. Existing baseline evidence is reused for test-only batches; this shared source change warrants one full-corpus check.

## Frozen checkpoint

Qualified checkpoint at 22:38 UTC, runtime `89d2a5b14134e6ff0e6a150dec3b6859d26ee74124988905dc7a0d0ce6ef67c6`:

| Measure | Saved | Independently reviewed with two current clean replays |
| --- | ---: | ---: |
| Scenarios | 3,101 | 3,101 |
| Four-live scenarios / distinct groups | 27 | 27 |
| Duel scenarios, preserved | 3,074 | 3,074 |
| Primary identities / 5,196 | 261 | 261 |
| Cycles or standalone groups / 3,324 | 136 | 136 |

New this resumption: 22 recorded and qualified representatives. Two additional attempted reactions failed correct target-announcement checks and have no saved passing fixture. Scenario volume is not a completion percentage. The catalog includes distinct pitch identities and 17,188 printing records. The implementation-candidate subset is 3,002 groups / 4,811 identities; its flags identify investigation candidates, not proven implementations. At this snapshot, 27 candidate groups have qualified four-live representatives, leaving 2,975 without one. Existing duel evidence is a separate measure.

All original 3,079 five-file fixture sets remain byte-identical. The 75 existing finite baselines meet current exact-inventory full-suite gates; 186 identities have partial evidence and 4,935 have no primary evidence. New representatives do not promote any complete card. During later inventory growth, finite-completion eligibility can temporarily show zero until a new full replay checkpoint is qualified; the frozen checkpoint remains valid for its own inventory.

## Batches

| Batch | Representative cards | State |
| --- | --- | --- |
| 82 | Feisty Locals red; Freewheeling Renegades red; Show of Strength red; Down and Dirty red | Four qualified; actual defenders/arsenal defense, two clean replays, four faults detected. |
| 83 | Browbeat blue; Lightning Surge red; Searing Ray red; Ghostly Visit red | Four qualified; physical hand, arsenal, pitch and banish effects, two clean replays, four faults detected. |
| 84 | Ancestral Empowerment red; Knives Out blue | Two qualified; physical draw and two real dagger activations, two clean subset replays, two faults detected. |
| 84 holds | Nip at the Heels blue; Shred red | Native target-announcement failure; unsaved; `FAB-REG-035`. |
| 85 | Minnowism red; Tome of Fyendal yellow; Ruby Amulet blue; Dissolve Reality yellow | Four qualified; two clean replays and four faults detected. Dissolve changes all four physical arsenals and creates four owned Ponders. |
| 86 | Sun Kiss red; Rejuvenate red; Tome of Divinity yellow; Drop of Dragon Blood red | Four qualified; two clean replays and four native faults detected. |
| 87 | Stubby Hammerers; Diamond, Pounamu and Amethyst Amulets blue | Four qualified; two clean replays and four native faults detected. |

These are bounded positive effects. Ordinary synchronous spell cases certify public before/after endpoints; they do not certify internal source-departure ordering, asynchronous lifetimes, replacement observers, all choices or sibling pitches. The reaction hold needs reviewed announcement-time target binding and revalidation before passing coverage. Gorganian Tome remains open (`FAB-REG-034`); wider Lunging Press target/lifecycle holds remain documented separately. That frozen checkpoint predates the separately documented Rise Above save-choice repair.

## Finding evidence

- [cycle-coverage-current.json](cycle-coverage-current.json): compact generated snapshot with saved, reviewed and one/two-current-replay group counts; actual four-live profiles; exact representative IDs and scenario slugs; blocked rows. Read `generated_utc` and `inventory` before treating it as a live counter.
- [card-regression-coverage.json](card-regression-coverage.json): per-card branches, exact source specification, test path, review and replay hashes. `complete_declared_baseline` has a finite documented scope; other passing evidence is partial.
- [card-regression-review.json](card-regression-review.json): canonical exact-hash independent saved-fixture registrations. [card-regression-issues.json](card-regression-issues.json) retains failing and held behavior.
- [card-regression-checkpoint.json](card-regression-checkpoint.json) and [FINAL_ASSESSMENT.md](FINAL_ASSESSMENT.md): historical consolidated checkpoint at runtime `561899c9b57e`, not this resumption's live totals.

For example, find `pitch-cycle:minnowism` in the compact index, then its exact primary `minnowism_red` and slug `card-minnowism-red-four-live-sparse-next-small-base-real-attack-buff`. That slug maps to `Tests/Integration/FaBSim/<slug>/` and its five runner files. Yellow and blue siblings have no individual verification from this red representative. The four-player label requires living seats and actual pass actors, not four serialized slots alone.

## Validation and maintenance

The discovery fix loads each reviewed slug from its exact registered source, including nine historical suffix specifications. The qualifier discovers the current native four-suite report. The compact index refuses mismatched saved inventories. Neither change weakens semantic or replay gates.

From repository root:

```powershell
python DevTools/FaB/CardRegression/run_card_regression_replays.py <unique-run-id>
php DevTools/RunIntegrationTests.php --root=FaBSim --test=<scenario-slug>
python DevTools/FaB/CardRegression/build_card_regression_coverage.py
python DevTools/FaB/CardRegression/summarize_card_regression_cycles.py
```

Current evidence includes the 3,079-test baseline pass; two full 3,087/3,087 passes; two clean per-batch replays of all 22 new fixtures; 77/77 existing semantic controls and 4/4 four-live controls detected; WTR, ARC, UPF and shortcut suites passed. Both fresh full 3,101/3,101 replays passed. All 22 new focused faults were detected. [Frozen checkpoint](card-regression-sparse-3101-checkpoint.json) retains the exact hashes and counts; later saves are separate progress until the current index is rebuilt. Final batch fault reports retain mutations, expected first failures and canonical-hash preservation. Keep only compact shared proof packets and immutable run reports in the repository; scratch modeling and bulk source-review copies stay outside it. No commits, pushes or deployments were performed.

<!-- PAUSED-CHECKPOINT -->
## Paused checkpoint â€” 21 October 2026

Stopped at the user's request after qualifying every recorded case: **3,161 saved / 3,161 qualified**, comprising 3,074 preserved duel cases and 87 genuine four-live cases. This resumption added **82 scenarios across 82 previously uncovered groups**. There are 321 primary identities with verified partial evidence out of 5,196 (pitch variants are distinct), and 196 groups with any verified primary evidence out of 3,324. Four-live first-pass breadth is 87 groups; 3,237 catalog groups still lack such a representative. Scenario volume is not a completion percentage.

All 15,395 original fixture files are byte-identical. Each new case has two clean targeted creation replays and a case-specific semantic fault gate; historical controls keep their original runtime. Six full runs completed since restart. The last full pass was **3,125/3,125** after the Rise shared-source repair; the 36 later additions each passed twice individually. The 52 targeted runs contain 164 scenario passes. No redundant final full run was performed with unchanged source. Current WTR, ARC, UPF and shortcut suites passed after that repair. The 75 finite baselines remain valid at their frozen 3,101 checkpoint; the stricter current exact-inventory classifier is separate and remains zero.

The only new gameplay-source fix is bounded Rise Above save-choice persistence (`FAB-REG-036`) in `FaBSim/Custom/WTRCards.php`; red has native evidence, while sibling/component scopes remain separate. No Core or generated code changed. Flex bookkeeping assertions and Potion's explicit four-seat queue checks corrected draft oracles/protocol, with no changed card rule or expected damage. The old failed drafts and false whole-control report remain preserved; the successful Zealous control and corrected Hulk control are identified separately.

Gorganian Tome (`FAB-REG-034`, source/component findings unapplied), Nip at the Heels/Shred (`FAB-REG-035`, native announcement timing), and broader Lunging Press (`FAB-REG-030`, partial verified scope) remain held. [Compact source-only triage](sparse-source-only-holds.json) records eight additional planning concerns, not confirmed gameplay bugs. Potion's single-Arsenal-card representative is qualified; multi-card shuffle ordering remains unverified. Recorder diagnostics on a pre-initialization rejection can mask the primary validation message with a null-Hand error; this diagnostic limitation is retained without altering the recorder.

Batch 104's four independently approved specifications are installed but **unrecorded and deferred**. Batch 105 scratch authoring stopped without a native input or fixture. No new coverage or exploratory fixes will execute after this checkpoint. All work remains uncommitted; nothing was staged, pushed or deployed.

See the [compact paused checkpoint](card-regression-sparse-pause-checkpoint.json) for exact hashes, all six full-run and 52 targeted-run receipts, review paths, preservation digest and deferred slugs. The current coverage/cycle indices above are authoritative; older consolidation reports retain their historical runtimes.

To resume, inspect Git/source hashes and these indices, then choose either the four deferred Batch 104 representatives or new uncovered groups. Record only reviewed specifications and run only the new/modified saved cases with `php DevTools/RunIntegrationTests.php --root=FaBSim --test=<slug>`. Rebuild coverage, then the cycle summary, sequentially after qualification. Keep the one-representative-per-cycle policy and preserve untested sibling/branch limitations.


Final reconciliation: [approved with limits](review-evidence/sparse-pause-final-independent-review-7970d344c965.json). This is a pause/handoff review, not a fresh exhaustive semantic audit. Of the historical registry entries, 590 retain legacy family/specification review with unknown individual saved-fixture inspection extent. Original fixture byte preservation is root-verified; the final reviewer reused that witness. Retained individual qualification gates and the frozen checkpoint remain the evidence for the new cases; untested pitch siblings and alternate branches receive no credit.

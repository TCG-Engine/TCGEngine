# FaBSim coverage guide and compact index

Frozen 20 October 2026: **3,079 qualified scenarios** (3,074 duels, five four-live-player games); **239 primary identities / 114 cycles or standalone groups**. Of 5,196 catalog identities,75 have a complete declared finite baseline,164 have partial verified branches, and4,957 have no primary scenario. Catalog membership is3,324 groups /17,188 printing records;3,210 groups lack primary scenarios. No pitch sibling inherits a representative's evidence.

Use [card-regression-coverage.json](card-regression-coverage.json) for current detailed evidence; [card-regression-checkpoint.json](card-regression-checkpoint.json) for frozen qualification and [card-regression-completion-scopes.json](card-regression-completion-scopes.json) for exact baseline obligations. [FINAL_ASSESSMENT.md](FINAL_ASSESSMENT.md) explains fixes, open risks and test results.

**Maintenance limit:** the legacy standalone builder omits nine current suffix-named inputs and its qualifier misses the new four-suite filename. Use the frozen current registry; review and correct that rebuild inventory before regenerating it.

The schema is `cards[]` keyed by `card_id`. Its `effect_branches[]` entries name `id` (scenario slug), `source_spec`, `effect_branches`, `recording_test_path`, `fixture_sha256`, `independent_review` and `replays[]` with current `runtime_sha256`. Check both review and replay hashes: a `recorded_semantically_checked` authoring status alone does not qualify a regression. `coverage_status=complete_declared_baseline` is a bounded intrinsic baseline; `partial` is named branch evidence; an identity with no branches has no primary-scenario coverage even if its metadata says `pending_semantic_scenario_review`. A present branch also needs exact review and current replay evidence; unqualified fang_strike/slither mentions do not increase the239 tested primary subjects.

For example:

| Card | Status | Actual evidence |
| --- | --- | --- |
| `wounding_blow_red` | Complete declared ordinary baseline | Includes `card-wounding-blow-red-unblocked`, saved under `Tests/Integration/FaBSim/<slug>/`, and separate pitch/defense/payment/refusal branches; duel scope. |
| `sigil_of_solace_red` | Partial | `card-sigil-of-solace-red-four-live-hold-priority-correct-rule-before-probe` exercises actualP4 cast/+3life and four manual priority passes. Other duel cases are separate evidence. |
| `read_the_runes_red` | Partial | Four-live creation of three owned Runechants; their future damage triggers are not covered by this case. Blue remains untested. |
| `stroke_of_foresight_yellow` | No primary scenario | Red representative evidence confers no yellow or blue credit. |
| `gorganian_tome` | Open, no primary scenario | Source/component defect evidence is not a passing native test. |

From the repository root, a read-only lookup:

```powershell
$coverage = Get-Content .\DevTools\FaB\CardRegression\card-regression-coverage.json -Raw | ConvertFrom-Json
$card = $coverage.cards | Where-Object card_id -eq 'sigil_of_solace_red'
$card | Select-Object card_id, coverage_status
$card.effect_branches | Select-Object id, status, recording_test_path
```

[cycle-coverage-assessment.json](cycle-coverage-assessment.json) gives the114 represented groups, exact tested variants and the five actual four-player cases. [cycle-group-map.compact.json](cycle-group-map.compact.json) maps all3,324 groups to their5,196 member IDs; it is membership only. A group absent from the represented table has no primary scenario. Merely mentioning a hero/support card or creating a token earns no primary evidence. Spell Fray Cloak's accepted Spellvoid has separate partial secondary evidence in Dart, excluded from239 primary subjects.

All five four-live pilots use living heroes, explicit per-seat queues/priority and real passes. Strike Gold includes actualP2/P3/P4 Sigil responses; Dart includes aP3 response andP4 Spellvoid decision. Most branches below remain duel-only. Existing matrices were preserved. Future work should default to four live players and one representative per pitch cycle, adding cases for material behavior or a specific bug.

## All represented primary groups

`B` means complete declared finite baseline for that exact member; `P` means partial. `4P` marks a member with one of the five four-live pilots, alongside any duel evidence. Every other tested member below has duel evidence only. Untested members are explicit; an empty final column means all listed group members have some primary evidence, not full cycle correctness.

| Exact group | Tested members | Members without primary evidence |
| --- | --- | --- |
| `identity:aetherstorm_wellingtons` | `aetherstorm_wellingtons` B |  |
| `identity:arcane_lantern` | `arcane_lantern` B |  |
| `identity:blink_blue` | `blink_blue` P |  |
| `identity:bravo` | `bravo` P |  |
| `identity:breaking_scales` | `breaking_scales` P |  |
| `identity:command_and_conquer_red` | `command_and_conquer_red` P |  |
| `identity:cosmic_flare_red` | `cosmic_flare_red` P |  |
| `identity:dodge_blue` | `dodge_blue` B |  |
| `identity:en_garde_red` | `en_garde_red` P |  |
| `identity:enclosed_firemind` | `enclosed_firemind` B |  |
| `identity:energy_potion_blue` | `energy_potion_blue` P |  |
| `identity:enlightened_strike_red` | `enlightened_strike_red` P |  |
| `identity:goliath_gauntlet` | `goliath_gauntlet` P |  |
| `identity:harmonized_kodachi` | `harmonized_kodachi` P |  |
| `identity:heartened_cross_strap` | `heartened_cross_strap` P |  |
| `identity:helm_of_isens_peak` | `helm_of_isens_peak` P |  |
| `identity:ironrot_gauntlet` | `ironrot_gauntlet` B |  |
| `identity:ironrot_helm` | `ironrot_helm` B |  |
| `identity:ironrot_legs` | `ironrot_legs` B |  |
| `identity:ironrot_plate` | `ironrot_plate` B |  |
| `identity:lunging_press_blue` | `lunging_press_blue` P |  |
| `identity:muscle_mutt_yellow` | `muscle_mutt_yellow` B |  |
| `identity:nullrune_boots` | `nullrune_boots` B |  |
| `identity:nullrune_gloves` | `nullrune_gloves` B |  |
| `identity:nullrune_hood` | `nullrune_hood` B |  |
| `identity:nullrune_robe` | `nullrune_robe` B |  |
| `identity:ominous_respite_yellow` | `ominous_respite_yellow` P |  |
| `identity:potion_of_strength_blue` | `potion_of_strength_blue` P |  |
| `identity:quicken` | `quicken` P |  |
| `identity:roaring_beam_yellow` | `roaring_beam_yellow` P |  |
| `identity:rotten_old_buckler` | `rotten_old_buckler` B |  |
| `identity:rout_red` | `rout_red` P |  |
| `identity:rusty_harpoon_blue` | `rusty_harpoon_blue` B |  |
| `identity:shamanic_shinbones` | `shamanic_shinbones` B |  |
| `identity:skybody_keikoi` | `skybody_keikoi` P |  |
| `identity:sledge_of_anvilheim` | `sledge_of_anvilheim` P |  |
| `identity:snapdragon_scalers` | `snapdragon_scalers` P |  |
| `identity:springboard_somersault_yellow` | `springboard_somersault_yellow` P |  |
| `identity:teklo_base_arms` | `teklo_base_arms` B |  |
| `identity:teklo_base_chest` | `teklo_base_chest` B |  |
| `identity:teklo_base_head` | `teklo_base_head` B |  |
| `identity:teklo_base_legs` | `teklo_base_legs` B |  |
| `identity:timesnap_potion_blue` | `timesnap_potion_blue` P |  |
| `identity:toughen_up_blue` | `toughen_up_blue` B |  |
| `pitch-cycle:aether_dart` | `aether_dart_red` P 4P, `aether_dart_yellow` P, `aether_dart_blue` P |  |
| `pitch-cycle:aether_hail` | `aether_hail_red` P, `aether_hail_yellow` P, `aether_hail_blue` P |  |
| `pitch-cycle:arcane_polarity` | `arcane_polarity_red` P, `arcane_polarity_yellow` P, `arcane_polarity_blue` P |  |
| `pitch-cycle:autumns_touch` | `autumns_touch_red` B, `autumns_touch_yellow` B, `autumns_touch_blue` B |  |
| `pitch-cycle:brutal_assault` | `brutal_assault_red` B, `brutal_assault_yellow` B, `brutal_assault_blue` B |  |
| `pitch-cycle:burgeoning` | `burgeoning_red` P, `burgeoning_yellow` P, `burgeoning_blue` P |  |
| `pitch-cycle:come_to_fight` | `come_to_fight_red` P, `come_to_fight_yellow` P, `come_to_fight_blue` P |  |
| `pitch-cycle:count_your_blessings` | `count_your_blessings_red` P, `count_your_blessings_yellow` P, `count_your_blessings_blue` P |  |
| `pitch-cycle:critical_strike` | `critical_strike_red` B, `critical_strike_yellow` B, `critical_strike_blue` B |  |
| `pitch-cycle:cut_deep` | `cut_deep_red` P, `cut_deep_yellow` P, `cut_deep_blue` P |  |
| `pitch-cycle:destructive_deliberation` | `destructive_deliberation_red` P | `destructive_deliberation_yellow`, `destructive_deliberation_blue` |
| `pitch-cycle:dust_runner_outlaw` | `dust_runner_outlaw_red` B, `dust_runner_outlaw_yellow` B, `dust_runner_outlaw_blue` B |  |
| `pitch-cycle:earth_form` | `earth_form_red` P, `earth_form_yellow` P, `earth_form_blue` P |  |
| `pitch-cycle:earthlore_surge` | `earthlore_surge_red` P, `earthlore_surge_yellow` P, `earthlore_surge_blue` P |  |
| `pitch-cycle:electrolyze` | `electrolyze_red` B, `electrolyze_yellow` B, `electrolyze_blue` B |  |
| `pitch-cycle:evasive_leap` | `evasive_leap_red` B, `evasive_leap_yellow` B, `evasive_leap_blue` B |  |
| `pitch-cycle:fate_foreseen` | `fate_foreseen_red` P, `fate_foreseen_yellow` P, `fate_foreseen_blue` P |  |
| `pitch-cycle:felling_swing` | `felling_swing_red` P, `felling_swing_yellow` P, `felling_swing_blue` P |  |
| `pitch-cycle:fertile_ground` | `fertile_ground_red` P, `fertile_ground_yellow` P, `fertile_ground_blue` P |  |
| `pitch-cycle:flock_of_the_feather_walkers` | `flock_of_the_feather_walkers_red` P, `flock_of_the_feather_walkers_yellow` P, `flock_of_the_feather_walkers_blue` P |  |
| `pitch-cycle:frosting` | `frosting_red` P, `frosting_yellow` P, `frosting_blue` P |  |
| `pitch-cycle:fry` | `fry_red` B, `fry_yellow` B, `fry_blue` B |  |
| `pitch-cycle:full_tilt` | `full_tilt_red` P, `full_tilt_yellow` P, `full_tilt_blue` P |  |
| `pitch-cycle:gas_guzzler` | `gas_guzzler_red` P, `gas_guzzler_yellow` P, `gas_guzzler_blue` P |  |
| `pitch-cycle:head_jab` | `head_jab_red` B, `head_jab_yellow` B, `head_jab_blue` B |  |
| `pitch-cycle:healing_balm` | `healing_balm_red` P, `healing_balm_yellow` P, `healing_balm_blue` P |  |
| `pitch-cycle:heart_wrencher` | `heart_wrencher_red` P, `heart_wrencher_yellow` P, `heart_wrencher_blue` P |  |
| `pitch-cycle:heavens_claws` | `heavens_claws_red` B, `heavens_claws_yellow` B, `heavens_claws_blue` B |  |
| `pitch-cycle:ice_bolt` | `ice_bolt_red` P, `ice_bolt_yellow` P, `ice_bolt_blue` P |  |
| `pitch-cycle:lead_the_charge` | `lead_the_charge_red` P, `lead_the_charge_yellow` P, `lead_the_charge_blue` P |  |
| `pitch-cycle:leg_tap` | `leg_tap_red` B, `leg_tap_yellow` B, `leg_tap_blue` B |  |
| `pitch-cycle:lightning_form` | `lightning_form_red` P | `lightning_form_yellow`, `lightning_form_blue` |
| `pitch-cycle:nimblism` | `nimblism_red` P, `nimblism_yellow` P, `nimblism_blue` P |  |
| `pitch-cycle:power_play` | `power_play_red` P, `power_play_yellow` P, `power_play_blue` P |  |
| `pitch-cycle:pummel` | `pummel_red` P, `pummel_yellow` P, `pummel_blue` P |  |
| `pitch-cycle:raging_onslaught` | `raging_onslaught_red` B, `raging_onslaught_yellow` B, `raging_onslaught_blue` B |  |
| `pitch-cycle:razor_reflex` | `razor_reflex_red` P, `razor_reflex_yellow` P, `razor_reflex_blue` P |  |
| `pitch-cycle:razzle_dazzle` | `razzle_dazzle_red` P, `razzle_dazzle_yellow` P, `razzle_dazzle_blue` P |  |
| `pitch-cycle:read_the_runes` | `read_the_runes_red` P 4P | `read_the_runes_yellow`, `read_the_runes_blue` |
| `pitch-cycle:rebellious_rush` | `rebellious_rush_red` B, `rebellious_rush_yellow` B, `rebellious_rush_blue` B |  |
| `pitch-cycle:ronin_renegade` | `ronin_renegade_red` B, `ronin_renegade_yellow` B, `ronin_renegade_blue` B |  |
| `pitch-cycle:savage_swing` | `savage_swing_red` P, `savage_swing_yellow` P, `savage_swing_blue` P |  |
| `pitch-cycle:scalding_rain` | `scalding_rain_red` P, `scalding_rain_yellow` P, `scalding_rain_blue` P |  |
| `pitch-cycle:scar_for_a_scar` | `scar_for_a_scar_red` P, `scar_for_a_scar_yellow` P, `scar_for_a_scar_blue` P |  |
| `pitch-cycle:seek_horizon` | `seek_horizon_red` P 4P | `seek_horizon_yellow`, `seek_horizon_blue` |
| `pitch-cycle:sharpen_steel` | `sharpen_steel_red` P, `sharpen_steel_yellow` P, `sharpen_steel_blue` P |  |
| `pitch-cycle:sic_em_shot` | `sic_em_shot_red` B, `sic_em_shot_yellow` B, `sic_em_shot_blue` B |  |
| `pitch-cycle:sigil_of_solace` | `sigil_of_solace_red` P 4P, `sigil_of_solace_yellow` P, `sigil_of_solace_blue` P |  |
| `pitch-cycle:sink_below` | `sink_below_red` P, `sink_below_yellow` P, `sink_below_blue` P |  |
| `pitch-cycle:sloggism` | `sloggism_red` P, `sloggism_yellow` P, `sloggism_blue` P |  |
| `pitch-cycle:smash_with_big_tree` | `smash_with_big_tree_red` B, `smash_with_big_tree_yellow` B, `smash_with_big_tree_blue` B |  |
| `pitch-cycle:snatch` | `snatch_red` P, `snatch_yellow` P, `snatch_blue` P |  |
| `pitch-cycle:stony_woottonhog` | `stony_woottonhog_red` P | `stony_woottonhog_yellow`, `stony_woottonhog_blue` |
| `pitch-cycle:strike_gold` | `strike_gold_red` P 4P, `strike_gold_yellow` P, `strike_gold_blue` P |  |
| `pitch-cycle:stroke_of_foresight` | `stroke_of_foresight_red` P | `stroke_of_foresight_yellow`, `stroke_of_foresight_blue` |
| `pitch-cycle:surging_militia` | `surging_militia_red` P | `surging_militia_yellow`, `surging_militia_blue` |
| `pitch-cycle:surging_strike` | `surging_strike_red` B, `surging_strike_yellow` B, `surging_strike_blue` B |  |
| `pitch-cycle:throttle` | `throttle_red` P, `throttle_yellow` P, `throttle_blue` P |  |
| `pitch-cycle:unmovable` | `unmovable_red` P, `unmovable_yellow` P, `unmovable_blue` P |  |
| `pitch-cycle:voltic_bolt` | `voltic_bolt_red` P, `voltic_bolt_yellow` P, `voltic_bolt_blue` P |  |
| `pitch-cycle:warriors_valor` | `warriors_valor_red` P, `warriors_valor_yellow` P, `warriors_valor_blue` P |  |
| `pitch-cycle:winters_grasp` | `winters_grasp_red` B, `winters_grasp_yellow` B, `winters_grasp_blue` B |  |
| `pitch-cycle:wounded_bull` | `wounded_bull_red` P, `wounded_bull_yellow` P, `wounded_bull_blue` P |  |
| `pitch-cycle:wounding_blow` | `wounding_blow_red` B, `wounding_blow_yellow` B, `wounding_blow_blue` B |  |
| `pitch-cycle:wrecker_romp` | `wrecker_romp_red` P, `wrecker_romp_yellow` P, `wrecker_romp_blue` P |  |
| `pitch-cycle:yinti_yanti` | `yinti_yanti_red` P, `yinti_yanti_blue` P | `yinti_yanti_yellow` |
| `pitch-cycle:zap` | `zap_red` P, `zap_yellow` P, `zap_blue` P |  |
| `pitch-cycle:zero_to_fifty` | `zero_to_fifty_red` P, `zero_to_fifty_yellow` P, `zero_to_fifty_blue` P |  |
| `pitch-cycle:zero_to_sixty` | `zero_to_sixty_red` P, `zero_to_sixty_yellow` P, `zero_to_sixty_blue` P |  |
| `pitch-cycle:zipper_hit` | `zipper_hit_red` P, `zipper_hit_yellow` P, `zipper_hit_blue` P |  |

## Physical layout and proposed organization

All3,079 scenario folders are immediate children of `Tests/Integration/FaBSim/`. None is an immediate scenario directory in the application `FaBSim/` root. The existing framework stores five files per scenario and currently discovers one flat level. Temporary runner games live separately under `FaBSim/Games/`; they are not canonical test fixtures.

A future layout could be `Tests/Integration/FaBSim/cards/<cycle>/<scenario>/` plus `rules/<scenario>/`. This handoff does not relocate any fixture. Migration needs a shared resolver/recursive discovery that finds actual complete fixtures, preserves globally unique scenario IDs and accepts legacy flat paths; recorder save routing; runner `--test` and `baseFixture` compatibility; Python inventory/control/coverage-tool traversal; and coverage/evidence path updates. Keep all five bytes identical, then check old/new lookup, recorder roundtrip,2/4-player controls and two full clean replays on the resulting framework runtime. Simply moving directories would currently break discovery.

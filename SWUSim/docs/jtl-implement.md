# JTL — Card Implementation Plan

> ## ✅ Progress: 100% CARD COMPLETE — 266 / 266 CardIDs done. Regression: **996 passing, 0 failed.**
> _Verified 2026-06-20 by (a) diffing the Done list against the generated dictionary (262 numbered JTL_001–262 + JTL_T01–T04 = 0 missing) AND (b) an inverse-stub sweep confirming every `Has*Ability` stub has a real handler (whenPlayed/onAttack/onAttackEnd/whenDefeated/onDefense/whenPlayedAsUpgrade/onAttached + leaderAbilities/baseAbilities/cardDiscardedHandlers)._
>
> **Final gap-closure 2026-06-20** — the inverse-stub sweep caught cards in the Done list that had a stub but no handler (silent no-ops), plus the 2 known not-in-list riders:
> - **JTL_070 U-Wing Lander** ✅ — complete-attack "move an upgrade on this unit to another friendly Vehicle". New on-attack-end handler reusing an extended `SWUQueueMoveUpgrade` (now takes `$sourceHostMz` + `$destScope='friendlyVehicle'`). 4 tests.
> - **JTL_191 Invincible** ✅ — deploy-leader bounce (return a non-leader unit ≤3 to owner's hand). New on-deploy-leader reaction hook in `SWUDeployLeader` → `JTL191DeployTrigger`. 4 tests.
> - **JTL_089 The Invisible Hand** ✅ — the "(and survives)" complete-attack COPY of its top-8 Droid search was never wired (only When Played was). Aliased `$onAttackEndAbilities["JTL_089:0"]` to the existing closure + stub. 1 test.
> - **JTL_039 Chimaera** ✅ — its own "When Defeated: create 2 TIE tokens" was missing (only the When-Played "use a WhenDefeated ability" was built). Added `$whenDefeatedAbilities["JTL_039:0"]`. 1 test.
> - **JTL_003 / JTL_006 / JTL_009 / JTL_017** (pilot leaders) ✅ — each had a real **"When deployed as an upgrade:"** ability that was unimplemented (the long-deferred deploy-as-Pilot Epic variant): JTL_003 = may Shield a unit in a different arena; JTL_006 = create 2 TIEs; JTL_009 = deal up to 4 split damage; JTL_017 = ready a resource per odd-cost friendly unit/upgrade. Registered `$whenPlayedAsUpgradeAbilities["X:0"]` (they fire via the existing pilot-deploy flush `_SWUFinalizeUpgradeAttach` → `CollectWhenPlayedAsUpgradeTriggers`). 4 tests.
> - **JTL_221 Stolen AT-Hauler** — sweep false positive: its "When Defeated: opponent may play from owner's discard free" is implemented via `$cardDiscardedHandlers['JTL_221:0']` (sets the `OTPF` modifier), not `whenDefeatedAbilities`. Already done + 3 tests.
> - Generator: `zzCardCodeGenerator.php` now detects the "completes an attack **(and survives)**:" phrasing → onAttackEnd stub (durable across regen).

266 cards total: 18 Leaders, 13 Bases, ~150 Units, ~60 Events, 7 Upgrades, 4 Tokens. **215 needs-work, 51 done** (8 vanilla + 9 blank bases + 25 keyword-only + 9 implemented) — _original baseline; see the progress line above for current status._

> **Core mechanic — Piloting (CR §17): already built.** `SWUComputePilotCost`, `SWUQueuePilotVehiclePick`, `SWUGetPilotValidTargets`, `SWUVehiclePilotCount/Capacity`, `SWUPilotCanAttach`, `IsPilot` (GameLogic + CardDQHandlers + KeywordEffects + CustomInput). The 38 cards in `GeneratedKeywordCode.php`'s `$Piloting_Cards` block have the keyword auto-wired; only the 6 pure-Piloting units are full no-ops, the rest still need their non-keyword abilities (Phase 17). All other JTL keywords carry over from SOR.

> **Core mechanic — Unit↔upgrade move/attach: now built (session 50).** `SWUMoveUnitToUpgrade($unitMz,$hostMz)` (defeats normal upgrades, drops damage, carries captives on the pilot subcard's `Captives` list) + `SWUMoveUpgradeToUnit($hostMz,$subIdx,$arena,$exhaust)` (recreates the arena unit) — **UniqueID is PRESERVED** across the transition so `SWU_PLAYED_UNIT_{uid}` survives (Luke SOR_005 "played this phase"). `_SWUFinalizeUpgradeAttach` now assigns a UID + sets `SWU_PLAYED_UNIT` on pilot plays. Captives ride through unit→upgrade→unit; `SWURescueCaptivesOf` extended to release captives nested on a pilot subcard if the whole host leaves play. Consumers built: **JTL_126 Eject** (upgrade→unit) + **JTL_038 Corvus** (unit→upgrade, AND relocates a pilot already an upgrade on another vehicle via `SWURelocatePilotSubcard`). 7 tests.
> **Defeat-replacement (CR) — deferred-bag, now built (session 50).** "If this would be defeated, you may instead …" via `$gDeferredReplacements`: `SWUDefeatUnit($p,$mz,$skipReplacement=false)` parks a would-be-defeated unit with an available replacement (NOT discarded; WhenDefeated doesn't fire) → `SWUFlushDeferredReplacements()` (called in `SWUAfterAction`) offers the controller the optional `DEFEAT_REPLACE` YESNO → on YES pick a target + apply the move; on NO / no-target → `SWUDefeatUnit(…,true)` real defeat. **JTL_049 L3-37** (unit→pilot-upgrade on a friendly pilot-less Vehicle) — covers BOTH the effect-defeat (`SWUDefeatUnit`) and combat-defeat (attacker/defender deflect in the `SWUCombatDamage` handler) paths; 3 tests. **JTL_094 Luke** (pilot-UPGRADE→exhausted ground unit) — the `upgrade_to_unit` kind, snapshot-based (`_SWUDeferPilotDefeatReplacements` at all 3 host-leave-play sites since the host is gone by drain time; `$gReplaceSnapshots` keyed by UID; `DEFEAT_REPLACE_UPG` rebuilds via `AddGroundArena` on YES / discards on NO); 2 tests. Covers ALL defeat sources: effect-defeat (`SWUDefeatUnit`), combat-defeat (`SWUCombatDamage`), host-leaves-play (3 sites), AND the "defeat an upgrade" EFFECT with the host surviving (`SWUDefeatUpgrade` deflect → same snapshot path). **934 passing, 9 defeat-replacement/follow-up tests.**

> **Core mechanic — Indirect damage: now built** (re-run). `SWUDealIndirectDamage` / `SWUDealIndirectToChosenPlayer` / `SWUDealIndirectToEachOpponent`, the receiver-assignment funnel (`SWUControllerAssignsIndirect` / `SWUApplyIndirectAssignment`), the "assign all" source set (`SWUIndirectAssignToOpponentSources`), and `INDIRECT_APPLY` / `INDIRECT_CHOOSE_PLAYER` handlers. Its dependent cards moved from pair-programmed to **autonomous** (Phase 21).

> **Implemented:** all phases 1–24 complete, plus the 2026-06-20 gap-closure pass (see progress block). No outstanding card work — the set is 100% card complete. The "Implemented so far (9)" seed list — JTL_013, JTL_036, JTL_100, JTL_143, JTL_172, JTL_221, JTL_234, JTL_245, JTL_249 — was the original baseline and is long superseded.

### Already Done
JTL_019, JTL_020, JTL_022, JTL_023, JTL_026, JTL_027, JTL_029, JTL_030, JTL_031, JTL_058, JTL_061, JTL_064, JTL_065, JTL_068, JTL_069, JTL_095, JTL_108, JTL_110, JTL_112, JTL_114, JTL_118, JTL_136, JTL_159, JTL_166, JTL_167, JTL_184, JTL_190, JTL_196, JTL_212, JTL_214, JTL_224, JTL_225, JTL_236, JTL_241, JTL_246, JTL_251, JTL_255, JTL_258, JTL_T01, JTL_T02, JTL_T03, JTL_T04, JTL_013, JTL_036, JTL_100, JTL_143, JTL_172, JTL_221, JTL_234, JTL_245, JTL_249, JTL_001, JTL_003, JTL_004, JTL_005, JTL_006, JTL_007, JTL_008, JTL_010, JTL_011, JTL_012, JTL_014, JTL_015, JTL_016, JTL_017, JTL_037, JTL_051, JTL_102, JTL_140, JTL_144, JTL_151, JTL_153, JTL_170, JTL_173, JTL_176, JTL_239, JTL_248, JTL_040, JTL_041, JTL_055, JTL_078, JTL_080, JTL_091, JTL_168, JTL_175, JTL_230, JTL_033, JTL_044, JTL_062, JTL_067, JTL_071, JTL_072, JTL_075, JTL_076, JTL_199, JTL_200, JTL_042, JTL_053, JTL_060, JTL_079, JTL_085, JTL_088, JTL_106, JTL_115, JTL_160, JTL_161, JTL_194, JTL_229, JTL_253, JTL_256, JTL_081, JTL_104, JTL_107, JTL_113, JTL_137, JTL_257, JTL_032, JTL_052, JTL_054, JTL_059, JTL_138, JTL_163, JTL_182, JTL_185, JTL_188, JTL_204, JTL_259, JTL_082, JTL_092, JTL_099, JTL_117, JTL_122, JTL_130, JTL_243, JTL_252, JTL_254, JTL_135, JTL_157, JTL_178, JTL_179, JTL_195, JTL_206, JTL_209, JTL_217, JTL_262, JTL_220, JTL_232, JTL_233, JTL_063, JTL_089, JTL_119, JTL_128, JTL_154, JTL_164, JTL_205, JTL_207, JTL_208, JTL_097, JTL_123, JTL_124, JTL_156, JTL_177, JTL_193, JTL_202, JTL_228, JTL_231, JTL_261, JTL_134, JTL_146, JTL_147, JTL_186, JTL_187, JTL_238, JTL_047, JTL_111, JTL_158, JTL_201, JTL_087, JTL_090, JTL_198, JTL_216, JTL_073, JTL_120, JTL_192, JTL_227, JTL_034, JTL_035, JTL_045, JTL_046, JTL_048, JTL_057, JTL_066, JTL_084, JTL_086, JTL_093, JTL_142, JTL_101, JTL_109, JTL_141, JTL_145, JTL_150, JTL_189, JTL_203, JTL_210, JTL_211, JTL_215, JTL_223, JTL_247, JTL_043, JTL_235, JTL_125, JTL_129, JTL_180, JTL_077, JTL_219, JTL_244, JTL_116, JTL_132, JTL_237, JTL_240, JTL_149, JTL_162, JTL_183, JTL_181, JTL_226, JTL_139, JTL_165, JTL_021, JTL_024, JTL_025, JTL_028, JTL_002, JTL_039, JTL_169, JTL_250, JTL_018, JTL_126, JTL_038, JTL_049, JTL_094, JTL_105, JTL_050, JTL_152, JTL_218, JTL_222, JTL_009, JTL_171, JTL_133, JTL_148, JTL_121, JTL_074, JTL_096, JTL_155, JTL_127, JTL_131, JTL_098, JTL_197, JTL_174, JTL_056, JTL_242, JTL_213, JTL_083, JTL_260, JTL_103, JTL_191, JTL_070

---

## Phase 1 — Leader activated abilities (autonomous)
- [x] **Batch 1.1 — JTL_001, JTL_003, JTL_004, JTL_005** — done (675 passing). Reused leader-action play-from-hand (SOR_003/SOR_129), HEAL_TARGET, GIVE_SHIELD, SWU_ATTACKED flag; JTL_005 deploy passive via `$playCostFieldModifiers`. 13 new tests.
  - JTL_001 Asajj Ventress: Action: 1 dmg to a friendly unit → 1 dmg to an enemy unit in same arena (deploy side is a Pilot)
  - JTL_003 Lando Calrissian: Action [1R]: play a unit; if you control a ground + a space unit, give a Shield
  - JTL_004 Rose Tico: Action: heal 2 from a Vehicle that attacked this phase
  - JTL_005 Admiral Piett: Action: play a Capital Ship unit, costs 1 less
- [x] **Batch 1.2 — JTL_006, JTL_007, JTL_008, JTL_010** — done (688 passing). New infra: `SWUCreateUnitToken` helper (Phase 8 reuse), `SWU_ATTACKED_VEHICLE` + `SWU_PLAYED_FO` flags, `SWU_PILOT_DISCOUNT` (durable pilot-cost discount honored at `SWUComputePilotCost`, consumed at attach charge), `_SWUIsResistanceTarget`. 13 new tests.
  - JTL_006 Darth Vader: Action: if you attacked w/ a non-token Vehicle this phase, create a TIE Fighter
  - JTL_007 Admiral Holdo: Action [1R]: a Resistance unit (or unit w/ Resistance upgrade) +2/+2 this phase
  - JTL_008 Wedge Antilles: Action: play a card using Piloting, costs 1 less
  - JTL_010 Captain Phasma: Action: if you played a First Order card this phase, 1 dmg to a base
- [x] **Batch 1.3 — JTL_011, JTL_012, JTL_014** — done (696 passing). JTL_011 (play Vehicle + buff another via snapshot-diff UID exclusion), JTL_012 (`SWU_ATTACKED_FIGHTER` flag), JTL_014 leader (discard 3+/draw). JTL_014 **deploy is a non-epic repeatable Action** (3 resources, control 6+, doesn't set EpicActionUsed — custom `SWUDeployLeader` gate + glow) with a cross-player When-Deployed reveal flow (reveal 4 → opponent discards 2 via opp TempZone → owner draws 1 of remaining via own TempZone). ⚠ **Cross-player data MUST pass via the decision param, not StoreVariable (it's player-scoped).** 8 new tests.
  - JTL_011 Major Vonreg: Action: play a Vehicle; give another unit +1/+0 this phase
  - JTL_012 Luke Skywalker: Action: if you attacked w/ a Fighter this phase, 1 dmg to a unit
  - JTL_014 Admiral Trench: Action: discard a card costing 3+, draw
- [x] **Batch 1.4 — JTL_015, JTL_016, JTL_017** — done (705 passing). JTL_015 (attack a space unit, +1/+0 + Saboteur this attack via attack-duration registry token), JTL_016 (exhaust non-leader → controller creates X-Wing JTL_T02; leader action + deploy On Attack), JTL_017 (reveal top, attack, +1/+0 if both costs odd & different). Confirmed all JTL leader deploy thresholds == printed cost (no override needed). 9 new tests.
  - JTL_015 Rio Durant: Action [1R]: attack w/ a space unit, +1/+0 + Saboteur for the attack
  - JTL_016 Admiral Ackbar: Action [1R]: exhaust a non-leader unit → its controller creates an X-Wing
  - JTL_017 Han Solo: Action: reveal top, attack; if revealed + unit have different odd costs, +1/+0

## Phase 2 — Targeted damage to a unit (autonomous)
- [x] **Batch 2.1 — JTL_037, JTL_051, JTL_102, JTL_140** — done (713). On-attack/when-played damage via may-choose + dynamic amounts; JTL_140 deal-1-to-each-up-to-3 (MZMULTICHOOSE + UID-snapshot AOE). 8 tests.
  - JTL_037 Banshee: On Attack: deal dmg to a unit = damage on this unit
  - JTL_051 Red Squadron X-Wing: When Played: may deal 2 to this, draw
  - JTL_102 Resistance Blue Squadron: When Played: dmg to a unit = # friendly space units
  - JTL_140 IG-2000: Overwhelm + When Played: 1 dmg to each of up to 3 units
- [x] **Batch 2.2 — JTL_144, JTL_151, JTL_153, JTL_170** — done (721). JTL_144 event (remaining-HP−1 in OnPlayEvent), JTL_151 (damaged-only target), JTL_153 (hand-count damage), JTL_170 (+1/+0 per damaged unit passive in ObjectCurrentPower + when-played deal-1-to-any-number). 8 tests.
  - JTL_144 No Disintegrations: dmg to a non-leader unit = 1 less than its remaining HP
  - JTL_151 Red Five: On Attack: may deal 2 to a damaged unit
  - JTL_153 Rebellious Hammerhead: When Played: dmg to a unit = cards in your hand
  - JTL_170 War Juggernaut: +1/+0 per damaged unit + When Played: 1 dmg to any number of units
- [x] **Batch 2.3 — JTL_173, JTL_176, JTL_239, JTL_248** — done (728). JTL_173 friendly+same-arena-enemy 3-each (event), JTL_176 deal-3-then-if-defeated-base (event, UID defeat check), JTL_239 (when-played damaged-only), JTL_248 (when-played self 3). 7 tests.
  - JTL_173 Fight Fire With Fire: 3 dmg to a friendly + an enemy unit in same arena
  - JTL_176 Shoot Down: 3 to a space unit; if defeated, may deal 2 to a base
  - JTL_239 TIE Dagger Vanguard: When Played: may deal 2 to a damaged unit
  - JTL_248 Dilapidated Ski Speeder: When Played: 3 dmg to this unit

## Phase 3 — Defeat unit / upgrade (autonomous)
- [x] **Batch 3.1 — JTL_040, JTL_041, JTL_055** — done (732). JTL_040 (when-defeated defeat ≤3-cost space), JTL_041 (defeat enemy + name-hunt deck&hand via CardTitle + EngineShuffle), JTL_055 (event defeat ≤3-HP space + conditional exp). ⚠ combat-whenDefeated on the NON-active player's unit stalls the harness — test via the active player attacking into a lethal counter. 4 tests.
  - JTL_040 Fleet Interdictor: Sentinel + When Defeated: defeat a space unit ≤3
  - JTL_041 Annihilator: When Played/Defeated: may defeat an enemy unit + name-hunt its deck&hand
  - JTL_055 You're All Clear, Kid: defeat a space unit ≤3 HP; if opp no space, give exp
- [x] **Batch 3.2 — JTL_078, JTL_080, JTL_091** — done (736). All events: JTL_078 (defeat non-leader Vehicle), JTL_080 (mass-defeat unupgraded via UID snapshot; token upgrades count as "upgraded"), JTL_091 (defeat friendly + may give 2 exp). 4 tests.
  - JTL_078 Direct Hit: defeat a non-leader Vehicle
  - JTL_080 Nebula Ignition: defeat each unit that isn't upgraded
  - JTL_091 Apology Accepted: defeat a friendly unit; may give 2 exp to a unit
- [x] **Batch 3.3 — JTL_168, JTL_175, JTL_230** — done (740). JTL_168 (on-attack may defeat upgrade via SWUQueueDefeatUpgrade), JTL_175 (event defeat non-leader upgrade `leader=0` + deal 1 to host via thenHandler), JTL_230 (event deal 2 + exhaust Droid/Vehicle). ⚠ fixed: the upgrade-defeat **auto-defeat single-match path** now also invokes the thenHandler (passes host mzID). 4 tests.
  - JTL_168 Insurgent Saboteurs: Saboteur + On Attack: may defeat an upgrade
  - JTL_175 System Shock: defeat a non-leader upgrade; 1 dmg to that unit
  - JTL_230 Electromagnetic Pulse: 2 dmg to a Droid/Vehicle and exhaust it

## Phase 4 — Heal / Shield / Experience grants (autonomous)
- [x] **Batch 4.1 — JTL_033, JTL_044, JTL_062** — done (744). JTL_033 (when-defeated heal base), JTL_044 (when-played shield a damaged Vehicle), JTL_062 (reactive "when healed" via new `_SWUOnUnitHealed` hook in OnHealUnit). 4 tests.
  - JTL_033 Onyx Squadron Brute: When Defeated: heal 2 from a base
  - JTL_044 Echo Base Engineer: When Played: may give a Shield to a damaged Vehicle
  - JTL_062 Silver Angel: when 1+ dmg healed from this: may deal 1 to a space unit
- [x] **Batch 4.2 — JTL_067, JTL_070, JTL_071, JTL_072** — done (748). JTL_067 (2 shields self), JTL_070 (3 exp self; ✅ complete-attack MOVE-UPGRADE rider DONE 2026-06-20 — see header), JTL_071 (when-defeated heal up to 3 unit/base), JTL_072 (shield up to 2 Fringe). 4 tests.
  - JTL_067 Cloaked StarViper: When Played: give 2 Shields to this
  - JTL_070 U-Wing Lander: When Played: 3 exp; complete-attack: move an upgrade to another Vehicle
  - JTL_071 CR90 Relief Runner: Restore 2 + When Defeated: heal up to 3 from a unit or base
  - JTL_072 Wing Guard Security Team: Sentinel + When Played: Shields to up to 2 Fringe units
- [x] **Batch 4.3 — JTL_075, JTL_076, JTL_199, JTL_200** — done (754). JTL_075 (event heal 3 unit/base), JTL_076 (event X-Wing + may shield another), JTL_199 (when-played: opp 3+ exhausted → shield), JTL_200 (on-attack mill; odd cost → may exp to another). 6 tests.
  - JTL_075 Repair: heal 3 from a unit or base
  - JTL_076 Covering the Wing: create an X-Wing; may give a Shield to another unit
  - JTL_199 Blade Squadron B-Wing: When Played: if another player has 3+ exhausted, give a Shield
  - JTL_200 Shuttle Tydirium: On Attack: discard from deck; if odd cost, may give an exp

## Phase 5 — Stat buffs / debuffs / auras / grants (autonomous)
- [x] **Batch 5.1 — JTL_042, JTL_053, JTL_060, JTL_079** — done (759). JTL_042 (event +N/+0 = damage), JTL_079 (event -5/-5), JTL_060 (when-defeated -1/-1), JTL_053 (The Ghost: self-Sentinel-while-upgraded already in HasConditionalKeyword_Sentinel + new aura sharing Sentinel to other friendly Spectre). 5 tests.
  - JTL_042 Power from Pain: a unit +1/+0 per damage on it, this phase
  - JTL_053 The Ghost: other friendly Spectre units gain its keywords; while upgraded gains Sentinel
  - JTL_060 Desperate Commando: When Defeated: may give a unit −1/−1 this phase
  - JTL_079 Out the Airlock: a unit −5/−5 this phase
- [x] **Batch 5.2 — JTL_085, JTL_088, JTL_106, JTL_115** — done (763). JTL_085/115 space auras via new `_SWUSpaceUnitBonus` (in ObjectCurrentPower+HP), JTL_088 (when-played/on-attack +2/+2 another FO), JTL_106 (event +N/+N all friendly, N=distinct names). 4 tests.
  - JTL_085 Victor Leader: other friendly space units +1/+1 (aura)
  - JTL_088 Captain Phasma (unit): When Played/On Attack: another First Order unit +2/+2 this phase
  - JTL_106 Unity of Purpose: +1/+1 per friendly unit with a different name
  - JTL_115 Clone Combat Squadron: +1/+1 per other friendly space unit (aura)
- [x] **Batch 5.3 — JTL_160, JTL_161, JTL_194, JTL_229** — done (768). JTL_160 (on-attack +2/+0 ground), JTL_161 (Vehicle aura +1/+0 via SWUTraitCommanderBonus + Overwhelm already wired; fixed pre-existing TraitContains→CardTraits bug), JTL_194 (event exhaust+-2/-0+conditional bounce), JTL_229 (event grant Sentinel this phase). 5 tests.
  - JTL_160 Supporting Eta-2: On Attack: may give a ground unit +2/+0 this phase
  - JTL_161 Captain Tarkin: friendly Vehicles +1/+0 + gain Overwhelm (aura)
  - JTL_194 Heartless Tactics: exhaust + −2/−0; if 0 power non-leader, may bounce
  - JTL_229 Diversion: give a unit Sentinel this phase
- [x] **Batch 5.4 — JTL_253, JTL_256** — done (770). JTL_253 (event two optional +2/+2 ground+space), JTL_256 (self +1/+0 per other copy). 2 tests.
  - JTL_253 Coordinated Front: may give a ground unit +2/+2 this phase
  - JTL_256 Swarming Vulture Droid: +1/+0 per other friendly Swarming Vulture Droid (aura)

## Phase 6 — Conditional keyword passives (autonomous)
- [x] **Batch 6.1 — JTL_081, JTL_104, JTL_107** — done (775). JTL_081 (Raid 1 while control a token), JTL_107 (Sentinel while control a Vehicle), JTL_104 (Sentinel while another Resistance card via `_SWUControlsAnotherResistance` + when-defeated deal power to enemy). ⚠ fixed pre-existing `TraitContains`→undefined-`CardTraits` bug (un-breaks `PlayerHasUnitWithTraitInPlay`). 5 tests.
  - JTL_081 First Order TIE Fighter: while you control a token unit, gains Raid 1
  - JTL_104 Raddus: while you control another Resistance card, gains Sentinel + When Defeated: deal power to enemy
  - JTL_107 Bunker Defender: while you control a Vehicle, gains Sentinel
- [x] **Batch 6.2 — JTL_113, JTL_137, JTL_257** — done (780). JTL_113 already wired (Sentinel while 6+ resources); JTL_137 (Overwhelm 4+ power, Raid 6+ power via ObjectCurrentPower), JTL_257 (Raid 2 while another Fighter). 5 tests.
  - JTL_113 Homestead Militia: while you control 6+ resources, gains Sentinel
  - JTL_137 Vonreg's TIE Interceptor: 4+ power → Overwhelm; 6+ power → Raid 1
  - JTL_257 Flanking Fang Fighter: while you control another Fighter, gains Raid 2

## Phase 7 — Cost-reduction & combat passives (autonomous)
- [x] **Batch 7.1 — JTL_032, JTL_052, JTL_054, JTL_059** — done (785). JTL_032 (Shielded auto + per-round first-WhenDefeated-unit -1 via field modifier + `SWU_KRENNIC_USED` flag set at play, cleared at regroup), JTL_052 (-1/-0 per damage), JTL_054 (defender debuffs attacker -1, modeled on SOR_071), JTL_059 (can't attack — gated in BeginSWUAttack). 5 tests.
  - JTL_032 Director Krennic: Shielded + first When-Defeated unit you play each round costs 1 less
  - JTL_052 D'Qar Cargo Frigate: −1/−0 per damage on it
  - JTL_054 Gold Leader: Shielded + while defending, the attacker gets −1/−0
  - JTL_059 Corporate Defense Shuttle: this unit can't attack
- [x] **Batch 7.2 — JTL_138, JTL_163, JTL_182, JTL_185** — done (789). JTL_138 (-1 if dealt indirect this phase via new `SWU_DEALT_INDIRECT` flag in SWUDealIndirectDamage; -1 path mirrors SHD_182), JTL_163 (-1 per damaged ground unit), JTL_182 (doesn't ready at regroup unless 4+ power — gated in ReadyPhase), JTL_185 (deal-first vs exhausted not-entered-this-phase unit — in SWUCombatDamage $hasShootFirst). 4 tests.
  - JTL_138 Decimator of Dissidents: if you dealt indirect dmg this phase, costs 1 less
  - JTL_163 AT-DP Occupier: cost −1 per damaged ground unit + Overwhelm
  - JTL_182 Rampart: doesn't ready in regroup unless its power is 4+
  - JTL_185 Hound's Tooth: vs an exhausted unit that didn't enter this phase, deals combat dmg first
- [x] **Batch 7.3 — JTL_188, JTL_191, JTL_204, JTL_259** — done (793). JTL_188 (combat-damage-to-base → `SWU_GIDEON_TAX` opp +1/unit cost), JTL_204 (-3 if opp 3+ space), JTL_191 (-1 if control unique Separatist; ✅ deploy-leader bounce rider DONE 2026-06-20 — see header), JTL_259 (cross-arena ground→space attack + -1/-0 self-debuff; added harness `AttackGroundArena:idx:S<idx>`). 4 tests.
  - JTL_188 Moff Gideon: when this deals combat dmg to an opp base, that opp's units cost 1 more this phase
  - JTL_191 Invincible: cost −1 if you control a unique Separatist + when you deploy a leader: bounce a ≤3
  - JTL_204 Home One: cost −3 if opp controls 3+ space units + Ambush
  - JTL_259 Retrofitted Airspeeder: Ambush + can attack space units; −1/−0 while attacking space

## Phase 8 — Token creation (autonomous)
- [x] **Batch 8.1 — JTL_082, JTL_092, JTL_099** — done (796). JTL_082 (TIE), JTL_099 (X-Wing), JTL_092 (8 readied TIEs + CANT_ATTACK_BASES marker, backstopped in combat). 3 tests.
  - JTL_082 Kijimi Patrollers: When Played: create a TIE Fighter
  - JTL_092 Scramble Fighters: create 8 TIEs readied; they can't attack bases this phase
  - JTL_099 Veteran Fleet Officer: When Played: create an X-Wing
- [x] **Batch 8.2 — JTL_117, JTL_122, JTL_130** — done (799). JTL_117 (X-Wing when-played/on-attack), JTL_122 (exhaust up-to-2 space → X-Wing each), JTL_130 (opp-resources/2 X-Wings w/ Sentinel-this-phase). 3 tests.
  - JTL_117 General Draven: When Played/On Attack: create an X-Wing
  - JTL_122 All Wings Report In: exhaust up to 2 space units → an X-Wing per exhaust
  - JTL_130 Timely Reinforcements: an X-Wing w/ Sentinel per 2 of an opp's resources
- [x] **Batch 8.3 — JTL_243, JTL_252, JTL_254** — done (802). JTL_243 (on-attack TIE), JTL_252 (Sentinel + when-played X-Wing), JTL_254 (event 2 X-Wings). 3 tests.
  - JTL_243 Quasar TIE Carrier: On Attack: create a TIE
  - JTL_252 Tantive IV: Sentinel + When Played: create an X-Wing
  - JTL_254 Dedicated Wingmen: create 2 X-Wings

## Phase 9 — Ready / exhaust effects (autonomous)
- [x] **Batch 9.1 — JTL_135, JTL_157, JTL_178** — done (806). JTL_135 (when-played ready if opp more space), JTL_157 (on-attack ready once/round via SWU_JTL157_USED flag), JTL_178 (event: if no initiative taken, ready enemy + friendly same-arena; gate via GetInitiativeCounter UNCLAIMED). 4 tests.
  - JTL_135 Special Forces TIE Fighter: When Played: if opp controls more space units, ready this
  - JTL_157 Relentless Firespray: On Attack: ready this; once per round
  - JTL_178 Face Off: if no player took initiative this phase, ready an enemy + a friendly in same arena
- [x] **Batch 9.2 — JTL_179, JTL_195, JTL_206** — done (809). JTL_179 (ready Fighter/Transport ≤6 power), JTL_195 (exhaust enemy → ready friendly ≤ its power same arena), JTL_206 (ready Vehicle + CANT_ATTACK_BASES). 3 tests.
  - JTL_179 Koiogran Turn: ready a Fighter/Transport unit ≤6 power
  - JTL_195 Cat and Mouse: exhaust an enemy → ready a ≤-power friendly in same arena
  - JTL_206 Fly Casual: ready a Vehicle; it can't attack bases this phase
- [x] **Batch 9.3 — JTL_209, JTL_217, JTL_262** — done (812). JTL_209 (event ready all space if opp more), JTL_217 (when-played may exhaust if another space unit), JTL_262 (event exhaust a unit). 3 tests.
  - JTL_209 It's a Trap: if opp controls more space units, ready each space unit you control
  - JTL_217 Death Space Skirmisher: When Played: if you control another space unit, may exhaust a unit
  - JTL_262 Evasive Maneuver: exhaust a unit

## Phase 10 — Bounce / return to hand (autonomous)
- [x] **Batch 10.1 — JTL_220, JTL_232, JTL_233** — done (815). JTL_220 (when-defeated bounce ≤2-power), JTL_232 (bounce space unit + non-leader upgrades to hand; ⚠ free-replay rider DEFERRED), JTL_233 (bounce up-to-2 same-arena combined-cost-≤3, validated in resolver). 3 tests.
  - JTL_220 Skyway Cloud Car: When Defeated: may return a non-leader unit ≤2 power
  - JTL_232 Jump to Lightspeed: return a friendly space unit + upgrades; replay a copy free this phase
  - JTL_233 Sweep the Area: return up to 2 non-leader units (combined cost ≤3) in same arena

## Phase 11 — Draw / discard / deck manipulation / search (autonomous)
- [x] **Batch 11.1 — JTL_063, JTL_089, JTL_119** — done (818). JTL_063 (when-defeated may draw), JTL_089 (when-played search top-8 Droid via DoTopDeckSearch; ✅ complete-attack copy DONE 2026-06-20 — see header), JTL_119 (when-played ramp top deck → resource). 3 tests.
  - JTL_063 Landing Shuttle: When Defeated: may draw
  - JTL_089 The Invisible Hand: When Played/complete-attack: search top 8 for a Droid, draw it (free if ≤2)
  - JTL_119 Resupply Carrier: When Played: may put top of deck into play as a resource
- [x] **Batch 11.2 — JTL_128, JTL_154, JTL_164** — done (821). JTL_128 (search top-8 up-to-2 Vehicles; SOR_125 reprint mechanic), JTL_154 (whenPlayed/whenDefeated choose-player discard + conditional 2nd; ⚠ 2nd-discard needs interactive cross-player, tested self+opp auto branches), JTL_164 (conditional ramp: opp>my resources, reuses JTL_119). 3 tests.
  - JTL_128 Prepare for Takeoff: search top 8 for up to 2 Vehicles, draw them
  - JTL_154 Profundity: Overwhelm + When Played/Defeated: discard race vs a chosen player
  - JTL_164 Cham Syndulla: When Played: if opp more resources, may put top into play as a resource
- [x] **Batch 11.3 — JTL_205, JTL_207, JTL_208** — done (824). JTL_205 (discard-pile card → bottom of owner's deck + X-Wing token; reuses SOR_252 move + JTL_T02), JTL_207 (look-at-hand discard an event; SOR_200 pattern + event filter), JTL_208 (mill 3 each deck, deal #odd-cost discarded to a unit). 3 tests.
  - JTL_205 Commence Patrol: put a discard-pile card on bottom of deck → create an X-Wing
  - JTL_207 Jam Communications: look at opp's hand, discard an event from it
  - JTL_208 Never Tell Me the Odds: mill 3 each; dmg to a unit = # odd-cost cards milled

## Phase 12 — Modified "attack with a unit" events (autonomous)
- [x] **Batch 12.1 — JTL_097, JTL_123, JTL_124** — done (827). Generalized SWUQueueAnotherAttack/ChainedAttackTrigger with a 5th `constraint` spec field (space/ground/trait). JTL_097 (whenPlayed MAY attack w/ Pilot-or-pilot-hosting unit, +1/+0 + granted RESTORE-1@attack), JTL_123 (event: attack even-if-exhausted, BeginSWUAttack noBases), JTL_124 (event: space attacker, chained ground attacker +2/+0). 3 tests.
  - JTL_097 Leia Organa: Restore 1 + When Played: attack w/ a Pilot unit, +1/+0 + Restore 1 this attack
  - JTL_123 Dogfight: attack w/ a unit even if exhausted; can't attack bases this attack
  - JTL_124 Tandem Assault: attack w/ a space unit, then a ground unit (+2/+0)
- [x] **Batch 12.2 — JTL_156, JTL_177, JTL_193** — done (830). Added per-attack MARKER turn effects (JTL_156/177/193, dur=attack) + helper _SWUAttackHasMarker. JTL_156 (Fighter +4/+0 + granted On-Attack via JTL156Attack dispatch: mill 2 defender deck, |costdiff| unpreventable self-dmg), JTL_177 (Vehicle +2/+0 + base-hit→draw in SWUCollectCombatHitTriggers), JTL_193 (Vehicle attack, counter-damage to attacker prevented in both combat branches). 3 tests.
  - JTL_156 Trench Run: attack w/ a Fighter +4/+0 + granted On-Attack deck-discard ability
  - JTL_177 Stay on Target: attack w/ a Vehicle +2/+0 + granted base-dmg→draw ability
  - JTL_193 I Have You Now: attack w/ a Vehicle; prevent all dmg to it this attack
- [x] **Batch 12.3 — JTL_202, JTL_228, JTL_231, JTL_261** — done (834). JTL_202 (host-reaction at upgrade-attach point: JTL202Upgrade trigger → may attack +1/+0), JTL_228 (event: space attack then may-exhaust a space unit), JTL_231 (event: Vehicle +2/+0 attack), JTL_261 (event: chained 2 space attacks, constraint=space). 4 tests.
  - JTL_202 Black Squadron Scout Wing: when you play an upgrade on this: may attack, +1/+0
  - JTL_228 Barrel Roll: attack w/ a space unit; after, may exhaust a space unit
  - JTL_231 Punch It: attack w/ a Vehicle, +2/+0
  - JTL_261 Attack Run: attack w/ 2 space units (one at a time)

## Phase 13 — On-Attack & unit activated abilities (autonomous)
- [x] **Batch 13.1 — JTL_134, JTL_146, JTL_147** — done (838). JTL_134 (Raid-1 FO aura in GetConditionalKeyword_Raid_Value + Action[Exhaust] draw-if-SWU_PLAYED_FO), JTL_146 (Action[Exhaust] attack-with-Fighter +2/+0 unit ability), JTL_147 (while-upgraded +1/+0 in ObjectCurrentPower + OnAttack may-deal-1 if controls Poe via _SWUControlsTitle). 4 tests.
  - JTL_134 General Hux: FO Raid 1 aura + Action: if you played a FO card this phase, draw
  - JTL_146 Massassi Tactical Officer: Action: attack w/ a Fighter, +2/+0
  - JTL_147 Black One: while upgraded +1/+0 + On Attack: if you control Poe, may deal 1
- [x] **Batch 13.2 — JTL_186, JTL_187, JTL_238** — done (841). Added SWU_PLAYED_BOUNTYHUNTER/SWU_PLAYED_PILOT phase flags (set in ActivateCard, cleared at regroup). JTL_186 (onAttack may-draw if played BH/Pilot), JTL_187 Bossk (onAttack exhaust+1 to defender via SWU_CURRENT_DEFENDER SWUVar; ⚠ manual HasOnAttackAbility stub add — generator missed this dual unit/Piloting card), JTL_238 (onAttack +1/+0 per damaged enemy unit). 3 tests.
  - JTL_186 Mist Hunter: On Attack: if you played a Bounty Hunter/Pilot this phase, may draw
  - JTL_187 Bossk: On Attack: exhaust the defender + 1 dmg
  - JTL_238 Sith Trooper: On Attack: +1/+0 per damaged unit the defender controls

## Phase 14 — When-Played reactive units (autonomous)
- [x] **Batch 14.1 — JTL_047, JTL_111, JTL_158, JTL_201** — done (846). New _SWUOnPlayerDrew hook in DoDrawCard (JTL_111 + SHD_184 opp-draw→may-give-exp, action phase only). JTL_047 (whenPlayed keyword choice stored per-UID SWU_YULAREN_<uid>_<KW>; new _SWUYularenGrants aura wired into Grit/Sentinel/Shielded/Restore conditionals), JTL_158 (whenPlayed self-dmg if no other Fighter), JTL_201 (opp discards, inspect latest discard for Unit → may exhaust). 5 tests.
  - JTL_047 Admiral Yularen: When Played: choose a keyword; friendly Vehicles gain it while in play
  - JTL_111 Seasoned Fleet Admiral: Raid 1 + when an opp draws in the action phase: may give an exp
  - JTL_158 Crackshot V-Wing: When Played: if you control no other Fighter, 1 dmg to this
  - JTL_201 Ahsoka Tano: When Played: opp discards; if a unit, may exhaust a unit

## Phase 15 — When-Defeated & regroup-start triggers (autonomous)
- [x] **Batch 15.1 — JTL_087, JTL_090, JTL_198** — done (850). JTL_087 (whenPlayed/whenDefeated create 1 TIE JTL_T01), JTL_090 (whenPlayed/onAttack/whenDefeated create 3 TIEs), JTL_198 (regroup-start self-1-dmg pass added to RegroupPhaseStart). 4 tests.
  - JTL_087 TIE Ambush Squadron: Ambush + When Played/Defeated: create a TIE
  - JTL_090 Executor: Overwhelm + When Played/On Attack/Defeated: create 3 TIEs
  - JTL_198 Fireball: Ambush + when the regroup phase starts: 1 dmg to this
- [x] **Batch 15.2 — JTL_216** — done (851). JTL_216 (regroup-start defeat-self drain loop in RegroupPhaseStart, modeled on SWU_SNEAK_DEFEAT). 1 test.
  - JTL_216 Contracted Hunter: Ambush + when the regroup phase starts: defeat this

## Phase 16 — Non-pilot upgrades (autonomous)
- [x] **Batch 16.1 — JTL_073, JTL_120** — done (853). JTL_073 (granted WhenDefeated via subcard scan in CollectWhenDefeatedTriggers → JTL073Defeat may-exhaust; covers both combat+effect defeat paths), JTL_120 (Vehicle attach restriction + granted defeat-on-combat-damage via JTL120Defeat in SWUCollectCombatHitTriggers, mirrors Rukh). 2 tests.
  - JTL_073 Grim Valor: attached gains "When Defeated: may exhaust a unit"
  - JTL_120 Dorsal Turret: attach Vehicle; attached gains "combat dmg to a unit while attacking → defeat it"
- [x] **Batch 16.2 — JTL_192, JTL_227** — done (855). JTL_192 (regroup ready-tax via SWUQueueJTL192RegroupTriggers in ReadyPhase: pay 2 keeps ready, else exhaust; 2 tests both branches). JTL_227 (Capital Ship/Transport attach restriction + granted onAttack via OnAttackFromUpgrade: may exhaust enemy non-leader → SWUDealIndirectDamage=its power). ✅ JTL_227 TEST DONE (977 passing): the previously-flagged "onAttack-mid-combat indirect-split mis-resolves" engine bug is **RESOLVED** — verified via TestSchemaStep that the indirect MZSPLITASSIGN now correctly offers the unit+base split (the exhausted unit at its remaining-HP cap), the damaged player assigns it cleanly, and combat target resolution is intact (host's 4 combat + 1 indirect = base 5). Fixed as a side effect of the session-50 indirect-funnel rework (threading the assignment through the decision PARAM so it survives the request boundary on the interactive MZSPLITASSIGN path). Guard test: `SuperheavyIonCannon227_OnAttack_ExhaustIndirect`.
  - JTL_192 In Debt to Crimson Dawn: when attached readies, exhaust it unless controller pays 2
  - JTL_227 Superheavy Ion Cannon: attach Capital/Transport; granted On-Attack exhaust→indirect (uses Phase 21)

## Phase 17 — Pilot card abilities (autonomous)
> Piloting keyword is built; these need the non-keyword text (attached-unit grants, when-played-as-upgrade triggers, if-would-be-defeated re-attach).
- [~] **Batch 17.1 — JTL_034, JTL_035** done (857); **JTL_038 DEFERRED** — JTL_034 (pilot grants Grit via HasConditionalKeyword_Grit upgrade loop), JTL_035 (granted onAttack -1/-1 to enemy in host arena, STAT_DEBUFF registry). ⚠ JTL_038 Corvus (whenPlayed re-host a friendly Pilot onto self + defeat its upgrades + heal) is a complex pilot-move mechanic — deferred. (Confirmed: pilot upgrades DO add printed power/HP to host.) 2 tests.
  - JTL_034 Interceptor Ace: attached gains Grit
  - JTL_035 Tam Ryvora: attached On Attack: an enemy in arena −1/−1 this phase
  - JTL_038 Corvus: Restore 2 + When Played: may attach a friendly Pilot unit/upgrade to this
- [~] **Batch 17.2 — JTL_045, JTL_046, JTL_048** done (860); **JTL_049 DEFERRED** — JTL_045 (Restore-1 grant in Restore upgrade loop), JTL_046 (granted onAttack: exp to host + 1 self-dmg), JTL_048 (granted onAttack: mill defender deck, ≤3-cost → draw). ⚠ JTL_049 L3-37 (if-would-be-defeated re-attach as pilot) is a defeat-replacement mechanic — deferred with JTL_038/094. KEY: a pilot attached as upgrade contributes its **upgradePower/upgradeHp** (JTL_046=2), not unit power. 3 tests.
  - JTL_045 Hera Syndulla: attached gains Restore 1
  - JTL_046 Paige Tico: attached On Attack: give this an exp, then 1 dmg to it
  - JTL_048 Cassian Andor: attached On Attack: discard top of defender deck; ≤3 → draw
  - JTL_049 L3-37: if this would be defeated, may instead attach as a Pilot upgrade
- [x] **Batch 17.3 — JTL_057, JTL_066, JTL_084, JTL_086** — done (864). JTL_057 (whenPlayedAsUpgrade may-heal-2), JTL_084 (whenPlayedAsUpgrade create TIE), JTL_086 (whenPlayedAsUpgrade may-give-exp-to-another), JTL_066 (granted onAttack may-heal-2; ⚠ spread/'any number' simplified to one unit). 4 tests.
  - JTL_057 Astromech Pilot: when played as an upgrade, may heal 2 from a unit
  - JTL_066 Trace Martez: attached On Attack: may heal 2 total among any number of units
  - JTL_084 Wingman Victor Two: when played as an upgrade, create a TIE
  - JTL_086 Wingman Victor Three: when played as an upgrade, may give an exp to another unit
- [~] **Batch 17.4 — JTL_093, JTL_142, JTL_098** done; **JTL_094 DEFERRED** — JTL_093 (+1/+0 per other friendly Pilot unit/upgrade via _SWUCountFriendlyPilots, self-unit + attached-host cases), JTL_142 Vader pilot (granted onAttack may-deal-1 + chain-deal-1-to-unit-or-base on a defeat). **JTL_098 done (Batch C, 962 passing):** Snap Wexley — whenPlayed(as unit)+onAttack arm `SWU_SNAP_DISCOUNT` (one-shot "next Resistance card −1", mirror of the SOR_056 Bendu flag: applied in `SWUComputePlayCost`, consumed in `ActivateCard`, cleared at regroup); whenPlayedAsUpgrade = `DoTopDeckSearch(5, Resistance, 1)`. Hand-added the `HasWhenPlayedAbility` stub + generator `$manualStubAdditions['whenPlayed']`. 2 tests (search, discount resource-delta). ⚠ JTL_094 (defeat-replacement re-attach, joins 038/049 cluster) deferred.
  - JTL_093 Nien Nunb: +1/+0 per other Pilot; attached gets the same
  - JTL_094 Luke Skywalker: if this upgrade would be defeated, may move him to ground as a unit
  - JTL_098 Snap Wexley: when played as unit/On Attack: next Resistance card −1
  - JTL_142 Darth Vader: Piloting; attached On Attack: may deal 1 dmg, chain to deal 1 more on a defeat
- [x] **Batch 17.5 — JTL_101, JTL_109, JTL_141, JTL_103** done — JTL_101 (playCostModifiers -1/pilot via _SWUCountFriendlyPilots + pilot-attach→X-Wing reaction at attach point), JTL_109 (conditional Sentinel grant from pilot upgrade when controlling ground+space), JTL_141 (enemy-damaged +3/+0 via _SWUEnemyUnitDamaged, self+host). **JTL_103 Chewbacca done (Batch E, 976 passing):** new shared **"can't be X by enemy card abilities" immunity layer** — 6 helpers in GameLogic.php (`SWUAvoidsDefeat`/`Bounce`/`Capture`/`AbilityDamage`/`TakeControl`/`Exhaust`), each checking self-CardID + attached-upgrade grants + controller field-passives. Gated at the chokepoints, applied ONLY for enemy-sourced ability actions (actor ≠ controller/owner): `SWUDefeatUnit` (new `$fromDamage` param so SBA "no remaining HP" / damage-lethal at 3 sites stays ungated — combat & lethal damage still defeat), `SWUBounceUnit`, `DoCaptureUnit`, `SWUTakeControlOfUnit` (only blocks non-owner takers), `OnExhaustCard` (self-exhaust always allowed), `SWUDealDamageToUnit` (prevents the instance). Wired 7 cards: **JTL_103** (self defeat+bounce; attached-pilot grants host the same), **SHD_187** (defeat+capture+damage), **TWI_220** (attached: defeat+bounce+capture), **LOF_040** (attached Force unit: exhaust), **LOF_073** (controller's upgraded units: exhaust+bounce), **SEC_012** deploy-side (defeat, while initiative — also added to `SWUImmuneToHpDefeat`), **LAW_149** (defeat+take-control). 7 tests across the verbs incl. a combat-still-kills negative guard. 3 tests.
  - JTL_101 Red Leader: cost −1 per friendly Pilot + when a Pilot attaches to this: create an X-Wing
  - JTL_103 Chewbacca: can't be defeated/bounced by enemy abilities; attached gains the same
  - JTL_109 Jarek Yeager: while you control a ground + a space unit, attached gains Sentinel
  - JTL_141 IG-88: while an enemy is damaged +3/+0; attached gets the same
- [x] **Batch 17.6 — JTL_145, JTL_150, JTL_189, JTL_148** done — JTL_145 (whenPlayedAsUpgrade may-pay-2 → ready a Resistance unit via READY_UNIT), JTL_150 (host-subtype passives: Fighter→Overwhelm, Transport→+0/+1, Speeder→Grit), JTL_189 (whenPlayedAsUpgrade deal 1, or 2 if host is Transport). **JTL_148 done (Batch A, 953 passing):** whenPlayedAsUpgrade `SWUQueueDefeatUpgrade(may, max:1, filter:'cost<=2', min:0)` — the `cost` clause already existed in `SWUUpgradeMatchesFilter`, so a one-liner. 2 tests (defeat + decline; a cost-3 upgrade present proves the filter). 3 tests.
  - JTL_145 BB-8: when played as an upgrade, may pay 2 → ready a Resistance unit
  - JTL_148 Frisk: when played as an upgrade, may defeat an upgrade ≤2
  - JTL_150 Biggs Darklighter: attached gains a keyword based on host subtype (Fighter/Transport/Speeder)
  - JTL_189 Boba Fett: Shielded + when played as an upgrade, 1 dmg (2 if attached is a Transport)
- [x] **Batch 17.7 — JTL_203, JTL_210, JTL_211, JTL_197** done — JTL_203 (whenPlayedAsUpgrade may-attack-with-host, Falcon→SHOOT_FIRST), JTL_210 (whenPlayed-as-unit exhaust up-to-2 ground [⚠ manual HasWhenPlayedAbility stub] + whenPlayedAsUpgrade exhaust enemy in arena), JTL_211 (Raid-1 grant via Raid upgrade loop). **JTL_197 done (Batch C, 962 passing):** Anakin — NEW `OnAttackEndFromUpgrade` seam (mirrors the OnAttack-from-upgrade scan: `CollectAfterAttackTriggers` scans the surviving attacker's upgrades against new global `$onAttackEndFromUpgradeAbilities`, `AddTrigger`→`OnAttackEndFromUpgradeTrigger`; the surviving-attacker null check IS the "and survives" gate). The ability offers a YESNO → new `SWUReturnUpgradeToHand($hostMz,$cardID)` detaches the pilot to its owner's hand. 2 tests (return, decline). 3 tests.
  - JTL_197 Anakin Skywalker: when attached completes an attack, may return this upgrade to hand
  - JTL_203 Han Solo: Ambush + when played as an upgrade, may attack (Falcon → deals first)
  - JTL_210 The Mandalorian: when played as a unit, exhaust up to 2 ground units
  - JTL_211 Independent Smuggler: Raid 1; attached gains Raid 1
- [x] **Batch 17.8 — JTL_215, JTL_223, JTL_213** done — JTL_215 (whenPlayedAsUpgrade mill 2, odd-cost→hand even→discard), JTL_223 (pilot-attach reaction JTL223Attach → may-bounce non-leader ≤2 or exhausted ≤4). **JTL_213 done (Batch D, 965 passing):** Sidon Ithano — whenPlayed-as-unit (manual stub) MZMAYCHOOSE an enemy pilotless Vehicle → `SWUMoveUnitToUpgrade(self, enemyHost, isPilot:true)`. As a Pilot Sidon is **−2/−2**, so it DEBUFFS the enemy host (SOR_237 2/3 → 0/1). 1 test.
  - JTL_213 Sidon Ithano: when played as a unit, may attach to an enemy Vehicle without a Pilot
  - JTL_215 BoShek: when played as an upgrade, discard 2 from deck, return the odd-cost ones
  - JTL_223 Razor Crest: when a Pilot attaches: may bounce a cheap/exhausted non-leader unit
- [x] **Batch 17.9 — JTL_247** done (878). While it has a Pilot on it, +1/+1 (ObjectCurrentPower + HP via _SWUHasPilotOnIt). 3 tests across 17.8/17.9.
  - JTL_247 Resistance X-Wing: while it has a Pilot, +1/+1

## Phase 18 — Take control / steal upgrades (autonomous)
- [~] **Batch 18.1 — JTL_043, JTL_056** done; **JTL_083 DEFERRED** — JTL_043 (event: take control of a non-leader unit via existing SWUTakeControlOfUnit, re-resolve by UID, then SWUDefeatUnit → owner's discard). **JTL_056 Hondo done (Batch D, 965 passing):** Shielded + On Attack → new shared **`SWUQueueMoveUpgrade($player,$filter,$tooltip)`** subsystem (stages every matching upgrade across all units into TempZone for a single MAY pick via `MoveUpgMap` tempZone-N→"hostMz:subIdx", then a destination-unit MZCHOOSE, then `SWUMoveUpgradeCrossUnit`). `filter='nonpilot'`. The destination MZCHOOSE is queued from the `MOVE_UPGRADE` CUSTOM (not the OnAttack closure) so it dodges the OnAttack MZCHOOSE-auto-skip gotcha; the upgrade MZMAYCHOOSE is fine in OnAttack. 1 test. **JTL_083 Pantoran Starship Thief done (Batch D2, 969 passing):** whenPlayed-as-unit MZMAYCHOOSE a pilotless Fighter/Transport (any owner; the may-pick IS the "pay 3" opt-in) → `SWUMoveUnitToUpgrade` self-attach as a Pilot + `SWUTakeControlOfUnit(host)`. The "when this upgrade detaches → owner takes control" half is one line added to the **SOR_122 Traitorous return-control condition** in `SWUDefeatUpgrade` (`JTL_083` shares it). 2 tests (attach+take-control, defeat-pilot→return).
  - JTL_043 No Glory, Only Results: take control of a non-leader unit, then defeat it
  - JTL_056 Hondo Ohnaka: Shielded + On Attack: steal a non-Pilot upgrade and reattach it
  - JTL_083 Pantoran Starship Thief: When Played: pay 3 → attach to + take control of a Fighter/Transport
- [~] **Batch 18.2 — JTL_235, JTL_242** done; **JTL_260 DEFERRED** — JTL_235 Commandeer (take control of non-leader Vehicle ≤6 w/o pilot, ready it, SWU_JTL235_RETURN_<uid> flag → RegroupPhaseStart bounce-to-owner pass). **JTL_242 Shuttle ST-149 done (Batch D, 965 passing):** Shielded + WhenPlayed/WhenDefeated → `SWUQueueMoveUpgrade(filter:'token')` (the same shared subsystem as JTL_056). 1 test (combat-defeat → move a Shield token cross-unit). **JTL_260 Death Star Plans done (Batch D2, 969 passing):** NEW `OnAttackedFromUpgrade` reactive seam (mirrors OnDefense: `CollectCombatStep1Triggers` scans the attacked defender's upgrades against new global `$onAttackedFromUpgradeAbilities`, `AddTrigger`→`OnAttackedFromUpgradeTrigger` for the ATTACKER, routed through `JTL_260#0` so the destination MZCHOOSE dodges the OnAttack-counting gotcha → `SWUMoveUpgradeCrossUnit` steals it to an attacker unit). Plus the granted **"first unit each round −2"** cost discount: `_SWUControlsUnitBearingUpgrade` gate in `SWUComputePlayCost`, `SWU_JTL260_USED` per-round flag consumed in `ActivateCard`, cleared at RegroupPhaseStart. 2 tests (steal-on-attacked, first-unit discount resource-delta). 2 tests.
  - JTL_235 Commandeer: take control of a non-Pilot Vehicle ≤6, ready it, return next regroup
  - JTL_242 Shuttle ST-149: Shielded + When Played/Defeated: steal a token upgrade and reattach
  - JTL_260 Death Star Plans: when attached is attacked, attacker steals this upgrade + granted cost-down

## Phase 19 — Power-based & mass damage events (autonomous)
- [x] **Batch 19.1 — JTL_125, JTL_129, JTL_127** done — JTL_125 (if more space units, deal 4 to enemy ground), JTL_129 (sum friendly-Vehicle power in chosen unit's arena → deal to it). **JTL_127 done (Batch B, 956 passing):** Lightspeed Assault — choose friendly space unit (capture power), choose enemy space unit, defeat friendly + deal its power to enemy, then `SWUDealIndirectDamage(player, enemyPower, enemyController)` ("if you do"). Enemy power captured before damage (power ≠ HP); friendly re-resolved by UID. 2 tests (defeat→deal→indirect-to-base, no-enemy-space fizzle).
  - JTL_125 Air Superiority: if you control more space units, 4 dmg to an enemy ground unit
  - JTL_127 Lightspeed Assault: defeat a friendly space unit, deal its power to an enemy space unit
  - JTL_129 Focus Fire: each friendly Vehicle in an arena deals its power to a chosen unit
- [x] **Batch 19.2 — JTL_180, JTL_131, JTL_174** done — JTL_180 (defeat all SOR_T02 shields on a unit, then deal 3). **JTL_131 done (Batch B, 956 passing):** Turbolaser Salvo — choose arena (OPTIONCHOOSE Ground&Space) → choose friendly space dealer → deal its power to EACH enemy unit in that arena (snapshot UIDs, index-shift safe; it's DIRECT AOE, not indirect as the batch label assumed). 1 test. **JTL_174 done (Batch C, 962 passing):** Hotshot Maneuver — choose a friendly unit; count its On Attack abilities = the exact set CollectCombatStep1Triggers fires (printed `CARDID:N` windows + upgrade-granted `:0`); MZMULTICHOOSE that many DIFFERENT enemy units, deal 2 each, then BeginSWUAttack with it. 2 tests (0-ability just-attacks, 1-ability deal-2-then-attack).
  - JTL_131 Turbolaser Salvo: a friendly space unit deals its power to each enemy in a chosen arena
  - JTL_174 Hotshot Maneuver: for each of a unit's On-Attack abilities, deal 2; then attack with it
  - JTL_180 Piercing Shot: defeat all Shields on a unit, 3 dmg to it

## Phase 20 — Misc events & effects (autonomous)
- [x] **Batch 20.1 — JTL_077, JTL_074, JTL_121** done — JTL_077 (mass: each unit gains Sentinel via JTL_077_SENTINEL grant + loses Saboteur via keywordSuppressors['JTL_077']). **JTL_074/121 done (Batch A, 953 passing):** JTL_074 Close the Shield Gate — choose a base (`["myBase-0","theirBase-0"]`), arm one-shot `SWU_SHIELD_GATE` flag on that base's owner, consumed at the top of `SWUDealDamageToBase` (1 test: prevent then attack-twice proves one-shot consume; cleared at RegroupPhaseStart). JTL_121 Salvage — OnPlayEvent offers AFFORDABLE Vehicle units in own discard → `SWUPlayDiscardUnitDiscounted` (now returns the new mzID, not bool) at full cost → deal 1 to it (2 tests: play+deal1, no-vehicle fizzle).
  - JTL_074 Close the Shield Gate: choose a base; prevent the next damage to it this phase
  - JTL_077 In the Heat of Battle: each unit gains Sentinel and loses Saboteur this phase
  - JTL_121 Salvage: play a Vehicle from your discard pile, then 1 dmg to it
- [x] **Batch 20.2 — JTL_219, JTL_244, JTL_155** done — JTL_219 (whenPlayed/onAttack deal-1-friendly + ready-a-resource), JTL_244 (up-to-3 units lose all abilities via LostAbilities 'JTL_244' token + LOSE_ABILITIES registry). **JTL_155 done (Batch A, 953 passing):** They Hate That Ship — opp gets 2 readied JTL_T01 TIEs (`SWUCreateUnitToken($opp,'JTL_T01',true)` ×2), then `SWUHandPlayablesAtDiscount(['Unit'],3)` filtered to Vehicle → nested `ActivateCard(...,discount:3)` under the SOR_219 save/restore guard (event owns the after-action). 1 test.
  - JTL_155 They Hate That Ship: opp creates 2 TIEs readied; then play a Vehicle −3
  - JTL_219 Rafa Martez: When Played/On Attack: 1 dmg to a friendly unit + ready a resource
  - JTL_244 There Is No Escape: up to 3 units lose all abilities & can't gain abilities this round

---

## Phase 21 — Indirect damage (autonomous)
> Subsystem now built (`SWUDealIndirectToChosenPlayer` / `ToEachOpponent` + the receiver-assignment funnel + `SWUIndirectAssignToOpponentSources`). These reuse it. **Watch on the run:** the modifiers 165/171 (extend the deal pipeline / source set) and reactive 133 (a hook on indirect-applied-to-a-unit) may want a checkpoint if the funnel doesn't already expose the needed seam — escalate if so. JTL_143 (the pattern-setter) is done.
- [x] **Batch 21.1 — JTL_116, JTL_132, JTL_237, JTL_240** — done (891). All reuse the indirect funnel: JTL_116 (whenPlayed indirect = #Vehicles), JTL_132 (onAttack/whenDefeated 1), JTL_237 (onAttack 3 to defending player), JTL_240 (whenPlayed/onAttack 1, 2 if controls Boba). Tested with damaged-player having no units → indirect auto-resolves to base. 4 tests.
  - JTL_116 Dornean Gunship: When Played: indirect to a player = # Vehicles you control
  - JTL_132 First Order Stormtrooper: On Attack/When Defeated: 1 indirect to a player
  - JTL_237 TIE Bomber: On Attack: 3 indirect to the defending player
  - JTL_240 Fett's Firespray: When Played/On Attack: 1 indirect (2 if you control Boba Fett)
- [~] **Batch 21.2 — JTL_149, JTL_162, JTL_183** done (893); **JTL_152 DEFERRED** — JTL_149 (onAttack 3 to defending player), JTL_162 (whenDefeated 3), JTL_183 (whenDefeated 2). Proved the cross-player MZSPLITASSIGN path (P2>AnswerDecision:myBase-0:N). ⚠ JTL_152 (onAttack indirect=power + if-base-damaged→draw — the reactive base-hit seam the phase note flagged) deferred. 2 tests.
  - JTL_149 Red Squadron Y-Wing: On Attack: 3 indirect to the defending player
  - JTL_152 Tactical Heavy Bomber: On Attack: indirect = power; if a base is damaged, draw
  - JTL_162 Droid Missile Platform: When Defeated: 3 indirect to a player
  - JTL_183 Zygerrian Starhopper: When Defeated: 2 indirect to a player
- [x] **Batch 21.3 — JTL_181, JTL_226, JTL_218, JTL_222 done** — JTL_181 (8 indirect, 12 if Capital Ship), JTL_226 (enemy non-leader -1/-0 per damage aura + whenPlayed 5 indirect). **JTL_218/222 (session 50):** the **indirect "then" continuation seam** — `SWUDealIndirectDamage`/`ToChosenPlayer`/`ApplyIndirectAssignment` gained an optional `$thenHandler` ("Name~args", threaded through the decision PARAM so it survives the request boundary on the interactive MZSPLITASSIGN path); after assignment it exposes `$gLastIndirectBaseDmg` + `$gLastIndirectUnitUIDs` and dispatches the continuation. JTL_218 (base-damaged→ready self), JTL_222 (exhaust each damaged unit). Also **JTL_152 Tactical Heavy Bomber** (onAttack indirect=power; base-damaged→draw) now uses it. 3 tests (940 passing). ⚠ Still deferred: JTL_133 (on-indirect-to-a-unit hook), JTL_009 (on-non-combat-damage hook), JTL_171 (assign-all-indirect per-unit grant). 3 tests.
  - JTL_181 Planetary Bombardment: 8 indirect (12 if you control a Capital Ship)
  - JTL_218 Guerilla Soldier: When Played: 3 indirect; if a base is damaged, ready this
  - JTL_222 Kimogila Heavy Fighter: When Played: 3 indirect; exhaust each unit damaged this way
  - JTL_226 Radiant VII: enemy non-leaders −1/−0 per dmg (aura) + When Played: 5 indirect
- [x] **Batch 21.4 — JTL_139, JTL_165, JTL_171 done** — JTL_139 (granted onAttack 2 indirect, 3 if host Underworld), JTL_165 (+1 to all indirect-to-opponents). **JTL_171 (session 50):** Targeting Computer — `SWUControllerAssignsIndirect` now also overrides when the controller has a unit bearing the JTL_171 upgrade (the controller assigns; exact "by this unit" scoping would need the source unit threaded through the funnel — documented over-approximation). 3 tests.
  - JTL_139 Dengar: Piloting; attached On Attack: 2 indirect (3 if attached is an Underworld unit)
  - JTL_165 Hunting Aggressor: indirect damage you deal to opponents is increased by 1
  - JTL_171 Targeting Computer: attached gains "you assign all indirect dealt by this unit"
- [~] **Batch 21.5 — JTL_009 done (941); JTL_133 DEFERRED** — **JTL_009 Boba Fett (session 50):** undeployed-leader "when you deal NON-COMBAT damage: may exhaust → 1 indirect." New `_SWUCollectBobaNonCombatReaction($dealer)` hooked at the END of `SWUDealDamageToUnit` (always non-combat) + `SWUDealDamageToBase` (gated by a new `$gInCombatDamage` flag set ONLY around the combat base-damage calls at CombatLogic 734/874, since that funnel serves both combat and effects). `SWU_BOBA_009_PENDING` keeps one offer outstanding; accept exhausts the leader so the reaction's own indirect can't re-fire it. ⚠ deploy-as-Pilot-upgrade Epic variant DEFERRED. 1 test. **JTL_133 Allegiant General Pryde done (session 50):** On Attack (2 indirect if initiative) + the passive "when indirect is dealt to a unit → may defeat a non-unique upgrade on it" — collector at the end of `SWUApplyIndirectAssignment` (per surviving damaged unit, if controller controls Pryde) using a new `$onlyHostUID` scope param on `SWUQueueDefeatUpgrade` + `unique=0` filter. ⚠ multi-unit-in-one-indirect clobbers the shared DefeatUpg StoreVariables (single-unit is the common case; documented edge). ⚠ indirect-to-UNIT doesn't trigger Boba (that path sets Damage directly in SWUApplyIndirectAssignment, bypassing the funnel) — documented edge.
  - JTL_009 Boba Fett (leader): non-combat dmg → may exhaust → 1 indirect to a player
  - JTL_133 Allegiant General Pryde: when indirect dmg is dealt to a unit: may defeat a non-unique upgrade

## Phase 22 — Starting-hand / opening-gameplay bases (pair-programmed)
> New setup subsystem: starting-hand size, mulligan, minimum deck size. (Deck validation itself is deferred per project scope; these affect opening gameplay.)
- [x] **Batch 22.1 — JTL_021, JTL_024, JTL_025, JTL_028** — done (911 passing). JTL_024/025 were already implemented (ValidateDeck.php `$deckSizeModifiers` — min-deck-size only). New `SWUStartingHandModifier($baseID)` / `SWUBaseSuppressesMulligan($baseID)` helpers (GameLogic.php) drive JTL_021 (−1 draw) / JTL_028 (+3 draw, no mulligan) in CreateGame's `QueuePregameSetup`; the same helper extends the harness Option-B builder (`6 → 6+mod`) so hand-size is regression-tested via the non-SkipPreGame flow. Mulligan suppression (028) verified by inspection (harness Option B doesn't simulate the mulligan DQ). 2 tests.
  - JTL_021 Colossus: draw 1 fewer card in your starting hand
  - JTL_024 Data Vault: your minimum deck size increased by 10
  - JTL_025 Thermal Oscillator: your minimum deck size decreased by 5
  - JTL_028 Nabat Village: draw 3 more cards in your starting hand; you can't take a mulligan

## Phase 23 — Reuse a "When-Defeated" ability (pair-programmed)
> New subsystem: re-trigger a unit's already-resolved (or another unit's) When-Defeated ability.
- [x] **Batch 23.1 — JTL_002, JTL_039, JTL_169** — done (917 passing). New "reuse a When-Defeated ability" subsystem: primitive **`SWUUseWhenDefeatedAbility($owner,$cardID,$mzID)`** re-fires a card's registered `whenDefeatedAbilities[":0"]` via `AddTrigger('WhenDefeated')+FlushTriggerBag` (no card registers a `:1` window, so "all its abilities" = the one closure). Two distinct hooks: **Thrawn (JTL_002) = use-time** — `SWUCollectThrawnReuse` fired in `DispatchTrigger`'s WhenDefeated case after every use (so replays chain), guarded by `SWU_THRAWN_REUSE_PENDING` to prevent duplicate prompts, mode via `_SWUThrawnReuseMode` (undeployed: exhaust ready leader; deployed: `SWU_THRAWN_DEPLOY_USED` once/round, cleared at RegroupPhaseStart). **Shadow Caster (JTL_169) = defeat-time** — collected in `CollectWhenDefeatedTriggers` (one `ShadowCasterReuse` trigger per controlled copy). Both hooks compose → one ability used 3× (orig + Thrawn + Shadow Caster), proven by `ThrawnShadowCaster_UseWhenDefeatedThreeTimes`. **JTL_039 Chimaera** WhenPlayed → MZMAYCHOOSE another friendly unit with a WD ability → primitive (unit stays alive). 6 tests. ⚠ Replay passes the now-dead unit's mzID as-is (degrades for self-referential reads; the JTL WD pool targets others — fine).
  - JTL_002 Grand Admiral Thrawn (leader): when you use a When-Defeated ability, may exhaust → use it again
  - JTL_039 Chimaera: When Played: may use a When-Defeated ability on another friendly unit
  - JTL_169 Shadow Caster: when a friendly unit is defeated, may use all its When-Defeated abilities again

## Phase 24 — Misc novel mechanics (pair-programmed)
- [~] **Batch 24.1 — JTL_018, JTL_050, JTL_096 done; JTL_096 done in Batch A (953 passing).**
  - [x] JTL_018 Kazuda Xiono (leader): new **"take an extra action"** mechanic = `SWUAfterActionExtra` (cleanup + reset pass counter, NO `SWUSwapTurnPlayer`). Undeployed Action [Exhaust]: friendly unit loses all abilities this round (reuse LostAbilities token `'JTL_018'`) → extra action. Deployed On Attack: MZMULTICHOOSE any number of friendly units → lose abilities (MZMULTICHOOSE works in-combat; MZCHOOSE doesn't). ⚠ deploy side needed a **manual `HasOnAttackAbility` stub + generator guard fix** (its deploy text has BOTH an own and a granted `"On Attack:"`; generator now uses `/(?<!")On Attack:/`). ⚠ **deploy-as-Pilot-upgrade Epic variant DEFERRED** to the re-host cluster. 2 tests.
  - [x] JTL_050 Phantom II: Grit (auto) + **Action [1 resource]** (unit-action, costKind 'none') attach itself to The Ghost (JTL_053, named host) via `SWUMoveUnitToUpgrade`; while attached The Ghost gets **+3/+3** (ObjectCurrentPower/HP) **+ Grit** (Grit conditional). Affordability gated on The Ghost in play. 1 test (937 passing).
  - [x] JTL_096 Blue Leader: Ambush + When Played: may pay 2 → move this to the ground arena + 2 exp. New `SWUMoveUnitBetweenArenas($unitMz,$targetArena)` (UID/Owner/Controller/Damage/Status/Subcards/TurnEffects preserved; re-resolves mzID by UID after cleanup). whenPlayed YESNO → JTL_096#0 pays 2, moves space→ground, 2 exp. 2 tests (pay + decline).
- [~] **Batch 24.2 — JTL_250 done (919 passing); JTL_105, JTL_126 pending (shared-infra cluster).**
  - [x] JTL_105 The Starhawk: Ambush (auto) + **cost-halving** — new `SWUApplyCostHalving($player,$resCost)` (ceil, once per controlled Starhawk) applied at BOTH `CanAffordActivationReserve` (so a card is affordable at half) and `SWUPayCost` (so it's paid at half). 2 tests (936 passing). ⚠ ability/upgrade-cost halving via direct `SWUExhaustResources` is a documented edge (those costs are usually 1 → ceil is a no-op).
  - [x] JTL_126 Eject: detach a Pilot upgrade → ground unit (exhausted), draw — via the new **move/attach subsystem** (see below).
  - [x] JTL_250 Sabine's Masterpiece: On Attack — per-aspect cumulative effects via new `_SWUControlsUnitWithAspect`. Vigilance→heal own base (auto; base-only MZCHOOSE can't present, MZCountChoices ignores base zones); Command→exp; Aggression→deal 1 to unit/base (new universal `DEAL_TARGET|N` handler); Cunning→OPTIONCHOOSE Exhaust/Ready a resource. ⚠ **All target picks use `SWUQueueMayChooseTarget` (MZMAYCHOOSE), NOT `SWUQueueChooseTarget` (MZCHOOSE)** — a mandatory multi-target MZCHOOSE auto-resolves to nothing inside the attacker's OnAttack (OnAttackTrigger restores $playerID before MZCountChoices runs → counts 0 → skips). 2 tests.

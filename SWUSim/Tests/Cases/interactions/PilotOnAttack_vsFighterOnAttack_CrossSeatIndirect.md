#// A pilot's granted On Attack against its Fighter host's OWN On Attack. Both are On Attack triggers of one
#// attack, both P1's, so P1 orders them (CR 7.6.9). When the host's On Attack is INDIRECT damage, the
#// DEFENDING player assigns it, and that cross-seat decision is part of resolving the host's ability.
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): SWU_TRIGGER_RESUME (GameLogic.php) waits on any
#//   seat owing a decision before dispatching the NEXT pooled trigger, and a resume that waited on another seat
#//   keeps running from that seat's queue (includeActor for the hand-back).
#//
#// FOUND BY: sweep retro #1/#2 of run 2 (2026-09-13). Uncovered combo shapes ORDER JTL_012:OnAttackFromUpgrade +
#//   LOF_144:OnAttack (69×) / JTL_147 (44×) / JTL_149 (37×) / JTL_151 (10×) from normal_luke_datavault, and
#//   ORDER JTL_142:OnAttackFromUpgrade + JTL_237:OnAttack (10×) from the Boba/Dedra lists.
#//
#// ★ WAS RED SECTIONS (4) — a candidate engine bug, same family as core/CrossPlayerDecisionPausesThePlay.md
#//   (memory "block-orders-one-queue-only"). When P1 orders the INDIRECT host trigger first, the engine
#//   dispatches the NEXT pooled trigger (the pilot's) onto P1's queue while P2 is still assigning the indirect
#//   damage. So P1 is asked the pilot's question before the host's ability has finished, and the pilot's target
#//   list is built from the board BEFORE the indirect damage lands. If the indirect defeats a unit, P1's list
#//   still offers it: the indices shift (theirSpaceArena-0 is now a different unit) and the last entry points
#//   at nothing. Picking it silently loses the pilot's damage. Probe 2026-09-13: Luke's 3 damage vanished that
#//   way. In the sweep the bots answer whichever seat is asked, so these games played the pilot's ping blind.
#//   Correct (CR 7.6.9 + 7.6.11): the host's ability resolves COMPLETELY, including the defending player's
#//   assignment, and only then is the next trigger resolved, against the board as it now is.
#//   The GREEN sections pass with the answers given in rules order; the RED ones assert the gating itself.
#//
#// THE CARDS.
#//   - JTL_012 Luke Skywalker (deployed as a pilot on a Fighter): the host gains "On Attack: You may deal 3
#//     damage to a unit." myLeaderDeployedPilot:true seats him on P1's FIRST unit, ground before space, so
#//     these boards give P1 no ground unit.
#//   - JTL_142 Darth Vader (Piloting): attached unit gains "On Attack: You may deal 1 damage to a unit. If a unit
#//     is defeated this way, you may deal 1 damage to a unit or base."
#//   - JTL_149 Red Squadron Y-Wing 1/3 / JTL_237 TIE Bomber 0/4: "On Attack: Deal 3 indirect damage to the
#//     defending player. (They assign 3 unpreventable damage among their base and units.)"
#//   - JTL_151 Red Five 3/4: "On Attack: You may deal 2 damage to a damaged unit."
#//   - LOF_144 Jedi Starfighter 1/4: "On Attack: You may deal 1 damage to a space unit."
#//   - SOR_237 Alliance X-Wing 2/3 · JTL_069 Munificent Frigate 4/7 · SOR_095 Battlefield Marine 3/3 ·
#//     SEC_028 Trayus Acolyte 2/4.
#// On the ordering prompt EffectStack-0 is the host's On Attack and EffectStack-1 the pilot's.
#//
# RED_YWingFirst_TheDefendingPlayerAssignsTheIndirect_BEFORE_LukesPromptOpens
#// Y-Wing's indirect first: P2 owes the assignment, and P1 must have NOTHING to answer until P2 has.
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_149:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
## EXPECT
P2HASDECISION
P1NODECISION

---

# RED_YWingFirst_LukesTargetsComeFromTheBoardAfterTheIndirect_TheDefeatedXWingIsGone
#// P2 puts all 3 indirect on the X-Wing (defeated). Luke's prompt must then offer exactly what is left: his own
#//   host and the Frigate, which is now theirSpaceArena-0. A list built before the indirect also offers
#//   theirSpaceArena-1, which no longer exists.
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_149:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P2>AnswerDecision:mySpaceArena-0:3
## EXPECT
P2SPACEARENACOUNT:1
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirSpaceArena-0

---

# RED_TIEBomberFirst_TheDefendingPlayerAssignsTheIndirect_BEFORE_VadersPromptOpens
#// The same gate on the Villainy side: TIE Bomber with Vader piloting. This is the Boba-list shape, where the
#//   Bomber's indirect is meant to soften units BEFORE the pilot's ping.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
## EXPECT
P2HASDECISION
P1NODECISION

---

# RED_TIEBomberFirst_VadersTargetsComeFromTheBoardAfterTheIndirect_TheDefeatedMarineIsGone
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P2>AnswerDecision:myGroundArena-0:3
## EXPECT
P2GROUNDARENACOUNT:1
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0

---

# TIEBomberFirst_TheIndirectSoftensTheMarine_VaderFinishesIt_AndTakesTheBonusPing
#// Rules order, answered in rules order: P2 puts 2 on the Marine and 1 on its base; Vader's 1 then defeats the
#//   Marine, which unlocks his "1 damage to a unit or base" (to the base). Base: 3 combat +1 indirect +1 bonus.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P2>AnswerDecision:myGroundArena-0:2,myBase-0:1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_028
P2BASEDMG:5

---

# VaderFirst_HisPingDoesNotDefeat_NoBonus_ThenTheIndirectBuildsOnTheNewBoard
#// The other order: Vader's 1 leaves the Marine alive (no bonus). The indirect is then built on the damaged
#//   Marine (it can take only 2 more). Base: 3 combat +1 indirect = 4, one less than the order above.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0:2,myBase-0:1
## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:4

---

# LukeFirst_OnTheYWing_HisDamageDefeatsTheXWing_ThenTheIndirectIsBuiltWithoutIt
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_149:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:mySpaceArena-0:3
## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:3

---

# RedFive_LukeAndRedFive_ThePlayerOrdersThem
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# RedFive_LukeFirst_MakesTheFrigateDamaged_RedFiveAddsTwo
#// Luke's 3 makes the Frigate a "damaged unit", so Red Five's 2 has a target: 5 in all.
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:5
P1NODECISION

---

# RedFive_RedFiveFirst_NoDamagedUnitYet_OnlyLukesThree
#// Red Five first: nothing is damaged, so its On Attack has no target and does not prompt. Luke's 3 follows.
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# JediStarfighter_LukeFirst_ThenTheStarfightersPing_BothLand
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: LOF_144:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:4
P1NODECISION

---

# SabinesMasterpiece_LukeFirst_ThenTheCommandClauseGivesAnExperience
#// Retro #7 (JTL_012 + JTL_250, 44×). JTL_250 Sabine's Masterpiece's On Attack reads the aspects of the friendly
#//   units: with a Command unit (ASH_099 Gozanti) its "give an Experience token to a unit" is offered. The pilot
#//   seats on the FIRST unit, so both P1 units are in space with the Masterpiece first.
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_250:1:0
WithP1SpaceArena: ASH_099:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>ResolveTrigger:OnAttackFromUpgrade
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:mySpaceArena-1
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:1:CARDID:ASH_099
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# DangerSquadronWingmen_LukeFirst_ThenTheWingmenGiveAnotherUnitAnAdvantage
#// Retro #6/#7 (ASH_157 + JTL_012, 3–13×). "Another unit": the Wingmen themselves are not offered.
## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: ASH_157:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>ResolveTrigger:OnAttackFromUpgrade
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P1SELECTABLEEXACT:mySpaceArena-1&theirSpaceArena-0

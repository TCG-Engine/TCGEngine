# VanillaFriendly_GetsPlusOnePlusOne
#// COVERAGE: offer=N/A (STRUCTURAL: constant ability, no choice) · decline=N/A (no "may")
#//           boundary=N/A (no threshold) · quantity=TwoRex_Stack + BlankedRex_BuffedByTheOtherRex
#//           negative=PrintedKeyword_NotBuffed, EnemyVanilla_NotBuffed, RexHimself (this section)
#//           gained-ability cells: UpgradeGrantedKeyword_*, KrellGrant_*, PhaseGrant_PyrrhicAssault_*,
#//           PilotGrant_*, EnemySatine_*, KeywordTokenUnit_*, Exiled_KeepsGrit_*
#//           blank cells: Imprisoned_*, KazudaAction_*, BlankedRex_*
#//           control=StolenVanilla_BuffedByNewController · reqboundary=N/A (STRUCTURAL: live read)
#//           dispatch=RexPlayedFromHand_BuffAppears · hp=HpHalf_KeepsDamagedUnitAlive
#//           modes=2P,TeamSuns (text says "Friendly") — TeamSuns_TeammatesRexBuffsYou
#//
#// HMW_141 Rex — Unit (Ground) 5/6, cost 5, [Command], Fringe/Clone.
#// "Friendly units with no abilities get +1/+1."
#// USER RULING 2026-09-16: no abilities = no printed text AND none gained; a blanked unit (Kazuda) qualifies.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:6

---

# VanillaSpaceUnit_AlsoBuffed

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: HMW_141:1:0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4

---

# PrintedKeyword_NotBuffed

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: HMW_141:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:4

---

# EnemyVanilla_NotBuffed

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_141:1:0
WithP2GroundArena: SOR_095:1:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3

---

# TwoRex_Stack

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArena: HMW_141:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# VanillaTokenUnit_Buffed

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: HMW_141:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# KeywordTokenUnit_NotBuffed
#// ASH_T01 Mandalorian token carries printed Shielded — a token with text is not "no abilities".

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_T01:1:0
WithP1GroundArena: HMW_141:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# StatOnlyUpgrade_StillBuffed
#// SOR_120 Academy Training (+2/+2, no text) grants no ability — the unit stays vanilla.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# UpgradeGrantedKeyword_NotBuffed
#// HMW_191 Hunter's Instinct (+2/+1) on a Creature (HMW_T03 Beast token, vanilla 3/3) grants Grit → gained ability.
#// 3 + 1 upgrade HP = 4; a Rex bonus would read 5.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_T03:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArenaUpgrade: 0:HMW_191

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:HP:4

---

# UpgradeConditionFalse_StillBuffed
#// The same HMW_191 on a NON-Creature grants nothing — the condition gates the gain, so the unit stays vanilla.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArenaUpgrade: 0:HMW_191

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:HP:5

---

# PilotGrant_NotBuffed
#// JTL_066 Trace Martez as a Pilot (+1/+2): "Attached unit gains: 'On Attack: …'" on a vanilla X-Wing (2/3).
#// 3 + 2 = 5; a Rex bonus would read 6.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: HMW_141:1:0
WithP1SpaceArenaUpgrade: 0:JTL_066

## EXPECT
P1SPACEARENAUNIT:0:HP:5

---

# KrellGrant_NotBuffed
#// SOR_105 General Krell: "Each other friendly unit gains: 'When Defeated: …'" — a gained ability.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArena: SOR_105:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# EnemySatine_NoOneIsVanilla
#// TWI_047 Satine Kryze: "Each unit (including enemy units) gains: 'Action […]'" — an ENEMY Satine still
#// gives your vanilla unit an ability.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP2GroundArena: TWI_047:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3

---

# PhaseGrant_PyrrhicAssault_RemovesBonus
#// TWI_103 Pyrrhic Assault: "For this phase, each friendly unit gains: 'When Defeated: …'".

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_103
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# Imprisoned_PrintedKeywordUnit_GetsBonus
#// SHD_072 Imprisoned blanks SOR_063 (loses Sentinel) → it now has no abilities → +1/+1.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArenaUpgrade: 0:SHD_072

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# KazudaAction_BlankedUnit_GetsBonus
#// JTL_018 Kazuda's leader action blanks SOR_063 for the round → it gains Rex's anthem (user ruling).

## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_018}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: HMW_141:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# Exiled_KeepsGrit_NotBuffed
#// SEC_054 Exiled from the Force blanks the unit EXCEPT Grit and grants Grit — it still has an ability.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArenaUpgrade: 0:SEC_054

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:HP:3

---

# BlankedRex_GrantsNothing

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArenaUpgrade: 1:SHD_072

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# BlankedRex_BuffedByTheOtherRex
#// A blanked Rex has no abilities himself, so the OTHER Rex buffs him; the vanilla unit gets only +1/+1.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArenaUpgrade: 1:SHD_072

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:7
P1GROUNDARENAUNIT:2:POWER:5

---

# StolenVanilla_BuffedByNewController

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2
WithP1GroundArena: HMW_141:1:0
WithP2GroundArena: HMW_141:1:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4

---

# RexPlayedFromHand_BuffAppears

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_141
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:POWER:4

---

# HpHalf_KeepsDamagedUnitAlive_UntilRexLeaves
#// SOR_095 (3 HP) with 3 damage survives on Rex's +1 HP; when Rex is defeated attacking, it dies too.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:3
WithP1GroundArena: HMW_141:1:5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENACOUNT:0

---

# TeamSuns_TeammatesRexBuffsYou

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: HMW_141:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# PraetorianGuard_KeywordConditionReadsPower_NoLoop
#// LOF_085 grants itself Sentinel while you control a unit with 4+ power; Rex's own read of that keyword on
#// friendly units must not loop. The guard is Sentinel (so no bonus) and the Marine reads 4/4.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArena: LOF_085:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:2:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:2:POWER:2

---

# PilotLeaderDeployedOntoVanillaVehicle_NotBuffed
#// JTL_018 Kazuda deploys as a Pilot (+3/+3) onto a vanilla X-Wing (2/3): the host gains his On Attack, so it
#// is no longer "no abilities" → 5/6 (a Rex bonus would read 6/7).

## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: HMW_141:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6

---

# SupportLendsAbility_NoBonusForThatAttack
#// ASH_202 Carson Teva's Support lends "deals combat damage before the defender" to the vanilla Marine for the
#// attack — a gained ability, so the Marine attacks at 3 (not Rex's 4).

## GIVEN
CommonSetup: yyw/grw/{myResources:4;handCardIds:ASH_202}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: HMW_141:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Imprisoned_CantGainKrellsAbility_StillGetsBonus
#// SHD_072 Imprisoned: "loses its current abilities and CAN'T GAIN abilities" — Krell's field grant does not
#// reach it, so it still has no abilities and keeps Rex's +1/+1.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: HMW_141:1:0
WithP1GroundArena: SOR_105:1:0
WithP1GroundArenaUpgrade: 0:SHD_072

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# HpHalf_DamagedUnitSurvivesWhileRexStays
#// SOR_095 (3 HP) with 3 damage is alive only because of Rex's +1 HP; an action that runs the state check
#// (Rex attacks the base) leaves it in play.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:3
WithP1GroundArena: HMW_141:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:3

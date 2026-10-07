# Reported_CCSLastIndex_DefeatedWhenItsSupportIsWiped
#// Bug report, game 1647080 (2026-10-06): "Clone Combat Squadron had 3 damage on it, but survived after I cleared
#// the board." JTL_115 Clone Combat Squadron is a 3/3, "+1/+1 for each other friendly space unit". With three other
#// friendly space units it is 6/6, so 3 damage is safe. P1's HMW_016 Maul plays LOF_213 The Legacy Run from hand and
#// defeats it; its When Defeated deals 6 divided among enemy units — 3/2/1 kills the Y-Wing (3 HP), the A-Wing (2 HP)
#// and the TIE (1 HP), none on CCS. CCS is then a 3/3 with 3 damage: no remaining HP, so it is defeated (CR: a unit with
#// no remaining HP is defeated). The reported board, CCS at the LAST space index exactly as in the game.

## GIVEN
CommonSetup: ngw/ngw/{myResources:6;myLeader:HMW_016;myBase:JTL_020;theirLeader:JTL_006;theirBase:ASH_026}
P1OnlyActions: true
WithP1Hand: LOF_213
WithP2SpaceArena: [JTL_212:1:0 SEC_213:1:0 JTL_081:1:0 JTL_115:0:3]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirSpaceArena-0:3,theirSpaceArena-1:2,theirSpaceArena-2:1

## EXPECT
P1DISCARDUNIT:0:CARDID:LOF_213
P2SPACEARENACOUNT:0

---

# CCSFirstIndex_DefeatedWhenItsSupportIsWiped
#// The same, with CCS at the FIRST space index — separates "the last index is skipped" from "the sweep never ran".

## GIVEN
CommonSetup: ngw/ngw/{myResources:6;myLeader:HMW_016;myBase:JTL_020;theirLeader:JTL_006;theirBase:ASH_026}
P1OnlyActions: true
WithP1Hand: LOF_213
WithP2SpaceArena: [JTL_115:0:3 JTL_212:1:0 SEC_213:1:0 JTL_081:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirSpaceArena-1:3,theirSpaceArena-2:2,theirSpaceArena-3:1

## EXPECT
P1DISCARDUNIT:0:CARDID:LOF_213
P2SPACEARENACOUNT:0

---

# CCSControl_SurvivesWhileOneSupportRemains
#// CONTROL: kill only two of the three (3 to the Y-Wing, 2 to the A-Wing, 1 to the TIE is replaced by 1 more to the
#// A-Wing). One friendly space unit is left, so CCS is a 4/4 with 3 damage and must SURVIVE — the fix must not defeat
#// a unit that still has remaining HP.

## GIVEN
CommonSetup: ngw/ngw/{myResources:6;myLeader:HMW_016;myBase:JTL_020;theirLeader:JTL_006;theirBase:ASH_026}
P1OnlyActions: true
WithP1Hand: LOF_213
WithP2SpaceArena: [JTL_212:1:0 SEC_213:1:0 JTL_081:1:0 JTL_115:0:3]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirSpaceArena-0:3,theirSpaceArena-1:3

## EXPECT
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:1:CARDID:JTL_115
P2SPACEARENAUNIT:1:DAMAGE:3

---

# FourLOM_DefeatedWhenZuckussLeaves_FourLOMLast
#// SHD_188 4-LOM is a 4/4; SHD_190 Zuckuss gives "each friendly unit named 4-LOM +1/+1", so with Zuckuss it is 5/5 and
#// 4 damage is safe. Zuckuss is 6/6 (+1/+1 from 4-LOM = 7/7) with 1 damage: 6 more from The Legacy Run defeats it.
#// 4-LOM is then a 4/4 with 4 damage → defeated. 4-LOM at the LAST ground index.

## GIVEN
CommonSetup: ngw/ngw/{myResources:6;myLeader:HMW_016;myBase:JTL_020;theirLeader:JTL_006;theirBase:ASH_026}
P1OnlyActions: true
WithP1Hand: LOF_213
WithP2GroundArena: [SHD_190:1:1 SHD_188:1:4]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0:6

## EXPECT
P2GROUNDARENACOUNT:0

---

# FourLOM_DefeatedWhenZuckussLeaves_FourLOMFirst

## GIVEN
CommonSetup: ngw/ngw/{myResources:6;myLeader:HMW_016;myBase:JTL_020;theirLeader:JTL_006;theirBase:ASH_026}
P1OnlyActions: true
WithP1Hand: LOF_213
WithP2GroundArena: [SHD_188:1:4 SHD_190:1:1]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-1:6

## EXPECT
P2GROUNDARENACOUNT:0

---

# Zuckuss_DefeatedWhenFourLOMLeaves_ZuckussLast
#// The mirror: Zuckuss (6/6, 7/7 with 4-LOM) carries 6 damage; 5 from The Legacy Run defeats 4-LOM (5/5 with Zuckuss),
#// and the 6th point goes to a SOR_095 Battlefield Marine (3/3) that survives it. Zuckuss is then 6/6 with 6 damage →
#// defeated. Zuckuss at the LAST ground index.

## GIVEN
CommonSetup: ngw/ngw/{myResources:6;myLeader:HMW_016;myBase:JTL_020;theirLeader:JTL_006;theirBase:ASH_026}
P1OnlyActions: true
WithP1Hand: LOF_213
WithP2GroundArena: [SOR_095:1:0 SHD_188:1:0 SHD_190:1:6]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0:1,theirGroundArena-1:5

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Zuckuss_DefeatedWhenFourLOMLeaves_ZuckussFirst

## GIVEN
CommonSetup: ngw/ngw/{myResources:6;myLeader:HMW_016;myBase:JTL_020;theirLeader:JTL_006;theirBase:ASH_026}
P1OnlyActions: true
WithP1Hand: LOF_213
WithP2GroundArena: [SHD_190:1:6 SOR_095:1:0 SHD_188:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-1:1,theirGroundArena-2:5

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# SingleDefeat_Vanquish_FourLOMFallsWhenZuckussIsDefeated
#// The plain effect-defeat path (SWUDefeatUnit, no batch): SOR_078 Vanquish "Defeat a non-leader unit" on Zuckuss.
#// 4-LOM (4/4, 5/5 with Zuckuss) carries 4 damage, so it is left without remaining HP and must be defeated.

## GIVEN
CommonSetup: ngw/ngw/{myResources:9;myhandCardIds:SOR_078}
P1OnlyActions: true
WithP2GroundArena: [SHD_190:1:0 SHD_188:1:4]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# SingleDefeat_Vanquish_Control_UndamagedFourLOMSurvives
#// CONTROL: the same Vanquish with 4-LOM undamaged — it only loses the +1/+1 and stays.

## GIVEN
CommonSetup: ngw/ngw/{myResources:9;myhandCardIds:SOR_078}
P1OnlyActions: true
WithP2GroundArena: [SHD_190:1:0 SHD_188:1:3]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_188
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Wipe_NebulaIgnition_FourLOMFallsWhenTheWindowCloses
#// The simultaneous-defeat WINDOW path: JTL_080 Nebula Ignition "Defeat each unit that isn't upgraded" defeats Zuckuss
#// and spares 4-LOM, which holds a Shield token (an upgrade). Inside the window the per-defeat sweep is held; it runs
#// when the window closes, and 4-LOM (4 damage, now 4/4) must be defeated then. (The Shield does not save it: losing
#// HP is not damage.)

## GIVEN
CommonSetup: ngw/ngw/{myResources:14;myhandCardIds:JTL_080}
P1OnlyActions: true
WithP2GroundArena: [SHD_190:1:0 SHD_188:1:4]
WithP2GroundArenaUpgrade: [1:SOR_T02]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0

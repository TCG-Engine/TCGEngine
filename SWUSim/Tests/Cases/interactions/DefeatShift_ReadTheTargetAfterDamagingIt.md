#// ONE BUG SHAPE, SIX CARDS: "deal damage to X, then do something to X" where the handler re-reads X by its
#// mzID AFTER the damage. If the damage defeats X, its slot is taken by the next unit in that arena, so the
#// "something" lands on the WRONG unit (memory "multi-unit-debuff-loop-defeat-shift" is the same family).
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): each card's handler captures the target's UID
#//   before the damage and re-finds it with SWUFindMzByUID (Qi'ra, Remove the Chip, DDC Defender, Grav Charge, Heavy
#//   Blaster Cannon).
#//
#// FOUND BY: a source scan (2026-09-13) after Darth Maul's second ping went astray in a retro #5 probe (see
#//   DarthMaul_DifferentUnit_AfterTheFirstPingDefeats.md for Maul's two handlers). The scan looked for
#//   SWUDealDamageToUnit($v, …) followed within 4 lines by GetZoneObject($v). Every hit was then probed.
#//
#// ★ WAS RED (5 sections, one per card). Each was reproduced 2026-09-13; each comment says what went wrong:
#//   - SHD_002 Qi'ra (front): "Deal 2 damage to a friendly unit. Then, give a Shield token to it." The handler
#//     means "shield only if it survived", but after a lethal 2 the Shield goes to the NEXT friendly unit.
#//   - TS26_69 Remove the Chip: "Deal 2 damage to a unit. If it's a Clone, ready it." Defeating a non-Clone
#//     readies the exhausted Clone behind it.
#//   - ASH_210 DDC Defender (On Defense, granted): "deal 1 damage to a unit in this unit's arena and exhaust
#//     it." Defeating the unit exhausts the one behind it.
#//   - ASH_085 Grav Charge: "When attached unit's attack ends: Deal 4 damage to it and defeat this upgrade."
#//     When the 4 defeats the host, the handler defeats the Grav Charge on the unit that slid into its slot.
#//   - LOF_171 Heavy Blaster Cannon: "When Played: You may deal 1 damage to a ground unit. Then, deal 1 damage to
#//     the same unit. Then, deal 1 damage to the same unit." Three calls on one mzID: when the first 1 defeats
#//     the unit, the other two land on the next ground unit. (Found by a second, wider scan.)
#//   Fix shape (APPLIED 2026-09-13): capture the target's UniqueID (or
#//   object) BEFORE dealing the damage, and re-find it by UID afterwards (SWUFindMzByUID).
#// Each RED has a CONTROL where the target survives, proving the effect itself works.
#//
#// Cards: SOR_095 Battlefield Marine 3/3 (seeded with 2 damage where it must die to 1–2) · SEC_028 Trayus
#//   Acolyte 2/4 · TS26_20 501st Veteran 0/4 (Clone) · SOR_046 Consular Security Force 3/7.
#//
# RED_Qira_TheTwoDamageDefeatsTheMarine_TheNextFriendlyUnitMustNotGetTheShield
## GIVEN
CommonSetup: ryk/ggw/{myLeader:SHD_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_095:1:2
WithP1GroundArena: SEC_028:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_028
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# CONTROL_Qira_TheTargetSurvives_ItGetsTheShield
## GIVEN
CommonSetup: ryk/ggw/{myLeader:SHD_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_028:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# RED_RemoveTheChip_DefeatsANonClone_TheExhaustedCloneBehindItStaysExhausted
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: TS26_69
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: TS26_20:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TS26_20
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# CONTROL_RemoveTheChip_OnTheCloneItself_ItSurvivesAndReadies
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: TS26_69
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: TS26_20:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:1:CARDID:TS26_20
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:1:READY

---

# RED_DDCDefender_TheOneDamageDefeatsTheMarine_TheAcolyteBehindItStaysReady
#// P1's Consular attacks P2's DDC-equipped Consular. P2's granted On Defense targets P1's Marine on 1 HP
#//   (theirGroundArena-1 from P2's side): 1 damage defeats it, and nothing else may be exhausted.
## GIVEN
CommonSetup: ggw/yyk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:2
WithP1GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:ASH_210
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_028
P1GROUNDARENAUNIT:1:READY

---

# CONTROL_DDCDefender_TheTargetSurvives_ItIsExhausted
## GIVEN
CommonSetup: ggw/yyk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:ASH_210
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:READY

---

# RED_GravCharge_TheFourDefeatsItsHost_TheOtherUnitsGravChargeStays
#// Both P1 units carry a Grav Charge. The Marine (3/3) attacks the base; its attack ends, it takes 4 and is
#//   defeated. Only ITS Grav Charge goes; the Acolyte keeps its own.
## GIVEN
CommonSetup: ggw/yyk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_085
WithP1GroundArena: SEC_028:1:0
WithP1GroundArenaUpgrade: 1:ASH_085
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_028
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# CONTROL_GravCharge_TheHostSurvivesTheFour_ItsGravChargeIsDefeated
## GIVEN
CommonSetup: ggw/yyk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_085
WithP1GroundArena: SEC_028:1:0
WithP1GroundArenaUpgrade: 1:ASH_085
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# RED_HeavyBlasterCannon_TheFirstPingDefeatsTheMarine_TheAcolyteTakesNothing
#// "The same unit" is the Marine: once it is defeated the rest of the damage has nowhere to go.
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SOR_046:1:0
WithP1Hand: LOF_171
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_028
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# CONTROL_HeavyBlasterCannon_AllThreeOnASurvivor
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1GroundArena: SOR_046:1:0
WithP1Hand: LOF_171
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SEC_028
P2GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:2

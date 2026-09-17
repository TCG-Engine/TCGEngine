# PlayAThreePowerUnit_Yes_DefeatsCampAndReadies
#// COVERAGE: offer=N/A (STRUCTURAL: a YESNO; the unit is the one just played) · decline=No_CampStays_UnitExhausted
#//           boundary=this section (3 power) paired with FourPowerUnit_NoOffer (4)
#//           negative=OpponentPlaysAUnit_NoOffer and CreatedToken_NoOffer (created ≠ played)
#//           control=N/A ("you" = the base's controller) · reqboundary=AcrossTheRequestBoundary
#//           modes=2P only ("When you play" is self-only)
#//
#// HMW_216 Insurgent Camp — Upgrade, cost 1, [Cunning][Heroism], Fortification.
#// "Fortify. When you play a unit with 3 or less power: You may defeat this upgrade. If you do, ready that unit."

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_216
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1BASE:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# No_CampStays_UnitExhausted

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_216
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1BASE:UPGRADECOUNT:1

---

# FourPowerUnit_NoOffer
#// HMW_228 Lakeside Shaaks (4/4) — its own When Played readies a resource and asks nothing in 2P.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_216
WithP1Hand: HMW_228

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1BASE:UPGRADECOUNT:1
P1NODECISION

---

# OpponentPlaysAUnit_NoOffer

## GIVEN
CommonSetup: ggw/ggw/{myResources:2;theirResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1BaseUpgrade: HMW_216
WithP2Hand: SOR_095

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P1BASE:UPGRADECOUNT:1
P1NODECISION

---

# CreatedToken_NoOffer
#// HMW_150 Migrate (3 resources → one 3-power Beast): the Beast is created, not played.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_216
WithP1Hand: HMW_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1BASE:UPGRADECOUNT:1
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_216
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1BASE:UPGRADECOUNT:0

---

# CloneCombatSquadronWithASpaceUnit_EntersAt4_NoOffer
#// Insurgent Camp reads the played unit's power as it enters play (current power), NOT printed power (unlike
#// ASH_248 Neel's errata). Clone Combat Squadron (JTL_115, printed 3/3, "+1/+1 for each other friendly space
#// unit") is played with an X-Wing token (JTL_T02) already in space, so it enters at 4 power: no offer, it
#// stays exhausted and the Camp stays on the base. (Ruling as of 2026-09-17.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_216
WithP1SpaceArena: JTL_T02:1:0
WithP1Hand: JTL_115

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:CARDID:JTL_115
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:EXHAUSTED
P1BASE:UPGRADECOUNT:1
P1NODECISION

---

# CloneCombatSquadronAlone_EntersAt3_Offered_CONTROL
#// Control: with no other friendly space unit, Clone Combat Squadron enters at its printed 3 power, so the Camp
#// is offered; YES defeats the Camp and readies it. Pairs with the section above (4 power, no offer).

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_216
WithP1Hand: JTL_115

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_115
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:READY
P1BASE:UPGRADECOUNT:0

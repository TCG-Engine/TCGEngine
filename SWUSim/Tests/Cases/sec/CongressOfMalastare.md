# FirstUpgradeCostsLess
#// SEC_064 Congress of Malastare (Ground, 5/5) — "The first upgrade you play each phase costs 1 resource
#//   less." P1 plays SOR_120 (cost 2) onto SEC_064; with the discount it costs 1, leaving 1 of 2 resources.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_064:1:0
WithP1Hand: SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# OnlyFirstUpgradeEachPhaseDiscounted
#// SEC_064 Congress of Malastare — the discount only applies to the FIRST upgrade you play each phase.
#//   P1 plays two Command upgrades (SOR_120, cost 2 each) onto Congress in the same phase. The first is
#//   discounted to 1, the second pays full 2. From 6 resources: 6 - 1 - 2 = 3 remaining.

## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_064:1:0
WithP1Hand: SOR_120
WithP1Hand: SOR_120

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# OpponentUpgradeNotDiscounted
#// SEC_064 Congress of Malastare — the discount is one-sided: it never reduces an OPPONENT's upgrades.
#//   Congress is P1's. P2 plays Devotion (SOR_070, Vigilance, cost 2) onto their own unit and must pay the
#//   full 2 (from 3 resources → 1 remaining), not the discounted 1.

## GIVEN
CommonSetup: ggk/bbk
WithActivePlayer: 2
WithP1GroundArena: SEC_064:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 3
WithP2Hand: SOR_070

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2RESAVAILABLE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2NODECISION

---

# FirstPilotUpgradeDiscounted_SecondPaysFull
#// SEC_064 Congress of Malastare — a unit played with Piloting is played AS AN UPGRADE, so it is eligible
#// for "the first upgrade you play each phase costs 1 less". P1 plays JTL_084 (Piloting 1) onto LAW_158
#// for 0, then JTL_086 (Piloting 1) onto JTL_214 for the full 1: 8 − 0 − 1 = 7.
#// Regression: the Piloting cost path is separate from the normal upgrade cost path, and it used to skip
#// this discount entirely while STILL spending the once-per-phase charge — so the pilot paid full price
#// and burned the discount for the rest of the phase.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: [SEC_064:1:0 LAW_158:1:0 JTL_214:1:0]
WithP1Hand: [JTL_084 JTL_086]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1RESAVAILABLE:7
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1

---

# PilotingUnitPlayedAsAUnit_NeitherDiscountedNorConsumesTheCharge
#// SEC_064 Congress of Malastare — the discount is for UPGRADES. A card that merely HAS Piloting but is
#// played as a unit is a unit play: it gets no discount, and it must not spend the once-per-phase charge.
#// P1 plays JTL_084 as a unit for its full 2, then plays JTL_086 with Piloting onto JTL_214 — that pilot
#// is still the first UPGRADE this phase, so it costs 1 − 1 = 0. 8 − 2 − 0 = 6.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: [SEC_064:1:0 LAW_158:1:0 JTL_214:1:0]
WithP1Hand: [JTL_084 JTL_086]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1RESAVAILABLE:6
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1

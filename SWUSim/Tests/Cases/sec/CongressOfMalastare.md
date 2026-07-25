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

## EXPECT
P2RESAVAILABLE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2NODECISION

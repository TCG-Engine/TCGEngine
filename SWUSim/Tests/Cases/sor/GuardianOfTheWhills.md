# FirstUpgrade_CostReduced
#// SOR_061 Guardian of the Whills (Unit 2/2, Vigilance) — "The first upgrade you play on this unit each
#// round costs 1 less." The Guardian is the only friendly unit, so SOR_069 Resilient (+0/+3, Vigilance,
#// cost 1) auto-attaches to it and the discount makes it cost 0: 3 ready resources → 3 left. The host
#// becomes 2/5 with one upgrade.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:3

---

# SecondUpgrade_FullCost
#// SOR_061 Guardian of the Whills — only the FIRST upgrade each round is discounted. Two SOR_069
#// (cost 1) on the same Guardian: the first costs 0 (charge spent), the second costs the full 1.
#// 3 ready resources → 0 + 1 = 2 left. (If the charge weren't consumed, both would be free → 3 left.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:2

---

# TwoGuardians_TwoDiscounts
#// SOR_061 Guardian of the Whills — each Guardian has its OWN per-round charge, so two Guardians grant
#// two separate discounts. Two SOR_069 (cost 1), each attached to a different Guardian, both cost 0:
#// 4 ready resources → 4 left. (One discount only → 3 left; no discount → 2 left.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:4

---

# UpgradeElsewhere_NoNetDiscount
#// SOR_061 Guardian of the Whills — the discount applies only to upgrades that actually land ON the
#// Guardian. With a Guardian (idx 0) and a non-Guardian unit (SOR_095, idx 1) both in play, P1 plays
#// SOR_069 (cost 1) onto SOR_095. The affordability gate showed -1 (a Guardian is in play), but ATTACH
#// reconciles: the upgrade went elsewhere, so the 1 is clawed back → net full cost (3 → 2). The
#// Guardian's charge stays UNUSED. (If the reconcile leaked the discount, RESAVAILABLE would be 3.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2

---

# ZeroCostUpgrade_ChargeNotWasted
#// SOR_061 Guardian of the Whills — attaching a 0-cost upgrade (SHD_068 Public Enemy, cost 0)
#// must NOT consume the Guardian's per-round charge (the −1 discount would do nothing on a 0-cost
#// card). After the 0-cost upgrade attaches, the charge is still available for the next upgrade.
#// SOR_069 Resilient (cost 1) then attaches and gets the −1 → costs 0. Total spent = 0 + 0 = 0.
#// 3 ready resources → still 3 left. If the charge were wasted on SHD_068, SOR_069 would cost 1
#// → 2 resources left.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SHD_068
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:3

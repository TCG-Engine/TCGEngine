# WhenDefeated_NextOfficialCostsLess
#// SEC_261 Inspiring Senator (Ground, 3/3) — When Defeated: the next Official unit you play this phase
#//   costs 1 resource less. SEC_261 attacks SOR_046 and dies to the counter (arming the discount); P1 then
#//   plays SEC_111 (Official, cost 2 → 1), leaving 1 of 2 resources.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_261:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_111

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:1

---

# OnlyNextOfficialDiscounted
#// SEC_261 Inspiring Senator — the discount applies to only the NEXT Official played, not every Official.
#//   SEC_261 attacks SOR_046 and dies, arming the discount. P1 then plays SEC_111 Jar Jar (Official, cost
#//   2 → 1) and next SEC_081 Major Partagaz (Official, cost 2, FULL). From 5 resources: 5-1-2 = 2 left
#//   (if the discount wrongly persisted the second play it would leave 3).

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_261:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_111
WithP1Hand: SEC_081

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:2

---

# DiscountExpiresNextPhase
#// SEC_261 Inspiring Senator — the discount lasts only the current phase. SEC_261 dies arming it, then
#//   the phase ends. In the next action phase P1 plays SEC_081 Major Partagaz (Official, cost 2) at FULL
#//   price: 2 ready resources - 2 = 0 left (a persisting discount would leave 1).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_261:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_081

## WHEN
- P1>AttackGroundArena:0:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_081
P1RESAVAILABLE:0

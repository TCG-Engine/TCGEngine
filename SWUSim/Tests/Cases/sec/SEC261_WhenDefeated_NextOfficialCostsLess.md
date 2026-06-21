# SEC_261 Inspiring Senator (Ground, 3/3) — When Defeated: the next Official unit you play this phase
#   costs 1 resource less. SEC_261 attacks SOR_046 and dies to the counter (arming the discount); P1 then
#   plays SEC_111 (Official, cost 2 → 1), leaving 1 of 2 resources.

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

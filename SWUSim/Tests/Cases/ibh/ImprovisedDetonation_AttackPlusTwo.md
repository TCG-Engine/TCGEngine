# IBH_021 Improvised Detonation (Event, cost 2, Cunning) — Attack with a unit; it gets +2/+0 for this
#   attack. P1's only ready unit (3 power) attacks the enemy base (no enemy units → auto-targets base)
#   for 3+2 = 5. (A missing +2 would show 3.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_021
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

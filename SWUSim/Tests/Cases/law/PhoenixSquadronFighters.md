# CostPerDamaged
#// LAW_110 Phoenix Squadron Fighters (6/6, space, cost 8) — costs 1 less per friendly damaged unit. With
#// 2 damaged friendly units it costs 6: plays with exactly 6 ready resources.

## GIVEN
CommonSetup: bbw/bgw/{myResources:6}
WithP1GroundArena: SEC_080:1:1
WithP1GroundArena: SEC_080:1:1
WithP1Hand: LAW_110

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_110
P1RESAVAILABLE:0

# SOR_220 Surprise Strike (Event, cost 2) — "Attack with a unit. It gets +3/+0 for
# this attack." P1's only ready unit (Battlefield Marine, 3/3) is auto-chosen, gets
# +3/+0, and — with P2 having no units — attacks P2's base for 3 + 3 = 6.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:3
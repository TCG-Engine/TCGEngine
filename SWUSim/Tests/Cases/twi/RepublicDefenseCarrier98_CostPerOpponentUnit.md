# TWI_098 Republic Defense Carrier (Unit 6/7, Space, cost 11, Command/Heroism) — Sentinel + "costs 1 less
# for each unit controlled by the opponent who controls the most units." P2 controls 3 units, so the cost
# is 11 - 3 = 8; P1 has exactly 8 resources. Base g + leader gw cover both pips.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:TWI_098}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_098
P1RESAVAILABLE:0

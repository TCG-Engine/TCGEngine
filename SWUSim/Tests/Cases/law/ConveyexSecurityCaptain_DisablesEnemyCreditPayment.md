# LAW_117 Conveyex Security Captain (Unit, cost 3, Vigilance, 2/4) — "Enemy Credit tokens lose all
#   abilities." P2 controls LAW_117, so P1's Credit token loses its "defeat to pay 1 less" ability:
#   no credit-payment offer appears when P1 plays a card, and P1 must pay the full cost in resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1
WithP2GroundArena: LAW_117:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

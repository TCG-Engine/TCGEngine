# LAW_149 Rey — "Opponents can't take control of this unit." P1 plays Change of Heart (SOR_224: take
# control of a non-leader unit) at Rey; it fizzles and Rey stays under P2's control (never enters P1's
# arena).

## GIVEN
CommonSetup: yyk/rrk/{myResources:10;handCardIds:SOR_224}
P1OnlyActions: true
WithP2GroundArena: LAW_149:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_149

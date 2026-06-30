# SEC_103 Mon Mothma — When Played: you may attack with any number of OTHER units (even if exhausted;
#   they can't attack bases). P1 plays Mon Mothma; her ready SOR_046 (3/7) attacks P2's SOR_128 (3/1),
#   defeating it (and taking 3 counter). Then the loop re-offers but SOR_046 is excluded → loop ends.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

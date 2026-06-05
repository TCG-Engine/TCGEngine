# SOR_216 Disarm — Give an enemy unit −4/−0 for this phase.
# Single enemy unit (Blizzard Assault AT-AT, 9/9) → auto-target.
# Power 9 − 4 = 5; HP unchanged at 9 (−0).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:9

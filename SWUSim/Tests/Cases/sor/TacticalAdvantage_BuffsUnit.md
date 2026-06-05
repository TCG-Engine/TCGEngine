# SOR_124 Tactical Advantage — "Give a unit +2/+2 for this phase." (Event, cost 1, Command)
# Single unit in play (Blizzard Assault AT-AT SOR_088, 9/9) → auto-target.
# Power 9+2=11, HP 9+2=11.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_124}
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_088
P1GROUNDARENAUNIT:0:POWER:11
P1GROUNDARENAUNIT:0:HP:11

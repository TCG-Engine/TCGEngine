# MinusTwoMinusTwo
#// LAW_131 Incapacitate (Vigilance event, cost 2) — "Give a unit -2/-2 for this phase." Single unit on
#// board (P2's SOR_046, 3/7) -> auto-target -> 1/5.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
P1DISCARDCOUNT:1

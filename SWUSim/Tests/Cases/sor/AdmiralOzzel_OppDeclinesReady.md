# SOR_129 Admiral Ozzel — the opponent's ready is a "may": declining leaves their unit exhausted.
# Ozzel plays SEC_080 (enters ready); P2 declines the ready → its SOR_046 stays EXHAUSTED.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_128
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:EXHAUSTED

# SOR_189 Leia Organa — NO: own unit is valid target; auto-exhausts when it's the only ready unit
# SOR_095 at myGroundArena-0 is the only other ready unit (Leia enters exhausted).

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust a unit

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_189

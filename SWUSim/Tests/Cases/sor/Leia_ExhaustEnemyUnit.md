# SOR_189 Leia Organa — NO: 2 enemy ready units → MZCHOOSE → exhausts chosen one

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust a unit
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

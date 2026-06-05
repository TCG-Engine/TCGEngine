# SOR_189 Leia Organa — NO: only 1 other ready unit → auto-exhausts (PASSPARAMETER)
# Leia enters exhausted (Status:0). P2's SOR_095 is the only other ready unit.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust a unit

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

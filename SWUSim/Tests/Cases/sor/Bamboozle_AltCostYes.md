# SOR_199 Bamboozle — alternate cost: discard Cunning card instead of paying 2
# P1 has 1 resource (can't afford normal cost). Waylay (SOR_222, Cunning) is in hand.
# Player chooses YES → Waylay discarded, resource NOT spent, effect still fires.

## GIVEN
CommonSetup: ygw/grw/{myResources:1;handCardIds:SOR_199,SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:1
P1HANDCOUNT:0
P1DISCARDCOUNT:2

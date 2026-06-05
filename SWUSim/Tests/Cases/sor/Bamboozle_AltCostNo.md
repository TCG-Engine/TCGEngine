# SOR_199 Bamboozle — alternate cost offered but declined; pays normal cost
# P1 has 2 resources and Waylay (Cunning) in hand. Chooses NO → pays 2R normally.
# Waylay remains in hand; only Bamboozle goes to discard.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199,SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

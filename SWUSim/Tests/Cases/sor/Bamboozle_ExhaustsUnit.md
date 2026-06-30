# SOR_199 Bamboozle — exhausts target unit (normal cost, no other Cunning card)
# Single target → auto-resolves without player choice.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
P1HANDCOUNT:0

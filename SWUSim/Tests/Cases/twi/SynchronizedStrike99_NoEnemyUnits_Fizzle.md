# TWI_099 Synchronized Strike — with no enemy units on the board the event fizzles cleanly: it goes to
# discard, no decision is pending, and nothing crashes.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2;handCardIds:TWI_099}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:TWI_099

# SOR_092 — no friendly unit to buff: the event fizzles (no decision) and goes to discard.
# Absence guard.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0    # enemy present, but no friendly to choose as dealer

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

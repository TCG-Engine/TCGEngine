# SOR_182 Bossk — playing a NON-event (a unit) does NOT trigger the reaction.
# Absence guard: Bossk only reacts to events, so playing a unit leaves no pending decision.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5;handCardIds:SEC_080}
WithP1GroundArena: SOR_182:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1NODECISION

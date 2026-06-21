# SEC_177 It's Not Over Yet (Event, cost 2, Aggression) — "You may ready a unit that didn't attack or
#   enter play this phase. Create a Spy token." A GIVEN exhausted SOR_095 (not played/attacked this
#   phase) is eligible → ready it; also create a Spy.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Hand: SEC_177

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:2
P1NODECISION

# SOR_097 Admiral Ackbar (Command/Heroism unit, cost 3, 1/4, Rebel/Official) — "Restore 1. When
# Played: You may deal damage to a unit equal to the number of units you control in its arena."
# P1 plays Ackbar into a ground arena that already has 2 friendly units → 3 friendly ground units
# (incl. Ackbar). Targeting an enemy GROUND unit deals 3 (the friendly ground count). LAW_124 (4/7)
# survives at DAMAGE:3.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:3
P2GROUNDARENAUNIT:0:DAMAGE:3

# SOR_051 Luke Skywalker — the "-6/-6 if a friendly unit was defeated this phase" branch. P1's
# SOR_210 (4/3) attacks an AT-ST and dies (a FRIENDLY unit defeated this phase). P2 passes, then P1
# plays Luke and targets the SECOND, undamaged AT-ST → -6/-6 for the phase → 0/1. (Luke can't target
# the first AT-ST + the -6 there: it already took 4 combat damage, so -6 HP would defeat it.)

## GIVEN
CommonSetup: bbw/bbw/{myResources:7}
WithP1GroundArena: SOR_210:1:0
WithP1Hand: SOR_051
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:POWER:0
P2GROUNDARENAUNIT:1:HP:1

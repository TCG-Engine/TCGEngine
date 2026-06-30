# IBH_052 Watch This (Event, cost 6, Cunning) — Return a non-leader unit (cost ≤6) to its owner's hand,
#   then exhaust each other enemy unit in the SAME arena. P1 returns an enemy ground unit; the other
#   enemy ground unit is exhausted, while a friendly ground unit and an enemy SPACE unit are untouched.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P2SPACEARENAUNIT:0:READY
P2HANDCOUNT:1
P1NODECISION

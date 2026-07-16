# ExhaustPerOfficial
#// SEC_196 No One Ever Knew (event, cost 2) — For each friendly Official unit, exhaust an enemy unit.
#//   With one Official (SEC_041) in play, P1 exhausts one enemy (SOR_046).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

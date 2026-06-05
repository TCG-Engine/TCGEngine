# JTL_203 Han Solo (pilot) — When played as an upgrade: You may attack with the attached unit. Played
# onto SOR_237, P1 chooses to attack the base, exhausting the host.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_203
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED

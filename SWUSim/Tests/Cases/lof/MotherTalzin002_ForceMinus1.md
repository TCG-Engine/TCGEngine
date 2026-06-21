# LOF_002 Mother Talzin — Action [Exhaust, use the Force]: Give a unit -1/-1 for this phase. SOR_046 (3/7)
# becomes 2/6 and P1 loses the Force token.

## GIVEN
P1LeaderBase: LOF_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P1NOFORCE

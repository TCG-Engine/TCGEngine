# SOR_009 Leia Organa — leader action with the opponent holding a UNIT (not just a base), so each
# attack chooses its target. First Rebel (3/7) attacks the enemy 3/1 (a real MZCHOOSE between the
# unit and the base) and defeats it; the chained second Rebel then attacks the base (the only target
# left) for 3.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3

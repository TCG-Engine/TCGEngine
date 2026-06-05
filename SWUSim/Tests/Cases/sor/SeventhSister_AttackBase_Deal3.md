# SOR_133 Seventh Sister (3/6) — "When this unit deals combat damage to an opponent's base: You
# may deal 3 damage to a ground unit that opponent controls." She attacks the base (3 damage),
# then deals 3 to the opponent's 3/7 ground unit.

## GIVEN
P1LeaderBase: SOR_011/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3

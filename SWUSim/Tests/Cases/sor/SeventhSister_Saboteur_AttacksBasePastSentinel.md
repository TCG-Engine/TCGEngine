# SOR_133 Seventh Sister — Saboteur lets her ignore Sentinel and attack the BASE even though P2
# controls a Sentinel (SOR_063, 2/4). The base takes her 3 combat damage, which then fires the
# rider: deal 3 to a ground unit P2 controls → the Sentinel takes 3 (survives at 4 HP). She takes
# no counter (bases don't fight back). Proves Saboteur + the base-damage trigger compose.

## GIVEN
P1LeaderBase: SOR_011/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0

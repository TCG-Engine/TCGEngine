# SEC_014 Sly Moore (deployed) — On Attack: You may deal 2 damage to an exhausted unit. Deployed SEC_014
# (3/6) attacks the enemy base; On Attack → deal 2 to the exhausted enemy SOR_095 (3/3 → DAMAGE:2).

## GIVEN
P1LeaderBase: SEC_014:1:1:1/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_014:1:0
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:2

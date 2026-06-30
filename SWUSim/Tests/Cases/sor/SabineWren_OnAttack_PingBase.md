# SOR_142 Sabine Wren — On Attack vs a unit, she may ping a BASE instead of the defender. Attacking
# SOR_063, she pings the enemy base for 1; SOR_063 takes only the 2 combat damage.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2

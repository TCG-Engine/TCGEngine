# SOR_142 Sabine Wren — On Attack vs a unit: "You may deal 1 to the defender or a base." Sabine
# attacks SOR_063 (2/4) and pings the DEFENDER → 1 (ping) + 2 (combat) = 3 damage. Sabine takes 2
# counter and survives (2/3).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2

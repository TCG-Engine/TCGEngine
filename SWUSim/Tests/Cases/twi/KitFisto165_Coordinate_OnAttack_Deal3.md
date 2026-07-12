# TWI_165 Kit Fisto (Unit 7/6, Ground) — "Saboteur. Coordinate - On Attack: You may deal 3 damage to
# a ground unit." With 3 friendly units (Coordinate active), Kit attacks P2's base (7 damage) and the
# On Attack may-deal targets SOR_046 in the ground arena for 3.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_165:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P2GROUNDARENAUNIT:0:DAMAGE:3

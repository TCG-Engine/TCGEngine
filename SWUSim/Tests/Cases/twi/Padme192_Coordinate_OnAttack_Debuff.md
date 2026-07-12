# TWI_192 Padmé Amidala (Unit 1/4, Ground) — "Coordinate - On Attack: Give an enemy unit -3/-0 for
# this phase." With 3 friendly units (Coordinate active), Padmé attacks P2's base and gives SOR_046
# (3 power) -3/-0 → power 0.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_192:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:POWER:0
